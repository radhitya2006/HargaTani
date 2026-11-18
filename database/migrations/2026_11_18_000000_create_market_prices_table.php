<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_prices', function (Blueprint $table) {
            $table->id();
            $table->string('commodity', 50);
            $table->string('province', 30);
            $table->string('market_type', 30);
            $table->date('observed_on');
            $table->unsignedInteger('price_per_kg');
            $table->string('source_url');
            $table->timestamp('imported_at');
            $table->unique(['commodity', 'province', 'market_type', 'observed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_prices');
    }
};
