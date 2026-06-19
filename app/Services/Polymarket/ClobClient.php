<?php

namespace App\Services\Polymarket;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Talks to the Polymarket CLOB. Reading the order book is public HTTP; placing
 * an order requires EIP-712 signing with the Polygon wallet key, which is
 * delegated to the Python sidecar (the key never enters PHP).
 */
class ClobClient
{
    private function url(): string
    {
        return config('polymarket.clob_url');
    }

    /**
     * Best ask (lowest sell) for a token — the price you'd pay to buy now.
     */
    public function bestAsk(string $tokenId): ?float
    {
        try {
            $response = Http::acceptJson()->timeout(15)
                ->get($this->url().'/book', ['token_id' => $tokenId]);

            if (! $response->successful()) {
                return null;
            }

            $asks = $response->json('asks', []);
            if (empty($asks)) {
                return null;
            }

            // The CLOB returns asks ascending by price; the first is the best.
            $prices = array_map(fn ($a) => (float) ($a['price'] ?? 0), $asks);
            $prices = array_filter($prices, fn ($p) => $p > 0);

            return empty($prices) ? null : min($prices);
        } catch (\Throwable $e) {
            Log::warning('CLOB book fetch failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Place a signed limit BUY order via the sidecar.
     *
     * @return array{ok: bool, order_id?: string, status?: string, error?: string}
     */
    public function placeBuyOrder(string $tokenId, float $price, float $size): array
    {
        $payload = [
            'action' => 'place_order',
            'token_id' => $tokenId,
            'side' => 'BUY',
            'price' => $price,
            'size' => $size,
            'host' => $this->url(),
            'signature_type' => (int) config('polymarket.sidecar.signature_type'),
            'funder' => config('polymarket.sidecar.funder'),
        ];

        return $this->runSidecar($payload);
    }

    /**
     * Fetch the wallet's USDC balance via the sidecar.
     *
     * @return array{ok: bool, balance?: float, error?: string}
     */
    public function balance(): array
    {
        return $this->runSidecar([
            'action' => 'balance',
            'host' => $this->url(),
            'signature_type' => (int) config('polymarket.sidecar.signature_type'),
            'funder' => config('polymarket.sidecar.funder'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function runSidecar(array $payload): array
    {
        $script = config('polymarket.sidecar.script');
        if (! is_file($script)) {
            return ['ok' => false, 'error' => 'sidecar script not found'];
        }

        try {
            $result = Process::timeout((int) config('polymarket.sidecar.timeout'))
                ->input(json_encode($payload))
                ->run([config('polymarket.sidecar.python'), $script]);

            if (! $result->successful()) {
                return ['ok' => false, 'error' => trim($result->errorOutput()) ?: 'sidecar exited non-zero'];
            }

            $decoded = json_decode(trim($result->output()), true);

            return is_array($decoded)
                ? $decoded
                : ['ok' => false, 'error' => 'unparseable sidecar output'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
