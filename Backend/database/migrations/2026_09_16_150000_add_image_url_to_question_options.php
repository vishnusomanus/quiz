<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('question_options', 'image_url')) {
            return;
        }

        Schema::table('question_options', function (Blueprint $table): void {
            $table->string('image_url', 2048)->nullable()->after('label');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('question_options', 'image_url')) {
            return;
        }

        Schema::table('question_options', function (Blueprint $table): void {
            $table->dropColumn('image_url');
        });
    }
};
