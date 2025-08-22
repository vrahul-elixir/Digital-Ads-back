<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Session\Middleware\StartSession;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ImageUploadController;
use App\Http\Controllers\Api\AppController;

// Rate limit login attempts (5 per minute)
Route::middleware('throttle:5,1')->post('/login', [UserController::class, 'login']);
Route::middleware('throttle:5,1')->post('/register', [UserController::class, 'register']);
Route::middleware('throttle:5,1')->post('/verify-otp', [UserController::class, 'verifyOtp']);

// Admin routes
Route::middleware('throttle:5,1')->post('/admin/login', [AdminController::class, 'adminLogin']);

// Authenticated & Session-enabled routes
Route::middleware(['auth:sanctum',  StartSession::class])->group(function () {
    Route::get('/profile', [UserController::class, 'profile']);
    Route::get('/logout', [UserController::class, 'logout']);

    Route::get('/plan-info', [MainController::class, 'GetPlan']);
    Route::get('/single-plan-detail', [MainController::class, 'GetPlanByName']);
    Route::post('/store-payment', [MainController::class, 'StorePayment']);
    Route::post('/store-business-info', [MainController::class, 'storeBusinessInfo']);

    // Admin authenticated routes
    Route::get('/admin/profile', [AdminController::class, 'adminProfile']);
    Route::get('/admin/logout', [AdminController::class, 'adminLogout']);
    
    // Plan management routes
    Route::post('/plans', [AdminController::class, 'storePlan']);
    Route::get('/plans', [AdminController::class, 'getPlans']);
    Route::get('/plans/{id}', [AdminController::class, 'getPlanById']);
    Route::delete('/plans/{id}', [AdminController::class, 'deletePlan']);

    // Subscription management routes
    Route::get('/subscriptions', [AdminController::class, 'getUserSubscriptions']);
    Route::get('/subscriptions/{id}', [AdminController::class, 'getSubscriptionById']);
    Route::get('/subscriptions-user/{id}', [AdminController::class, 'getLatestSubscriptionByUserId']);
    Route::post('/subscriptions/{id}', [AdminController::class, 'updateSubscription']);

    // Payment management routes
    Route::get('/payments', [AdminController::class, 'getAllPayments']);
    Route::get('/payments/{id}', [AdminController::class, 'getPaymentById']);
    Route::get('/payments/stats', [AdminController::class, 'getPaymentStats']);

    // Customer management routes
    Route::get('/customers', [AdminController::class, 'getCustomerInfo']);
    Route::get('/customers/{id}', [AdminController::class, 'getSingleCustomer']);

    // Campaign management routes
    Route::get('/campaigns', [CampaignController::class, 'index']);
    Route::post('/update-campaigns-media-data/', [CampaignController::class, 'UpdateCampaignsMedia']);
    Route::get('/campaigns/{id}', [CampaignController::class, 'show']);
    Route::post('/campaigns', [CampaignController::class, 'store']);
    Route::put('/campaigns/{id}', [CampaignController::class, 'update']);
    Route::delete('/campaigns/{id}', [CampaignController::class, 'destroy']);
    Route::get('/campaigns/user/{user_id}', [CampaignController::class, 'getUserCampaigns']);

    // Media upload routes
    Route::post('/upload/single', [ImageUploadController::class, 'uploadSingleMedia']);
    Route::post('/upload/multiple', [ImageUploadController::class, 'uploadMultipleMedia']);
    Route::delete('/upload/delete', [ImageUploadController::class, 'deleteMedia']);
});
