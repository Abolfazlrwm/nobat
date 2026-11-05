<?php

namespace App\Services\Booking\Exceptions;

use RuntimeException;

class SlotUnavailableException extends RuntimeException
{
    public function __construct(string $message = 'متأسفانه این بازه زمانی دیگر در دسترس نیست.')
    {
        parent::__construct($message);
    }
}
