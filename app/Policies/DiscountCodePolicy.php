<?php

namespace App\Policies;

use App\Models\DiscountCode;
use App\Models\User;

class DiscountCodePolicy
{
    public function manage(User $user, DiscountCode $discountCode): bool
    {
        return $user->isSuperAdmin()
            || $discountCode->business->owner_id === $user->id;
    }
}
