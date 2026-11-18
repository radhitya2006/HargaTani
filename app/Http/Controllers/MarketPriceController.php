<?php

namespace App\Http\Controllers;

use App\Services\PihpsBaliPrices;
use App\Support\MarketPriceSeries;
use App\Support\PriceCommodities;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class MarketPriceController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $filters = $request->query();
        $commodity = $filters['komoditas'] ?? null;
        $period = $filters['periode'] ?? null;
        $days = in_array($period, array_map(strval(...), PriceCommodities::PERIODS), true)
            ? (int) $period : PriceCommodities::PERIODS[0];
        if ($commodity === 'cabai-merah') {
            return redirect()->route('prices.index', ['komoditas' => 'cabai-merah-besar', 'periode' => $days]);
        }
        if (! is_string($commodity) || ! isset(PriceCommodities::ALL[$commodity])) {
            $commodity = PriceCommodities::DEFAULT;
        }

        $series = MarketPriceSeries::forCommodity($commodity, $days);
        $prices = array_column($series['observations'], 'price');

        return view('prices.index', $series + [
            'commodities' => PriceCommodities::ALL, 'commodity' => $commodity,
            'periods' => PriceCommodities::PERIODS, 'days' => $days,
            'minimum' => $prices ? min($prices) : null, 'maximum' => $prices ? max($prices) : null,
            'sourceUrl' => PihpsBaliPrices::SOURCE_URL,
        ]);
    }
}
