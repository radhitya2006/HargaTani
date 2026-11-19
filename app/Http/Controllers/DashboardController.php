<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Enums\Role;
use App\Models\Product;
use App\Models\User;
use App\Support\MarketPriceSeries;
use App\Support\PriceCommodities;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $role = $request->user()?->role;

        return match ($role) {
            Role::Petani, Role::Pembeli => to_route($role->value.'.dashboard'),
            Role::Admin => $this->admin(),
            default => abort(403),
        };
    }

    public function admin(): View
    {
        $activeUsers = User::query()->where('is_active', true)->toBase()
            ->selectRaw('role, COUNT(*) AS total')->groupBy('role')->pluck('total', 'role');
        $runs = DB::table('price_sync_runs');

        return view('dashboard', [
            'metrics' => [
                'Petani aktif' => (int) ($activeUsers[Role::Petani->value] ?? 0),
                'Pembeli aktif' => (int) ($activeUsers[Role::Pembeli->value] ?? 0),
                'Produk tayang' => Product::query()->published()->count(),
            ],
            'syncRuns' => (clone $runs)->latest('id')->limit(5)->get(),
            'lastSuccessfulSync' => $runs->where('status', 'success')->latest('id')->value('finished_at'),
        ]);
    }

    public function petani(Request $request): View
    {
        $products = Product::query()->where('farmer_id', $request->user()?->id)
            ->whereIn('status', [ProductStatus::Active, ProductStatus::Draft]);
        $totals = (clone $products)->toBase()
            ->selectRaw('status, COUNT(*) AS total, SUM(CASE WHEN stock_kg = 0 THEN 1 ELSE 0 END) AS sold_out')
            ->groupBy('status')->get()->keyBy('status');
        $active = $totals->get(ProductStatus::Active->value);
        $soldOut = (int) ($active->sold_out ?? 0);

        return view('role-dashboard', [
            'role' => Role::Petani,
            'metrics' => [
                'Tayang dengan stok' => (int) ($active->total ?? 0) - $soldOut,
                'Draf' => (int) ($totals->get(ProductStatus::Draft->value)->total ?? 0),
                'Tayang, stok habis' => $soldOut,
            ],
            'products' => $products->latest('updated_at')->latest('id')->limit(5)->get(),
            'marketCards' => $this->marketCards(),
        ]);
    }

    public function pembeli(): View
    {
        return view('role-dashboard', [
            'role' => Role::Pembeli,
            'products' => Product::query()->published()->where('stock_kg', '>', 0)
                ->with('farmer:id,name')->latest('id')->limit(3)->get(),
            'marketCards' => $this->marketCards(),
        ]);
    }

    /** @return array<string, array<string, mixed>> */
    private function marketCards(): array
    {
        $cards = [];
        foreach (PriceCommodities::ALL as $slug => $commodity) {
            $cards[$slug] = $commodity + MarketPriceSeries::forCommodity($slug);
        }

        return $cards;
    }
}
