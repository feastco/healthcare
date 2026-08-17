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

        if ($roles === [] || ! $user->hasAnyRole($roles)) {
            return false;
        }

        if (in_array('Doctor', $roles, true) && ! $this->isDoctorOwner($user, $appointment)) {
            return false;
        }

        return true;
    }

    private function isDoctorOwner(User $user, Appointment $appointment): bool
    {
        return $appointment->doctor()->where('user_id', $user->id)->exists();
    }
}
