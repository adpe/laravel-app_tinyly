<?php

namespace App\Services\Polymarket;

use App\Models\Trade;
use App\Models\TradingSignal;
use Illuminate\Support\Facades\Log;

/**
 * Turns an actionable signal into a recorded position — simulated in paper mode,
 * or a real signed CLOB order in live mode. All sizing/limit checks happen in
 * the RiskManager first.
 */
class TradeExecutor
{
    public function __construct(
        private readonly RiskManager $risk,
        private readonly ClobClient $clob,
    ) {}

    /**
     * @return array{trade: ?Trade, reason: string}
     */
    public function execute(TradingSignal $signal): array
    {
        $gate = $this->risk->canOpenPosition();
        if (! $gate['allowed']) {
            return ['trade' => null, 'reason' => $gate['reason']];
        }

        $market = $signal->market;
        $token = $market->tokenIdFor($signal->side);
        if (! $token) {
            return ['trade' => null, 'reason' => 'no token id for side'];
        }

        $stake = $this->risk->positionSize($signal->side, $signal->market_prob, $signal->fair_prob);
        if ($stake < 1.0) {
            return ['trade' => null, 'reason' => 'position size below $1 minimum'];
        }

        // Cost of one share of the chosen side.
        $cost = strtoupper($signal->side) === 'NO'
            ? 1 - $signal->market_prob
            : $signal->market_prob;

        $live = $this->risk->isLive();

        // In live mode prefer the real best ask; add a small buffer to cross.
        $price = $cost;
        if ($live && ($ask = $this->clob->bestAsk($token)) !== null) {
            $price = min(0.99, $ask * 1.01);
        }
        $price = max(0.01, min(0.99, round($price, 3)));

        $size = round($stake / $price, 2);

        $trade = new Trade([
            'pm_signal_id' => $signal->id,
            'pm_market_id' => $market->id,
            'side' => $signal->side,
            'token_id' => $token,
            'price' => $price,
            'size' => $size,
            'stake' => round($price * $size, 2),
            'mode' => $live ? 'live' : 'paper',
        ]);

        if (! $live) {
            $trade->status = 'simulated';
            $trade->save();
            $signal->update(['status' => 'traded']);

            return ['trade' => $trade, 'reason' => 'paper'];
        }

        $result = $this->clob->placeBuyOrder($token, $price, $size);

        if ($result['ok'] ?? false) {
            $trade->status = $result['status'] ?? 'submitted';
            $trade->order_id = $result['order_id'] ?? null;
            $trade->meta = $result;
        } else {
            $trade->status = 'failed';
            $trade->error = $result['error'] ?? 'unknown error';
            Log::error('Live order failed', ['signal' => $signal->id, 'error' => $trade->error]);
        }

        $trade->save();
        $signal->update(['status' => $trade->status === 'failed' ? 'open' : 'traded']);

        return ['trade' => $trade, 'reason' => $live ? 'live' : 'paper'];
    }
}
