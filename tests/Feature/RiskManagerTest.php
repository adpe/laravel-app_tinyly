<?php

namespace Tests\Feature;

use App\Models\PolymarketMarket;
use App\Models\Trade;
use App\Services\Polymarket\RiskManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'polymarket.risk.bankroll' => 1000,
            'polymarket.risk.max_stake' => 100,
            'polymarket.risk.daily_loss_limit' => 500,
            'polymarket.risk.max_trades_per_day' => 3,
            'polymarket.risk.kelly_fraction' => 0.5,
            'polymarket.risk.paused' => false,
        ]);
    }

    public function test_kelly_sizing_scales_with_edge(): void
    {
        $risk = new RiskManager;

        // YES at 0.50 with fair 0.60: fullKelly = (.6-.5)/(1-.5) = 0.2.
        // stake = 1000 * 0.5 * 0.2 = 100, capped at max_stake 100.
        $this->assertEqualsWithDelta(100.0, $risk->positionSize('YES', 0.50, 0.60), 0.01);

        // Smaller edge → smaller stake. fair 0.55: fullKelly = .05/.5 = 0.1 → 50.
        $this->assertEqualsWithDelta(50.0, $risk->positionSize('YES', 0.50, 0.55), 0.01);
    }

    public function test_no_stake_when_no_edge(): void
    {
        $risk = new RiskManager;
        $this->assertSame(0.0, $risk->positionSize('YES', 0.60, 0.55));
    }

    public function test_no_side_sizing_uses_complement(): void
    {
        $risk = new RiskManager;
        // Buying NO when yesPrice 0.40 (NO cost 0.60) and fair yes 0.30 (NO fair 0.70):
        // fullKelly = (.7-.6)/(1-.6) = 0.25 → 1000*0.5*0.25 = 125, capped at 100.
        $this->assertEqualsWithDelta(100.0, $risk->positionSize('NO', 0.40, 0.30), 0.01);
    }

    public function test_daily_trade_count_blocks_new_positions(): void
    {
        $market = PolymarketMarket::create([
            'condition_id' => 'c1', 'question' => 'q', 'sport' => 'nba',
        ]);

        foreach (range(1, 3) as $i) {
            Trade::create([
                'pm_market_id' => $market->id, 'side' => 'YES', 'token_id' => 't',
                'price' => 0.5, 'size' => 10, 'stake' => 5, 'mode' => 'paper', 'status' => 'simulated',
            ]);
        }

        $gate = (new RiskManager)->canOpenPosition();
        $this->assertFalse($gate['allowed']);
        $this->assertStringContainsString('daily trade count', $gate['reason']);
    }

    public function test_pause_resume_toggles_kill_switch(): void
    {
        $risk = new RiskManager;
        $this->assertFalse($risk->isPaused());

        $risk->pause();
        $this->assertTrue($risk->isPaused());
        $this->assertFalse($risk->canOpenPosition()['allowed']);

        $risk->resume();
        $this->assertFalse($risk->isPaused());
    }
}
