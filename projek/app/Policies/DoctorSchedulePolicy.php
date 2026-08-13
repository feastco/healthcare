<?php

namespace App\Policies;

use App\Models\DoctorSchedule;
use App\Models\User;

class DoctorSchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('schedules.view');
    }

    public function view(User $user, DoctorSchedule $schedule): bool
    {
        return $user->hasPermissionTo('schedules.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('schedules.create');
    }

    public function update(User $user, DoctorSchedule $schedule): bool
    {
        return $user->hasPermissionTo('schedules.update');
    }

    public function delete(User $user, DoctorSchedule $schedule): bool
    {
        return $user->hasPermissionTo('schedules.delete');
    }
}
