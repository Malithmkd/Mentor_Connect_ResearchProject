<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds skill onboarding flag to users table.
 * Used to detect first-login and show the skill selection prompt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('skills_onboarded')->default(false)->after('account_status')
                ->comment('True after user has selected preferred skills on first login');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('skills_onboarded');
        });
    }
};
