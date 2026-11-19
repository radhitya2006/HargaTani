<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\MarketPriceSeries;
use App\Support\PriceCommodities;
use Illuminate\View\View;

final class HomeController extends Controller
{
    public function __invoke(): View
    {
        $marketCards = [];
        foreach (PriceCommodities::ALL as $slug => $item) {
            $marketCards[$slug] = $item + MarketPriceSeries::forCommodity($slug, PriceCommodities::PERIODS[0]);
        }

        return view('welcome', [
            'marketCards' => $marketCards,
            'featuredProducts' => Product::query()->published()->with('farmer:id,name')->latest('id')->limit(3)->get(),
        ]);
    }
}
