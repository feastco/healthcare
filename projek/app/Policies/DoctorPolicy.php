<?php

namespace App\Policies;

use App\Models\Doctor;
use App\Models\User;

class DoctorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('doctors.view');
    }

    public function view(User $user, Doctor $doctor): bool
    {
        return $user->hasPermissionTo('doctors.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('doctors.create');
    }

    public function update(User $user, Doctor $doctor): bool
    {
        return $user->hasPermissionTo('doctors.update');
    }

    public function delete(User $user, Doctor $doctor): bool
    {
        return $user->hasPermissionTo('doctors.delete');
    }
}
