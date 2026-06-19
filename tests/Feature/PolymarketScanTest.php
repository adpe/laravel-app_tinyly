<?php

namespace Tests\Feature;

use App\Models\Trade;
use App\Models\TradingSignal;
use App\Services\Polymarket\EdgeEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class PolymarketScanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'polymarket.live' => false,
            'polymarket.min_edge' => 0.05,
            'polymarket.min_confidence' => 0.5,
            'polymarket.min_liquidity' => 100,
            'polymarket.tags' => ['nba'],
            'polymarket.telegram.bot_token' => 'test-token',
            'polymarket.telegram.chat_id' => '42',
            'polymarket.risk.max_stake' => 50,
            'polymarket.risk.daily_loss_limit' => 500,
            'polymarket.risk.max_trades_per_day' => 10,
        ]);

        Http::fake([
            'gamma-api.polymarket.com/*' => Http::response([$this->fakeMarket()]),
            'api.telegram.org/*' => Http::response(['ok' => true]),
        ]);
    }

    private function fakeMarket(): array
    {
        return [
            'conditionId' => '0xabc',
            'slug' => 'will-team-a-win-nba',
            'question' => 'Will Team A win?',
            'outcomes' => json_encode(['Yes', 'No']),
            'outcomePrices' => json_encode(['0.60', '0.40']),
            'clobTokenIds' => json_encode(['111', '222']),
            'liquidityNum' => 9000,
            'volumeNum' => 50000,
            'endDate' => now()->addDay()->toIso8601String(),
        ];
    }

    private function stubEngine(array $evaluation): void
    {
        $engine = Mockery::mock(EdgeEngine::class);
        $engine->shouldReceive('evaluate')->andReturn($evaluation);
        $this->app->instance(EdgeEngine::class, $engine);
    }

    public function test_actionable_signal_creates_paper_trade_and_alerts(): void
    {
        $this->stubEngine([
            'side' => 'YES',
            'market_prob' => 0.60,
            'fair_prob' => 0.75,
            'edge' => 0.15,
            'confidence' => 0.80,
            'sources' => ['odds' => ['prob' => 0.75, 'confidence' => 0.9]],
        ]);

        $this->artisan('polymarket:scan')->assertSuccessful();

        $this->assertDatabaseHas('pm_markets', ['condition_id' => '0xabc', 'sport' => 'nba']);

        $signal = TradingSignal::first();
        $this->assertNotNull($signal);
        $this->assertSame('YES', $signal->side);
        $this->assertSame('traded', $signal->status);

        $trade = Trade::first();
        $this->assertNotNull($trade);
        $this->assertSame('paper', $trade->mode);
        $this->assertSame('simulated', $trade->status);
        $this->assertGreaterThan(0, $trade->stake);

        // A Telegram alert was attempted.
        Http::assertSent(fn ($r) => str_contains($r->url(), 'api.telegram.org'));
    }

    public function test_weak_edge_is_recorded_but_not_traded(): void
    {
        $this->stubEngine([
            'side' => 'YES',
            'market_prob' => 0.60,
            'fair_prob' => 0.62,
            'edge' => 0.02,         // below min_edge 0.05
            'confidence' => 0.80,
            'sources' => [],
        ]);

        $this->artisan('polymarket:scan')->assertSuccessful();

        $this->assertDatabaseHas('pm_markets', ['condition_id' => '0xabc']);
        $this->assertSame(0, TradingSignal::count());
        $this->assertSame(0, Trade::count());
    }

    public function test_dry_run_alerts_without_trading(): void
    {
        $this->stubEngine([
            'side' => 'YES',
            'market_prob' => 0.60,
            'fair_prob' => 0.75,
            'edge' => 0.15,
            'confidence' => 0.80,
            'sources' => [],
        ]);

        $this->artisan('polymarket:scan --dry')->assertSuccessful();

        $this->assertSame(1, TradingSignal::count());
        $this->assertSame(0, Trade::count());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
