<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TradingSignal extends Model
{
    protected $table = 'pm_signals';

    protected $fillable = [
        'pm_market_id', 'side', 'market_prob', 'fair_prob', 'edge',
        'confidence', 'sources', 'status',
    ];

    protected $casts = [
        'market_prob' => 'float',
        'fair_prob' => 'float',
        'edge' => 'float',
        'confidence' => 'float',
        'sources' => 'array',
    ];

    public function market(): BelongsTo
    {
        return $this->belongsTo(PolymarketMarket::class, 'pm_market_id');
    }

    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class, 'pm_signal_id');
    }
}
