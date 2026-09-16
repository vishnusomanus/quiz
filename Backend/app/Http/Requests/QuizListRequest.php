<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class QuizListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function isAdminCatalog(): bool
    {
        $user = $this->user('api');

        return $user instanceof User
            && $user->role === UserRole::Admin
            && ! $this->filled('user_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => [$this->isAdminCatalog() ? 'nullable' : 'required', 'uuid', Rule::exists('users', 'uuid')],
            'country' => ['nullable', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'The user_id field is required.',
            'user_id.uuid' => 'The user_id must be a valid UUID.',
            'user_id.exists' => 'The specified user was not found.',
            'country.size' => 'The country must be a 2-letter ISO country code.',
            'country.regex' => 'The country must be a 2-letter ISO country code.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('country') && is_string($this->input('country'))) {
            $this->merge([
                'country' => strtoupper($this->input('country')),
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
