<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\OptionImageController;
use App\Http\Controllers\Api\V1\QuizAttemptController;
use App\Http\Controllers\Api\V1\QuizController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::get('/quizzes', [QuizController::class, 'index']);
    Route::get('/quizzes/{quiz}/leaderboard', [QuizController::class, 'leaderboard'])->whereUuid('quiz');
    Route::get('/quizzes/{quiz}', [QuizController::class, 'show'])->whereUuid('quiz');

    Route::middleware('auth:api')->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/user', [UserController::class, 'me']);
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{uuid}', [UserController::class, 'show'])->whereUuid('uuid');

        Route::post('/quizzes/{quiz}/submit', [QuizController::class, 'submit'])->whereUuid('quiz');
        Route::post('/quizzes/{quiz}/attempts', [QuizAttemptController::class, 'store'])->whereUuid('quiz');
        Route::get('/attempts/{attempt}', [QuizAttemptController::class, 'show'])->whereUuid('attempt');
        Route::post('/attempts/{attempt}/answers', [QuizAttemptController::class, 'answer'])->whereUuid('attempt');

        Route::middleware('role:admin')->group(function (): void {
            Route::post('/uploads/option-images', [OptionImageController::class, 'store']);
            Route::post('/quizzes', [QuizController::class, 'store']);
            Route::put('/quizzes/{quiz}', [QuizController::class, 'update'])->whereUuid('quiz');
            Route::patch('/quizzes/{quiz}', [QuizController::class, 'update'])->whereUuid('quiz');
            Route::delete('/quizzes/{quiz}', [QuizController::class, 'destroy'])->whereUuid('quiz');
        });
    });
});
