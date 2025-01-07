<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Admin\Master\UsersController;
use App\Http\Controllers\Admin\Master\ClassesController;
use App\Http\Controllers\Admin\Master\BusesController;
use App\Http\Controllers\Admin\Master\LocationsController;
use App\Http\Controllers\Admin\Master\RolesController;
use App\Http\Controllers\Admin\Master\RoutesController;
use App\Http\Controllers\Admin\Master\SpecialDaysController;
use App\Http\Controllers\Admin\Master\FacilitiesController;
use App\Http\Controllers\Admin\SchedulesController;
use App\Http\Controllers\Admin\Master\UserRoleController;
use App\Http\Controllers\Admin\Master\RoleManagementController;
use App\Http\Controllers\Admin\Master\AssignRoleController;

// Register and Login
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    // Logout
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('user', [AuthController::class, 'user']);

    // Users CRUD operations
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::apiResource('users', UsersController::class);
        Route::apiResource('roles', RolesController::class);
        Route::apiResource('classes', ClassesController::class);
        Route::apiResource('busses', BusesController::class);
        Route::apiResource('specialdays', SpecialDaysController::class);
        Route::apiResource('locations', LocationsController::class);
        Route::apiResource('facility', FacilitiesController::class);
        Route::apiResource('routes', RoutesController::class);
        Route::apiResource('sdays', SpecialDaysController::class);
        Route::apiResource('schedules', SchedulesController::class);
        Route::apiResource('userrole', UserRoleController::class);
        Route::apiResource('role-permissions', RoleManagementController::class);
        Route::apiResource('assign-roles', AssignRoleController::class);
        Route::apiResource('menus', \App\Http\Controllers\Admin\Master\MenusController::class);
    });
});
