<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Models\Order;
use App\Models\Product;
use App\Services\MarketplaceOrders;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class OrderController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Order::class);
        $user = $request->user();
        $isFarmer = $user->role === Role::Petani;
        $party = $isFarmer ? 'buyer' : 'farmer';

        return view('orders.index', [
            'orders' => Order::query()->where($isFarmer ? 'farmer_id' : 'buyer_id', $user->id)
                ->with(['product:id,name', $party.':id,name'])->latest('id')->paginate(12),
            'isFarmer' => $isFarmer,
        ]);
    }

    public function create(Product $product): View
    {
        Gate::authorize('create', Order::class);
        $listing = Product::query()->published()->with('farmer:id,name,district')->whereKey($product->id)->firstOrFail();
        return view('orders.create', ['product' => $listing]);
    }

    public function store(Request $request, Product $product, MarketplaceOrders $orders): JsonResponse|RedirectResponse
    {
        Gate::authorize('create', Order::class);
        $quantity = $request->input('quantity_kg');
        if (is_string($quantity)) {
            $request->merge(['quantity_kg' => str_replace(',', '.', $quantity)]);
        }

        $data = $request->validate([
            'quantity_kg' => ['required', 'numeric', 'regex:'.Product::WEIGHT_PATTERN, 'min:0.01'],
            'price_per_kg' => ['required', 'integer', 'min:1', 'max:9999999999'],
            'submission_token' => ['required', 'uuid'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'quantity_kg.*' => 'Masukkan jumlah 0,01–99.999.999,99 kg dengan paling banyak dua angka desimal.',
            'price_per_kg.*' => 'Muat ulang halaman untuk melihat harga yang berlaku.',
            'submission_token.*' => 'Muat ulang halaman sebelum mengirim pesanan.',
            'notes.string' => 'Catatan harus berupa teks.',
            'notes.max' => 'Catatan maksimal 500 karakter.',
        ]);

        $order = $orders->place(
            $request->user(), $product, (string) $data['quantity_kg'],
            (int) $data['price_per_kg'], $data['submission_token'], $data['notes'] ?? null,
        );

        $request->session()->flash('status', 'Pesanan terkirim.');

        return $request->expectsJson()
            ? response()->json(['redirect' => route('orders.show', $order, absolute: false)])
            : to_route('orders.show', $order);
    }

    public function show(Request $request, Order $order): Response
    {
        Gate::authorize('view', $order);
        $isFarmer = $request->user()->role === Role::Petani;
        $relation = $isFarmer ? 'buyer' : 'farmer';
        $canViewContact = Gate::allows('viewContact', $order);
        $order->load([
            'product:id,name,pickup_location,district,stock_kg',
            $relation.':id,name'.($canViewContact ? ',transaction_contact' : ''),
            'statusChanges' => fn ($history) => $history->with('actor:id,name')->orderBy('id'),
        ]);
        $party = $isFarmer ? $order->buyer : $order->farmer;

        return response()->view('orders.show', [
            'order' => $order,
            'isFarmer' => $isFarmer,
            'party' => $party,
            'canViewContact' => $canViewContact,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, Order $order, OrderStatus $status, MarketplaceOrders $orders): RedirectResponse
    {
        Gate::authorize('transition', [$order, $status]);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']], [
            'reason.string' => 'Alasan pembatalan harus berupa teks.',
            'reason.max' => 'Alasan pembatalan maksimal 500 karakter.',
        ]);

        $orders->transition($request->user(), $order, $status, $data['reason'] ?? null);

        return to_route('orders.show', $order)->with('status', 'Pesanan '.$status->value.'.');
    }
}
