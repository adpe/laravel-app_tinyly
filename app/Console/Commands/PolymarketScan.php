<?php

namespace App\Console\Commands;

use App\Models\PolymarketMarket;
use App\Models\TradingSignal;
use App\Services\Polymarket\EdgeEngine;
use App\Services\Polymarket\GammaClient;
use App\Services\Polymarket\RiskManager;
use App\Services\Polymarket\TelegramNotifier;
use App\Services\Polymarket\TradeExecutor;
use Illuminate\Console\Command;

class PolymarketScan extends Command
{
    protected $signature = 'polymarket:scan
        {--dry : Evaluate and alert but never place a trade (paper or live)}';

    protected $description = 'Scan Polymarket sports/e-sport markets, detect edges, alert and (optionally) trade';

    public function handle(
        GammaClient $gamma,
        EdgeEngine $engine,
        TradeExecutor $executor,
        TelegramNotifier $telegram,
        RiskManager $risk,
    ): int {
        $markets = $gamma->discoverMarkets();
        $this->info(sprintf('Discovered %d candidate market(s). Live mode: %s',
            $markets->count(), $risk->isLive() ? 'YES' : 'paper'));

        $minEdge = (float) config('polymarket.min_edge');
        $minConfidence = (float) config('polymarket.min_confidence');
        $rows = [];

        foreach ($markets as $data) {
            $market = $this->upsertMarket($data);

            $evaluation = $engine->evaluate($data);
            if ($evaluation === null) {
                continue;
            }

            $actionable = $evaluation['edge'] >= $minEdge
                && $evaluation['confidence'] >= $minConfidence;

            $rows[] = [
                $this->shorten($market->question),
                $evaluation['side'],
                sprintf('%.0f%%', $evaluation['market_prob'] * 100),
                sprintf('%.0f%%', $evaluation['fair_prob'] * 100),
                sprintf('%+.1f%%', $evaluation['edge'] * 100),
                sprintf('%.0f%%', $evaluation['confidence'] * 100),
                $actionable ? '✓' : '',
            ];

            if (! $actionable || $this->recentlySignaled($market)) {
                continue;
            }

            $signal = $this->createSignal($market, $evaluation);

            $trade = null;
            if (! $this->option('dry')) {
                $result = $executor->execute($signal);
                $trade = $result['trade'];
                if ($trade === null) {
                    $this->warn("  skipped trade for #{$market->id}: {$result['reason']}");
                }
            }

            $telegram->notifySignal($signal, $trade);
            $this->line(sprintf('  ⚑ signal #%d %s edge %+.1f%% conf %.0f%%',
                $signal->id, $signal->side, $signal->edge * 100, $signal->confidence * 100));
        }

        if (! empty($rows)) {
            $this->table(['Market', 'Side', 'Mkt', 'Fair', 'Edge', 'Conf', 'Act'],
                array_map('array_values', $rows));
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function upsertMarket(array $data): PolymarketMarket
    {
        return PolymarketMarket::updateOrCreate(
            ['condition_id' => $data['condition_id']],
            [
                'slug' => $data['slug'],
                'question' => $data['question'],
                'sport' => $data['sport'],
                'yes_token_id' => $data['yes_token_id'],
                'no_token_id' => $data['no_token_id'],
                'yes_price' => $data['yes_price'],
                'liquidity' => $data['liquidity'],
                'volume' => $data['volume'],
                'ends_at' => $data['ends_at'],
                'active' => true,
                'meta' => $data['meta'] ?? null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $evaluation
     */
    private function createSignal(PolymarketMarket $market, array $evaluation): TradingSignal
    {
        return $market->signals()->create([
            'side' => $evaluation['side'],
            'market_prob' => $evaluation['market_prob'],
            'fair_prob' => $evaluation['fair_prob'],
            'edge' => $evaluation['edge'],
            'confidence' => $evaluation['confidence'],
            'sources' => $evaluation['sources'],
            'status' => 'open',
        ]);
    }

    private function recentlySignaled(PolymarketMarket $market): bool
    {
        return $market->signals()
            ->where('created_at', '>=', now()->subHours(6))
            ->exists();
    }

    private function shorten(string $text): string
    {
        return strlen($text) > 40 ? substr($text, 0, 37).'...' : $text;
    }
}
