<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\Role;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class MarketplaceOrders
{
    public function place(
        User $buyer, Product $product, string $quantity, int $quotedPrice, string $token, ?string $notes,
    ): Order {
        return DB::transaction(function () use ($buyer, $product, $quantity, $quotedPrice, $token, $notes): Order {
            // Share the account lock with self-deletion and serialize submissions from the same buyer.
            $accounts = User::query()->whereKey(array_filter([$buyer->id, $product->farmer_id]))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $currentBuyer = $accounts->get($buyer->id);
            abort_unless($currentBuyer && Gate::forUser($currentBuyer)->allows('create', Order::class), 403);

            $existing = Order::query()->where('buyer_id', $buyer->id)->where('submission_token', $token)->first();
            if ($existing !== null) {
                if ($existing->product_id !== $product->id) {
                    throw ValidationException::withMessages(['order' => 'Muat ulang halaman sebelum membuat pesanan ini.']);
                }

                return $existing;
            }

            $listing = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $farmer = $listing->farmer_id === null ? null : $accounts->get($listing->farmer_id);
            if ($listing->status !== ProductStatus::Active || ! $farmer?->is_active || $farmer->role !== Role::Petani) {
                throw ValidationException::withMessages(['order' => 'Produk ini sudah tidak dapat dipesan.']);
            }
            if ($listing->price_per_kg !== $quotedPrice) {
                throw ValidationException::withMessages(['order' => 'Harga produk telah berubah. Muat ulang halaman dan periksa total terbaru sebelum memesan.']);
            }

            $weight = BigDecimal::of($quantity);
            $this->ensureAvailable($weight, $listing);

            $order = new Order;
            $order->product_id = $listing->id;
            $order->buyer_id = $buyer->id;
            $order->farmer_id = $listing->farmer_id;
            $order->submission_token = $token;
            $order->quantity_kg = (string) $weight;
            $order->price_per_kg = $listing->price_per_kg;
            $order->total_price = $weight->multipliedBy($listing->price_per_kg)->toScale(0, RoundingMode::HalfUp)->toInt();
            $order->notes = $notes;
            $order->save();
            $this->recordStatus($order, $buyer);

            return $order;
        }, 3);
    }

    public function transition(User $actor, Order $order, OrderStatus $target, ?string $reason): void
    {
        DB::transaction(function () use ($actor, $order, $target, $reason): void {
            $actor = User::query()->whereKey($actor->id)->sharedLock()->firstOrFail();
            $current = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('transition', [$current, $target]);

            if ($current->status === $target && $current->statusChanges()->latest('id')->first()?->actor_id === $actor->id) {
                return;
            }

            $allowed = match ($current->status) {
                OrderStatus::Pending => $actor->role === Role::Petani
                    ? in_array($target, [OrderStatus::Accepted, OrderStatus::Rejected], true)
                    : $target === OrderStatus::Cancelled,
                OrderStatus::Accepted => $actor->role === Role::Petani
                    ? $target === OrderStatus::Cancelled
                    : $target === OrderStatus::Completed,
                default => false,
            };
            if (! $allowed) {
                throw ValidationException::withMessages(['status' => 'Tindakan ini tidak tersedia untuk status pesanan saat ini.']);
            }

            $restoreStock = $current->status === OrderStatus::Accepted && $target === OrderStatus::Cancelled;
            if ($restoreStock && blank($reason)) {
                throw ValidationException::withMessages(['reason' => 'Masukkan alasan pembatalan.']);
            }

            if ($target === OrderStatus::Accepted || $restoreStock) {
                $listing = Product::query()->whereKey($current->product_id)->lockForUpdate()->firstOrFail();
                $stock = BigDecimal::of($listing->stock_kg);
                $weight = BigDecimal::of($current->quantity_kg);
                if ($restoreStock) {
                    $stock = $stock->plus($weight);
                } else {
                    $this->ensureAvailable($weight, $listing);
                    $stock = $stock->minus($weight);
                }
                $listing->stock_kg = (string) $stock;
                $listing->save();
            }

            $current->status = $target;
            $current->save();
            $this->recordStatus($current, $actor, $restoreStock ? $reason : null);
        }, 3);
    }

    private function ensureAvailable(BigDecimal $weight, Product $product): void
    {
        if ($weight->isGreaterThan($product->stock_kg)) {
            throw ValidationException::withMessages(['quantity_kg' => 'Stok tersedia tidak mencukupi.']);
        }
    }

    private function recordStatus(Order $order, User $actor, ?string $reason = null): void
    {
        $order->statusChanges()->create([
            'actor_id' => $actor->id,
            'status' => $order->status,
            'reason' => $reason,
        ]);
    }
}
