<?php

namespace Tests\Unit;

use App\Support\SellingPriceCalculator;
use PHPUnit\Framework\TestCase;

class SellingPriceCalculatorTest extends TestCase
{
    public function test_suggested_price_is_rounded_after_markup_on_exact_cost(): void
    {
        $this->assertSame([
            'costPerKg' => 3,
            'suggestedPrice' => 4,
        ], SellingPriceCalculator::calculate(10, '3', '10'));
    }

    public function test_half_rupiah_rounds_up_with_fractional_weight_and_zero_markup(): void
    {
        $this->assertSame([
            'costPerKg' => 13,
            'suggestedPrice' => 13,
        ], SellingPriceCalculator::calculate(1, '0.08', '0'));
    }
}
