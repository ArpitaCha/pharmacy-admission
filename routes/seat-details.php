<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\wbscte\SeatController;

Route::get('/seat-report', [SeatController::class, 'seat_report']);
Route::get('/generate-seat-master', [SeatController::class, 'generate_seat_master']);
