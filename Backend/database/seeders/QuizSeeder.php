<?php

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class QuizSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Quiz::withTrashed()->where('title', 'World Flags')->get()->each->forceDelete();

        $admin = User::query()->where('email', 'admin@example.com')->first();

        $this->seedQuiz(
            'World Flags',
            'Identify flags from images. Mix of single-choice and multiple-choice visual questions.',
            [],
            150,
            25,
            $admin?->id,
            [
                [
                    'prompt' => 'Which flag belongs to India?',
                    'type' => QuestionType::SingleChoice,
                    'options' => [
                        ['label' => 'United States', 'image_url' => 'https://flagcdn.com/w160/us.png', 'is_correct' => false],
                        ['label' => 'India', 'image_url' => 'https://flagcdn.com/w160/in.png', 'is_correct' => true],
                        ['label' => 'Japan', 'image_url' => 'https://flagcdn.com/w160/jp.png', 'is_correct' => false],
                        ['label' => 'France', 'image_url' => 'https://flagcdn.com/w160/fr.png', 'is_correct' => false],
                    ],
                ],
                [
                    'prompt' => 'Which of these flags belong to European countries?',
                    'type' => QuestionType::MultipleChoice,
                    'options' => [
                        ['label' => 'Germany', 'image_url' => 'https://flagcdn.com/w160/de.png', 'is_correct' => true],
                        ['label' => 'Brazil', 'image_url' => 'https://flagcdn.com/w160/br.png', 'is_correct' => false],
                        ['label' => 'Italy', 'image_url' => 'https://flagcdn.com/w160/it.png', 'is_correct' => true],
                        ['label' => 'Canada', 'image_url' => 'https://flagcdn.com/w160/ca.png', 'is_correct' => false],
                    ],
                ],
            ],
        );

        $this->seedQuiz(
            'India General Knowledge',
            'A short quiz about India.',
            ['IN'],
            180,
            30,
            $admin?->id,
            [
                [
                    'prompt' => 'What is the capital of India?',
                    'options' => [
                        ['label' => 'Mumbai', 'is_correct' => false],
                        ['label' => 'New Delhi', 'is_correct' => true],
                        ['label' => 'Kolkata', 'is_correct' => false],
                    ],
                ],
                [
                    'prompt' => 'Which river is the longest in India?',
                    'options' => [
                        ['label' => 'Yamuna', 'is_correct' => false],
                        ['label' => 'Ganga', 'is_correct' => true],
                        ['label' => 'Godavari', 'is_correct' => false],
                    ],
                ],
                [
                    'prompt' => 'India\'s national animal is the tiger.',
                    'options' => [
                        ['label' => 'True', 'is_correct' => true],
                        ['label' => 'False', 'is_correct' => false],
                    ],
                ],
            ],
        );

        $this->seedQuiz(
            'World Capitals',
            'A worldwide quiz available in every country.',
            [],
            120,
            20,
            $admin?->id,
            [
                [
                    'prompt' => 'What is the capital of France?',
                    'options' => [
                        ['label' => 'Lyon', 'is_correct' => false],
                        ['label' => 'Paris', 'is_correct' => true],
                        ['label' => 'Marseille', 'is_correct' => false],
                    ],
                ],
                [
                    'prompt' => 'What is the capital of Japan?',
                    'options' => [
                        ['label' => 'Osaka', 'is_correct' => false],
                        ['label' => 'Tokyo', 'is_correct' => true],
                        ['label' => 'Kyoto', 'is_correct' => false],
                    ],
                ],
            ],
        );

        $this->seedQuiz(
            'United States Civics',
            'A quiz assigned to the United States.',
            ['US'],
            150,
            25,
            $admin?->id,
            [
                [
                    'prompt' => 'How many states are in the United States?',
                    'options' => [
                        ['label' => '50', 'is_correct' => true],
                        ['label' => '52', 'is_correct' => false],
                        ['label' => '13', 'is_correct' => false],
                    ],
                ],
                [
                    'prompt' => 'The US Declaration of Independence was signed in which year?',
                    'options' => [
                        ['label' => '1776', 'is_correct' => true],
                        ['label' => '1789', 'is_correct' => false],
                        ['label' => '1812', 'is_correct' => false],
                    ],
                ],
            ],
        );

        $this->seedQuiz(
            'Science Basics',
            'A mixed single- and multiple-choice quiz available worldwide.',
            [],
            180,
            30,
            $admin?->id,
            [
                [
                    'prompt' => 'What is H2O commonly known as?',
                    'type' => QuestionType::SingleChoice,
                    'options' => [
                        ['label' => 'Oxygen', 'is_correct' => false],
                        ['label' => 'Water', 'is_correct' => true],
                        ['label' => 'Hydrogen', 'is_correct' => false],
                    ],
                ],
                [
                    'prompt' => 'Which of these are planets in our solar system?',
                    'type' => QuestionType::MultipleChoice,
                    'options' => [
                        ['label' => 'Earth', 'is_correct' => true],
                        ['label' => 'Mars', 'is_correct' => true],
                        ['label' => 'Pluto', 'is_correct' => false],
                        ['label' => 'Venus', 'is_correct' => true],
                    ],
                ],
                [
                    'prompt' => 'Which of these are states of matter?',
                    'type' => QuestionType::MultipleChoice,
                    'options' => [
                        ['label' => 'Solid', 'is_correct' => true],
                        ['label' => 'Liquid', 'is_correct' => true],
                        ['label' => 'Plasma', 'is_correct' => true],
                        ['label' => 'Gravity', 'is_correct' => false],
                    ],
                ],
            ],
        );

        $this->seedQuiz(
            'Ocean Trivia',
            'Unpublished draft for admin review. Learners should not see this quiz.',
            [],
            120,
            25,
            $admin?->id,
            [
                [
                    'prompt' => 'What is the largest ocean on Earth?',
                    'options' => [
                        ['label' => 'Atlantic Ocean', 'is_correct' => false],
                        ['label' => 'Pacific Ocean', 'is_correct' => true],
                        ['label' => 'Indian Ocean', 'is_correct' => false],
                    ],
                ],
                [
                    'prompt' => 'Which of these are saltwater bodies?',
                    'type' => QuestionType::MultipleChoice,
                    'options' => [
                        ['label' => 'The Mediterranean Sea', 'is_correct' => true],
                        ['label' => 'Lake Baikal', 'is_correct' => false],
                        ['label' => 'The Caribbean Sea', 'is_correct' => true],
                        ['label' => 'The Amazon River', 'is_correct' => false],
                    ],
                ],
            ],
            published: false,
        );
    }

    /**
     * @param  list<string>  $countries
     * @param  list<array{prompt: string, type?: QuestionType, options: list<array{label: string, image_url?: string, is_correct: bool}>}>  $questions
     */
    private function seedQuiz(
        string $title,
        string $description,
        array $countries,
        int $timeLimitSeconds,
        int $perQuestionSeconds,
        ?int $createdBy,
        array $questions,
        bool $published = true,
    ): Quiz {
        $quiz = Quiz::query()->firstOrNew(['title' => $title]);

        if (! $quiz->exists) {
            $quiz->uuid = (string) Str::uuid();
        }

        $quiz->fill([
            'description' => $description,
            'time_limit_seconds' => $timeLimitSeconds,
            'per_question_time_seconds' => $perQuestionSeconds,
            'is_published' => $published,
            'created_by' => $createdBy,
        ]);
        $quiz->save();

        $quiz->countries()->delete();

        foreach ($countries as $country) {
            $quiz->countries()->create([
                'country_code' => $country,
            ]);
        }

        if ($quiz->questions()->exists()) {
            return $quiz;
        }

        foreach ($questions as $index => $questionData) {
            $question = Question::query()->create([
                'uuid' => (string) Str::uuid(),
                'quiz_id' => $quiz->id,
                'prompt' => $questionData['prompt'],
                'type' => $questionData['type'] ?? QuestionType::SingleChoice,
                'points' => 1,
                'sort_order' => $index + 1,
            ]);

            foreach ($questionData['options'] as $optionIndex => $optionData) {
                QuestionOption::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'question_id' => $question->id,
                    'label' => $optionData['label'],
                    'image_url' => $optionData['image_url'] ?? null,
                    'is_correct' => $optionData['is_correct'],
                    'sort_order' => $optionIndex + 1,
                ]);
            }
        }

        return $quiz;
    }
}
