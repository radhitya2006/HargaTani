<?php

namespace App\Support;

use App\Models\MarketPrice;
use Carbon\CarbonImmutable;

final class MarketPriceSeries
{
    /**
     * @return array{observations: list<array{date: CarbonImmutable, price: int}>, latest: array{date: CarbonImmutable, price: int}|null,
     *     previous: array{date: CarbonImmutable, price: int}|null, change: int|null, isStale: bool}
     */
    public static function forCommodity(string $commodity, int $days): array
    {
        $rows = MarketPrice::query()->pihpsBali($commodity)->orderByDesc('observed_on')->limit($days)->get();
        $end = $rows->first()?->observed_on;
        $observations = [];
        if ($end !== null) {
            $start = $end->subDays($days - 1);
            foreach ($rows->reverse() as $row) {
                if ($row->observed_on >= $start) {
                    $observations[] = ['date' => $row->observed_on->settings(['locale' => 'id']), 'price' => $row->price_per_kg];
                }
            }
        }
        $last = count($observations) - 1;
        $latest = $observations[$last] ?? null;
        $previous = $observations[$last - 1] ?? null;

        return [
            'observations' => $observations, 'latest' => $latest, 'previous' => $previous,
            'change' => $previous && $latest ? $latest['price'] - $previous['price'] : null,
            'isStale' => $end !== null && $end->toDateString() < CarbonImmutable::today('Asia/Makassar')->subDays(7)->toDateString(),
        ];
    }
}
