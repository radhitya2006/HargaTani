<?php

namespace App\Support;

use App\Models\MarketPrice;
use App\Services\PihpsBaliPrices;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/** @phpstan-type Stats array{commodity: string, source_url: string, start: string, end: string, first_observation: string|null, last_observation: string|null, latest_price: int|null, count: int, average: int|null, minimum: int|null, maximum: int|null, minimum_date: string|null, maximum_date: string|null, change: int|null} */
final class MarketTrend
{
    /** @return Stats */
    public static function forCommodity(string $commodity, int $days): array
    {
        return self::forPeriods([$commodity], [$days])[$days][$commodity];
    }

    /**
     * @param  non-empty-list<string>  $commodities
     * @param  non-empty-list<int>  $periods
     * @return array<int, array<string, Stats>>
     */
    public static function forPeriods(array $commodities, array $periods): array
    {
        $end = CarbonImmutable::today('Asia/Makassar');
        $endDate = $end->toDateString();
        $earliest = $end->subDays(max($periods) - 1)->toDateString();
        $byCommodity = MarketPrice::query()->pihpsBali($commodities)
            ->whereBetween('observed_on', [$earliest, $endDate])
            ->where('price_per_kg', '>', 0)
            ->orderBy('observed_on')->get(['commodity', 'observed_on', 'price_per_kg'])->groupBy('commodity');
        $results = [];
        foreach ($periods as $days) {
            $start = $end->subDays($days - 1)->toDateString();
            foreach ($commodities as $commodity) {
                $observations = ($byCommodity->get($commodity) ?? collect())
                    ->filter(fn (MarketPrice $price): bool => $price->observed_on->toDateString() >= $start)
                    ->values();
                $results[$days][$commodity] = self::statistics($observations, $commodity, $start, $endDate);
            }
        }

        return $results;
    }

    /**
     * @param  Collection<int, MarketPrice>  $observations
     * @return Stats
     */
    private static function statistics(Collection $observations, string $commodity, string $start, string $end): array
    {
        $prices = $observations->map(fn (MarketPrice $observation): int => $observation->price_per_kg)->all();
        $count = count($prices);
        $minimum = $count > 0 ? min($prices) : null;
        $maximum = $count > 0 ? max($prices) : null;

        return [
            'commodity' => $commodity,
            'source_url' => PihpsBaliPrices::SOURCE_URL,
            'start' => $start,
            'end' => $end,
            'first_observation' => $observations->first()?->observed_on->toDateString(),
            'last_observation' => $observations->last()?->observed_on->toDateString(),
            'latest_price' => $prices[$count - 1] ?? null,
            'count' => $count,
            'average' => $count > 0 ? (int) round(array_sum($prices) / $count) : null,
            'minimum' => $minimum,
            'maximum' => $maximum,
            'minimum_date' => $observations->last(fn (MarketPrice $item): bool => $item->price_per_kg === $minimum)?->observed_on->toDateString(),
            'maximum_date' => $observations->last(fn (MarketPrice $item): bool => $item->price_per_kg === $maximum)?->observed_on->toDateString(),
            // Observed prices are unsigned 32-bit integers, so the difference fits in a PHP integer.
            'change' => $count >= 2 ? (int) ($prices[$count - 1] - $prices[0]) : null,
        ];
    }

    /** @return array{change: string, range: string, average: string} */
    public static function summarySections(int $change, int $minimum, int $maximum, int $average): array
    {
        $direction = match ($change <=> 0) {
            -1 => 'turun '.self::rupiah(abs($change)).' per kg',
            1 => 'naik '.self::rupiah($change).' per kg',
            default => 'tetap sama',
        };

        return [
            'change' => "Dari pengamatan pertama ke terakhir, harga {$direction}.",
            'range' => 'Harga berkisar antara '.self::rupiah($minimum).' dan '.self::rupiah($maximum).' per kg.',
            'average' => 'Harga rata-rata '.self::rupiah($average).' per kg.',
        ];
    }

    public static function rupiah(int $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }
}
