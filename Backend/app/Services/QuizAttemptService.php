<?php

namespace App\Services;

use App\Enums\QuestionType;
use App\Enums\QuizAttemptStatus;
use App\Enums\UserRole;
use App\Exceptions\QuizException;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class QuizAttemptService
{
    public function __construct(
        protected QuizService $quizService,
    ) {}

    public function startOrResume(Quiz $quiz, User $user): QuizAttempt
    {
        $this->assertLearner($user);
        $this->quizService->ensureVisible($quiz, $user);

        if ($quiz->questions()->count() === 0) {
            throw new QuizException('This quiz has no questions', 422);
        }

        return DB::transaction(function () use ($quiz, $user): QuizAttempt {
            $attempts = QuizAttempt::query()
                ->where('quiz_id', $quiz->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->get();

            $attempt = $attempts->firstWhere('status', QuizAttemptStatus::InProgress);

            if ($attempt !== null) {
                $attempt = $this->expireIfNeeded($attempt);

                if ($attempt->isOpen()) {
                    $this->timeoutElapsedQuestions($attempt);

                    return $this->freshAttempt($attempt);
                }

                return $this->freshAttempt($attempt);
            }

            $finished = $attempts->first(
                fn (QuizAttempt $existing): bool => in_array($existing->status, [
                    QuizAttemptStatus::Completed,
                    QuizAttemptStatus::Expired,
                ], true),
            );

            if ($finished !== null) {
                return $this->freshAttempt($finished);
            }

            $now = now();
            $expiresAt = $quiz->time_limit_seconds
                ? $now->copy()->addSeconds((int) $quiz->time_limit_seconds)
                : null;

            $created = QuizAttempt::query()->create([
                'quiz_id' => $quiz->id,
                'user_id' => $user->id,
                'status' => QuizAttemptStatus::InProgress,
                'started_at' => $now,
                'expires_at' => $expiresAt,
            ]);

            $this->timeoutElapsedQuestions($created);

            return $this->freshAttempt($created);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function currentState(QuizAttempt $attempt, User $user): array
    {
        $this->assertLearner($user);
        $this->assertOwner($attempt, $user);

        $attempt = DB::transaction(function () use ($attempt): QuizAttempt {
            $locked = QuizAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            $locked = $this->expireIfNeeded($locked);

            if ($locked->isOpen()) {
                $this->timeoutElapsedQuestions($locked);
            }

            return $this->freshAttempt($locked);
        });

        return $this->attemptPayload($attempt);
    }

    /**
     * @param  list<string>  $optionUuids
     * @return array<string, mixed>
     */
    public function recordAnswer(QuizAttempt $attempt, User $user, string $questionUuid, array $optionUuids): array
    {
        $this->assertLearner($user);
        $this->assertOwner($attempt, $user);

        $attempt = DB::transaction(function () use ($attempt, $questionUuid, $optionUuids): QuizAttempt {
            $locked = QuizAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            $wasOpen = $locked->isOpen();
            $locked = $this->expireIfNeeded($locked);

            if (! $locked->isOpen()) {
                if ($wasOpen) {
                    return $locked;
                }

                throw new QuizException('This attempt is no longer in progress', 409);
            }

            $this->timeoutElapsedQuestions($locked);
            $locked = $this->freshAttempt($locked);

            if (! $locked->isOpen()) {
                return $locked;
            }

            $alreadyAnswered = $locked->answers->first(
                fn (QuizAttemptAnswer $answer): bool => $answer->isAnswered()
                    && $answer->question?->uuid === $questionUuid,
            );

            if ($alreadyAnswered !== null) {
                return $locked;
            }

            $current = $this->nextUnansweredQuestion($locked);

            if ($current === null) {
                return $this->finalize($locked, QuizAttemptStatus::Completed);
            }

            if ($current->uuid !== $questionUuid) {
                throw new QuizException('It is not this question\'s turn', 422);
            }

            $answer = $this->serveQuestion($locked, $current);
            $timedOut = $this->hasQuestionTimedOut($answer, $current, $locked->quiz);
            $optionIds = $timedOut || $optionUuids === []
                ? []
                : $this->resolveOptionIds($current, $optionUuids);

            $this->writeAnswer($answer, $current, $optionIds, $timedOut || $optionIds === []);
            $locked = $this->freshAttempt($locked);

            if ($this->nextUnansweredQuestion($locked) === null) {
                return $this->finalize($locked, QuizAttemptStatus::Completed);
            }

            $this->serveQuestion($locked, $this->nextUnansweredQuestion($locked));

            return $this->freshAttempt($locked);
        });

        return $this->attemptPayload($attempt);
    }

    /**
     * @param  list<array{question_id: string, option_ids: list<string>}>  $answers
     * @return array<string, mixed>
     */
    public function submit(Quiz $quiz, User $user, array $answers): array
    {
        $this->assertLearner($user);
        $this->quizService->ensureVisible($quiz, $user);

        if ($quiz->questions()->count() === 0) {
            throw new QuizException('This quiz has no questions', 422);
        }

        $quiz->loadMissing('questions');
        $questionUuids = $quiz->questions->pluck('uuid');

        foreach ($answers as $answer) {
            if (! $questionUuids->contains($answer['question_id'])) {
                throw new QuizException('One or more questions do not belong to this quiz', 422);
            }
        }

        $attempt = DB::transaction(function () use ($quiz, $user, $answers): QuizAttempt {
            $existingAttempts = QuizAttempt::query()
                ->where('quiz_id', $quiz->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->get();

            $existing = $existingAttempts->firstWhere('status', QuizAttemptStatus::InProgress);

            if ($existing !== null) {
                $existing = $this->expireIfNeeded($existing);

                if (! $existing->isOpen()) {
                    return $this->freshAttempt($existing);
                }

                $this->timeoutElapsedQuestions($existing);
                $this->applySubmittedAnswers($existing, $answers);

                return $this->finalize($this->freshAttempt($existing), QuizAttemptStatus::Completed);
            }

            $finished = $existingAttempts->first(
                fn (QuizAttempt $row): bool => in_array($row->status, [
                    QuizAttemptStatus::Completed,
                    QuizAttemptStatus::Expired,
                ], true),
            );

            if ($finished !== null) {
                return $this->freshAttempt($finished);
            }

            $now = now();
            $created = QuizAttempt::query()->create([
                'quiz_id' => $quiz->id,
                'user_id' => $user->id,
                'status' => QuizAttemptStatus::InProgress,
                'started_at' => $now,
                'expires_at' => $quiz->time_limit_seconds
                    ? $now->copy()->addSeconds((int) $quiz->time_limit_seconds)
                    : null,
            ]);

            $this->applySubmittedAnswers($this->freshAttempt($created), $answers);

            return $this->finalize($this->freshAttempt($created), QuizAttemptStatus::Completed);
        });

        return $this->attemptPayload($attempt, includeAnswers: true);
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function leaderboard(Quiz $quiz, int $perPage): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);
        $page = LengthAwarePaginator::resolveCurrentPage();

        $ranked = QuizAttempt::query()
            ->with('user')
            ->where('quiz_id', $quiz->id)
            ->whereIn('status', [
                QuizAttemptStatus::Completed,
                QuizAttemptStatus::Expired,
            ])
            ->orderByDesc('score')
            ->orderByRaw('time_taken_seconds is null')
            ->orderBy('time_taken_seconds')
            ->orderBy('completed_at')
            ->get()
            ->unique('user_id')
            ->values();

        $items = $ranked
            ->forPage($page, $perPage)
            ->values()
            ->map(function (QuizAttempt $attempt, int $index) use ($page, $perPage): array {
                return [
                    'rank' => (($page - 1) * $perPage) + $index + 1,
                    'user_id' => $attempt->user?->uuid,
                    'user_name' => $attempt->user?->name,
                    'score' => $attempt->score,
                    'time_taken_seconds' => $attempt->time_taken_seconds,
                    'completed_at' => $attempt->completed_at?->toIso8601String(),
                ];
            })
            ->all();

        return new LengthAwarePaginator(
            $items,
            $ranked->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()],
        );
    }

    public function finalize(QuizAttempt $attempt, QuizAttemptStatus $status = QuizAttemptStatus::Completed): QuizAttempt
    {
        $attempt->loadMissing(['quiz.questions.options', 'answers']);

        foreach ($attempt->quiz->questions as $question) {
            $answer = $attempt->answers->firstWhere('question_id', $question->id);

            if ($answer !== null && $answer->isAnswered()) {
                continue;
            }

            if ($answer === null) {
                $answer = new QuizAttemptAnswer([
                    'quiz_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'served_at' => now(),
                ]);
            }

            $this->writeAnswer($answer, $question, [], timedOut: true);
        }

        $attempt->load('answers');

        $startedAt = $attempt->started_at ?? $attempt->created_at;
        $endedAt = now();

        if ($status === QuizAttemptStatus::Expired && $attempt->expires_at !== null && $endedAt->gt($attempt->expires_at)) {
            $endedAt = $attempt->expires_at;
        }

        $attempt->status = $status;
        $attempt->completed_at = $endedAt;
        $attempt->score = (int) $attempt->answers->sum('points_awarded');
        $attempt->time_taken_seconds = max(0, $endedAt->getTimestamp() - $startedAt->getTimestamp());
        $attempt->current_question_served_at = null;
        $attempt->save();

        return $this->freshAttempt($attempt);
    }

    /**
     * @return array<string, mixed>
     */
    public function attemptPayload(QuizAttempt $attempt, bool $includeAnswers = false): array
    {
        $attempt->loadMissing(['quiz.questions.options', 'answers.question', 'user']);

        $currentQuestion = null;
        $currentRemaining = null;

        if ($attempt->isOpen()) {
            $question = $this->nextUnansweredQuestion($attempt);

            if ($question !== null) {
                $answer = $attempt->answers->firstWhere('question_id', $question->id);
                $currentQuestion = $this->quizService->questionPayload($question, includeCorrectAnswers: false);
                $currentRemaining = $this->questionRemainingSeconds($answer, $question, $attempt->quiz);
            }
        }

        $answeredCount = $attempt->answers->filter(fn (QuizAttemptAnswer $answer): bool => $answer->isAnswered())->count();
        $total = $attempt->quiz->questions->count();

        $payload = [
            'id' => $attempt->uuid,
            'quiz_id' => $attempt->quiz->uuid,
            'status' => $attempt->status->value,
            'score' => $attempt->isOpen() ? null : $attempt->score,
            'time_taken_seconds' => $attempt->isOpen() ? null : $attempt->time_taken_seconds,
            'remaining_seconds' => $attempt->isOpen() ? $attempt->remainingSeconds() : 0,
            'current_question_remaining_seconds' => $currentRemaining,
            'started_at' => $attempt->started_at?->toIso8601String(),
            'completed_at' => $attempt->completed_at?->toIso8601String(),
            'expires_at' => $attempt->expires_at?->toIso8601String(),
            'progress' => [
                'answered' => $answeredCount,
                'total' => $total,
            ],
            'current_question' => $currentQuestion,
        ];

        if ($includeAnswers || ! $attempt->isOpen()) {
            $payload['answers'] = $attempt->quiz->questions->map(function (Question $question) use ($attempt): array {
                $answer = $attempt->answers->firstWhere('question_id', $question->id);

                return [
                    'question_id' => $question->uuid,
                    'selected_option_ids' => $this->optionUuidsForIds($question, $answer?->selected_option_ids ?? []),
                    'is_correct' => $answer?->is_correct,
                    'points_awarded' => (int) ($answer?->points_awarded ?? 0),
                    'time_taken_seconds' => $answer?->time_taken_seconds,
                ];
            })->values()->all();
        }

        return $payload;
    }

    private function expireIfNeeded(QuizAttempt $attempt): QuizAttempt
    {
        if ($attempt->isOpen() && $attempt->hasExpired()) {
            return $this->finalize($attempt, QuizAttemptStatus::Expired);
        }

        return $attempt;
    }

    private function timeoutElapsedQuestions(QuizAttempt $attempt): void
    {
        $attempt = $this->freshAttempt($attempt);

        while ($attempt->isOpen()) {
            $question = $this->nextUnansweredQuestion($attempt);

            if ($question === null) {
                $this->finalize($attempt, QuizAttemptStatus::Completed);

                return;
            }

            $answer = $this->serveQuestion($attempt, $question);

            if (! $this->hasQuestionTimedOut($answer, $question, $attempt->quiz)) {
                return;
            }

            $this->writeAnswer($answer, $question, [], timedOut: true);
            $attempt = $this->freshAttempt($attempt);
        }
    }

    private function nextUnansweredQuestion(QuizAttempt $attempt): ?Question
    {
        $attempt->loadMissing(['quiz.questions.options', 'answers']);

        $answeredIds = $attempt->answers
            ->filter(fn (QuizAttemptAnswer $answer): bool => $answer->isAnswered())
            ->pluck('question_id');

        return $attempt->quiz->questions
            ->first(fn (Question $question): bool => ! $answeredIds->contains($question->id));
    }

    private function serveQuestion(QuizAttempt $attempt, Question $question): QuizAttemptAnswer
    {
        $answer = QuizAttemptAnswer::query()->firstOrCreate(
            [
                'quiz_attempt_id' => $attempt->id,
                'question_id' => $question->id,
            ],
            [
                'served_at' => now(),
            ],
        );

        if ($answer->served_at === null) {
            $answer->served_at = now();
            $answer->save();
        }

        $attempt->current_question_served_at = $answer->served_at;
        $attempt->save();

        return $answer;
    }

    /**
     * @param  list<int>  $optionIds
     */
    private function writeAnswer(
        QuizAttemptAnswer $answer,
        Question $question,
        array $optionIds,
        bool $timedOut,
    ): void {
        $grade = $this->grade($question, $optionIds, $timedOut);
        $servedAt = $answer->served_at ?? now();
        $answeredAt = now();

        $answer->fill([
            'selected_option_ids' => $optionIds,
            'is_correct' => $grade['is_correct'],
            'points_awarded' => $grade['points_awarded'],
            'served_at' => $servedAt,
            'answered_at' => $answeredAt,
            'time_taken_seconds' => max(0, $answeredAt->getTimestamp() - $servedAt->getTimestamp()),
        ])->save();
    }

    /**
     * @param  list<int>  $selectedOptionIds
     * @return array{is_correct: bool, points_awarded: int}
     */
    private function grade(Question $question, array $selectedOptionIds, bool $timedOut): array
    {
        if ($timedOut || $selectedOptionIds === []) {
            return [
                'is_correct' => false,
                'points_awarded' => 0,
            ];
        }

        $correct = $question->correctOptionIds();
        $selected = array_values(array_unique($selectedOptionIds));
        sort($selected);

        $isCorrect = $correct === $selected;

        return [
            'is_correct' => $isCorrect,
            'points_awarded' => $isCorrect ? (int) $question->points : 0,
        ];
    }

    /**
     * @param  list<array{question_id: string, option_ids?: list<string>}>  $answers
     */
    private function applySubmittedAnswers(QuizAttempt $attempt, array $answers): void
    {
        $attempt = $this->freshAttempt($attempt);
        $byQuestionUuid = collect($answers)->keyBy('question_id');

        foreach ($attempt->quiz->questions as $question) {
            $existing = $attempt->answers->firstWhere('question_id', $question->id);

            if ($existing !== null && $existing->isAnswered()) {
                continue;
            }

            $submitted = $byQuestionUuid->get($question->uuid);
            $optionUuids = is_array($submitted) ? ($submitted['option_ids'] ?? []) : [];
            $answer = $existing ?? $this->serveQuestion($attempt, $question);
            $timedOut = $this->hasQuestionTimedOut($answer, $question, $attempt->quiz);
            $optionIds = $timedOut || $optionUuids === []
                ? []
                : $this->resolveOptionIds($question, $optionUuids);

            $this->writeAnswer($answer, $question, $optionIds, $timedOut || $optionIds === []);
        }
    }

    /**
     * @param  list<string>  $optionUuids
     * @return list<int>
     */
    private function resolveOptionIds(Question $question, array $optionUuids): array
    {
        $question->loadMissing('options');

        $uniqueUuids = array_values(array_unique($optionUuids));

        if ($uniqueUuids === []) {
            throw new QuizException('At least one option is required', 422);
        }

        $type = $question->type instanceof QuestionType ? $question->type : QuestionType::from((string) $question->type);

        if ($type === QuestionType::SingleChoice && count($uniqueUuids) !== 1) {
            throw new QuizException('This question accepts exactly one option', 422);
        }

        $options = $question->options->whereIn('uuid', $uniqueUuids);

        if ($options->count() !== count($uniqueUuids)) {
            throw new QuizException('One or more options do not belong to this question', 422);
        }

        return $options->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();
    }

    /**
     * @param  list<int>|null  $optionIds
     * @return list<string>
     */
    private function optionUuidsForIds(Question $question, ?array $optionIds): array
    {
        if ($optionIds === null || $optionIds === []) {
            return [];
        }

        $question->loadMissing('options');

        return $question->options
            ->whereIn('id', $optionIds)
            ->pluck('uuid')
            ->values()
            ->all();
    }

    private function questionTimeLimitSeconds(Question $question, Quiz $quiz): ?int
    {
        return $question->time_limit_seconds ?? $quiz->per_question_time_seconds;
    }

    private function hasQuestionTimedOut(QuizAttemptAnswer $answer, Question $question, Quiz $quiz): bool
    {
        $limit = $this->questionTimeLimitSeconds($question, $quiz);

        if ($limit === null || $answer->served_at === null) {
            return false;
        }

        return now()->getTimestamp() >= $answer->served_at->getTimestamp() + $limit;
    }

    private function questionRemainingSeconds(?QuizAttemptAnswer $answer, Question $question, Quiz $quiz): ?int
    {
        $limit = $this->questionTimeLimitSeconds($question, $quiz);

        if ($limit === null) {
            return null;
        }

        $servedAt = $answer?->served_at ?? now();

        return max(0, ($servedAt->getTimestamp() + $limit) - now()->getTimestamp());
    }

    private function assertLearner(User $user): void
    {
        if ($user->role !== UserRole::Customer) {
            throw new QuizException('Only learners can attend quizzes', 403);
        }
    }

    private function assertOwner(QuizAttempt $attempt, User $user): void
    {
        if ($attempt->user_id !== $user->id) {
            throw new QuizException('Forbidden', 403);
        }
    }

    private function freshAttempt(QuizAttempt $attempt): QuizAttempt
    {
        return $attempt->fresh(['quiz.questions.options', 'answers.question', 'user']) ?? $attempt;
    }
}
