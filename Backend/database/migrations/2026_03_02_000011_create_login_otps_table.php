<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('login_otps', function (Blueprint $table): void {
            $table->id();
            $table->string('contact'); // phone or email
            $table->string('channel'); // 'phone' or 'email'
            $table->string('role');    // customer, delivery_agent, etc.
            $table->string('otp');
            $table->timestamp('expires_at');
            $table->timestamp('last_sent_at');
            $table->timestamps();

            $table->unique(['contact', 'channel', 'role'], 'login_otps_contact_channel_role_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_otps');
    }
};
