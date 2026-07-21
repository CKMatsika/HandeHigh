<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AIController;

Route::middleware('auth:sanctum')->group(function () {
    // AI Service Endpoints
    Route::prefix('ai')->group(function () {
        Route::post('/student-performance-prediction', [AIController::class, 'studentPerformancePrediction']);
        Route::post('/timetable-generation', [AIController::class, 'timetableGeneration']);
        Route::post('/academic-recommendations', [AIController::class, 'academicRecommendations']);
        Route::post('/financial-forecasting', [AIController::class, 'financialForecasting']);
        Route::post('/chatbot', [AIController::class, 'chatbotResponse']);
    });
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
