<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::controller(DashboardController::class)->middleware('auth')->group(function() {
    Route::get('/', 'show');
});

Route::controller(AuthController::class)->middleware('guest')->group(function() {
    Route::get('/login', 'show')->name('login');
    Route::post('/login', 'authenticate');
});

