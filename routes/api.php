<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\SportController;
use App\Http\Controllers\SocialiteController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Routes đăng nhập Google
Route::get('/auth/google', [SocialiteController::class, 'googleLogin']);
Route::get('/auth/google/callback', [SocialiteController::class, 'googleAuthentication']);

Route::get('/auth/facebook', [SocialiteController::class, 'facebookLogin']);
Route::get('/auth/facebook/callback', [SocialiteController::class, 'facebookAuthentication']);

// Routes không cần xác thực
// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Sport
Route::get('/sports', [SportController::class, 'index']);
Route::get('/sports/{id}', [SportController::class, 'show']);

// Category
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);



// Routes cần xác thực
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Chỉ admin mới có quyền
    Route::middleware([AdminMiddleware::class])->group(function () {
        // Routes cho môn thể thao
        Route::post('/sports', [SportController::class, 'store']);
        Route::post('/sports/{id}', [SportController::class, 'update']);
        Route::delete('/sports/{id}', [SportController::class, 'destroy']);


    });
});