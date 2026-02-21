<?php

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
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('users', UserController::class);

    Route::apiResource('movies', MovieController::class);
    Route::post('episodes', [EpisodeController::class, 'store']);

    Route::post('comments', [CommentController::class, 'store']);

    Route::post('favorites/{movieId}', [FavoriteController::class, 'toggle']);

    Route::post('watch-history', [WatchHistoryController::class, 'store']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{notificationId}/read', [NotificationController::class, 'markAsRead']);
});

// Route::get('/users', [UserController::class, 'index']);
// Route::post('/users', [UserController::class, 'store']);
// Route::get('/users/{id}', [UserController::class, 'show']);
// Route::put('/users/{id}', [UserController::class, 'update']);
// Route::delete('/users/{id}', [UserController::class, 'destroy']);
