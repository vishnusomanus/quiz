<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_seeder_creates_quiz_accounts_with_roles_and_countries(): void
    {
        $this->seed(UserSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->first();
        $indiaCustomer = User::query()->where('email', 'customer@example.com')->first();
        $usCustomer = User::query()->where('email', 'us.customer@example.com')->first();

        $this->assertNotNull($admin);
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertNull($admin->country);
        $this->assertNotEmpty($admin->uuid);
        $this->assertTrue(Hash::check('password', $admin->password));

        $this->assertNotNull($indiaCustomer);
        $this->assertSame(UserRole::Customer, $indiaCustomer->role);
        $this->assertSame('IN', $indiaCustomer->country);

        $this->assertNotNull($usCustomer);
        $this->assertSame(UserRole::Customer, $usCustomer->role);
        $this->assertSame('US', $usCustomer->country);

        $anita = User::query()->where('email', 'anita@example.com')->first();
        $james = User::query()->where('email', 'james@example.com')->first();

        $this->assertNotNull($anita);
        $this->assertSame(UserRole::Customer, $anita->role);
        $this->assertSame('IN', $anita->country);

        $this->assertNotNull($james);
        $this->assertSame(UserRole::Customer, $james->role);
        $this->assertSame('US', $james->country);
    }

    public function test_user_seeder_is_idempotent(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(UserSeeder::class);

        $this->assertSame(5, User::query()->count());
    }
}
