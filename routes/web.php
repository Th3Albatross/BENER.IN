<?php

use App\Http\Controllers\AttendantController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Storage;

Route::middleware('auth')->group(function() {
    Route::get('/dashboard', [DashboardController::class, 'show']);
    Route::controller(ReportController::class)->group(function() {
        Route::post('/reports/create', 'create');
        Route::get('/reports/create', 'show');
        Route::get('/reports/{uuid}', 'showReport');
        Route::get('/reports/{uuid}/{filename}', 'serveImage')->where('filename', '[^/]+');
    });
    Route::get('/images/{scrambldToken}', [AttendantController::class, 'serveImage']);
});

Route::controller(AuthController::class)->middleware('guest')->group(function() {
    Route::get('/login', 'show')->name('login');
    Route::post('/login', 'authenticate');
});

Route::get('/', function() {
    return redirect()->to('/dashboard');
});

// testing purpose, pls delete
Route::get('/test', [AttendantController::class, 'test']);
