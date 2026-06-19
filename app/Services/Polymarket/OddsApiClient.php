<?php

namespace App\Services\Polymarket;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Signal source #1 — compares a Polymarket market against sportsbook consensus
 * (vig removed) using The Odds API. Produces a "fair" probability for the
 * market's Yes side plus a confidence reflecting match quality.
 */
class OddsApiClient
{
    /**
     * Map internal sport tags to The Odds API sport keys (group prefixes).
     * A market tagged "nba" will pull odds from "basketball_nba", etc.
     *
     * @var array<string, array<int, string>>
     */
    private array $sportKeyMap = [
        'nba' => ['basketball_nba'],
        'nfl' => ['americanfootball_nfl'],
        'mlb' => ['baseball_mlb'],
        'nhl' => ['icehockey_nhl'],
        'soccer' => ['soccer_epl', 'soccer_uefa_champs_league', 'soccer_spain_la_liga', 'soccer_italy_serie_a', 'soccer_germany_bundesliga', 'soccer_usa_mls'],
        'tennis' => ['tennis_atp', 'tennis_wta'],
        'ufc' => ['mma_mixed_martial_arts'],
        'mma' => ['mma_mixed_martial_arts'],
        'csgo' => ['esports_csgo'],
        'cs2' => ['esports_csgo'],
        'league-of-legends' => ['esports_lol'],
        'dota-2' => ['esports_dota2'],
        'valorant' => ['esports_valorant'],
    ];

    public function isConfigured(): bool
    {
        return ! empty(config('polymarket.odds_api.key'));
    }

    /**
     * Estimate the vig-free fair probability for the market's Yes side.
     *
     * @param  array<string, mixed>  $market  normalised Gamma market
     * @return array{prob: float, confidence: float}|null
     */
    public function fairProbForMarket(array $market): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $sportKeys = $this->sportKeyMap[$market['sport']] ?? [];
        if (empty($sportKeys)) {
            return null;
        }

        $question = Str::lower($market['question'] ?? '');

        foreach ($sportKeys as $sportKey) {
            foreach ($this->events($sportKey) as $event) {
                $match = $this->matchEvent($event, $question);
                if ($match !== null) {
                    return $match;
                }
            }
        }

        return null;
    }

    /**
     * Fetch (and cache) h2h odds events for a sport key.
     *
     * @return array<int, array<string, mixed>>
     */
    private function events(string $sportKey): array
    {
        return Cache::remember("odds_api:$sportKey", now()->addMinutes(10), function () use ($sportKey) {
            $response = Http::acceptJson()->timeout(20)->get(
                config('polymarket.odds_api.url')."/sports/$sportKey/odds",
                [
                    'apiKey' => config('polymarket.odds_api.key'),
                    'regions' => config('polymarket.odds_api.regions'),
                    'markets' => 'h2h',
                    'oddsFormat' => 'decimal',
                ]
            );

            return $response->successful() && is_array($response->json())
                ? $response->json()
                : [];
        });
    }

    /**
     * If the Polymarket question references one of this event's competitors,
     * return that competitor's vig-free win probability.
     *
     * @param  array<string, mixed>  $event
     * @return array{prob: float, confidence: float}|null
     */
    private function matchEvent(array $event, string $question): ?array
    {
        $home = $event['home_team'] ?? null;
        $away = $event['away_team'] ?? null;
        if (! $home || ! $away) {
            return null;
        }

        $probs = $this->consensusProbabilities($event);
        if ($probs === null) {
            return null;
        }

        $homeHit = $this->nameInQuestion($home, $question);
        $awayHit = $this->nameInQuestion($away, $question);

        // Need to identify exactly which competitor the Yes outcome refers to.
        if ($homeHit && ! $awayHit) {
            return ['prob' => $probs[$home], 'confidence' => 0.9];
        }
        if ($awayHit && ! $homeHit) {
            return ['prob' => $probs[$away], 'confidence' => 0.9];
        }

        return null;
    }

    /**
     * Average vig-free win probability per competitor across all bookmakers.
     *
     * @param  array<string, mixed>  $event
     * @return array<string, float>|null
     */
    private function consensusProbabilities(array $event): ?array
    {
        $totals = [];
        $counts = [];

        foreach ($event['bookmakers'] ?? [] as $book) {
            foreach ($book['markets'] ?? [] as $bookMarket) {
                if (($bookMarket['key'] ?? null) !== 'h2h') {
                    continue;
                }

                $implied = [];
                foreach ($bookMarket['outcomes'] ?? [] as $outcome) {
                    $price = (float) ($outcome['price'] ?? 0);
                    if ($price > 0) {
                        $implied[$outcome['name']] = 1 / $price;
                    }
                }

                $overround = array_sum($implied);
                if ($overround <= 0) {
                    continue;
                }

                // Remove vig by normalising implied probabilities to sum to 1.
                foreach ($implied as $name => $p) {
                    $totals[$name] = ($totals[$name] ?? 0) + ($p / $overround);
                    $counts[$name] = ($counts[$name] ?? 0) + 1;
                }
            }
        }

        if (empty($totals)) {
            return null;
        }

        $result = [];
        foreach ($totals as $name => $sum) {
            $result[$name] = $sum / $counts[$name];
        }

        return $result;
    }

    private function nameInQuestion(string $team, string $question): bool
    {
        // Match on the most distinctive token of the team name (e.g. "Lakers").
        $tokens = collect(preg_split('/\s+/', Str::lower($team)))
            ->filter(fn ($t) => strlen($t) >= 4);

        if ($tokens->isEmpty()) {
            return Str::contains($question, Str::lower($team));
        }

        return $tokens->contains(fn ($token) => Str::contains($question, $token));
    }
}
