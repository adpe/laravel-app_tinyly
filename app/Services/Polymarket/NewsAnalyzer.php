<?php

namespace App\Services\Polymarket;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Signal source #3 — asks Claude to estimate a fair probability for a market
 * given its question and context. This is a model-reasoning signal; it is most
 * useful as a sanity check / tie-breaker and is weighted accordingly.
 */
class NewsAnalyzer
{
    public function isConfigured(): bool
    {
        return (bool) config('polymarket.news.enabled')
            && ! empty(config('polymarket.news.api_key'));
    }

    /**
     * @param  array<string, mixed>  $market  normalised Gamma market
     * @return array{prob: float, confidence: float, rationale: string}|null
     */
    public function fairProbForMarket(array $market): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $ends = isset($market['ends_at']) && $market['ends_at']
            ? $market['ends_at']->toDayDateTimeString()
            : 'unknown';

        $prompt = <<<TXT
        You are a sports trading analyst. Estimate the probability that the
        following Polymarket question resolves YES.

        Question: {$market['question']}
        Sport: {$market['sport']}
        Resolves: {$ends}
        Current market-implied probability of YES: {$market['yes_price']}

        Respond with ONLY a JSON object, no prose:
        {"prob": <0..1 fair probability of YES>,
         "confidence": <0..1 how confident you are given available info>,
         "rationale": "<one short sentence>"}

        Be calibrated. If you lack the specific information to beat the market,
        set confidence low (<0.3) and prob close to the market price.
        TXT;

        try {
            $response = Http::withHeaders([
                'x-api-key' => config('polymarket.news.api_key'),
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(45)->post('https://api.anthropic.com/v1/messages', [
                'model' => config('polymarket.news.model'),
                'max_tokens' => 300,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if (! $response->successful()) {
                Log::warning('NewsAnalyzer API error', ['status' => $response->status()]);

                return null;
            }

            $text = $response->json('content.0.text', '');

            return $this->parse($text);
        } catch (\Throwable $e) {
            Log::warning('NewsAnalyzer failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return array{prob: float, confidence: float, rationale: string}|null
     */
    private function parse(string $text): ?array
    {
        if (! preg_match('/\{.*\}/s', $text, $matches)) {
            return null;
        }

        $data = json_decode($matches[0], true);
        if (! is_array($data) || ! isset($data['prob'])) {
            return null;
        }

        return [
            'prob' => max(0.0, min(1.0, (float) $data['prob'])),
            'confidence' => max(0.0, min(1.0, (float) ($data['confidence'] ?? 0))),
            'rationale' => (string) ($data['rationale'] ?? ''),
        ];
    }
}
