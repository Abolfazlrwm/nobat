<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkingHour;

class WorkingHourPolicy
{
    public function manage(User $user, WorkingHour $workingHour): bool
    {
        return $user->isSuperAdmin()
            || $workingHour->business->owner_id === $user->id;
    }
}
