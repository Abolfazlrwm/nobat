<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Services\Payment\Contracts\PaymentGateway;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * ZarinPal payment gateway (OpenBanking v4 API).
 *
 * Flow: request -> redirect the customer to StartPay -> the gateway
 * redirects back to our callback -> verify.
 *
 * Sandbox is enabled with ZARINPAL_SANDBOX=true; amounts are stored in
 * Toman and sent to ZarinPal in Rial (x10) as required by the v4 API.
 */
class ZarinPalGateway implements PaymentGateway
{
    private const SANDBOX_BASE = 'https://sandbox.zarinpal.com';

    private const PRODUCTION_BASE = 'https://payment.zarinpal.com';

    public function request(Payment $payment, string $description, string $callbackUrl): array
    {
        $payload = [
            'merchant_id' => $this->merchantId(),
            'amount' => $this->toRial($payment->amount),
            'callback_url' => $callbackUrl,
            'description' => $description,
        ];

        try {
            $response = $this->http()->post('/pg/v4/payment/request.json', $payload);
        } catch (Throwable $exception) {
            throw new RuntimeException('ZarinPal request failed: '.$exception->getMessage(), previous: $exception);
        }

        $data = $response->json('data') ?? [];
        $authority = $data['authority'] ?? null;

        if (empty($authority)) {
            throw new RuntimeException('ZarinPal request rejected: '.$this->describeErrors($response->json('errors')));
        }

        return [
            'authority' => (string) $authority,
            'redirect_url' => $this->base().'/pg/StartPay/'.(string) $authority,
        ];
    }

    public function verify(Payment $payment): array
    {
        $payload = [
            'merchant_id' => $this->merchantId(),
            'amount' => $this->toRial($payment->amount),
            'authority' => (string) $payment->authority,
        ];

        try {
            $response = $this->http()->post('/pg/v4/payment/verify.json', $payload);
        } catch (Throwable $exception) {
            return [
                'verified' => false,
                'already_verified' => false,
                'ref_id' => null,
                'card_pan' => null,
                'error' => 'ZarinPal verify failed: '.$exception->getMessage(),
            ];
        }

        $data = $response->json('data') ?? [];
        $code = (int) ($data['code'] ?? 0);

        if ($code === 100) {
            return [
                'verified' => true,
                'already_verified' => false,
                'ref_id' => isset($data['ref_id']) ? (string) $data['ref_id'] : null,
                'card_pan' => isset($data['card_pan']) ? (string) $data['card_pan'] : null,
                'error' => null,
            ];
        }

        // 101 = "already verified" — treat as an idempotent success so a
        // repeated webhook never creates a second paid record.
        if ($code === 101) {
            return [
                'verified' => true,
                'already_verified' => true,
                'ref_id' => isset($data['ref_id']) ? (string) $data['ref_id'] : null,
                'card_pan' => isset($data['card_pan']) ? (string) $data['card_pan'] : null,
                'error' => null,
            ];
        }

        return [
            'verified' => false,
            'already_verified' => false,
            'ref_id' => null,
            'card_pan' => null,
            'error' => 'ZarinPal verify rejected (code '.$code.'): '.$this->describeErrors($response->json('errors')),
        ];
    }

    public function refund(Payment $payment, int $amount): array
    {
        // A settled production payment can only be refunded from the ZarinPal
        // merchant panel or via the bank — refuse to fake it loudly.
        if (! (bool) $this->config('sandbox', true)) {
            throw new RuntimeException('ZarinPal refund of a settled payment must be issued from the merchant panel.');
        }

        try {
            // Sandbox: unVerify reverses a verified-but-unsettled transaction.
            $this->http()->post('/pg/v4/payment/unVerify.json', [
                'merchant_id' => $this->merchantId(),
                'authority' => (string) $payment->authority,
            ]);
        } catch (Throwable) {
            // The sandbox reversal is best-effort; the local record is the
            // source of truth for the demo.
        }

        return [
            'succeeded' => true,
            'gateway_refund_id' => null,
            'error' => null,
            'simulated' => true,
        ];
    }

    private function http()
    {
        return Http::asJson()
            ->acceptJson()
            ->timeout((int) $this->config('timeout', 10))
            ->retry((int) $this->config('retries', 2), 200, throw: false);
    }

    private function base(): string
    {
        return (bool) $this->config('sandbox', true) ? self::SANDBOX_BASE : self::PRODUCTION_BASE;
    }

    private function merchantId(): string
    {
        $merchantId = (string) $this->config('merchant_id');

        if ($merchantId === '') {
            throw new RuntimeException('ZarinPal merchant id is not configured (ZARINPAL_MERCHANT_ID).');
        }

        return $merchantId;
    }

    private function toRial(int $toman): int
    {
        return $toman * 10;
    }

    private function describeErrors(mixed $errors): string
    {
        if (is_array($errors)) {
            $first = $errors[0] ?? null;

            if (is_array($first) && isset($first['message'])) {
                return (string) $first['message'];
            }

            if (isset($errors['message'])) {
                return (string) $errors['message'];
            }

            return json_encode($errors, JSON_UNESCAPED_UNICODE) ?: 'unknown error';
        }

        return 'unknown error';
    }

    private function config(string $key, mixed $default = null): mixed
    {
        return config("nobat.payment.gateways.zarinpal.{$key}", $default);
    }
}
