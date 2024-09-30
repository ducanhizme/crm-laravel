<?php

use App\Enums\TokenAbility;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;
Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->name('verification.verify')
    ->middleware('auth:sanctum', 'signed');
Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])
    ->name('verification.send')
    ->middleware(['auth:sanctum', 'throttle:6,1']);

Route::get('refresh-token', AuthController::class . '@refreshToken')
    ->prefix('auth')
    ->middleware(['auth:sanctum', 'ability:' . TokenAbility::ISSUE_ACCESS_TOKEN->value]);

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware(['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value]);
    Route::get('refresh-token', [AuthController::class, 'refreshToken'])->middleware(['auth:sanctum', 'ability:' . TokenAbility::ISSUE_ACCESS_TOKEN->value]);
});

Route::apiResource('workspaces', \App\Http\Controllers\WorkspaceController::class)
    ->middleware(['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value,'verified']);

