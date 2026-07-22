<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
        Route::post('/refresh', [AuthController::class, 'refresh']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/logout-device', [AuthController::class, 'logoutFromDevice']);
            Route::post('/logout-all', [AuthController::class, 'logoutFromAllDevices']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::match(['put', 'post'], '/profile', [AuthController::class, 'updateProfile']);
        });
    });