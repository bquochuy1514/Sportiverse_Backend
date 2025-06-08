<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SportController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController; 
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\ResetPasswordController;
use App\Http\Controllers\SocialiteController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;


Route::get('/test', [ProductController::class, 'test']);

// Routes đăng nhập Google
Route::get('/auth/google', [SocialiteController::class, 'googleLogin']);
Route::get('/auth/google/callback', [SocialiteController::class, 'googleAuthentication']);

Route::get('/auth/facebook', [SocialiteController::class, 'facebookLogin']);
Route::get('/auth/facebook/callback', [SocialiteController::class, 'facebookAuthentication']);

// Routes không cần xác thực
// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
// Reset Password
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);;
Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.reset');

// Sport
Route::get('/sports', [SportController::class, 'index']);
Route::get('/sports/{id}', [SportController::class, 'show']);

// Category
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/sub-categories', [CategoryController::class, 'getSubCategories']);


// Product
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products-sale', [ProductController::class, 'index2']);
Route::get('/featured-products', [ProductController::class, 'featuredProducts']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/products-categories/{category_slug}', [ProductController::class, 'getProductsThroughSportSlug']);


// Routes cần xác thực
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Phần User
    Route::post('/user/update', [UserController::class, 'update']);
    Route::post('/user/change-password', [UserController::class, 'changePassword']);

    // Phần Cart
    Route::post('/cart/add', [CartController::class, 'store']);
    Route::get('/my-cart', [CartController::class, 'show']);
    Route::delete('/cart/remove/{id}', [CartController::class, 'destroy']);
    Route::put('/cart/update/{id}', [CartController::class, 'update']);
    Route::get('/cart/count',  [CartController::class, 'countItems']);

    // Order routes
    Route::post('/orders', [OrderController::class, 'placeOrder']);
    Route::get('/orders', [OrderController::class, 'getUserOrders']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::put('/orders/{id}/cancel', [OrderController::class, 'cancel']);

    // Coupon
    Route::post('/coupons/apply', [CouponController::class, 'applyCoupon']);
    Route::get('/coupons/{id}', [CouponController::class, 'show']);
    
    // Chỉ admin mới có quyền
    Route::middleware([AdminMiddleware::class])->group(function () {
        // Routes cho người dùng
        Route::get('/users', [UserController::class, 'index']);

        // Routes cho môn thể thao
        Route::post('/sports', [SportController::class, 'store']);
        Route::post('/sports/{id}', [SportController::class, 'update']);
        Route::delete('/sports/{id}', [SportController::class, 'destroy']);

        // Coupon admin routes
        Route::get('/coupons', [CouponController::class, 'index']);
        Route::post('/coupons', [CouponController::class, 'store']);
        Route::delete('/coupons/{id}', [CouponController::class, 'destroy']);

        // Product
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    });
});