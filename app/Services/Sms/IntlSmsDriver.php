<?php

namespace App\Services\Sms;

use App\Services\Sms\Contracts\SmsDriver;

/**
 * Routes each message by destination: domestic Iranian numbers ride the
 * Kavenegar gateway (cheap, local sender name), everything international
 * rides Twilio. A business with diaspora customers picks one driver and
 * stops caring which gateway actually fires.
 */
class IntlSmsDriver implements SmsDriver
{
    /**
     * Prefixes Kavenegar handles natively: the saved domestic form (09…)
     * and the country-coded one (+98…, sometimes typed without the plus).
     * The bare "98" is only domestic when a mobile digit (9) follows.
     */
    private const DOMESTIC_PATTERNS = ['09', '+98', '989'];

    public function __construct(
        private readonly KavenegarSmsDriver $domestic,
        private readonly TwilioSmsDriver $international,
    ) {}

    public function send(string $phone, string $message): void
    {
        $digits = preg_replace('/[^\d+]/', '', $phone) ?? $phone;

        foreach (self::DOMESTIC_PATTERNS as $pattern) {
            if (str_starts_with($digits, $pattern)) {
                $this->domestic->send($digits, $message);

                return;
            }
        }

        $this->international->send($digits, $message);
    }
}
