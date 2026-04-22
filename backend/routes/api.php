<?php

use App\Http\Controllers\Api\Admin\AnalyticsController;
use App\Http\Controllers\Api\Admin\ModerationController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FlagController;
use App\Http\Controllers\Api\ObservationController;
use App\Http\Controllers\Api\PhotoUploadController;
use App\Http\Controllers\Api\PlantRecordController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::get('/records', [PlantRecordController::class, 'index']);
Route::get('/records/{publicId}', [PlantRecordController::class, 'show']);
Route::get('/profiles/{handle}', [ProfileController::class, 'show']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::patch('/profile', [ProfileController::class, 'update']);

    Route::post('/uploads/photos', [PhotoUploadController::class, 'store']);

    Route::post('/records', [PlantRecordController::class, 'store']);
    Route::post('/records/{publicId}/observations', [ObservationController::class, 'store']);
    Route::post('/flags', [FlagController::class, 'store']);

    Route::prefix('admin/analytics')->group(function (): void {
        Route::get('/summary', [AnalyticsController::class, 'summary']);
        Route::get('/trends', [AnalyticsController::class, 'trends']);
        Route::get('/top-searches', [AnalyticsController::class, 'topSearches']);
    });

    Route::prefix('admin/moderation')->group(function (): void {
        Route::get('/pending', [ModerationController::class, 'pending']);
        Route::post('/records/{publicId}/verify', [ModerationController::class, 'verify']);
        Route::get('/flags', [ModerationController::class, 'flags']);
        Route::post('/flags/{uid}/resolve', [ModerationController::class, 'resolveFlag']);
    });

    Route::prefix('admin/users')->group(function (): void {
        Route::get('/', [UserManagementController::class, 'index']);
        Route::get('/{handle}', [UserManagementController::class, 'show']);
        Route::patch('/{handle}', [UserManagementController::class, 'update']);
        Route::post('/{handle}/ban', [UserManagementController::class, 'ban']);
        Route::delete('/{handle}', [UserManagementController::class, 'destroy']);
    });
});
