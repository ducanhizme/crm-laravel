<?php

use App\Enums\TokenAbility;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Workspace\WorkspaceController;
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

Route::apiResource('workspaces', WorkspaceController::class)
    ->middleware(['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value]);
Route::post('workspaces/remove-user', [WorkspaceController::class, 'removeUser'])
    ->middleware(['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value, \App\Http\Middleware\CurrentWorkspaceMiddleware::class]);

Route::post('workspaces/leave-workspaces', [WorkspaceController::class, 'leaveWorkspaces'])
    ->middleware(['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value, \App\Http\Middleware\CurrentWorkspaceMiddleware::class]);

Route::post('invitations', [App\Http\Controllers\Workspace\InvitationController::class, 'invite'])
    ->middleware(['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value, \App\Http\Middleware\CurrentWorkspaceMiddleware::class]);

Route::get('accept-invitation/{token}', [App\Http\Controllers\Workspace\InvitationController::class, 'accept'])
    ->middleware(['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value, \App\Http\Middleware\ValidateInvitationToken::class]);

Route::post('invitations', [App\Http\Controllers\Workspace\InvitationController::class, 'invite'])
    ->middleware(['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value]);

Route::get('accept-invitation/{token}', [App\Http\Controllers\Workspace\InvitationController::class, 'accept'])
    ->middleware(['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value, \App\Http\Middleware\ValidateInvitationToken::class]);
Route::apiResource('tasks', \App\Http\Controllers\Task\TaskController::class)
    ->middleware(['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value, \App\Http\Middleware\CurrentWorkspaceMiddleware::class]);
Route::apiResource('statuses', \App\Http\Controllers\Task\StatusController::class)
    ->middleware(['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value, \App\Http\Middleware\CurrentWorkspaceMiddleware::class]);

//Route::middleware(['auth:sanctum'])->group(function () {
//    Route::middleware(['throttle:6.1'])->group(function () {
//        Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
//        Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])->name('verification.send');
//    });
//    Route::prefix('auth')->group(function () {
//        Route::post('logout', [AuthController::class, 'logout'])->middleware(['ability:' . TokenAbility::ACCESS_API->value]);
//        Route::get('refresh-token', AuthController::class . '@refreshToken')->middleware(['ability:' . TokenAbility::ISSUE_ACCESS_TOKEN->value]);
//    });
//});
//
//Route::prefix('auth')->group(function () {
//    Route::post('register', [AuthController::class, 'register']);
//    Route::post('login', [AuthController::class, 'login']);
//});
