<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiResponseTrait;
use App\Http\Controllers\Api\HandlesQuizApiErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttemptAnswerRequest;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\QuizAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class QuizAttemptController extends Controller
{
    use ApiResponseTrait, HandlesQuizApiErrors;

    public function __construct(
        protected QuizAttemptService $quizAttemptService,
        protected AuditLogService $auditLogService,
    ) {}

    public function store(Request $request, Quiz $quiz): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $request->user();
            $attempt = $this->quizAttemptService->startOrResume($quiz, $user);

            $this->auditLogService->log([
                'action' => config('constants.QUIZ_ATTEND'),
                'entity' => 'quiz_attempt',
                'entity_id' => $attempt->id,
                'change_log' => [
                    'quiz_id' => $quiz->id,
                    'status' => $attempt->status->value,
                ],
            ]);

            return $this->sendResponse(
                $this->quizAttemptService->attemptPayload($attempt),
                'Attempt ready',
                $attempt->wasRecentlyCreated ? 201 : 200,
            );
        } catch (Throwable $exception) {
            return $this->quizErrorResponse($exception, 'Failed to start quiz attempt');
        }
    }

    public function show(Request $request, QuizAttempt $attempt): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $request->user();
            $payload = $this->quizAttemptService->currentState($attempt, $user);

            return $this->sendResponse($payload, 'Success');
        } catch (Throwable $exception) {
            return $this->quizErrorResponse($exception, 'Failed to get quiz attempt');
        }
    }

    public function answer(StoreAttemptAnswerRequest $request, QuizAttempt $attempt): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $request->user();
            $validated = $request->validated();
            $payload = $this->quizAttemptService->recordAnswer(
                $attempt,
                $user,
                $validated['question_id'],
                $validated['option_ids'],
            );

            $this->auditLogService->log([
                'action' => config('constants.QUIZ_ATTEND'),
                'entity' => 'quiz_attempt',
                'entity_id' => $attempt->id,
                'change_log' => [
                    'question_id' => $validated['question_id'],
                    'status' => $payload['status'] ?? null,
                ],
            ]);

            $message = ($payload['status'] ?? null) === 'in_progress'
                ? 'Answer recorded'
                : 'Quiz completed';

            return $this->sendResponse($payload, $message);
        } catch (Throwable $exception) {
            return $this->quizErrorResponse($exception, 'Failed to record answer');
        }
    }
}
