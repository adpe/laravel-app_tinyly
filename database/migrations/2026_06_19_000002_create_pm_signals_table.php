<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pm_market_id')->constrained('pm_markets')->cascadeOnDelete();
            $table->string('side');                       // YES or NO (the side to buy)
            $table->decimal('market_prob', 8, 4);         // implied prob from price
            $table->decimal('fair_prob', 8, 4);           // blended estimate
            $table->decimal('edge', 8, 4);                // fair_prob - market_prob (for side)
            $table->decimal('confidence', 8, 4);          // 0..1
            $table->json('sources');                      // per-source breakdown
            $table->string('status')->default('open');    // open|traded|skipped|expired
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_signals');
    }
};
