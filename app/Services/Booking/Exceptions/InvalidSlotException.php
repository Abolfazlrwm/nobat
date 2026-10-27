<?php

namespace App\Services\Booking\Exceptions;

use RuntimeException;

class InvalidSlotException extends RuntimeException
{
    public function __construct(string $message = 'بازه زمانی انتخاب‌شده معتبر نیست.')
    {
        parent::__construct($message);
    }
}
