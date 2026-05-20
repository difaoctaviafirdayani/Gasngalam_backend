<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DestinationController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\BusinessClaimController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\NotificationController;

/*
|--------------------------------------------------------------------------
| GasNgalam API Routes
|--------------------------------------------------------------------------
*/

// ─── AUTH (public) ───────────────────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// ─── DESTINATIONS (public) ───────────────────────────────────────────────────
Route::get('/destinations',          [DestinationController::class, 'index']);
Route::get('/destinations/{id}',     [DestinationController::class, 'show']);
Route::get('/categories',            [DestinationController::class, 'categories']);

// ─── REVIEWS (public baca) ───────────────────────────────────────────────────
Route::get('/destinations/{id}/reviews', [ReviewController::class, 'index']);

// ─── PROTECTED (login required) ──────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout',       [AuthController::class, 'logout']);
    Route::get('/me',            [AuthController::class, 'me']);
    Route::post('/user/profile', [AuthController::class, 'updateProfile']);

    // User data
    Route::get('/user/reviews', [ReviewController::class, 'myReviews']);
    Route::get('/user/claims',  [BusinessClaimController::class, 'myKlaims']);

    // Favorites
    Route::get('/favorites',         [FavoriteController::class, 'index']);
    Route::get('/favorites/ids',     [FavoriteController::class, 'ids']);
    Route::post('/favorites/{id}',   [FavoriteController::class, 'store']);
    Route::delete('/favorites/{id}', [FavoriteController::class, 'destroy']);

    // Reviews — tulis & laporkan
    Route::post('/destinations/{id}/reviews', [ReviewController::class, 'store']);
    Route::post('/reviews/{id}/report',       [ReviewController::class, 'report']);

    // Business Claims
    Route::post('/claims', [BusinessClaimController::class, 'store']);

    // Notifications
    Route::get('/notifications',              [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/{id}/read',  [NotificationController::class, 'markRead']);
    Route::patch('/notifications/read-all',   [NotificationController::class, 'markAllRead']);

    // ─── ADMIN ONLY ────────────────────────────────────────────────────────
    Route::middleware('admin')->prefix('admin')->group(function () {

        // Dashboard stats
        Route::get('/stats',   [AdminController::class, 'stats']);

        // Users
        Route::get('/users',         [AdminController::class, 'users']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);

        // Destinations CRUD
        Route::post('/destinations',                        [DestinationController::class, 'store']);
        Route::post('/destinations/{id}',                   [DestinationController::class, 'update']);
        Route::delete('/destinations/{id}',                 [DestinationController::class, 'destroy']);
        Route::delete('/destinations/{id}/photos/{photoId}',[DestinationController::class, 'deletePhoto']);

        // Claims management
        Route::get('/claims',        [BusinessClaimController::class, 'index']);
        Route::patch('/claims/{id}', [BusinessClaimController::class, 'update']);

        // Reviews management
        Route::get('/reviews',               [AdminController::class, 'reviews']);
        Route::delete('/reviews/{id}',       [ReviewController::class, 'destroy']);
        Route::patch('/reviews/{id}/report', [AdminController::class, 'toggleReport']);
    });
});