<?php

namespace App\Services\Sms;

use App\Services\Sms\Contracts\SmsDriver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Kavenegar (https://kavenegar.com) gateway driver.
 *
 * Credentials live in config/services.php and are read from the
 * environment:
 *
 *     KAVENEGAR_API_KEY=...   # panel -> API access
 *     KAVENEGAR_SENDER=...    # optional dedicated line number
 *
 * Activate with NOBAT_SMS_DRIVER=kavenegar. Transient HTTP failures are
 * retried twice; a rejected or unconfigured gateway fails loudly so the
 * queued job can retry with its own backoff schedule.
 */
class KavenegarSmsDriver implements SmsDriver
{
    private const ENDPOINT = 'https://api.kavenegar.com/v1/%s/sms/send.json';

    public function send(string $phone, string $message): void
    {
        $key = trim((string) config('services.kavenegar.key'));

        if ($key === '') {
            throw new RuntimeException('KAVENEGAR_API_KEY is not configured.');
        }

        $payload = array_filter([
            'receptor' => $phone,
            'message' => $message,
            'sender' => trim((string) config('services.kavenegar.sender')),
        ]);

        try {
            $response = Http::timeout(10)
                ->retry(2, 500, throw: false)
                ->get(sprintf(self::ENDPOINT, $key), $payload)
                ->throw()
                ->json();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Kavenegar gateway is unreachable: '.$exception->getMessage(),
                previous: $exception,
            );
        }

        $status = (int) ($response['return']['status'] ?? 0);

        if ($status !== 200) {
            throw new RuntimeException(sprintf(
                'Kavenegar rejected the message (status %s): %s',
                $status !== 0 ? (string) $status : 'unknown',
                $response['return']['message'] ?? 'no details returned',
            ));
        }

        Log::info('SMS dispatched via Kavenegar', [
            'to' => $phone,
            'message_id' => $response['entries'][0]['messageid'] ?? null,
        ]);
    }
}
