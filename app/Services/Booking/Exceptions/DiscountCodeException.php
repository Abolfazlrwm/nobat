<?php

namespace App\Services\Booking\Exceptions;

use RuntimeException;

/**
 * Thrown when a discount code cannot be honoured for a purchase.
 * The message is customer-facing and already localized.
 */
class DiscountCodeException extends RuntimeException {}
