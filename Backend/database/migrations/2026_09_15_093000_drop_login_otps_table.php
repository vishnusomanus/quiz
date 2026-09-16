<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('login_otps');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('login_otps', function (Blueprint $table): void {
            $table->id();
            $table->string('contact');
            $table->string('channel');
            $table->string('role');
            $table->string('otp');
            $table->timestamp('expires_at');
            $table->timestamp('last_sent_at');
            $table->timestamps();

            $table->unique(['contact', 'channel', 'role'], 'login_otps_contact_channel_role_unique');
        });
    }
};
