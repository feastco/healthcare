<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\User;
use App\Policies\AppointmentPolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\DoctorPolicy;
use App\Policies\DoctorSchedulePolicy;
use App\Policies\PatientPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(Patient::class, PatientPolicy::class);
        Gate::policy(Doctor::class, DoctorPolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(DoctorSchedule::class, DoctorSchedulePolicy::class);
        Gate::policy(Appointment::class, AppointmentPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
    }
}
