<?php

use App\Http\Controllers\Web\AdminDashboardController;
use App\Http\Controllers\Web\AdminSessionController;
use App\Http\Controllers\Web\ModerationPanelController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', fn () => redirect()->route('admin.login'))->name('login');

Route::middleware('guest')->group(function (): void {
    Route::get('/admin/login', [AdminSessionController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminSessionController::class, 'store'])->name('admin.login.store');
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function (): void {
    Route::post('/logout', [AdminSessionController::class, 'destroy'])->name('logout');
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    Route::prefix('moderation')->name('moderation.')->group(function (): void {
        Route::get('/pending', [ModerationPanelController::class, 'pending'])->name('pending');
        Route::get('/records/{publicId}', [ModerationPanelController::class, 'show'])->name('show');
        Route::post('/records/{publicId}/verify', [ModerationPanelController::class, 'verify'])->name('verify');
        Route::post('/records/{publicId}/reject', [ModerationPanelController::class, 'reject'])->name('reject');
    });
});
