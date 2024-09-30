<?php

use App\Enums\TokenAbility;
use App\Http\Controllers\Auth\AuthenticationController;
use App\Http\Controllers\Workspace\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::get('refresh-token', AuthenticationController::class . '@refreshToken')
    ->prefix('auth')
    ->middleware(['auth:sanctum', 'ability:' . TokenAbility::ISSUE_ACCESS_TOKEN->value]);

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthenticationController::class, 'register']);
    Route::post('login', [AuthenticationController::class, 'login']);
    Route::post('logout', [AuthenticationController::class, 'logout'])->middleware(['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value]);
    Route::get('refresh-token', [AuthenticationController::class, 'refreshToken'])->middleware(['auth:sanctum', 'ability:' . TokenAbility::ISSUE_ACCESS_TOKEN->value]);
});

Route::apiResource('workspaces', WorkspaceController::class)
    ->middleware(['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value]);

Route::post('invitations', [App\Http\Controllers\Workspace\InvitationController::class, 'invite'])
    ->middleware(['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value]);

Route::get('accept-invitation/{token}', [App\Http\Controllers\Workspace\InvitationController::class, 'accept'])
    ->middleware(['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value, \App\Http\Middleware\ValidateInvitationToken::class]);

