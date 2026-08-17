<?php

use App\Http\Controllers\Web\AppointmentController;
use App\Http\Controllers\Web\AuditLogController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DepartmentController;
use App\Http\Controllers\Web\DoctorController;
use App\Http\Controllers\Web\InvoiceController;
use App\Http\Controllers\Web\InvoicePaymentController;
use App\Http\Controllers\Web\MyQueueController;
use App\Http\Controllers\Web\PatientController;
use App\Http\Controllers\Web\PermissionController;
use App\Http\Controllers\Web\RoleController;
use App\Http\Controllers\Web\ScheduleController;
use App\Http\Controllers\Web\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/home', function () {
        return view('home', ['title' => 'Home']);
    })->name('home');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('role:Super Admin|IT/Admin')
        ->name('dashboard');

    Route::prefix('master-data')
        ->name('patients.')
        ->middleware('role:Super Admin|Registration Staff')
        ->group(function () {
            Route::get('/patients', [PatientController::class, 'index'])->name('index');
            Route::get('/patients/create', [PatientController::class, 'create'])->name('create');
            Route::post('/patients', [PatientController::class, 'store'])->name('store');
            Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('show');
            Route::get('/patients/{patient}/edit', [PatientController::class, 'edit'])->name('edit');
            Route::put('/patients/{patient}', [PatientController::class, 'update'])->name('update');
        });

    Route::prefix('master-data')
        ->name('departments.')
        ->middleware('role:Super Admin|Registration Staff')
        ->group(function () {
            Route::get('/departments', [DepartmentController::class, 'index'])->name('index');
            Route::get('/departments/create', [DepartmentController::class, 'create'])->name('create');
            Route::post('/departments', [DepartmentController::class, 'store'])->name('store');
            Route::get('/departments/{department}', [DepartmentController::class, 'show'])->name('show');
            Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])->name('edit');
            Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('update');
            Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('destroy');
        });

    Route::prefix('master-data')
        ->name('doctors.')
        ->middleware('role:Super Admin|Registration Staff')
        ->group(function () {
            Route::get('/doctors', [DoctorController::class, 'index'])->name('index');
            Route::get('/doctors/create', [DoctorController::class, 'create'])->name('create');
            Route::post('/doctors', [DoctorController::class, 'store'])->name('store');
            Route::get('/doctors/{doctor}', [DoctorController::class, 'show'])->name('show');
            Route::get('/doctors/{doctor}/edit', [DoctorController::class, 'edit'])->name('edit');
            Route::put('/doctors/{doctor}', [DoctorController::class, 'update'])->name('update');
            Route::delete('/doctors/{doctor}', [DoctorController::class, 'destroy'])->name('destroy');
        });

    Route::prefix('master-data')
        ->name('schedules.')
        ->middleware('role:Super Admin|Registration Staff')
        ->group(function () {
            Route::get('/schedules', [ScheduleController::class, 'index'])->name('index');
            Route::get('/schedules/create', [ScheduleController::class, 'create'])->name('create');
            Route::post('/schedules', [ScheduleController::class, 'store'])->name('store');
            Route::get('/schedules/{schedule}/edit', [ScheduleController::class, 'edit'])->name('edit');
            Route::put('/schedules/{schedule}', [ScheduleController::class, 'update'])->name('update');
            Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('destroy');
        });

    Route::prefix('operations')
        ->name('appointments.')
        ->middleware('role:Super Admin|Registration Staff')
        ->group(function () {
            Route::get('/appointments', [AppointmentController::class, 'index'])->name('index');
            Route::get('/appointments/create', [AppointmentController::class, 'create'])->name('create');
            Route::post('/appointments', [AppointmentController::class, 'store'])->name('store');
            Route::get('/appointments/{appointment}', [AppointmentController::class, 'show'])->name('show');
        });

    Route::prefix('operations')
        ->name('my-queue.')
        ->middleware('role:Super Admin|Doctor')
        ->group(function () {
            Route::get('/my-queue', [MyQueueController::class, 'index'])->name('index');
            Route::post('/my-queue/{appointment}/status', [MyQueueController::class, 'updateStatus'])->name('status');
        });

    Route::prefix('operations')
        ->name('invoices.')
        ->middleware('role:Super Admin|Cashier')
        ->group(function () {
            Route::get('/invoices', [InvoiceController::class, 'index'])->name('index');
            Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('show');
            Route::post('/invoices/{invoice}/payments', [InvoicePaymentController::class, 'store'])->name('payments.store');
        });

    Route::prefix('monitoring')
        ->name('audit-logs.')
        ->middleware('role:IT/Admin')
        ->group(function () {
            Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('index');
            Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('show');
        });

    Route::prefix('administration')
        ->name('users.')
        ->middleware('role:Super Admin')
        ->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('index');
            Route::get('/users/create', [UserController::class, 'create'])->name('create');
            Route::post('/users', [UserController::class, 'store'])->name('store');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('update');
        });

    Route::prefix('administration')
        ->name('roles.')
        ->middleware('role:Super Admin')
        ->group(function () {
            Route::get('/roles', [RoleController::class, 'index'])->name('index');
            Route::get('/roles/create', [RoleController::class, 'create'])->name('create');
            Route::post('/roles', [RoleController::class, 'store'])->name('store');
            Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('edit');
            Route::put('/roles/{role}', [RoleController::class, 'update'])->name('update');
            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('destroy');
        });

    Route::prefix('administration')
        ->name('permissions.')
        ->middleware('role:Super Admin')
        ->group(function () {
            Route::get('/permissions', [PermissionController::class, 'index'])->name('index');
            Route::get('/permissions/{role}/edit', [PermissionController::class, 'edit'])->name('edit');
            Route::put('/permissions/{role}', [PermissionController::class, 'update'])->name('update');
        });
});
