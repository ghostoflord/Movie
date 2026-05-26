<?php

use App\Http\Controllers\VnpayController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// VNPay redirect (không có prefix /api): http://localhost:8080/vnpay-return
Route::get('/vnpay-return', [VnpayController::class, 'return']);
