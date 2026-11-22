<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['farmer_id']);
            $table->foreignId('farmer_id')->nullable()->change();
            $table->foreign('farmer_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('farmer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('buyer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('submission_token');
            $table->decimal('quantity_kg', 10, 2);
            $table->unsignedBigInteger('price_per_kg');
            $table->unsignedBigInteger('total_price');
            $table->string('notes', 500)->nullable();
            $table->string('status', 20)->default(OrderStatus::Pending->value);
            $table->timestamps();
            $table->unique(['buyer_id', 'submission_token']);
            $table->index(['farmer_id', 'id']);
            $table->index(['buyer_id', 'id']);
            $table->index(['product_id', 'status']);
        });

        Schema::create('order_status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20);
            $table->string('reason', 500)->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_changes');
        Schema::dropIfExists('orders');

        // These archived products would have been removed by the original cascade on account deletion.
        DB::table('products')->whereNull('farmer_id')->delete();
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['farmer_id']);
            $table->foreignId('farmer_id')->nullable(false)->change();
            $table->foreign('farmer_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
