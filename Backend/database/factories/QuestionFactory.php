<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'prompt' => fake()->sentence().'?',
            'type' => QuestionType::SingleChoice,
            'points' => 1,
            'time_limit_seconds' => null,
            'sort_order' => 0,
        ];
    }
}
