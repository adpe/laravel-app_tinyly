<?php

namespace App\Services\Polymarket;

use App\Models\Trade;
use Illuminate\Support\Facades\Cache;

/**
 * Enforces position sizing and trading limits on every order. Nothing reaches
 * the exchange without passing through here.
 */
class RiskManager
{
    /**
     * Whether real funds may be moved. Requires live=true AND not paused.
     */
    public function isLive(): bool
    {
        return (bool) config('polymarket.live') && ! $this->isPaused();
    }

    public function isPaused(): bool
    {
        // Either the static config flag or a runtime override (set from the
        // Telegram /pause command) keeps the bot halted.
        return (bool) config('polymarket.risk.paused')
            || (bool) Cache::get('polymarket.paused', false);
    }

    public function pause(): void
    {
        Cache::forever('polymarket.paused', true);
    }

    public function resume(): void
    {
        Cache::forget('polymarket.paused');
    }

    /**
     * Gate on the day's activity (applies to paper and live alike so the
     * simulation mirrors the real limits).
     *
     * @return array{allowed: bool, reason: string}
     */
    public function canOpenPosition(): array
    {
        if ($this->isPaused()) {
            return ['allowed' => false, 'reason' => 'bot is paused (kill switch on)'];
        }

        $today = Trade::whereDate('created_at', today())
            ->whereIn('status', ['simulated', 'submitted', 'filled']);

        $count = (clone $today)->count();
        if ($count >= (int) config('polymarket.risk.max_trades_per_day')) {
            return ['allowed' => false, 'reason' => 'daily trade count reached'];
        }

        $exposure = (clone $today)->sum('stake');
        if ($exposure >= (float) config('polymarket.risk.daily_loss_limit')) {
            return ['allowed' => false, 'reason' => 'daily exposure/loss limit reached'];
        }

        return ['allowed' => true, 'reason' => ''];
    }

    /**
     * Fractional-Kelly position size in USDC for the chosen side.
     *
     * @param  string  $side  YES or NO
     * @param  float  $yesPrice  current Yes price (0..1)
     * @param  float  $fairYesProb  blended fair probability of Yes
     */
    public function positionSize(string $side, float $yesPrice, float $fairYesProb): float
    {
        // Translate everything into the side actually being bought.
        if (strtoupper($side) === 'NO') {
            $cost = 1 - $yesPrice;        // price of a NO share
            $fair = 1 - $fairYesProb;     // fair prob of NO
        } else {
            $cost = $yesPrice;
            $fair = $fairYesProb;
        }

        if ($cost <= 0 || $cost >= 1) {
            return 0.0;
        }

        // Full-Kelly fraction of bankroll for a 0/1 payout bought at `cost`.
        $fullKelly = ($fair - $cost) / (1 - $cost);
        if ($fullKelly <= 0) {
            return 0.0;
        }

        $bankroll = (float) config('polymarket.risk.bankroll');
        $kellyFraction = (float) config('polymarket.risk.kelly_fraction');
        $stake = $bankroll * $kellyFraction * $fullKelly;

        // Cap by per-trade max and by remaining daily budget.
        $stake = min($stake, (float) config('polymarket.risk.max_stake'));
        $stake = min($stake, $this->remainingDailyBudget());

        return round(max(0.0, $stake), 2);
    }

    private function remainingDailyBudget(): float
    {
        $used = Trade::whereDate('created_at', today())
            ->whereIn('status', ['simulated', 'submitted', 'filled'])
            ->sum('stake');

        return max(0.0, (float) config('polymarket.risk.daily_loss_limit') - (float) $used);
    }
}
