<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WaitlistEntry;

class WaitlistEntryPolicy
{
    public function manage(User $user, WaitlistEntry $entry): bool
    {
        return $user->isSuperAdmin()
            || $entry->business->owner_id === $user->id;
    }
}
