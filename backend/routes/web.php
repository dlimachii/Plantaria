<?php

use App\Http\Controllers\Web\AdminDashboardController;
use App\Http\Controllers\Web\AdminSessionController;
use App\Http\Controllers\Web\FlagPanelController;
use App\Http\Controllers\Web\ModerationPanelController;
use App\Http\Controllers\Web\UserPanelController;
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

    Route::get('/flags', [FlagPanelController::class, 'index'])->name('flags.index');
    Route::post('/flags/{uid}', [FlagPanelController::class, 'update'])->name('flags.update');

    Route::get('/users', [UserPanelController::class, 'index'])->name('users.index');
    Route::get('/users/{handle}', [UserPanelController::class, 'show'])->name('users.show');
    Route::post('/users/{handle}', [UserPanelController::class, 'update'])->name('users.update');
});
