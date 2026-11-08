<?php

namespace App\Services\Sms;

use App\Services\Sms\Contracts\SmsDriver;
use InvalidArgumentException;

/**
 * Resolves the configured SMS driver at runtime. New gateways plug in
 * by implementing SmsDriver and registering themselves here.
 */
class SmsManager
{
    /** @var array<string, class-string<SmsDriver>> */
    private array $drivers = [
        'log' => LogSmsDriver::class,
        'kavenegar' => KavenegarSmsDriver::class,
    ];

    public function driver(?string $name = null): SmsDriver
    {
        $name ??= (string) config('nobat.sms.driver', 'log');

        $class = $this->drivers[$name] ?? null;

        if ($class === null) {
            throw new InvalidArgumentException("SMS driver [{$name}] is not supported.");
        }

        return app($class);
    }

    public function send(string $phone, string $message): void
    {
        $this->driver()->send($phone, $message);
    }

    public function extend(string $name, string $driverClass): void
    {
        $this->drivers[$name] = $driverClass;
    }
}
