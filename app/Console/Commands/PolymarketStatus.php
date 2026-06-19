<?php

namespace App\Console\Commands;

use App\Models\Trade;
use App\Models\TradingSignal;
use App\Services\Polymarket\ClobClient;
use App\Services\Polymarket\RiskManager;
use Illuminate\Console\Command;

class PolymarketStatus extends Command
{
    protected $signature = 'polymarket:status {--balance : Query on-chain USDC balance via the sidecar}';

    protected $description = 'Show the trading bot status, limits and recent activity';

    public function handle(RiskManager $risk, ClobClient $clob): int
    {
        $this->table(['Setting', 'Value'], [
            ['Mode', $risk->isLive() ? 'LIVE (real funds)' : 'paper (simulation)'],
            ['Paused (kill switch)', $risk->isPaused() ? 'YES' : 'no'],
            ['Bankroll', '$'.config('polymarket.risk.bankroll')],
            ['Max stake/trade', '$'.config('polymarket.risk.max_stake')],
            ['Daily exposure limit', '$'.config('polymarket.risk.daily_loss_limit')],
            ['Max trades/day', config('polymarket.risk.max_trades_per_day')],
            ['Kelly fraction', config('polymarket.risk.kelly_fraction')],
            ['Min edge', (config('polymarket.min_edge') * 100).'%'],
            ['Min confidence', (config('polymarket.min_confidence') * 100).'%'],
        ]);

        $todayCount = Trade::whereDate('created_at', today())->count();
        $todayStake = Trade::whereDate('created_at', today())->sum('stake');
        $this->info(sprintf('Today: %d trade(s), $%.2f staked. Open signals: %d.',
            $todayCount, $todayStake, TradingSignal::where('status', 'open')->count()));

        Trade::with('market')->latest()->take(10)->get()->each(function (Trade $t) {
            $this->line(sprintf('  #%d [%s/%s] %s %.2f@%.3f $%.2f — %s',
                $t->id, $t->mode, $t->status, $t->side, $t->size, $t->price, $t->stake,
                $this->shorten($t->market?->question ?? '')));
        });

        if ($this->option('balance')) {
            $result = $clob->balance();
            $this->info($result['ok'] ?? false
                ? sprintf('Wallet USDC balance: $%.2f', $result['balance'] ?? 0)
                : 'Balance check failed: '.($result['error'] ?? 'unknown'));
        }

        return self::SUCCESS;
    }

    private function shorten(string $text): string
    {
        return strlen($text) > 45 ? substr($text, 0, 42).'...' : $text;
    }
}
