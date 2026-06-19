<?php

namespace App\Http\Controllers;

use App\Models\Trade;
use App\Services\Polymarket\ClobClient;
use App\Services\Polymarket\RiskManager;
use App\Services\Polymarket\TelegramNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Receives Telegram updates so the bot can be controlled from the phone.
 * Supported commands: /status, /pause, /resume, /balance.
 *
 * The webhook URL embeds a secret (config polymarket.telegram.webhook_secret)
 * and we additionally verify the chat id, so only the configured operator can
 * issue commands.
 */
class TelegramWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $secret,
        RiskManager $risk,
        TelegramNotifier $telegram,
        ClobClient $clob,
    ) {
        abort_unless(hash_equals((string) config('polymarket.telegram.webhook_secret'), $secret), 404);

        $chatId = (string) $request->input('message.chat.id');
        if ($chatId !== (string) config('polymarket.telegram.chat_id')) {
            return response()->json(['ok' => true]); // ignore strangers silently
        }

        $text = Str::lower(trim((string) $request->input('message.text')));

        match (true) {
            Str::startsWith($text, '/pause') => $this->reply($telegram, $this->pause($risk)),
            Str::startsWith($text, '/resume') => $this->reply($telegram, $this->resume($risk)),
            Str::startsWith($text, '/balance') => $this->reply($telegram, $this->balance($clob)),
            Str::startsWith($text, '/status') => $this->reply($telegram, $this->status($risk)),
            default => $this->reply($telegram, 'Commands: /status /pause /resume /balance'),
        };

        return response()->json(['ok' => true]);
    }

    private function pause(RiskManager $risk): string
    {
        $risk->pause();

        return '⏸ Bot paused. No new positions will be opened until /resume.';
    }

    private function resume(RiskManager $risk): string
    {
        $risk->resume();

        return '▶️ Bot resumed.'.($risk->isLive() ? ' Mode: LIVE.' : ' Mode: paper.');
    }

    private function balance(ClobClient $clob): string
    {
        $result = $clob->balance();

        return ($result['ok'] ?? false)
            ? sprintf('💰 Wallet USDC balance: $%.2f', $result['balance'] ?? 0)
            : '⚠️ Balance check failed: '.($result['error'] ?? 'unknown');
    }

    private function status(RiskManager $risk): string
    {
        $count = Trade::whereDate('created_at', today())->count();
        $stake = Trade::whereDate('created_at', today())->sum('stake');

        return implode("\n", [
            'Mode: '.($risk->isLive() ? 'LIVE' : 'paper'),
            'Paused: '.($risk->isPaused() ? 'yes' : 'no'),
            sprintf('Today: %d trade(s), $%.2f staked', $count, $stake),
        ]);
    }

    private function reply(TelegramNotifier $telegram, string $message): void
    {
        $telegram->send($message);
    }
}
