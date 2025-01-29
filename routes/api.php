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
use App\Http\Controllers\Admin\ScheduleRuteController;
use App\Http\Controllers\Admin\BookingProcessController;
use App\Http\Controllers\Admin\PaymentsController;
use App\Http\Controllers\Admin\BookingTransferController;
use App\Http\Controllers\Admin\BookingsController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Api\DashboardController;
// Register and Login
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::get('guest/locations/get-name', [LocationsController::class, 'getNameLocations']);
Route::get('guest/classes/get-name', [ClassesController::class, 'getNameList']);
Route::get('guest/schedule-rutes', [ScheduleRuteController::class, 'getNameList']);
Route::get('guest/schedule-rutes/{id}/seat', [ScheduleRuteController::class, 'getSeats']);
Route::post('guest/booking-transfer', [BookingTransferController::class, 'storeGuest']);
Route::get('guest/check-payment/{id}', [BookingsController::class, 'show']);

// Midtrans Callback Routes (No Auth Required)
Route::post('/midtrans/notification', [BookingTransferController::class, 'handlePaymentNotification']);
Route::get('/midtrans/finish', [BookingTransferController::class, 'handlePaymentFinish']);
Route::get('/midtrans/unfinish', [BookingTransferController::class, 'handlePaymentUnfinish']);
Route::get('/midtrans/error', [BookingTransferController::class, 'handlePaymentError']);

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
    Route::get('locations/get-state', [LocationsController::class, 'getLocations']);
    Route::get('locations/get-name', [LocationsController::class, 'getNameLocations']);
    Route::apiResource('locations', LocationsController::class);
    Route::apiResource('facility', FacilitiesController::class);
    Route::apiResource('routes', RoutesController::class);
    Route::apiResource('sdays', SpecialDaysController::class);
    Route::apiResource('schedules', SchedulesController::class);
    Route::apiResource('userrole', UserRoleController::class);
    Route::apiResource('role-permissions', RoleManagementController::class);
    Route::apiResource('assign-roles', AssignRoleController::class);
    Route::apiResource('menus', \App\Http\Controllers\Admin\Master\MenusController::class);
    Route::get('schedule-master', [App\Http\Controllers\Admin\Master\ScheduleMasterController::class, 'index']);
    Route::get('schedule-master-update', [App\Http\Controllers\Admin\Master\ScheduleMasterController::class, 'update']);
    Route::get('schedule-rutes/{id}/seat', [ScheduleRuteController::class, 'getSeats']);
    Route::get('schedule-rute/manifest/{scheduleId}', [ScheduleRuteController::class, 'getManifest']);
    Route::apiResource('schedule-rutes', ScheduleRuteController::class);
    Route::post('/booking-proses', [BookingProcessController::class, 'store']);
    Route::post('/booking-transfer', [BookingTransferController::class, 'store']);
    Route::post('payment', [PaymentsController::class, 'createTransaction']);
    Route::get('check-payment', [BookingsController::class, 'index']);
    Route::get('check-payment/{id}', [BookingsController::class, 'show']);
    Route::get('check-payment-status/{paymentId}', [BookingsController::class, 'checkPaymentStatus']);
    Route::get('bookings/three-days', [BookingsController::class, 'getBookingsThreeDays']);
    Route::get('bookings/one-day', [BookingsController::class, 'getBookingsOneDay']);
    Route::get('bookings/get-class', [BookingsController::class, 'getBookingsByClass']);
    Route::apiResource('orders', OrderController::class);
    Route::post('orders/{id}/cancel', [OrderController::class, 'cancelBooking']);
    Route::get('utility-bbm/create-data', [\App\Http\Controllers\Admin\Master\UtilityBBMController::class, 'getDataCreate']);
    Route::apiResource('utility-bbm', \App\Http\Controllers\Admin\Master\UtilityBBMController::class);
    Route::get('dashboard/operasional', [\App\Http\Controllers\Admin\Master\DashboardController::class, 'getAllDashboard']);
    Route::get('/buses-routes', [App\Http\Controllers\Admin\Master\ScheduleMasterController::class, 'getBusesAndRoutes']);
    
    // Refund Routes
    Route::apiResource('refunds', \App\Http\Controllers\Admin\RefundController::class);
    
    // Reschedule Routes
    Route::apiResource('reschedules', \App\Http\Controllers\Admin\RescheduleController::class);
    
    // Laporan Routes
    Route::get('laporan/pendapatan', [\App\Http\Controllers\Admin\Laporan\PendapatanController::class, 'index']);
    Route::get('laporan/pendapatan/download', [\App\Http\Controllers\Admin\Laporan\PendapatanController::class, 'download']);

    Route::get('laporan/refund', [\App\Http\Controllers\Admin\Laporan\RefundController::class, 'index']);
    Route::get('laporan/refund/download', [\App\Http\Controllers\Admin\Laporan\RefundController::class, 'download']);

    Route::get('laporan/pengeluaran', [\App\Http\Controllers\Admin\Laporan\PengeluaranController::class, 'index']);
    Route::get('laporan/pengeluaran/download', [\App\Http\Controllers\Admin\Laporan\PengeluaranController::class, 'download']);

    Route::get('laporan/bersih', [\App\Http\Controllers\Admin\Laporan\BersihController::class, 'index']);
    Route::get('laporan/bersih/download', [\App\Http\Controllers\Admin\Laporan\BersihController::class, 'download']);

    Route::get('master/users/cek-username', [\App\Http\Controllers\Admin\Master\UsersController::class, 'cekUsername']);

    Route::get('route-groups/list/routes', [\App\Http\Controllers\Admin\Master\RouteGroupController::class, 'getRouteList']);
    Route::apiResource('route-groups', \App\Http\Controllers\Admin\Master\RouteGroupController::class);
});
   
});
