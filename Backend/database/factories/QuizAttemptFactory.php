<?php

namespace Database\Factories;

use App\Enums\QuizAttemptStatus;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = now();

        return [
            'quiz_id' => Quiz::factory(),
            'user_id' => User::factory(),
            'status' => QuizAttemptStatus::InProgress,
            'started_at' => $startedAt,
            'expires_at' => $startedAt->copy()->addMinutes(5),
            'score' => 0,
        ];
    }

    public function completed(int $score = 1, int $timeTakenSeconds = 20): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => QuizAttemptStatus::Completed,
            'completed_at' => now(),
            'score' => $score,
            'time_taken_seconds' => $timeTakenSeconds,
        ]);
    }
}
