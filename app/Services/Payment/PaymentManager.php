<?php

namespace App\Services\Payment;

use App\Services\Payment\Contracts\PaymentGateway;
use InvalidArgumentException;

/**
 * Resolves the configured payment gateway at runtime. New gateways plug
 * in by implementing PaymentGateway and registering themselves here,
 * mirroring the SMS driver pattern.
 */
class PaymentManager
{
    /** @var array<string, class-string<PaymentGateway>> */
    private array $drivers = [
        'log' => LogPaymentGateway::class,
        'zarinpal' => ZarinPalGateway::class,
    ];

    public function driver(?string $name = null): PaymentGateway
    {
        $name ??= (string) config('nobat.payment.driver', 'log');

        $class = $this->drivers[$name] ?? null;

        if ($class === null) {
            throw new InvalidArgumentException("Payment driver [{$name}] is not supported.");
        }

        return app($class);
    }

    public function extend(string $name, string $driverClass): void
    {
        $this->drivers[$name] = $driverClass;
    }
}
