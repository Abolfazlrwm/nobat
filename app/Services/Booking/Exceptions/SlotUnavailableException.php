<?php

namespace App\Services\Booking\Exceptions;

use RuntimeException;

class SlotUnavailableException extends RuntimeException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? __('bookings.slot_unavailable'));
    }
}
