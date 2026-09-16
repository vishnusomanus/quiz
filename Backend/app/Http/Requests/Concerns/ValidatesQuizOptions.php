<?php

namespace App\Http\Requests\Concerns;

use App\Enums\QuestionType;
use Illuminate\Contracts\Validation\Validator;

trait ValidatesQuizOptions
{
    protected function validateQuizOptionRules(Validator $validator): void
    {
        foreach ($this->input('questions', []) as $index => $question) {
            $options = is_array($question['options'] ?? null) ? $question['options'] : [];
            $correctCount = collect($options)
                ->filter(fn (mixed $option): bool => is_array($option) && ($option['is_correct'] ?? false))
                ->count();
            $type = $question['type'] ?? QuestionType::SingleChoice->value;

            if ($type === QuestionType::SingleChoice->value && $correctCount !== 1) {
                $validator->errors()->add(
                    "questions.{$index}.options",
                    'Single choice questions must have exactly one correct option.',
                );
            }

            if ($type === QuestionType::MultipleChoice->value && $correctCount < 1) {
                $validator->errors()->add(
                    "questions.{$index}.options",
                    'Multiple choice questions must have at least one correct option.',
                );
            }

            foreach ($options as $optionIndex => $option) {
                if (! is_array($option)) {
                    continue;
                }

                $label = trim((string) ($option['label'] ?? ''));
                $imageUrl = trim((string) ($option['image_url'] ?? ''));

                if ($label === '' && $imageUrl === '') {
                    $validator->errors()->add(
                        "questions.{$index}.options.{$optionIndex}",
                        'Each option must have a label or an image.',
                    );
                }
            }
        }
    }
}
