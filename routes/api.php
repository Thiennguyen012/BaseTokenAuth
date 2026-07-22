<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/locale-test', function () {
    return response()->json([
        'message' => __('messages.welcome'),
        'logout' => __('messages.logout'),
    ]);
});
