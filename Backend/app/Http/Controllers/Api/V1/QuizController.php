<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiResponseTrait;
use App\Http\Controllers\Api\HandlesQuizApiErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuizListRequest;
use App\Http\Requests\StoreQuizRequest;
use App\Http\Requests\SubmitQuizRequest;
use App\Http\Requests\UpdateQuizRequest;
use App\Models\Quiz;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\QuizAttemptService;
use App\Services\QuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class QuizController extends Controller
{
    use ApiResponseTrait, HandlesQuizApiErrors;

    public function __construct(
        protected QuizService $quizService,
        protected QuizAttemptService $quizAttemptService,
        protected AuditLogService $auditLogService,
    ) {}

    public function index(QuizListRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $perPage = (int) ($validated['per_page'] ?? 15);

            if ($request->isAdminCatalog()) {
                $isPublished = array_key_exists('is_published', $validated)
                    ? (bool) $validated['is_published']
                    : null;
                $quizzes = $this->quizService->paginateForAdmin(
                    $validated['country'] ?? null,
                    $isPublished,
                    $perPage,
                );

                $this->auditLogService->log([
                    'action' => config('constants.QUIZ_LIST'),
                    'entity' => 'quiz',
                    'entity_id' => null,
                    'change_log' => [
                        'scope' => 'admin',
                        'country' => $validated['country'] ?? null,
                        'is_published' => $isPublished,
                        'total' => $quizzes->total(),
                    ],
                ]);

                return $this->sendPaginatedResponse($quizzes, 'Success');
            }

            $user = $this->quizService->findUserByPublicId($validated['user_id']);
            $quizzes = $this->quizService->paginateForUser(
                $user,
                $validated['country'] ?? null,
                $perPage,
            );

            $this->auditLogService->log([
                'action' => config('constants.QUIZ_LIST'),
                'entity' => 'quiz',
                'entity_id' => null,
                'change_log' => [
                    'user_id' => $user->id,
                    'country' => $validated['country'] ?? $user->country,
                    'total' => $quizzes->total(),
                ],
            ]);

            return $this->sendPaginatedResponse($quizzes, 'Success');
        } catch (Throwable $exception) {
            return $this->quizErrorResponse($exception, 'Failed to list quizzes');
        }
    }

    public function show(Request $request, Quiz $quiz): JsonResponse
    {
        try {
            $payload = $this->quizService->show($quiz, $request->user('api'));

            $this->auditLogService->log([
                'action' => config('constants.QUIZ_VIEW'),
                'entity' => 'quiz',
                'entity_id' => $quiz->id,
                'change_log' => ['viewed_by' => $request->user('api')?->id],
            ]);

            return $this->sendResponse($payload, 'Success');
        } catch (Throwable $exception) {
            return $this->quizErrorResponse($exception, 'Failed to get quiz');
        }
    }

    public function store(StoreQuizRequest $request): JsonResponse
    {
        try {
            /** @var User $admin */
            $admin = $request->user();
            $quiz = $this->quizService->create($request->validated(), $admin);

            $this->auditLogService->log([
                'action' => config('constants.QUIZ_CREATE'),
                'entity' => 'quiz',
                'entity_id' => $quiz->id,
                'change_log' => ['title' => $quiz->title],
            ]);

            return $this->sendResponse(
                $this->quizService->toDetailPayload($quiz, includeCorrectAnswers: true),
                'Quiz created successfully',
                201,
            );
        } catch (Throwable $exception) {
            return $this->quizErrorResponse($exception, 'Failed to create quiz');
        }
    }

    public function update(UpdateQuizRequest $request, Quiz $quiz): JsonResponse
    {
        try {
            $quiz = $this->quizService->update($quiz, $request->validated());

            $this->auditLogService->log([
                'action' => config('constants.QUIZ_UPDATE'),
                'entity' => 'quiz',
                'entity_id' => $quiz->id,
                'change_log' => ['title' => $quiz->title],
            ]);

            return $this->sendResponse(
                $this->quizService->toDetailPayload($quiz, includeCorrectAnswers: true),
                'Quiz updated successfully',
            );
        } catch (Throwable $exception) {
            return $this->quizErrorResponse($exception, 'Failed to update quiz');
        }
    }

    public function destroy(Quiz $quiz): JsonResponse
    {
        try {
            $this->quizService->delete($quiz);

            $this->auditLogService->log([
                'action' => config('constants.QUIZ_DELETE'),
                'entity' => 'quiz',
                'entity_id' => $quiz->id,
                'change_log' => ['title' => $quiz->title],
            ]);

            return $this->sendResponse(null, 'Quiz deleted successfully');
        } catch (Throwable $exception) {
            return $this->quizErrorResponse($exception, 'Failed to delete quiz');
        }
    }

    public function submit(SubmitQuizRequest $request, Quiz $quiz): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $request->user();
            $payload = $this->quizAttemptService->submit(
                $quiz,
                $user,
                $request->validated('answers'),
            );

            $this->auditLogService->log([
                'action' => config('constants.QUIZ_SUBMIT'),
                'entity' => 'quiz_attempt',
                'entity_id' => $quiz->id,
                'change_log' => [
                    'quiz_id' => $quiz->id,
                    'score' => $payload['score'] ?? null,
                ],
            ]);

            return $this->sendResponse($payload, 'Quiz submitted successfully');
        } catch (Throwable $exception) {
            return $this->quizErrorResponse($exception, 'Failed to submit quiz');
        }
    }

    public function leaderboard(Request $request, Quiz $quiz): JsonResponse
    {
        try {
            $this->quizService->ensureVisible($quiz, $request->user('api'));
            $perPage = max(1, min(100, (int) $request->get('per_page', 15)));
            $leaderboard = $this->quizAttemptService->leaderboard($quiz, $perPage);

            $this->auditLogService->log([
                'action' => config('constants.QUIZ_LEADERBOARD'),
                'entity' => 'quiz',
                'entity_id' => $quiz->id,
                'change_log' => ['per_page' => $perPage],
            ]);

            return $this->sendPaginatedResponse($leaderboard, 'Success');
        } catch (Throwable $exception) {
            return $this->quizErrorResponse($exception, 'Failed to load leaderboard');
        }
    }
}
