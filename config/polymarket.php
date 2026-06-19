<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Polymarket public APIs
    |--------------------------------------------------------------------------
    |
    | The Gamma API is fully public (no auth) and is used for market discovery
    | and metadata. The CLOB API serves the order book and is also used for
    | placing orders (the latter requires signed requests, handled by the
    | Python sidecar).
    |
    */

    'gamma_url' => env('POLYMARKET_GAMMA_URL', 'https://gamma-api.polymarket.com'),
    'clob_url' => env('POLYMARKET_CLOB_URL', 'https://clob.polymarket.com'),

    /*
    |--------------------------------------------------------------------------
    | Market discovery
    |--------------------------------------------------------------------------
    |
    | Which sports tags to scan. The Gamma "tag" slugs below are matched
    | case-insensitively against each market's tags/category. Add or remove
    | slugs to control what the bot looks at.
    |
    */

    'tags' => array_filter(array_map('trim', explode(',', (string) env(
        'POLYMARKET_TAGS',
        'esports,csgo,cs2,league-of-legends,dota-2,valorant,sports,soccer,nba,nfl,mlb,nhl,tennis,ufc,mma'
    )))),

    // Skip markets that are this many hours (or less) from resolution; near the
    // close, spreads widen and there is little time to react.
    'min_hours_to_resolution' => (float) env('POLYMARKET_MIN_HOURS', 1),

    // Ignore markets thinner than this (USD liquidity) — you cannot get filled.
    'min_liquidity' => (float) env('POLYMARKET_MIN_LIQUIDITY', 500),

    // Max markets to evaluate per scan (keeps API + LLM cost bounded).
    'scan_limit' => (int) env('POLYMARKET_SCAN_LIMIT', 60),

    /*
    |--------------------------------------------------------------------------
    | Strategy / edge engine
    |--------------------------------------------------------------------------
    |
    | A "signal" is raised when the composite fair-probability estimate
    | disagrees with the market price by more than `min_edge`, and the
    | confidence in that estimate clears `min_confidence`.
    |
    | The three signal sources are blended by the weights below. Weights are
    | relative; a source contributes nothing when its data is unavailable
    | (e.g. no odds match, or LLM disabled), and the remaining weights are
    | renormalised.
    |
    */

    'weights' => [
        'odds' => (float) env('POLYMARKET_WEIGHT_ODDS', 0.5),
        'heuristics' => (float) env('POLYMARKET_WEIGHT_HEURISTICS', 0.2),
        'news' => (float) env('POLYMARKET_WEIGHT_NEWS', 0.3),
    ],

    // Minimum absolute edge (fair_prob - market_prob) to act on, e.g. 0.05 = 5%.
    'min_edge' => (float) env('POLYMARKET_MIN_EDGE', 0.05),

    // Minimum blended confidence [0..1] to act on.
    'min_confidence' => (float) env('POLYMARKET_MIN_CONFIDENCE', 0.55),

    /*
    |--------------------------------------------------------------------------
    | Risk management
    |--------------------------------------------------------------------------
    |
    | These limits are enforced on every order. `live` MUST be explicitly set
    | to true to move real funds; otherwise the bot runs in paper mode and only
    | records simulated fills.
    |
    */

    'live' => env('POLYMARKET_LIVE', false),

    'risk' => [
        // Total bankroll the bot is allowed to consider (USDC).
        'bankroll' => (float) env('POLYMARKET_BANKROLL', 200),

        // Hard cap on a single position (USDC).
        'max_stake' => (float) env('POLYMARKET_MAX_STAKE', 20),

        // Stop opening new positions once realised+staked loss for the day hits this (USDC).
        'daily_loss_limit' => (float) env('POLYMARKET_DAILY_LOSS_LIMIT', 50),

        // Max number of new positions per day.
        'max_trades_per_day' => (int) env('POLYMARKET_MAX_TRADES_PER_DAY', 10),

        // Fraction of full-Kelly to stake (0.25 = quarter Kelly). Lower = safer.
        'kelly_fraction' => (float) env('POLYMARKET_KELLY_FRACTION', 0.25),

        // Global kill switch. When true, no orders are placed (paper or live).
        'paused' => env('POLYMARKET_PAUSED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | CLOB execution sidecar
    |--------------------------------------------------------------------------
    |
    | Placing an order requires EIP-712 signing with your Polygon wallet key.
    | That is delegated to a small Python process using Polymarket's official
    | py-clob-client. The private key is read by the sidecar from the env vars
    | below — it is NEVER loaded into PHP and must NEVER be committed.
    |
    */

    'sidecar' => [
        'python' => env('POLYMARKET_PYTHON', 'python3'),
        'script' => base_path('sidecar/clob_executor.py'),
        'timeout' => (int) env('POLYMARKET_SIDECAR_TIMEOUT', 60),
        // signature_type: 0 = EOA key, 1 = email/Magic proxy, 2 = browser proxy.
        'signature_type' => (int) env('POLYMARKET_SIGNATURE_TYPE', 0),
        'funder' => env('POLYMARKET_FUNDER', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | The Odds API (signal source #1)
    |--------------------------------------------------------------------------
    |
    | https://the-odds-api.com — free tier available. Used to compare
    | Polymarket prices against sportsbook consensus (vig-removed).
    |
    */

    'odds_api' => [
        'key' => env('ODDS_API_KEY'),
        'url' => env('ODDS_API_URL', 'https://api.the-odds-api.com/v4'),
        'regions' => env('ODDS_API_REGIONS', 'eu,us'),
    ],

    /*
    |--------------------------------------------------------------------------
    | News analysis (signal source #3) — Claude
    |--------------------------------------------------------------------------
    */

    'news' => [
        'enabled' => env('POLYMARKET_NEWS_ENABLED', false),
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('POLYMARKET_NEWS_MODEL', 'claude-opus-4-8'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Telegram alerts
    |--------------------------------------------------------------------------
    */

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
        // Shared secret appended to the webhook URL to authenticate Telegram.
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    ],

];
