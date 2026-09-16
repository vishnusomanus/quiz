<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function login(array $validated): array
    {
        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['Invalid credentials.']]);
        }

        $tokenResult = $user->createToken('auth');
        $expiresIn = (int) config('passport.tokens.expire_in', 3600);
        $publicUuid = $user->getOrCreateUuid();

        return [
            'data' => [
                'token' => $tokenResult->accessToken,
                'token_type' => 'Bearer',
                'expires_in' => $expiresIn,
                'user' => [
                    'id' => $publicUuid,
                    'uuid' => $publicUuid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'country' => $user->country,
                ],
            ],
            'message' => 'Login successful',
            'code' => 200,
            'status' => true,
            'activityLog' => [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ];
    }

    public function logout(?User $user): array
    {
        $user?->token()?->revoke();

        return [
            'data' => null,
            'message' => 'Logged out successfully',
            'code' => 200,
            'status' => true,
            'activityLog' => [
                'user_id' => $user?->id,
            ],
        ];
    }
}
