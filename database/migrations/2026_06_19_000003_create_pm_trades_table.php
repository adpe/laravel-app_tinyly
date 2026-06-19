<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pm_signal_id')->nullable()->constrained('pm_signals')->nullOnDelete();
            $table->foreignId('pm_market_id')->constrained('pm_markets')->cascadeOnDelete();
            $table->string('side');                       // YES or NO
            $table->string('token_id');
            $table->decimal('price', 8, 4);               // limit price paid
            $table->decimal('size', 16, 4);               // shares
            $table->decimal('stake', 16, 4);              // USDC notional
            $table->string('mode');                       // paper|live
            $table->string('status');                     // simulated|submitted|filled|failed|rejected
            $table->string('order_id')->nullable();       // CLOB order id (live)
            $table->decimal('realized_pnl', 16, 4)->nullable();
            $table->text('error')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['mode', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_trades');
    }
};
