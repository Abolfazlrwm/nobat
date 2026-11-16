<?php

namespace App\Services\Sms;

use App\Services\Sms\Contracts\SmsDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Twilio (https://twilio.com) gateway driver — international numbers.
 *
 * Credentials live in config/services.php and are read from the
 * environment:
 *
 *     TWILIO_ACCOUNT_SID=AC...
 *     TWILIO_AUTH_TOKEN=...
 *     TWILIO_FROM=+15005550006  # an E.164 sender (number or alphanumeric)
 *
 * Activate with NOBAT_SMS_DRIVER=twilio, or intl to route by country:
 * domestic (+98/09…) rides Kavenegar, everything else rides Twilio.
 * Transient HTTP failures are retried twice; a rejected or unconfigured
 * gateway fails loudly so the queued job can retry with its own backoff.
 */
class TwilioSmsDriver implements SmsDriver
{
    private const ENDPOINT = 'https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json';

    public function send(string $phone, string $message): void
    {
        $sid = trim((string) config('services.twilio.sid'));
        $token = trim((string) config('services.twilio.token'));
        $from = trim((string) config('services.twilio.from'));

        if ($sid === '' || $token === '' || $from === '') {
            throw new RuntimeException(
                'Twilio is not configured: TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN and TWILIO_FROM are required.'
            );
        }

        $to = $this->toE164($phone);

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->retry(2, 500, throw: false)
                ->withBasicAuth($sid, $token)
                ->post(sprintf(self::ENDPOINT, $sid), [
                    'From' => $from,
                    'To' => $to,
                    'Body' => $message,
                ]);
        } catch (Throwable $exception) {
            Log::error('Twilio sms transport failed', [
                'to' => $to,
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Twilio send failed: '.$exception->getMessage(), previous: $exception);
        }

        if (! $response->successful()) {
            Log::error('Twilio sms rejected', [
                'to' => $to,
                'status' => $response->status(),
                'message' => $response->json('message') ?? $response->body(),
            ]);

            throw new RuntimeException(
                'Twilio rejected the message (HTTP '.$response->status().'): '
                .($response->json('message') ?? 'no details returned')
            );
        }

        Log::info('SMS dispatched via Twilio', [
            'to' => $to,
            'message_id' => $response->json('sid'),
        ]);
    }

    /**
     * Twilio speaks E.164 only: domestic Iranian inputs saved as
     * 09121234567 grow the +98 country code; anything already international
     * just gets the leading plus it was missing.
     */
    private function toE164(string $phone): string
    {
        $digits = preg_replace('/[^\d+]/', '', $phone) ?? $phone;

        if (str_starts_with($digits, '+')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '+98'.substr($digits, 1);
        }

        return '+'.$digits;
    }
}
