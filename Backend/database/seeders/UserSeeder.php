<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Seed Quiz API login accounts used by Swagger, quiz listing, and admin CRUD.
     */
    public function run(): void
    {
        $this->seedUser('admin@example.com', [
            'name' => 'Quiz Admin',
            'phone' => '9876543210',
            'role' => UserRole::Admin,
            'country' => null,
        ]);

        $this->seedUser('customer@example.com', [
            'name' => 'India Customer',
            'phone' => '9876543212',
            'role' => UserRole::Customer,
            'country' => 'IN',
        ]);

        $this->seedUser('us.customer@example.com', [
            'name' => 'US Customer',
            'phone' => '9876543213',
            'role' => UserRole::Customer,
            'country' => 'US',
        ]);

        $this->seedUser('anita@example.com', [
            'name' => 'Anita Sharma',
            'phone' => '9876543214',
            'role' => UserRole::Customer,
            'country' => 'IN',
        ]);

        $this->seedUser('james@example.com', [
            'name' => 'James Carter',
            'phone' => '9876543215',
            'role' => UserRole::Customer,
            'country' => 'US',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function seedUser(string $email, array $attributes): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'password' => 'password',
                'email_verified_at' => now(),
                ...$attributes,
            ],
        );

        if (empty($user->uuid)) {
            $user->forceFill([
                'uuid' => (string) Str::uuid(),
            ])->save();
        }
    }
}
