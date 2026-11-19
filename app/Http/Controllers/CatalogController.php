<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\BaliDistricts;
use App\Support\PriceCommodities;
use App\Support\ProductPhoto;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('q');
        $search = is_string($search) ? Str::substr(trim($search), 0, 120) : '';
        $commodity = $request->query('komoditas');
        $commodity = is_string($commodity) && isset(PriceCommodities::ALL[$commodity]) ? $commodity : '';
        $district = $request->query('lokasi');
        $district = is_string($district) && in_array($district, BaliDistricts::ALL, true) ? $district : '';

        $query = Product::query()->published()->with('farmer:id,name')->latest('id');
        if ($search !== '') {
            $pattern = '%'.strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
            $query->whereRaw("name LIKE ? ESCAPE '!'", [$pattern]);
        }
        if ($commodity !== '') {
            $query->where('commodity', $commodity);
        }
        if ($district !== '') {
            $query->where('district', $district);
        }

        $filters = array_filter(['q' => $search, 'komoditas' => $commodity, 'lokasi' => $district], fn (string $value) => $value !== '');

        return view('catalog.index', [
            'products' => $query->paginate(12)->appends($filters),
            'commodities' => PriceCommodities::ALL,
            'districts' => BaliDistricts::ALL,
            'search' => $search,
            'commodity' => $commodity,
            'district' => $district,
            'hasFilters' => $filters !== [],
        ]);
    }

    public function show(string $product): View
    {
        $listing = Product::query()->published()->with('farmer:id,name,district')->whereKey($product)->firstOrFail();

        return view('catalog.show', [
            'product' => $listing,
            'commodityName' => PriceCommodities::ALL[$listing->commodity]['name'],
        ]);
    }

    public function photo(string $product): StreamedResponse
    {
        $listing = Product::query()->published()->whereKey($product)->firstOrFail();

        return ProductPhoto::response($listing);
    }
}
