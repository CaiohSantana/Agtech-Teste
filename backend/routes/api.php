<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

Route::get('/machines', [MachineController::class, 'index']);
Route::get('/clients', [ClientController::class, 'index']);
Route::post('/reservations', [ReservationController::class, 'store']);
Route::get('/reservations', [ReservationController::class, 'index']);
Route::patch(
    '/reservations/{reservation}/status',
    [ReservationController::class, 'updateStatus']
);
