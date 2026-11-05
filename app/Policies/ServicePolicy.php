<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function manage(User $user, Service $service): bool
    {
        return $user->isSuperAdmin()
            || $service->business->owner_id === $user->id;
    }
}
