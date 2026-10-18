<?php

use App\Http\Controllers\AttendantController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::middleware('auth')->group(function() {
    Route::get('/dashboard', [DashboardController::class, 'show']);
    Route::controller(ReportController::class)->group(function() {
        Route::post('/reports/create', 'create');
        Route::get('/reports/create', 'show');
        Route::get('/reports/{uuid}/edit', 'editReport')
            ->name('reports.edit');
        Route::put('/reports/{uuid}', 'updateReport')
            ->name('reports.update');
        Route::get('/reports/{uuid}', 'showReport')
            ->name('reports.show');
    });
    Route::get('/blob/{scrambldToken}', [AttendantController::class, 'serve'])
        ->name('blob.serve')
        ->where('scrambldToken', '[a-zA-Z0-9_-]+');
});

Route::controller(AuthController::class)->middleware('guest')->group(function() {
    Route::get('/login', 'show')->name('login');
    Route::post('/login', 'authenticate');
});

Route::get('/', function() {
    return redirect()->to('/dashboard');
});

