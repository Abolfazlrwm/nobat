<?php

namespace App\Services\Booking\Exceptions;

use RuntimeException;

class InvalidSlotException extends RuntimeException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? __('bookings.slot_invalid'));
    }
}
