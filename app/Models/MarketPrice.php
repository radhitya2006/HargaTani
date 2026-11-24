<?php

namespace App\Models;

use App\Services\PihpsBaliPrices;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CarbonImmutable $observed_on
 * @property int $price_per_kg
 * @property \DateTimeInterface $imported_at
 */
final class MarketPrice extends Model
{
    public $timestamps = false;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'observed_on' => 'immutable_date',
            'price_per_kg' => 'integer',
            'imported_at' => 'immutable_datetime',
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @param  string|list<string>  $commodity
     */
    #[Scope]
    protected function pihpsBali(Builder $query, string|array $commodity): void
    {
        $query->whereIn('commodity', (array) $commodity)
            ->where('province', PihpsBaliPrices::PROVINCE)
            ->where('market_type', PihpsBaliPrices::MARKET_TYPE);
    }
}
