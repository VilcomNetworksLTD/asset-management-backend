<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthandAccessController;


// Public routes
Route::post('/register', [AuthandAccessController::class, 'register']);
Route::post('/verify-otp', [AuthandAccessController::class, 'verifyOtp']);
Route::post('/login', [AuthandAccessController::class, 'login']);
Route::post('/resend-otp', [AuthandAccessController::class, 'resendOtp']);
Route::post('/forgot-password', [AuthandAccessController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthandAccessController::class, 'resetPassword']);

// Protected routes (require valid token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthandAccessController::class, 'logout']);
});
