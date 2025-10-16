<?php

use Illuminate\Support\Facades\Route;
use Modules\Approval\Http\Controllers\ApprovalTypesController;
use Modules\Approval\Http\Controllers\ApprovalRulesController;
use Modules\Approval\Http\Controllers\ApprovalRuleUsersController;

/*
|--------------------------------------------------------------------------
| API Routes for Approval Module
|--------------------------------------------------------------------------
|
| Semua route di sini otomatis masuk ke group "api" middleware,
| sesuai dengan setting RouteServiceProvider modul.
|
*/

Route::middleware('auth:api')->prefix('approvals')->group(function () {
    // Approval Types
    Route::apiResource('types', ApprovalTypesController::class);

    // Approval Rules
    Route::apiResource('rules', ApprovalRulesController::class);

    // Approval Rule Users
    Route::apiResource('rule-users', ApprovalRuleUsersController::class);
});
