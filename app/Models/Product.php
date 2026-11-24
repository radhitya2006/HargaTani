<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Enums\Role;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int|null $farmer_id
 * @property bool $description_ai_generated
 * @property string $photo_path
 * @property int $price_per_kg
 * @property string $stock_kg
 * @property CarbonImmutable $harvested_on
 * @property ProductStatus $status
 */
#[Fillable(['name', 'commodity', 'description', 'price_per_kg', 'stock_kg', 'harvested_on', 'district', 'pickup_location'])]
final class Product extends Model
{
    public const WEIGHT_PATTERN = '/\A[0-9]{1,8}(?:\.[0-9]{1,2})?\z/';

    public const MAX_STOCK_KG = '99999999.99';

    protected $attributes = ['status' => 'draf', 'description_ai_generated' => false];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'description_ai_generated' => 'boolean',
            'price_per_kg' => 'integer',
            'stock_kg' => 'decimal:2',
            'harvested_on' => 'immutable_date',
            'status' => ProductStatus::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', ProductStatus::Active)
            ->whereHas('farmer', fn ($farmer) => $farmer->where('is_active', true)->where('role', Role::Petani));
    }

    public function isSoldOut(): bool
    {
        return $this->status === ProductStatus::Active && $this->stock_kg === '0.00';
    }

    public static function photoDirectory(int $farmerId): string
    {
        return 'products/'.$farmerId;
    }
}
