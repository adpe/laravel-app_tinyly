# Polymarket sports / e-sport trading bot

An automated edge-finder for Polymarket sports & e-sport markets. It scans
markets on a schedule, estimates a "fair" probability from up to three
independent signal sources, and — when the market price disagrees by enough —
alerts you on Telegram and (optionally) places the bet automatically via the
CLOB API, so you never have to log in to Polymarket.

## ⚠️ Read this first (honest expectations)

- **The market price already *is* the implied probability.** A market at 90¢
  means ~90% implied — you pay for that near-certainty. "High chance to win"
  does **not** mean "profitable".
- **Edge only exists when a *better* estimate disagrees with the price.** That
  is what the odds-comparison and news sources are for. The pure-Polymarket
  heuristic, by design, produces almost no edge on its own.
- **No bot can guarantee winning.** Treat this as a disciplined execution and
  alerting tool, not a money printer. Start in paper mode and only go live once
  the recorded signals have shown a real, positive track record.
- **Geography & funds.** Trading needs a funded Polygon (USDC) wallet and may be
  geo-restricted depending on where you are. That's on you to sort out.

## How it works

```
polymarket:scan  (scheduled every 5 min)
  → GammaClient       discover active sports/e-sport binary markets (public API)
  → EdgeEngine        blend up to 3 fair-probability estimates:
        #1 OddsApiClient   sportsbook consensus, vig removed   (weight 0.5)
        #2 heuristics      favourite–longshot bias + liquidity (weight 0.2)
        #3 NewsAnalyzer    Claude fair-value estimate           (weight 0.3)
  → if |edge| ≥ min_edge AND confidence ≥ min_confidence:
        → record a TradingSignal
        → RiskManager   gate (kill switch, daily caps) + fractional-Kelly size
        → TradeExecutor paper-simulate, or place a live signed CLOB order
        → TelegramNotifier  push the alert to your phone
```

All discovered markets, signals and trades are persisted (`pm_markets`,
`pm_signals`, `pm_trades`) so you can audit and back-test the strategy.

## Setup

### 1. Configure environment

Copy the new keys from `.env.example` into your `.env`. The bot runs out of the
box in **paper mode** against the public Gamma API with no keys at all — alerts
and live trading just stay disabled until you add the relevant credentials.

| Capability | Required env |
|---|---|
| Market scanning (always on) | none — public API |
| Telegram alerts & control | `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID`, `TELEGRAM_WEBHOOK_SECRET` |
| Signal #1 sportsbook odds | `ODDS_API_KEY` (https://the-odds-api.com) |
| Signal #3 Claude news | `POLYMARKET_NEWS_ENABLED=true`, `ANTHROPIC_API_KEY` |
| Live trading | `POLYMARKET_LIVE=true`, `POLYMARKET_PRIVATE_KEY`, sidecar deps |

> **Network egress:** allow outbound access to `gamma-api.polymarket.com`,
> `clob.polymarket.com`, `api.the-odds-api.com`, `api.telegram.org` and
> `api.anthropic.com`.

### 2. Migrate

```bash
php artisan migrate
```

### 3. Telegram bot

1. Create a bot with [@BotFather](https://t.me/BotFather) → `TELEGRAM_BOT_TOKEN`.
2. Message your bot once, then read your numeric chat id (e.g. via
   `https://api.telegram.org/bot<token>/getUpdates`) → `TELEGRAM_CHAT_ID`.
3. Pick any random string for `TELEGRAM_WEBHOOK_SECRET`.
4. Register the control webhook (so you can `/pause` `/resume` `/status`
   `/balance` from your phone):
   ```bash
   curl "https://api.telegram.org/bot<token>/setWebhook?url=https://YOUR_APP/telegram/webhook/<secret>"
   ```

### 4. The execution sidecar (only needed for live trading)

Order signing uses Polymarket's official Python client so the private key never
touches PHP:

```bash
pip install -r sidecar/requirements.txt
```

Set `POLYMARKET_PRIVATE_KEY` (Polygon wallet key), and if you fund through an
email/Magic or browser proxy wallet, also set `POLYMARKET_FUNDER` and the
matching `POLYMARKET_SIGNATURE_TYPE` (0 = EOA, 1 = email/Magic, 2 = browser).

### 5. Run the scheduler

The scan is registered in `routes/console.php` to run every 5 minutes. In
production run Laravel's scheduler:

```bash
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

## Usage

```bash
php artisan polymarket:scan          # one scan now (paper or live per config)
php artisan polymarket:scan --dry    # evaluate + alert, never trade
php artisan polymarket:status        # mode, limits, today's activity, recent trades
php artisan polymarket:status --balance   # also query wallet USDC via the sidecar
```

Telegram commands: `/status`, `/pause`, `/resume`, `/balance`.

## Going live (checklist)

1. Run in paper mode for a while; review `pm_signals` / `pm_trades`.
2. Add `ODDS_API_KEY` (and optionally enable the news source) — without a real
   external estimate the bot has no genuine edge and will rarely fire.
3. Fund a Polygon wallet with USDC and install the sidecar.
4. Tighten risk in `.env`: `POLYMARKET_MAX_STAKE`, `POLYMARKET_DAILY_LOSS_LIMIT`,
   `POLYMARKET_MAX_TRADES_PER_DAY`, `POLYMARKET_KELLY_FRACTION`.
5. Set `POLYMARKET_LIVE=true`. Keep `/pause` handy as a kill switch.

## Risk controls (always enforced)

- **Kill switch** — `POLYMARKET_PAUSED=true` or `/pause`; halts all new positions.
- **Per-trade cap** — `POLYMARKET_MAX_STAKE`.
- **Daily exposure cap** — `POLYMARKET_DAILY_LOSS_LIMIT`.
- **Daily count cap** — `POLYMARKET_MAX_TRADES_PER_DAY`.
- **Fractional Kelly** — `POLYMARKET_KELLY_FRACTION` (0.25 = quarter Kelly).

These apply identically in paper and live mode, so the simulation mirrors how
live trading would actually behave.
