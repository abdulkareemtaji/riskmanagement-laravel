<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RiskController;
use App\Http\Controllers\Api\MitigationActionController;
use App\Http\Controllers\Api\RiskAssessmentController;
use App\Http\Controllers\Api\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// API Version 1
Route::prefix('v1')->group(function () {
    
    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Protected routes
    Route::middleware(['auth:api', 'throttle:60,1'])->group(function () {
        
        // Risk Management routes
        Route::apiResource('risks', RiskController::class);
        Route::post('risks/{risk}/assessments', [RiskAssessmentController::class, 'store']);
        Route::get('risks/{risk}/assessments', [RiskAssessmentController::class, 'index']);
        
        // Mitigation Actions routes
        Route::apiResource('mitigation-actions', MitigationActionController::class);
        Route::get('risks/{risk}/mitigation-actions', [MitigationActionController::class, 'getByRisk']);
        
        // Risk Assessments routes
        Route::apiResource('risk-assessments', RiskAssessmentController::class)->except(['store']);
        
        // Reporting and Analytics routes
        Route::prefix('reports')->group(function () {
            Route::get('dashboard', [ReportController::class, 'dashboard']);
            Route::get('risk-summary', [ReportController::class, 'riskSummary']);
            Route::get('risk-matrix', [ReportController::class, 'riskMatrix']);
            Route::get('risks-by-category', [ReportController::class, 'risksByCategory']);
            Route::get('risks-by-department', [ReportController::class, 'risksByDepartment']);
            Route::get('overdue-actions', [ReportController::class, 'overdueActions']);
            Route::get('high-risk-items', [ReportController::class, 'highRiskItems']);
        });
        
        // User profile route
        Route::get('user', function (Request $request) {
            return $request->user()->load('roles', 'permissions');
        });
    });
});
