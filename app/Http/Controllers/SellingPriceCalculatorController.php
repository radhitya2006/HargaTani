<?php

namespace App\Http\Controllers;

use App\Services\PihpsBaliPrices;
use App\Support\MarketPriceSeries;
use App\Support\PriceCommodities;
use App\Support\SellingPriceCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Rule;

final class SellingPriceCalculatorController extends Controller
{
    public function show(Request $request): Response
    {
        $commodity = $request->query('komoditas');
        if (! is_string($commodity) || ! isset(PriceCommodities::ALL[$commodity])) {
            $commodity = PriceCommodities::DEFAULT;
        }

        return $this->page(['komoditas' => $commodity]);
    }

    public function calculate(Request $request): Response
    {
        $inputs = [];
        foreach (['komoditas', 'biaya_panen', 'jumlah_kg', 'tambahan_keuntungan'] as $field) {
            $value = $request->post($field);
            $inputs[$field] = is_string($value) ? $value : '';
        }

        $weight = str_replace(',', '.', $inputs['jumlah_kg']);
        $markup = str_replace(',', '.', $inputs['tambahan_keuntungan']);

        $validator = Validator::make([...$inputs, 'jumlah_kg' => $weight, 'tambahan_keuntungan' => $markup], [
            'komoditas' => ['bail', 'required', Rule::in(array_keys(PriceCommodities::ALL))],
            'biaya_panen' => ['bail', 'required', 'regex:'.SellingPriceCalculator::COST_PATTERN],
            'jumlah_kg' => ['bail', 'required', 'regex:'.SellingPriceCalculator::WEIGHT_PATTERN, 'numeric', 'gt:0'],
            'tambahan_keuntungan' => ['bail', 'required', 'regex:'.SellingPriceCalculator::MARKUP_PATTERN],
        ], [
            'komoditas.*' => 'Pilih komoditas yang tersedia.',
            'biaya_panen.required' => 'Masukkan total biaya satu panen.',
            'biaya_panen.regex' => 'Masukkan rupiah bulat dari 0 hingga 9.999.999.999 tanpa pemisah ribuan.',
            'jumlah_kg.required' => 'Masukkan jumlah panen layak jual.',
            'jumlah_kg.regex' => 'Masukkan jumlah yang positif, maksimal 99.999.999,99 kg, dengan hingga dua angka desimal.',
            'jumlah_kg.gt' => 'Jumlah panen layak jual harus lebih dari 0 kg.',
            'tambahan_keuntungan.required' => 'Masukkan persentase tambahan keuntungan. Gunakan 0 jika tidak ada tambahan.',
            'tambahan_keuntungan.regex' => 'Masukkan persentase dari 0 hingga 9.999,99 dengan hingga dua angka desimal.',
        ]);

        if ($validator->fails()) {
            return $this->page($inputs, $validator->errors());
        }

        return $this->page($inputs, result: SellingPriceCalculator::calculate((int) $inputs['biaya_panen'], $weight, $markup));
    }

    /**
     * @param  array<string, string>  $inputs
     * @param  array{costPerKg: int, suggestedPrice: int}|null  $result
     */
    private function page(array $inputs, ?MessageBag $validationErrors = null, ?array $result = null): Response
    {
        return response()->view('calculator.index', [
            'commodities' => PriceCommodities::ALL,
            'inputs' => $inputs,
            'validationErrors' => $validationErrors,
            'result' => $result,
            'reference' => $result === null ? null : MarketPriceSeries::forCommodity($inputs['komoditas'], 1),
            'sourceUrl' => PihpsBaliPrices::SOURCE_URL,
        ], $validationErrors === null ? 200 : 422)->header('Cache-Control', 'no-store');
    }
}
