<?php

namespace App\Http\Controllers;

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

        return view('welcome', ['marketCards' => $marketCards]);
    }
}
