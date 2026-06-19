<?php

namespace App\Services\Polymarket;

/**
 * The core strategy. For a given market it gathers up to three independent
 * "fair probability" estimates (sportsbook odds, market heuristics, Claude news
 * analysis), blends them by configured weight × each source's confidence, and
 * derives the edge versus the current market price.
 *
 * Reality check encoded here: the market price already equals the implied
 * probability, so a source only contributes edge when it disagrees with the
 * price AND is confident. When the only available source is the heuristic one
 * (which is derived from the price itself), the edge stays near zero.
 */
class EdgeEngine
{
    public function __construct(
        private readonly OddsApiClient $odds,
        private readonly NewsAnalyzer $news,
    ) {}

    /**
     * @param  array<string, mixed>  $market  normalised Gamma market
     * @return array<string, mixed>|null null when no price / sources available
     */
    public function evaluate(array $market): ?array
    {
        $marketProb = $market['yes_price'] ?? null;
        if ($marketProb === null || $marketProb <= 0 || $marketProb >= 1) {
            return null;
        }

        $weights = config('polymarket.weights');
        $sources = [];

        // #1 Sportsbook odds.
        if ($odds = $this->odds->fairProbForMarket($market)) {
            $sources['odds'] = [
                'prob' => $odds['prob'],
                'confidence' => $odds['confidence'],
                'weight' => (float) $weights['odds'],
            ];
        }

        // #2 Heuristics (favourite–longshot bias + liquidity-scaled confidence).
        $sources['heuristics'] = [
            'prob' => $this->heuristicProb($marketProb),
            'confidence' => $this->heuristicConfidence($market),
            'weight' => (float) $weights['heuristics'],
        ];

        // #3 Claude news analysis.
        if ($news = $this->news->fairProbForMarket($market)) {
            $sources['news'] = [
                'prob' => $news['prob'],
                'confidence' => $news['confidence'],
                'weight' => (float) $weights['news'],
                'rationale' => $news['rationale'] ?? '',
            ];
        }

        return $this->blend($marketProb, $sources);
    }

    /**
     * Favourite–longshot bias: favourites tend to be slightly underpriced and
     * longshots overpriced. Nudge the fair probability toward the extreme for
     * favourites and away for longshots. Intentionally small.
     */
    private function heuristicProb(float $marketProb): float
    {
        $alpha = 0.06;
        $fair = $marketProb + $alpha * ($marketProb - 0.5);

        return max(0.01, min(0.99, $fair));
    }

    /**
     * @param  array<string, mixed>  $market
     */
    private function heuristicConfidence(array $market): float
    {
        $minLiquidity = max(1.0, (float) config('polymarket.min_liquidity', 500));
        $liquidity = (float) ($market['liquidity'] ?? 0);

        // Deeper books → more trust, but heuristics cap out as a weak signal.
        return min(0.5, ($liquidity / ($minLiquidity * 5)) * 0.5);
    }

    /**
     * Blend the sources into a single fair probability, edge and confidence.
     *
     * @param  array<string, array<string, mixed>>  $sources
     * @return array<string, mixed>
     */
    private function blend(float $marketProb, array $sources): array
    {
        $totalEffective = 0.0;
        $weightedProb = 0.0;
        $configWeightTotal = 0.0;
        $confidenceWeighted = 0.0;
        $probs = [];

        foreach ($sources as $s) {
            $effective = $s['weight'] * $s['confidence'];
            $totalEffective += $effective;
            $weightedProb += $s['prob'] * $effective;
            $configWeightTotal += $s['weight'];
            $confidenceWeighted += $s['confidence'] * $s['weight'];
            $probs[] = $s['prob'];
        }

        // No source carried any weight → defer to the market (no edge).
        $fairProb = $totalEffective > 0 ? $weightedProb / $totalEffective : $marketProb;

        $rawConfidence = $configWeightTotal > 0 ? $confidenceWeighted / $configWeightTotal : 0.0;
        $confidence = $rawConfidence * (0.5 + 0.5 * $this->agreement($probs));

        // Pick the side with positive edge.
        if ($fairProb >= $marketProb) {
            $side = 'YES';
            $edge = $fairProb - $marketProb;
        } else {
            $side = 'NO';
            $edge = $marketProb - $fairProb;
        }

        return [
            'side' => $side,
            'market_prob' => round($marketProb, 4),
            'fair_prob' => round($fairProb, 4),
            'edge' => round($edge, 4),
            'confidence' => round(min(1.0, max(0.0, $confidence)), 4),
            'sources' => $sources,
        ];
    }

    /**
     * Agreement in [0,1]: 1 when all sources concur, lower as they diverge.
     *
     * @param  array<int, float>  $probs
     */
    private function agreement(array $probs): float
    {
        if (count($probs) < 2) {
            return 1.0;
        }

        $mean = array_sum($probs) / count($probs);
        $variance = array_sum(array_map(fn ($p) => ($p - $mean) ** 2, $probs)) / count($probs);
        $std = sqrt($variance);

        // A 0.25 std (very divergent for probabilities) collapses agreement to 0.
        return max(0.0, 1.0 - min(1.0, $std * 4));
    }
}
