<?php

use App\Enums\TokenAbility;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;
Route::prefix('auth')->group(function () {
    Route::post('google/redirect', [GoogleAuthController::class, 'redirect']);
    Route::get('google/callback', [GoogleAuthController::class, 'callback']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware(['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value]);
    Route::get('refresh-token', [AuthController::class, 'refreshToken'])->middleware(['auth:sanctum', 'ability:' . TokenAbility::ISSUE_ACCESS_TOKEN->value]);
});

Route::apiResource('workspaces', WorkspaceController::class)
    ->middleware(['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value]);


