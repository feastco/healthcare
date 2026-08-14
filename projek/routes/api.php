<?php

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AuditController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\DoctorController;
use App\Http\Controllers\Api\V1\DoctorScheduleController;
use App\Http\Controllers\Api\V1\InvoicePaymentController;
use App\Http\Controllers\Api\V1\PatientController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::middleware('permission:users.view,sanctum')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
    });

    Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.create,sanctum');
    Route::put('/users/{id}', [UserController::class, 'update'])->middleware('permission:users.update,sanctum');

    Route::middleware('permission:roles.view,sanctum')->group(function () {
        Route::get('/roles', [RoleController::class, 'index']);
        Route::get('/roles/{id}', [RoleController::class, 'show']);
    });

    Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:roles.create,sanctum');
    Route::put('/roles/{id}', [RoleController::class, 'update'])->middleware('permission:roles.update,sanctum');
    Route::delete('/roles/{id}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete,sanctum');

    Route::post('/roles/{roleId}/permissions', [RoleController::class, 'grantPermissions'])
        ->middleware('permission:roles.assign-permissions,sanctum');
    Route::delete('/roles/{roleId}/permissions/{permissionId}', [RoleController::class, 'revokePermission'])
        ->middleware('permission:roles.revoke-permissions,sanctum');

    Route::get('/permissions', [PermissionController::class, 'index'])->middleware('permission:permissions.view,sanctum');

    Route::middleware('permission:patients.view,sanctum')->group(function () {
        Route::get('/patients', [PatientController::class, 'index']);
        Route::get('/patients/{id}', [PatientController::class, 'show']);
    });

    Route::post('/patients', [PatientController::class, 'store'])->middleware('permission:patients.create,sanctum');
    Route::put('/patients/{id}', [PatientController::class, 'update'])->middleware('permission:patients.update,sanctum');

    Route::middleware('permission:doctors.view,sanctum')->group(function () {
        Route::get('/doctors', [DoctorController::class, 'index']);
        Route::get('/doctors/{id}', [DoctorController::class, 'show']);
    });

    Route::post('/doctors', [DoctorController::class, 'store'])->middleware('permission:doctors.create,sanctum');
    Route::put('/doctors/{id}', [DoctorController::class, 'update'])->middleware('permission:doctors.update,sanctum');
    Route::delete('/doctors/{id}', [DoctorController::class, 'destroy'])->middleware('permission:doctors.delete,sanctum');

    Route::middleware('permission:departments.view,sanctum')->group(function () {
        Route::get('/departments', [DepartmentController::class, 'index']);
        Route::get('/departments/{id}', [DepartmentController::class, 'show']);
    });

    Route::post('/departments', [DepartmentController::class, 'store'])->middleware('permission:departments.create,sanctum');
    Route::put('/departments/{id}', [DepartmentController::class, 'update'])->middleware('permission:departments.update,sanctum');
    Route::delete('/departments/{id}', [DepartmentController::class, 'destroy'])->middleware('permission:departments.delete,sanctum');

    Route::get('/doctors/{doctorId}/schedules', [DoctorScheduleController::class, 'index'])->middleware('permission:schedules.view,sanctum');
    Route::post('/doctors/{doctorId}/schedules', [DoctorScheduleController::class, 'store'])->middleware('permission:schedules.create,sanctum');
    Route::put('/schedules/{id}', [DoctorScheduleController::class, 'update'])->middleware('permission:schedules.update,sanctum');
    Route::delete('/schedules/{id}', [DoctorScheduleController::class, 'destroy'])->middleware('permission:schedules.delete,sanctum');

    Route::middleware('permission:appointments.view,sanctum')->group(function () {
        Route::get('/appointments', [AppointmentController::class, 'index']);
        Route::get('/appointments/{id}', [AppointmentController::class, 'show']);
    });

    Route::post('/appointments', [AppointmentController::class, 'store'])->middleware('permission:appointments.create,sanctum');
    Route::patch('/appointments/{id}/status', [AppointmentController::class, 'updateStatus']);

    Route::get('/invoices/{invoiceId}/payments', [InvoicePaymentController::class, 'index']);
    Route::post('/invoices/{invoiceId}/payments', [InvoicePaymentController::class, 'store']);

    Route::get('/audits', [AuditController::class, 'index']);
});
