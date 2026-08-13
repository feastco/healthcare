<?php

use App\Http\Controllers\Api\V1\AuthController;
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
});
