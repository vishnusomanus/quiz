<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(3),
            'description' => fake()->paragraph(),
            'time_limit_seconds' => 300,
            'per_question_time_seconds' => 30,
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_published' => false,
        ]);
    }

    /**
     * @param  list<string>  $countryCodes
     */
    public function forCountries(array $countryCodes): static
    {
        return $this->afterCreating(function (Quiz $quiz) use ($countryCodes): void {
            foreach ($countryCodes as $code) {
                $quiz->countries()->create([
                    'country_code' => strtoupper($code),
                ]);
            }
        });
    }

    public function withQuestions(int $count = 2): static
    {
        return $this->afterCreating(function (Quiz $quiz) use ($count): void {
            for ($index = 1; $index <= $count; $index++) {
                $question = Question::factory()->create([
                    'quiz_id' => $quiz->id,
                    'prompt' => "Question {$index}?",
                    'sort_order' => $index,
                ]);

                QuestionOption::factory()->create([
                    'question_id' => $question->id,
                    'label' => 'Wrong',
                    'is_correct' => false,
                    'sort_order' => 1,
                ]);

                QuestionOption::factory()->create([
                    'question_id' => $question->id,
                    'label' => 'Right',
                    'is_correct' => true,
                    'sort_order' => 2,
                ]);
            }
        });
    }
}
