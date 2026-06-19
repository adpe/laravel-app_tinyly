<?php

namespace App\Services\Polymarket;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Reads public market data from the Polymarket Gamma API (no auth required).
 *
 * Returns normalised, binary (Yes/No) sports & e-sport markets ready for the
 * edge engine to evaluate.
 */
class GammaClient
{
    public function __construct(
        private readonly string $baseUrl = '',
    ) {}

    private function url(): string
    {
        return $this->baseUrl ?: config('polymarket.gamma_url');
    }

    /**
     * Fetch active sports/e-sport markets matching the configured tags.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function discoverMarkets(): Collection
    {
        $limit = (int) config('polymarket.scan_limit', 60);
        $tags = collect(config('polymarket.tags', []))->map(fn ($t) => Str::lower($t));
        $minLiquidity = (float) config('polymarket.min_liquidity', 0);
        $minHours = (float) config('polymarket.min_hours_to_resolution', 0);

        $response = Http::acceptJson()
            ->timeout(20)
            ->get($this->url().'/markets', [
                'active' => 'true',
                'closed' => 'false',
                'archived' => 'false',
                'order' => 'volumeNum',
                'ascending' => 'false',
                // Over-fetch; we filter to sports & binary markets client-side.
                'limit' => max($limit * 4, 200),
            ]);

        if (! $response->successful()) {
            return collect();
        }

        return collect($response->json())
            ->map(fn ($m) => $this->normalize($m, $tags))
            ->filter()
            ->filter(fn ($m) => $m['liquidity'] >= $minLiquidity)
            ->filter(fn ($m) => $m['ends_at'] === null
                || $m['ends_at']->greaterThan(now()->addHours($minHours)))
            ->sortByDesc('volume')
            ->take($limit)
            ->values();
    }

    /**
     * Normalise a raw Gamma market into the internal shape, or null if it is
     * not a sports/e-sport binary market we can trade.
     *
     * @param  array<string, mixed>  $m
     * @param  Collection<int, string>  $tags
     * @return array<string, mixed>|null
     */
    private function normalize(array $m, Collection $tags): ?array
    {
        $outcomes = $this->decodeList($m['outcomes'] ?? null);
        $prices = $this->decodeList($m['outcomePrices'] ?? null);
        $tokenIds = $this->decodeList($m['clobTokenIds'] ?? null);

        // Only binary Yes/No markets (two outcomes, two tokens).
        if (count($outcomes) !== 2 || count($tokenIds) !== 2) {
            return null;
        }

        $sport = $this->matchSport($m, $tags);
        if ($sport === null) {
            return null;
        }

        // Map the "Yes" outcome to its token + price.
        $yesIndex = $this->yesIndex($outcomes);

        return [
            'condition_id' => (string) ($m['conditionId'] ?? $m['id'] ?? ''),
            'slug' => $m['slug'] ?? null,
            'question' => (string) ($m['question'] ?? ''),
            'sport' => $sport,
            'yes_token_id' => (string) ($tokenIds[$yesIndex] ?? ''),
            'no_token_id' => (string) ($tokenIds[1 - $yesIndex] ?? ''),
            'yes_price' => isset($prices[$yesIndex]) ? (float) $prices[$yesIndex] : null,
            'liquidity' => (float) ($m['liquidityNum'] ?? $m['liquidity'] ?? 0),
            'volume' => (float) ($m['volumeNum'] ?? $m['volume'] ?? 0),
            'ends_at' => $this->parseDate($m['endDate'] ?? null),
            'outcomes' => $outcomes,
            'meta' => [
                'event' => $m['events'][0]['title'] ?? ($m['groupItemTitle'] ?? null),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $m
     * @param  Collection<int, string>  $tags
     */
    private function matchSport(array $m, Collection $tags): ?string
    {
        $haystack = Str::lower(implode(' ', array_filter([
            $m['slug'] ?? '',
            $m['category'] ?? '',
            collect($m['tags'] ?? [])->map(fn ($t) => is_array($t) ? ($t['label'] ?? $t['slug'] ?? '') : $t)->implode(' '),
            collect($m['events'] ?? [])->map(fn ($e) => ($e['title'] ?? '').' '.($e['slug'] ?? ''))->implode(' '),
        ])));

        foreach ($tags as $tag) {
            if ($tag !== '' && Str::contains($haystack, $tag)) {
                return $tag;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $outcomes
     */
    private function yesIndex(array $outcomes): int
    {
        foreach ($outcomes as $i => $outcome) {
            if (Str::lower(trim($outcome)) === 'yes') {
                return $i;
            }
        }

        return 0;
    }

    /**
     * Gamma encodes list fields as JSON strings; decode defensively.
     *
     * @return array<int, mixed>
     */
    private function decodeList(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
