<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Admin\Master\UsersController;
use App\Http\Controllers\Admin\Master\ClassesController;
use App\Http\Controllers\Admin\Master\BusesController;
use App\Http\Controllers\Admin\Master\LocationsController;
use App\Http\Controllers\Admin\Master\RoutesController;
use App\Http\Controllers\Admin\Master\SpecialDaysController;
use App\Http\Controllers\Admin\SchedulesController;

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
        Route::apiResource('classes', ClassesController::class);
        Route::apiResource('busses', BusesController::class);
        Route::apiResource('locations', LocationsController::class);
        Route::apiResource('routes', RoutesController::class);
        Route::apiResource('sdays', SpecialDaysController::class);
        Route::apiResource('schedules', SchedulesController::class);
    });
});
