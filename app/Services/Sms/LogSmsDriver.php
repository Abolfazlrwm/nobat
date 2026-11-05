<?php

namespace App\Services\Sms;

use App\Services\Sms\Contracts\SmsDriver;
use Illuminate\Support\Facades\Log;

/**
 * Writes messages to the application log instead of a real gateway.
 * Perfect for local development and CI pipelines.
 */
class LogSmsDriver implements SmsDriver
{
    public function send(string $phone, string $message): void
    {
        Log::info('SMS sent', ['to' => $phone, 'message' => $message]);
    }
}
