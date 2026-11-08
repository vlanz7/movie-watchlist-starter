<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\WatchedController;
use App\Http\Controllers\WatchlistController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1'); // maks 10x/menit/IP
    Route::get('me', [AuthController::class, 'me'])->middleware('jwt');
});

Route::get('movies', [MovieController::class, 'index']);
Route::get('movies/{id}', [MovieController::class, 'show'])->whereNumber('id');
Route::post('movies/recommend', [MovieController::class, 'recommend'])->middleware('throttle:10,1'); // hemat kuota Gemini

Route::middleware('jwt')->group(function () {
    Route::get('watchlist', [WatchlistController::class, 'index']);
    Route::post('watchlist', [WatchlistController::class, 'store']);
    Route::delete('watchlist/{movieId}', [WatchlistController::class, 'destroy'])->whereNumber('movieId');

    Route::get('watched', [WatchedController::class, 'index']);
    Route::post('watched', [WatchedController::class, 'store']);
});