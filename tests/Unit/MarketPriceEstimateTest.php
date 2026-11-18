<?php

namespace Tests\Unit;

use App\Support\MarketPriceEstimate;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class MarketPriceEstimateTest extends TestCase
{
    public function test_inclusive_calendar_boundaries_and_average(): void
    {
        $today = CarbonImmutable::parse('2026-11-18', 'Asia/Makassar');
        $observations = [];
        foreach ([7 => 10, 8 => 20, 11 => 30, 15 => 40, 19 => 50, 25 => 60, 29 => 74] as $age => $price) {
            $observations[] = ['date' => $today->subDays($age), 'price' => $price];
        }

        $estimate = MarketPriceEstimate::fromLatestObservations($observations, $today);
        $this->assertSame(41, $estimate->pricePerKg);
        $this->assertSame('2026-11-11', $estimate->referenceDate?->toDateString());
        $this->assertFalse($estimate->isStale);

        $observations[6]['date'] = $today->subDays(30);
        $estimate = MarketPriceEstimate::fromLatestObservations($observations, $today);
        $this->assertSame(6, $estimate->observationCount);
        $this->assertNull($estimate->pricePerKg);
        $this->assertFalse($estimate->isStale);
    }

    public function test_a_full_window_does_not_override_stale_data(): void
    {
        $today = CarbonImmutable::parse('2026-11-18', 'Asia/Makassar');
        $observations = [];
        foreach (range(8, 14) as $age) {
            $observations[] = ['date' => $today->subDays($age), 'price' => 40000];
        }

        $estimate = MarketPriceEstimate::fromLatestObservations($observations, $today);
        $this->assertSame(7, $estimate->observationCount);
        $this->assertNull($estimate->pricePerKg);
        $this->assertTrue($estimate->isStale);
    }
}
