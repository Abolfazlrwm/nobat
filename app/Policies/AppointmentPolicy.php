<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($appointment->business->owner_id === $user->id) {
            return true;
        }

        if ($appointment->staff->user_id === $user->id) {
            return true;
        }

        return $appointment->user_id === $user->id;
    }

    public function changeStatus(User $user, Appointment $appointment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($appointment->business->owner_id === $user->id) {
            return true;
        }

        return $appointment->staff->user_id === $user->id;
    }
}
