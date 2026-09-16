<?php

namespace App\Services;

use App\Enums\QuestionType;
use App\Enums\QuizAttemptStatus;
use App\Enums\UserRole;
use App\Exceptions\QuizException;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuizService
{
    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateForUser(User $user, ?string $country, int $perPage): LengthAwarePaginator
    {
        $country = $this->resolveCountry($user, $country);

        return Quiz::query()
            ->published()
            ->forCountry($country)
            ->with('countries')
            ->with(['attempts' => function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->whereIn('status', [
                        QuizAttemptStatus::InProgress,
                        QuizAttemptStatus::Completed,
                        QuizAttemptStatus::Expired,
                    ])
                    ->latest('id');
            }])
            ->withCount('questions')
            ->withExists(['attempts as is_completed' => function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->whereIn('status', [
                        QuizAttemptStatus::Completed,
                        QuizAttemptStatus::Expired,
                    ]);
            }])
            ->orderBy('title')
            ->paginate($perPage)
            ->through(fn (Quiz $quiz): array => $this->toListPayload($quiz));
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateForAdmin(?string $country, ?bool $isPublished, int $perPage): LengthAwarePaginator
    {
        $country = $country !== null && $country !== '' ? strtoupper($country) : null;

        return Quiz::query()
            ->when($isPublished !== null, function (Builder $query) use ($isPublished): void {
                $query->where('is_published', $isPublished);
            })
            ->forCountry($country)
            ->with('countries')
            ->withCount('questions')
            ->latest()
            ->paginate($perPage)
            ->through(fn (Quiz $quiz): array => $this->toListPayload($quiz, forAdmin: true));
    }

    /**
     * @return array<string, mixed>
     */
    public function show(Quiz $quiz, ?User $viewer = null): array
    {
        $this->ensureVisible($quiz, $viewer);

        $quiz->load(['countries', 'questions.options']);

        return $this->toDetailPayload($quiz, $this->isAdmin($viewer));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $admin): Quiz
    {
        return DB::transaction(function () use ($data, $admin): Quiz {
            $quiz = Quiz::query()->create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'time_limit_seconds' => $data['time_limit_seconds'] ?? null,
                'per_question_time_seconds' => $data['per_question_time_seconds'] ?? null,
                'is_published' => $data['is_published'] ?? true,
                'created_by' => $admin->id,
            ]);

            $this->syncCountries($quiz, $data['countries'] ?? []);
            $this->syncQuestions($quiz, $data['questions'] ?? []);

            return $quiz->load(['countries', 'questions.options']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Quiz $quiz, array $data): Quiz
    {
        return DB::transaction(function () use ($quiz, $data): Quiz {
            $fillable = [];

            foreach (['title', 'description', 'time_limit_seconds', 'per_question_time_seconds', 'is_published'] as $field) {
                if (array_key_exists($field, $data)) {
                    $fillable[$field] = $data[$field];
                }
            }

            if ($fillable !== []) {
                $quiz->fill($fillable);
                $quiz->save();
            }

            if (array_key_exists('countries', $data)) {
                $this->syncCountries($quiz, $data['countries'] ?? []);
            }

            if (array_key_exists('questions', $data)) {
                $this->syncQuestions($quiz, $data['questions']);
            }

            return $quiz->refresh()->load(['countries', 'questions.options']);
        });
    }

    public function delete(Quiz $quiz): void
    {
        $quiz->delete();
    }

    public function findUserByPublicId(string $uuid): User
    {
        return User::query()->where('uuid', $uuid)->firstOrFail();
    }

    public function ensureVisible(Quiz $quiz, ?User $viewer): void
    {
        if ($quiz->is_published || $this->isAdmin($viewer)) {
            return;
        }

        throw new QuizException('Quiz not found', 404);
    }

    public function isAdmin(?User $user): bool
    {
        return $user !== null && $user->role === UserRole::Admin;
    }

    /**
     * @return array<string, mixed>
     */
    public function toListPayload(Quiz $quiz, bool $forAdmin = false): array
    {
        $payload = [
            'id' => $quiz->uuid,
            'title' => $quiz->title,
            'description' => $quiz->description,
            'time_limit_seconds' => $quiz->time_limit_seconds,
            'per_question_time_seconds' => $quiz->per_question_time_seconds,
            'countries' => $quiz->countries->pluck('country_code')->values()->all(),
            'question_count' => (int) ($quiz->questions_count ?? $quiz->questions()->count()),
        ];

        if ($forAdmin) {
            $payload['is_published'] = (bool) $quiz->is_published;

            return $payload;
        }

        $payload['is_completed'] = (bool) ($quiz->is_completed ?? false);
        $payload['in_progress_attempt_id'] = $quiz->relationLoaded('attempts')
            ? $quiz->attempts->firstWhere('status', QuizAttemptStatus::InProgress)?->uuid
            : null;
        $payload['completed_attempt_id'] = $quiz->relationLoaded('attempts')
            ? $quiz->attempts
                ->first(fn (QuizAttempt $attempt): bool => in_array($attempt->status, [
                    QuizAttemptStatus::Completed,
                    QuizAttemptStatus::Expired,
                ], true))
                ?->uuid
            : null;

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDetailPayload(Quiz $quiz, bool $includeCorrectAnswers = false): array
    {
        return [
            'id' => $quiz->uuid,
            'title' => $quiz->title,
            'description' => $quiz->description,
            'time_limit_seconds' => $quiz->time_limit_seconds,
            'per_question_time_seconds' => $quiz->per_question_time_seconds,
            'is_published' => $quiz->is_published,
            'countries' => $quiz->countries->pluck('country_code')->values()->all(),
            'questions' => $quiz->questions->map(function (Question $question) use ($includeCorrectAnswers): array {
                return $this->questionPayload($question, $includeCorrectAnswers);
            })->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function questionPayload(Question $question, bool $includeCorrectAnswers = false): array
    {
        return [
            'id' => $question->uuid,
            'prompt' => $question->prompt,
            'type' => $question->type instanceof QuestionType ? $question->type->value : $question->type,
            'points' => $question->points,
            'time_limit_seconds' => $question->time_limit_seconds,
            'sort_order' => $question->sort_order,
            'options' => $question->options->map(function (QuestionOption $option) use ($includeCorrectAnswers): array {
                return $this->optionPayload($option, $includeCorrectAnswers);
            })->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function optionPayload(QuestionOption $option, bool $includeCorrectAnswers = false): array
    {
        $payload = [
            'id' => $option->uuid,
            'label' => $option->label,
            'image_url' => $option->image_url,
            'sort_order' => $option->sort_order,
        ];

        if ($includeCorrectAnswers) {
            $payload['is_correct'] = $option->is_correct;
        }

        return $payload;
    }

    private function resolveCountry(User $user, ?string $country): ?string
    {
        $resolved = $country !== null && $country !== '' ? $country : $user->country;

        return $resolved !== null && $resolved !== '' ? strtoupper($resolved) : null;
    }

    /**
     * @param  list<string>  $countries
     */
    private function syncCountries(Quiz $quiz, array $countries): void
    {
        $codes = collect($countries)
            ->filter(fn (mixed $code): bool => is_string($code) && $code !== '')
            ->map(fn (string $code): string => strtoupper($code))
            ->unique()
            ->values();

        $quiz->countries()->delete();

        foreach ($codes as $code) {
            $quiz->countries()->create([
                'country_code' => $code,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     */
    private function syncQuestions(Quiz $quiz, array $questions): void
    {
        $keptQuestionIds = [];

        foreach ($questions as $index => $questionData) {
            $question = $this->resolveQuestion($quiz, $questionData);
            $question->fill([
                'prompt' => $questionData['prompt'],
                'type' => $questionData['type'] ?? QuestionType::SingleChoice->value,
                'points' => $questionData['points'] ?? 1,
                'time_limit_seconds' => $questionData['time_limit_seconds'] ?? null,
                'sort_order' => $questionData['sort_order'] ?? ($index + 1),
            ]);
            $question->quiz_id = $quiz->id;
            $question->save();

            $keptQuestionIds[] = $question->id;
            $this->syncOptions($question, $questionData['options'] ?? []);
        }

        Question::query()
            ->where('quiz_id', $quiz->id)
            ->whereNotIn('id', $keptQuestionIds)
            ->delete();
    }

    /**
     * @param  array<string, mixed>  $questionData
     */
    private function resolveQuestion(Quiz $quiz, array $questionData): Question
    {
        $uuid = $questionData['id'] ?? $questionData['uuid'] ?? null;

        if (! is_string($uuid) || $uuid === '') {
            $question = new Question;
            $question->uuid = (string) Str::uuid();

            return $question;
        }

        $question = Question::query()
            ->where('quiz_id', $quiz->id)
            ->where('uuid', $uuid)
            ->first();

        if ($question === null) {
            throw new QuizException('Question not found for this quiz', 422);
        }

        return $question;
    }

    /**
     * @param  list<array<string, mixed>>  $options
     */
    private function syncOptions(Question $question, array $options): void
    {
        $keptOptionIds = [];

        foreach ($options as $index => $optionData) {
            $option = $this->resolveOption($question, $optionData);
            $option->fill([
                'label' => (string) ($optionData['label'] ?? ''),
                'image_url' => $this->normalizeImageUrl($optionData['image_url'] ?? null),
                'is_correct' => (bool) ($optionData['is_correct'] ?? false),
                'sort_order' => $optionData['sort_order'] ?? ($index + 1),
            ]);
            $option->question_id = $question->id;
            $option->save();

            $keptOptionIds[] = $option->id;
        }

        QuestionOption::query()
            ->where('question_id', $question->id)
            ->whereNotIn('id', $keptOptionIds)
            ->delete();
    }

    /**
     * @param  array<string, mixed>  $optionData
     */
    private function resolveOption(Question $question, array $optionData): QuestionOption
    {
        $uuid = $optionData['id'] ?? $optionData['uuid'] ?? null;

        if (! is_string($uuid) || $uuid === '') {
            $option = new QuestionOption;
            $option->uuid = (string) Str::uuid();

            return $option;
        }

        $option = QuestionOption::query()
            ->where('question_id', $question->id)
            ->where('uuid', $uuid)
            ->first();

        if ($option === null) {
            throw new QuizException('Option not found for this question', 422);
        }

        return $option;
    }

    private function normalizeImageUrl(mixed $url): ?string
    {
        if (! is_string($url)) {
            return null;
        }

        $trimmed = trim($url);

        return $trimmed === '' ? null : $trimmed;
    }
}
