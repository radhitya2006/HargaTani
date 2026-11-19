<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('commodity', 50);
            $table->text('description');
            $table->string('photo_path');
            $table->unsignedBigInteger('price_per_kg');
            $table->decimal('stock_kg', 10, 2);
            $table->date('harvested_on');
            $table->string('district', 50);
            $table->string('pickup_location', 250);
            $table->enum('status', ['draf', 'aktif', 'arsip'])->default('draf');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
