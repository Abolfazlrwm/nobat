<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\User;

class StaffPolicy
{
    public function manage(User $user, Staff $staff): bool
    {
        return $user->isSuperAdmin()
            || $staff->business->owner_id === $user->id;
    }
}
