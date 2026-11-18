<?php

namespace App\Services;

use App\Models\MarketPrice;
use Illuminate\Support\Facades\DB;

final class MarketPriceImport
{
    /**
     * @param  array<string, array<string, int>>  $series
     * @return array{inserted: int, updated: int, unchanged: int}
     */
    public function store(int $runId, array $series, string $message): array
    {
        return DB::transaction(function () use ($runId, $series, $message) {
            $counts = ['inserted' => 0, 'updated' => 0, 'unchanged' => 0];
            $now = now();

            foreach ($series as $slug => $prices) {
                if ($prices === []) {
                    continue;
                }
                $existing = MarketPrice::query()->pihpsBali($slug)->whereIn('observed_on', array_keys($prices))
                    ->lockForUpdate()->get()->keyBy(fn (MarketPrice $price) => $price->observed_on->toDateString());
                $newRows = [];
                foreach ($prices as $date => $amount) {
                    $price = $existing->get($date);
                    if ($price === null) {
                        $newRows[] = [
                            'commodity' => $slug, 'province' => PihpsBaliPrices::PROVINCE,
                            'market_type' => PihpsBaliPrices::MARKET_TYPE, 'observed_on' => $date,
                            'price_per_kg' => $amount, 'imported_at' => $now, 'source_url' => PihpsBaliPrices::SOURCE_URL,
                        ];
                    } elseif ($price->price_per_kg === $amount) {
                        $counts['unchanged']++;
                    } else {
                        DB::table('market_price_revisions')->insert([
                            'market_price_id' => $price->getKey(), 'price_sync_run_id' => $runId,
                            'old_price' => $price->price_per_kg, 'new_price' => $amount, 'changed_at' => $now,
                        ]);
                        $price->price_per_kg = $amount;
                        $price->imported_at = $now;
                        $price->save();
                        $counts['updated']++;
                    }
                }
                if ($newRows !== []) {
                    MarketPrice::query()->insert($newRows);
                    $counts['inserted'] += count($newRows);
                }
            }
            DB::table('price_sync_runs')->where('id', $runId)->update($counts + [
                'status' => 'success', 'finished_at' => $now, 'message' => $message ?: null,
            ]);

            return $counts;
        }, 3);
    }
}
