<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Session\Middleware\StartSession;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\AppController;

// Rate limit login attempts (5 per minute)
Route::middleware('throttle:5,1')->post('/login', [UserController::class, 'login']);
Route::middleware('throttle:5,1')->post('/register', [UserController::class, 'register']);

// Authenticated & Session-enabled routes
Route::middleware(['auth:sanctum',  StartSession::class])->group(function () {
    Route::get('/profile', [UserController::class, 'me']);
    Route::get('/logout', [UserController::class, 'me']);
});
