<?php

namespace Tests\Unit;

use App\Services\Polymarket\EdgeEngine;
use App\Services\Polymarket\NewsAnalyzer;
use App\Services\Polymarket\OddsApiClient;
use Mockery;
use Tests\TestCase;

class EdgeEngineTest extends TestCase
{
    private function engine(?array $odds, ?array $news): EdgeEngine
    {
        $oddsClient = Mockery::mock(OddsApiClient::class);
        $oddsClient->shouldReceive('fairProbForMarket')->andReturn($odds);

        $newsClient = Mockery::mock(NewsAnalyzer::class);
        $newsClient->shouldReceive('fairProbForMarket')->andReturn($news);

        return new EdgeEngine($oddsClient, $newsClient);
    }

    private function market(float $yesPrice, float $liquidity = 5000): array
    {
        return [
            'question' => 'Will Team A win?',
            'sport' => 'nba',
            'yes_price' => $yesPrice,
            'liquidity' => $liquidity,
            'ends_at' => now()->addDay(),
        ];
    }

    public function test_heuristics_only_produces_negligible_edge(): void
    {
        $result = $this->engine(null, null)->evaluate($this->market(0.60));

        $this->assertNotNull($result);
        // The favourite–longshot nudge is intentionally tiny.
        $this->assertLessThan(0.03, $result['edge']);
    }

    public function test_confident_odds_source_creates_yes_edge(): void
    {
        // Sportsbooks say 75% but the market prices 60% → buy YES.
        $result = $this->engine(['prob' => 0.75, 'confidence' => 0.9], null)
            ->evaluate($this->market(0.60));

        $this->assertSame('YES', $result['side']);
        $this->assertGreaterThan(0.05, $result['edge']);
        $this->assertGreaterThan(0.5, $result['confidence']);
    }

    public function test_confident_odds_source_creates_no_edge(): void
    {
        // Sportsbooks say 30% but the market prices 55% → buy NO.
        $result = $this->engine(['prob' => 0.30, 'confidence' => 0.9], null)
            ->evaluate($this->market(0.55));

        $this->assertSame('NO', $result['side']);
        $this->assertGreaterThan(0.05, $result['edge']);
    }

    public function test_disagreeing_sources_lower_confidence(): void
    {
        $agree = $this->engine(['prob' => 0.80, 'confidence' => 0.9], ['prob' => 0.80, 'confidence' => 0.9])
            ->evaluate($this->market(0.60));

        $disagree = $this->engine(['prob' => 0.80, 'confidence' => 0.9], ['prob' => 0.40, 'confidence' => 0.9])
            ->evaluate($this->market(0.60));

        $this->assertGreaterThan($disagree['confidence'], $agree['confidence']);
    }

    public function test_returns_null_for_degenerate_price(): void
    {
        $this->assertNull($this->engine(null, null)->evaluate($this->market(0.0)));
        $this->assertNull($this->engine(null, null)->evaluate($this->market(1.0)));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
