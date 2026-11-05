<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;

class BusinessPolicy
{
    public function manage(User $user, Business $business): bool
    {
        return $user->isSuperAdmin() || $business->owner_id === $user->id;
    }

    public function administer(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
