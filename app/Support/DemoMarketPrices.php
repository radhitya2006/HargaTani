<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/** Fixed example series for the public preview. */
final class DemoMarketPrices
{
    public const LATEST_DATE = '2026-05-19';

    public const DEFAULT_COMMODITY = 'cabai-merah';

    public const PERIODS = [14, 30, 90];

    public const COMMODITIES = [
        'cabai-merah' => ['name' => 'Cabai Merah', 'tone' => 'chili', 'latest' => 45000, 'previous' => 42500, 'variation' => 500],
        'bawang-merah' => ['name' => 'Bawang Merah', 'tone' => 'shallot', 'latest' => 32000, 'previous' => 33500, 'variation' => 350],
        'tomat' => ['name' => 'Tomat', 'tone' => 'tomato', 'latest' => 12000, 'previous' => 11500, 'variation' => 150],
        'beras-medium' => ['name' => 'Beras Medium', 'tone' => 'rice', 'latest' => 13500, 'previous' => 13500, 'variation' => 50],
    ];

    /** @return non-empty-list<array{date: CarbonImmutable, price: int}> */
    public static function observations(string $commodity, int $days): array
    {
        $item = self::COMMODITIES[$commodity];
        $latestDate = CarbonImmutable::parse(self::LATEST_DATE)->settings(['locale' => 'id']);
        $pattern = [0, -1, -3, -2, 1, 2, 0, -2, -1, 1, 3, 2];
        $observations = [];

        for ($daysAgo = $days - 1; $daysAgo > 0; $daysAgo--) {
            $price = $daysAgo === 1
                ? $item['previous']
                : $item['previous'] + $item['variation'] * ($pattern[$daysAgo % count($pattern)] - intdiv($daysAgo, 18));

            $observations[] = ['date' => $latestDate->subDays($daysAgo), 'price' => $price];
        }

        $observations[] = ['date' => $latestDate, 'price' => $item['latest']];

        return $observations;
    }
}
