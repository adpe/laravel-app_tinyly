<?php

namespace App\Services\Polymarket;

use App\Models\Trade;
use App\Models\TradingSignal;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotifier
{
    public function isConfigured(): bool
    {
        return ! empty(config('polymarket.telegram.bot_token'))
            && ! empty(config('polymarket.telegram.chat_id'));
    }

    public function send(string $text): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $token = config('polymarket.telegram.bot_token');
            $response = Http::timeout(15)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => config('polymarket.telegram.chat_id'),
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('Telegram send failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function notifySignal(TradingSignal $signal, ?Trade $trade = null): bool
    {
        $market = $signal->market;
        $url = $market->slug ? "https://polymarket.com/event/{$market->slug}" : 'https://polymarket.com';

        $lines = [
            '🎯 <b>Edge detected</b> · '.strtoupper((string) $market->sport),
            htmlspecialchars($market->question),
            '',
            sprintf('Side: <b>%s</b>', $signal->side),
            sprintf('Market: %.1f%%  →  Fair: %.1f%%', $signal->market_prob * 100, $signal->fair_prob * 100),
            sprintf('Edge: <b>%+.1f%%</b>  ·  Confidence: %.0f%%', $signal->edge * 100, $signal->confidence * 100),
        ];

        $lines[] = 'Sources: '.$this->sourceSummary($signal->sources);

        if ($trade) {
            $lines[] = '';
            $mode = strtoupper($trade->mode);
            $lines[] = $trade->status === 'failed'
                ? sprintf('⚠️ %s order failed: %s', $mode, $trade->error)
                : sprintf('✅ %s %s: %.2f shares @ %.3f ($%.2f)', $mode, $trade->status, $trade->size, $trade->price, $trade->stake);
        }

        $lines[] = '';
        $lines[] = $url;

        return $this->send(implode("\n", $lines));
    }

    /**
     * @param  array<string, mixed>|null  $sources
     */
    private function sourceSummary(?array $sources): string
    {
        if (empty($sources)) {
            return 'none';
        }

        return collect($sources)
            ->map(fn ($s, $name) => sprintf('%s %.0f%%(c%.0f)', $name, ($s['prob'] ?? 0) * 100, ($s['confidence'] ?? 0) * 100))
            ->implode(', ');
    }
}
