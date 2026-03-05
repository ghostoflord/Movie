<?php

use App\Http\Controllers\Admin\CrawlController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\EpisodeController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WatchHistoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verify'])->name('verification.verify');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('users', UserController::class);

    Route::apiResource('movies', MovieController::class);

    Route::post('comments', [CommentController::class, 'store']);

    Route::post('favorites/{movieId}', [FavoriteController::class, 'toggle']);

    Route::post('watch-history', [WatchHistoryController::class, 'store']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{notificationId}/read', [NotificationController::class, 'markAsRead']);

    Route::post('/admin/crawl-movies', [CrawlController::class, 'start']);
    Route::get('/admin/crawl-status', [CrawlController::class, 'status']);
    Route::post('/admin/crawl/category', [CrawlController::class, 'crawlCategory']);

    Route::get('/episodes', [EpisodeController::class, 'index']);
    Route::post('/episodes', [EpisodeController::class, 'store']);
    Route::get('/episodes/{id}', [EpisodeController::class, 'show']);
    Route::put('/episodes/{id}', [EpisodeController::class, 'update']);
    Route::delete('/episodes/{id}', [EpisodeController::class, 'destroy']);
    Route::get('/movies/{movieId}/episodes', [EpisodeController::class, 'getByMovie']);
});
