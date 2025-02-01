<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-pdf/{bookingId}', [App\Http\Controllers\Admin\BookingTransferController::class, 'testGeneratePDF']);
