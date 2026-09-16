<?php

namespace App\Http\Requests;

use App\Enums\QuestionType;
use App\Http\Requests\Concerns\ValidatesQuizOptions;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateQuizRequest extends FormRequest
{
    use ValidatesQuizOptions;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'time_limit_seconds' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'per_question_time_seconds' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'is_published' => ['sometimes', 'boolean'],
            'countries' => ['sometimes', 'array'],
            'countries.*' => ['string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
            'questions' => ['sometimes', 'array', 'min:1'],
            'questions.*.id' => ['sometimes', 'uuid'],
            'questions.*.prompt' => ['required_with:questions', 'string'],
            'questions.*.type' => ['sometimes', Rule::enum(QuestionType::class)],
            'questions.*.points' => ['sometimes', 'integer', 'min:1'],
            'questions.*.time_limit_seconds' => ['nullable', 'integer', 'min:1'],
            'questions.*.sort_order' => ['sometimes', 'integer', 'min:0'],
            'questions.*.options' => ['required_with:questions', 'array', 'min:2'],
            'questions.*.options.*.id' => ['sometimes', 'uuid'],
            'questions.*.options.*.label' => ['nullable', 'string', 'max:255'],
            'questions.*.options.*.image_url' => ['nullable', 'string', 'max:2048', 'url'],
            'questions.*.options.*.is_correct' => ['required_with:questions.*.options', 'boolean'],
            'questions.*.options.*.sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The quiz title is required.',
            'questions.min' => 'At least one question is required.',
            'questions.*.options.min' => 'Each question must have at least two options.',
            'questions.*.options.*.image_url.url' => 'Each option image must be a valid URL.',
            'countries.*.size' => 'Country codes must be 2-letter ISO values.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->exists('questions')) {
                return;
            }

            $this->validateQuizOptionRules($validator);
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('countries') && is_array($this->input('countries'))) {
            $this->merge([
                'countries' => collect($this->input('countries'))
                    ->map(fn (mixed $code): mixed => is_string($code) ? strtoupper($code) : $code)
                    ->all(),
            ]);
        }
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }
}
