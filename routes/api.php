<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SportController;
use App\Http\Controllers\SocialiteController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

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
Route::get('/sub-categories', [CategoryController::class, 'getSubCategories']);

// coupon
Route::get('/coupons/{id}', [CouponController::class, 'show']);

// Product
Route::get('/products', [ProductController::class, 'index']);
Route::get('/featured-products', [ProductController::class, 'featuredProducts']);
Route::get('/products/{id}', [ProductController::class, 'show']);


// Routes cần xác thực
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Phần User
    Route::post('/user/update', [UserController::class, 'update']);
    Route::post('/user/change-password', [UserController::class, 'changePassword']);

    // Chỉ admin mới có quyền
    Route::middleware([AdminMiddleware::class])->group(function () {
        // Routes cho môn thể thao
        Route::post('/sports', [SportController::class, 'store']);
        Route::post('/sports/{id}', [SportController::class, 'update']);
        Route::delete('/sports/{id}', [SportController::class, 'destroy']);

        // Coupon
        Route::get('/coupons', [CouponController::class, 'index']);
        Route::post('/coupons', [CouponController::class, 'store']);
        Route::delete('/coupons/{id}', [CouponController::class, 'destroy']);

        // Product
        Route::post('/products', [ProductController::class, 'store']);
        Route::post('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    });
});