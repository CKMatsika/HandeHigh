<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AIController;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Timetable Experience & Synchronization APIs
    Route::prefix('timetable')->group(function () {
        Route::get('/today', [\App\Http\Controllers\API\TimetableAPIController::class, 'today']);
        Route::get('/version', [\App\Http\Controllers\API\TimetableAPIController::class, 'version']);
        Route::get('/class/{class}', [\App\Http\Controllers\API\TimetableAPIController::class, 'classSchedule']);
        Route::get('/teacher/{teacher}', [\App\Http\Controllers\API\TimetableAPIController::class, 'teacherSchedule']);
        Route::get('/room/{room}', [\App\Http\Controllers\API\TimetableAPIController::class, 'roomSchedule']);
        Route::get('/{timetable}/version', [\App\Http\Controllers\API\TimetableAPIController::class, 'version']);
        Route::get('/{timetable}/changes', [\App\Http\Controllers\API\TimetableAPIController::class, 'changes']);
        Route::get('/slots/{slot}/substitutes', [\App\Http\Controllers\API\TimetableAPIController::class, 'recommendSubstitutes']);

        Route::post('/{timetable}/slots/{slot}/teacher', [\App\Http\Controllers\API\TimetableAPIController::class, 'changeTeacher']);
        Route::post('/{timetable}/slots/{slot}/room', [\App\Http\Controllers\API\TimetableAPIController::class, 'changeRoom']);
        Route::post('/{timetable}/slots/{slot}/move', [\App\Http\Controllers\API\TimetableAPIController::class, 'moveLesson']);
        Route::post('/{timetable}/slots/{slot}/cancel', [\App\Http\Controllers\API\TimetableAPIController::class, 'cancelLesson']);
        Route::post('/{timetable}/slots/{slot}/restore', [\App\Http\Controllers\API\TimetableAPIController::class, 'restoreLesson']);
        Route::post('/{timetable}/slots/{slot}/substitute', [\App\Http\Controllers\API\TimetableAPIController::class, 'assignSubstitute']);
    });
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['auth:sanctum', 'tenant']);
