<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Support\BaliDistricts;
use App\Support\PriceCommodities;
use App\Support\ProductPhoto;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class ProductController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Product::class);

        return view('products.index', [
            'products' => Product::query()->where('farmer_id', $request->user()?->id)->latest('id')->paginate(12),
            'commodities' => PriceCommodities::ALL,
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Product::class);

        return $this->form(defaultDistrict: $request->user()?->district);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $farmerId = $request->user()->id;
        $photo = $this->savePhoto($request->file('photo'), $farmerId);
        try {
            $product = new Product($request->safe()->except('photo'));
            $product->farmer_id = $farmerId;
            $product->photo_path = $photo;
            $product->save();
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($photo);

            throw $exception;
        }

        return to_route('petani.products.edit', $product)->with('status', 'Draf produk disimpan.');
    }

    public function edit(Product $product): View
    {
        Gate::authorize('view', $product);

        return $this->form($product);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $photo = $request->hasFile('photo') ? $this->savePhoto($request->file('photo'), $product->farmer_id) : null;
        try {
            $oldPhoto = DB::transaction(function () use ($request, $product, $photo): ?string {
                $current = Product::query()->whereKey($product->getKey())->lockForUpdate()->firstOrFail();
                $current->fill($request->safe()->except('photo'));
                $oldPhoto = null;
                if ($photo !== null) {
                    $oldPhoto = $current->photo_path;
                    $current->photo_path = $photo;
                }
                $current->save();

                return $oldPhoto;
            });
        } catch (Throwable $exception) {
            if ($photo !== null) {
                Storage::disk('local')->delete($photo);
            }

            throw $exception;
        }

        if ($oldPhoto !== null) {
            Storage::disk('local')->delete($oldPhoto);
        }

        return to_route('petani.products.edit', $product)->with('status', 'Perubahan produk disimpan.');
    }

    public function updateStatus(Request $request, Product $product): RedirectResponse
    {
        Gate::authorize('update', $product);
        $action = $request->input('action');
        [$changes, $message] = match ($action) {
            'publish' => [['status' => ProductStatus::Active], 'Produk diterbitkan.'],
            'archive' => [['status' => ProductStatus::Archived], 'Produk diarsipkan.'],
            'sell_out' => [['stock_kg' => '0.00'], 'Stok produk ditandai habis.'],
            default => throw ValidationException::withMessages(['action' => 'Pilih tindakan produk yang tersedia.']),
        };

        DB::transaction(function () use ($product, $action, $changes): void {
            $current = Product::query()->whereKey($product->getKey())->lockForUpdate()->firstOrFail();
            if ($action === 'sell_out' && $current->status !== ProductStatus::Active) {
                throw ValidationException::withMessages(['action' => 'Terbitkan produk sebelum menandainya habis.']);
            }

            $current->forceFill($changes)->save();
        });

        return to_route('petani.products.edit', $product)->with('status', $message);
    }

    public function photo(Product $product): StreamedResponse
    {
        Gate::authorize('view', $product);

        return ProductPhoto::response($product);
    }

    private function form(?Product $product = null, ?string $defaultDistrict = null): View
    {
        return view('products.form', [
            'product' => $product,
            'defaultDistrict' => $defaultDistrict,
            'commodities' => PriceCommodities::ALL,
            'districts' => BaliDistricts::ALL,
        ]);
    }

    /** @param UploadedFile|array<array-key, mixed>|null $file */
    private function savePhoto(UploadedFile|array|null $file, int $farmerId): string
    {
        if ($file instanceof UploadedFile) {
            $path = $file->store(Product::photoDirectory($farmerId), 'local');
            if ($path !== false) {
                return $path;
            }
        }

        throw ValidationException::withMessages(['photo' => 'Foto tidak dapat disimpan. Coba unggah kembali.']);
    }
}
