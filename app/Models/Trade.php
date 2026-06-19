<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trade extends Model
{
    protected $table = 'pm_trades';

    protected $fillable = [
        'pm_signal_id', 'pm_market_id', 'side', 'token_id', 'price', 'size',
        'stake', 'mode', 'status', 'order_id', 'realized_pnl', 'error', 'meta',
    ];

    protected $casts = [
        'price' => 'float',
        'size' => 'float',
        'stake' => 'float',
        'realized_pnl' => 'float',
        'meta' => 'array',
    ];

    public function signal(): BelongsTo
    {
        return $this->belongsTo(TradingSignal::class, 'pm_signal_id');
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(PolymarketMarket::class, 'pm_market_id');
    }
}
