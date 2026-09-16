<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\QuizException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

trait HandlesQuizApiErrors
{
    protected function quizErrorResponse(Throwable $exception, string $fallbackMessage): JsonResponse
    {
        if ($exception instanceof QuizException) {
            return $this->sendError($exception->getMessage(), null, $exception->statusCode());
        }

        if ($exception instanceof ModelNotFoundException) {
            return $this->sendError('Resource not found', null, 404);
        }

        Log::channel('quiz_errors')->info($fallbackMessage.': '.$exception->getMessage(), [
            'trace' => $exception->getTraceAsString(),
            'line' => $exception->getLine(),
        ]);

        $errorPayload = [
            'detail' => $exception->getMessage(),
        ];

        if (app()->environment('local')) {
            $errorPayload['trace'] = $exception->getTraceAsString();
            $errorPayload['line'] = $exception->getLine();
        }

        return $this->sendError($fallbackMessage, $errorPayload, 500);
    }
}
