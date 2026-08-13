<?php

namespace App\Policies;

use App\Actions\TransitionQueueAction;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('appointments.view');
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->hasPermissionTo('appointments.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('appointments.create');
    }

    public function updateStatus(User $user, Appointment $appointment, AppointmentStatus $target): bool
    {
        $roles = TransitionQueueAction::rolesFor($appointment->status, $target);

        return $roles !== [] && $user->hasAnyRole($roles);
    }
}
