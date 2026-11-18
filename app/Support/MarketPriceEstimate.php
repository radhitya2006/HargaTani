<?php

namespace App\Support;

use App\Models\MarketPrice;
use Carbon\CarbonImmutable;

final readonly class MarketPriceEstimate
{
    public const OBSERVATIONS = 7;

    public const WINDOW_DAYS = 30;

    public const MAX_AGE_DAYS = 7;

    private function __construct(
        public ?int $pricePerKg,
        public ?CarbonImmutable $referenceDate,
        public int $observationCount,
        public bool $isStale,
    ) {}

    public static function forCommodity(string $commodity): self
    {
        $today = CarbonImmutable::today('Asia/Makassar');
        $rows = MarketPrice::query()->pihpsBali($commodity)
            ->where('observed_on', '<=', $today->toDateString())
            ->where('price_per_kg', '>', 0)
            ->orderByDesc('observed_on')
            ->limit(self::OBSERVATIONS)
            ->get();

        $observations = [];
        foreach ($rows as $row) {
            $observations[] = ['date' => $row->observed_on->settings(['locale' => 'id']), 'price' => $row->price_per_kg];
        }

        return self::fromLatestObservations($observations, $today);
    }

    /**
     * @param  list<array{date: CarbonImmutable, price: int}>  $observations  Up to seven valid observations, most recent first.
     */
    public static function fromLatestObservations(array $observations, CarbonImmutable $today): self
    {
        $referenceDate = $observations[0]['date'] ?? null;
        $windowStart = $today->subDays(self::WINDOW_DAYS - 1)->toDateString();
        $recent = array_filter($observations, fn (array $observation): bool => $observation['date']->toDateString() >= $windowStart);
        $isStale = $referenceDate !== null && $referenceDate->toDateString() < $today->subDays(self::MAX_AGE_DAYS)->toDateString();
        $count = count($recent);
        $price = ! $isStale && $count === self::OBSERVATIONS
            ? (int) round(array_sum(array_column($recent, 'price')) / $count)
            : null;

        return new self($price, $referenceDate, $count, $isStale);
    }
}
