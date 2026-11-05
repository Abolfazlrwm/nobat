<?php

namespace App\Services\Sms\Contracts;

interface SmsDriver
{
    /**
     * Deliver a text message to the given mobile number.
     */
    public function send(string $phone, string $message): void;
}
