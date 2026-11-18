<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->date('range_start');
            $table->date('range_end');
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->string('status', 20);
            $table->unsignedInteger('inserted')->default(0);
            $table->unsignedInteger('updated')->default(0);
            $table->unsignedInteger('unchanged')->default(0);
            $table->text('message')->nullable();
        });

        Schema::create('market_price_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_price_id')->constrained();
            $table->foreignId('price_sync_run_id')->constrained();
            $table->unsignedInteger('old_price');
            $table->unsignedInteger('new_price');
            $table->timestamp('changed_at');
            $table->unique(['market_price_id', 'price_sync_run_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_price_revisions');
        Schema::dropIfExists('price_sync_runs');
    }
};
