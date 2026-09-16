<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PassportUpgradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_oauth_tables_match_passport_v13_schema(): void
    {
        $this->assertTrue(Schema::hasTable('oauth_clients'));
        $this->assertTrue(Schema::hasColumn('oauth_clients', 'owner_id'));
        $this->assertTrue(Schema::hasColumn('oauth_clients', 'owner_type'));
        $this->assertTrue(Schema::hasColumn('oauth_clients', 'redirect_uris'));
        $this->assertTrue(Schema::hasColumn('oauth_clients', 'grant_types'));
        $this->assertFalse(Schema::hasColumn('oauth_clients', 'user_id'));
        $this->assertFalse(Schema::hasColumn('oauth_clients', 'redirect'));
        $this->assertFalse(Schema::hasColumn('oauth_clients', 'personal_access_client'));
        $this->assertFalse(Schema::hasColumn('oauth_clients', 'password_client'));
        $this->assertFalse(Schema::hasTable('oauth_personal_access_clients'));
        $this->assertTrue(Schema::hasTable('oauth_device_codes'));
        $this->assertTrue(Schema::hasColumn('oauth_device_codes', 'client_id'));
    }
}
