<?php

declare(strict_types=1);

use App\Core\Roles\Http\Controllers\PermissionController;
use App\Core\Roles\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

/**
 * Core role and permission management API routes.
 *
 * Registered under api/v1 by RoleServiceProvider, following the
 * same per-module routing convention as extensions and other
 * Core modules (Auth, Users).
 */
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/permissions', [PermissionController::class, 'index']);

    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{role}', [RoleController::class, 'update']);
    Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
    Route::post('/roles/assign', [RoleController::class, 'assign']);
});
