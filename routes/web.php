<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\FacilityController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AttendantController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResponsesController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'show']);

    /*
     * Report authoring and editing belongs to students only.
     */
    Route::controller(ReportController::class)->middleware('role:siswa')->group(function () {
        Route::post('/reports/create', 'createReport')->name('reports.create');
        Route::get('/reports/create', 'showCreateReportPage')->name('reports.showCreate');
        Route::get('/reports/{uuid}/edit', 'showEditReportPage')->name('reports.showEdit');
        Route::put('/reports/{uuid}', 'updateReport')->name('reports.update');
    });

    /*
     * Viewing a report is shared by the student who owns it and petugas.
     * Admin has no report access at all.
     */
    Route::get('/reports/{uuid}', [ReportController::class, 'showReportDetailPage'])
        ->middleware('role:siswa,petugas')
        ->name('reports.showDetail');

    Route::patch('/reports/{uuid}/status', [ReportController::class, 'updateStatus'])
        ->middleware('role:petugas')
        ->name('reports.updateStatus');

    Route::controller(ResponsesController::class)->middleware('role:siswa,petugas')->group(function () {
        Route::get('/discussions', 'index')->name('discussions.index');
        Route::get('/reports/{uuid}/discussion', 'show')->name('responses.show');
        Route::post('/reports/{uuid}/discussion', 'store')->name('responses.store');
        Route::patch('/reports/{uuid}/discussion/{responseId}', 'update')->name('responses.update');
    });

    Route::get('/blob/{scrambldToken}', [AttendantController::class, 'serve'])
        ->name('blob.serve')
        ->where('scrambldToken', '[a-zA-Z0-9_-]+');

    Route::controller(SettingsController::class)->group(function () {
        Route::get('/settings', 'index')->name('settings.index');
        Route::patch('/settings/profile-photo', 'updateProfilePhoto')->name('settings.profile-photo.update');
        Route::delete('/settings/profile-photo', 'resetProfilePhoto')->name('settings.profile-photo.reset');
        Route::patch('/settings/password', 'updatePassword')->name('settings.password.update');
    });

    Route::get('/analytics', [AnalyticsController::class, 'index'])
        ->middleware('role:petugas')
        ->name('analytics.index');

    /*
     * Admin manages users, facilities and categories. These routes are the only
     * way to mutate master data, so they are gated behind the admin role.
     */
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except('show');
        Route::resource('facilities', FacilityController::class)->except('show');
        Route::resource('categories', CategoryController::class)->except('show');
    });

    Route::get('/logout', [AuthController::class, 'deauthenticate']);
});

Route::controller(AuthController::class)->middleware('guest')->group(function () {
    Route::get('/login', 'show')->name('login');
    Route::post('/login', 'authenticate');
});

Route::get('/', function () {
    return redirect()->to('/dashboard');
});
