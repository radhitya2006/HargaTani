<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $product_id
 * @property int|null $buyer_id
 * @property int|null $farmer_id
 * @property string $quantity_kg
 * @property int $price_per_kg
 * @property int $total_price
 * @property string|null $notes
 * @property OrderStatus $status
 */
final class Order extends Model
{
    protected $attributes = ['status' => 'menunggu'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity_kg' => 'decimal:2',
            'price_per_kg' => 'integer',
            'total_price' => 'integer',
            'status' => OrderStatus::class,
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<User, $this> */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /** @return BelongsTo<User, $this> */
    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    /** @return HasMany<OrderStatusChange, $this> */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(OrderStatusChange::class);
    }

    public function formattedTotal(): string
    {
        return 'Rp'.preg_replace('/\B(?=(?:[0-9]{3})+\z)/', '.', (string) $this->total_price);
    }
}
