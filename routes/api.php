<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DestinationController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\BusinessClaimController;
use App\Http\Controllers\Api\AdminController;

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

// ─── REVIEWS (public — baca, tulis butuh login) ──────────────────────────────
Route::get('/destinations/{id}/reviews', [ReviewController::class, 'index']);

// ─── PROTECTED (login required) ──────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Favorites
    Route::get('/favorites',              [FavoriteController::class, 'index']);
    Route::get('/favorites/ids',          [FavoriteController::class, 'ids']);
    Route::post('/favorites/{id}',        [FavoriteController::class, 'store']);
    Route::delete('/favorites/{id}',      [FavoriteController::class, 'destroy']);

    // Reviews (write)
    Route::post('/destinations/{id}/reviews', [ReviewController::class, 'store']);

    // Business Claims
    Route::post('/claims', [BusinessClaimController::class, 'store']);

    // ─── ADMIN ONLY ────────────────────────────────────────────────────────
    Route::middleware('admin')->prefix('admin')->group(function () {

        // Dashboard stats
        Route::get('/stats',   [AdminController::class, 'stats']);

        // Users
        Route::get('/users',         [AdminController::class, 'users']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);

        // Destinations CRUD
        Route::post('/destinations',       [DestinationController::class, 'store']);
        Route::put('/destinations/{id}',   [DestinationController::class, 'update']);
        Route::delete('/destinations/{id}',[DestinationController::class, 'destroy']);

        // Claims management
        Route::get('/claims',         [BusinessClaimController::class, 'index']);
        Route::patch('/claims/{id}',  [BusinessClaimController::class, 'update']);

        // Reviews management
        Route::get('/reviews',                    [AdminController::class, 'reviews']);
        Route::delete('/reviews/{id}',            [ReviewController::class, 'destroy']);
        Route::patch('/reviews/{id}/report',      [AdminController::class, 'toggleReport']);
    });
});
