<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_markets', function (Blueprint $table) {
            $table->id();
            $table->string('condition_id')->unique();      // Polymarket market id
            $table->string('yes_token_id')->nullable();     // ERC1155 token for "Yes"
            $table->string('no_token_id')->nullable();
            $table->string('slug')->nullable();
            $table->text('question');
            $table->string('sport')->nullable();            // matched tag/category
            $table->decimal('yes_price', 8, 4)->nullable(); // last seen market price for Yes
            $table->decimal('liquidity', 16, 2)->nullable();
            $table->decimal('volume', 16, 2)->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['active', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_markets');
    }
};
