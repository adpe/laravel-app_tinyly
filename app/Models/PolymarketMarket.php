<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PolymarketMarket extends Model
{
    protected $table = 'pm_markets';

    protected $fillable = [
        'condition_id', 'yes_token_id', 'no_token_id', 'slug', 'question',
        'sport', 'yes_price', 'liquidity', 'volume', 'ends_at', 'active', 'meta',
    ];

    protected $casts = [
        'yes_price' => 'float',
        'liquidity' => 'float',
        'volume' => 'float',
        'ends_at' => 'datetime',
        'active' => 'boolean',
        'meta' => 'array',
    ];

    public function signals(): HasMany
    {
        return $this->hasMany(TradingSignal::class, 'pm_market_id');
    }

    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class, 'pm_market_id');
    }

    public function tokenIdFor(string $side): ?string
    {
        return strtoupper($side) === 'NO' ? $this->no_token_id : $this->yes_token_id;
    }
}
