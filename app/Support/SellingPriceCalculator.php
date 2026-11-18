<?php

namespace App\Support;

final class SellingPriceCalculator
{
    public const COST_PATTERN = '/\A[0-9]{1,10}\z/';

    public const WEIGHT_PATTERN = '/\A[0-9]{1,8}(?:\.[0-9]{1,2})?\z/';

    public const MARKUP_PATTERN = '/\A[0-9]{1,4}(?:\.[0-9]{1,2})?\z/';

    /**
     * Input is validated against the patterns above, with positive weight and decimal dots.
     * The digit limits keep the scaled values within a 64-bit integer.
     *
     * @return array{costPerKg: int, suggestedPrice: int}
     */
    public static function calculate(int $harvestCost, string $weight, string $markup): array
    {
        $weightHundredths = self::hundredths($weight);
        $markupHundredths = self::hundredths($markup);

        return [
            'costPerKg' => self::roundRupiah($harvestCost * 100, $weightHundredths),
            'suggestedPrice' => self::roundRupiah($harvestCost * (10_000 + $markupHundredths), $weightHundredths * 100),
        ];
    }

    private static function hundredths(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }

    private static function roundRupiah(int $numerator, int $denominator): int
    {
        return intdiv($numerator + intdiv($denominator, 2), $denominator);
    }
}
