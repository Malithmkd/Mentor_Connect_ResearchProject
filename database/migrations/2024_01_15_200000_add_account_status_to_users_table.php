<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replace email-based verification with admin approval workflow.
 * Adds:
 *   - account_status  ENUM('pending','approved','rejected')  default 'pending'
 *   - rejection_reason  TEXT  nullable  (admin can note why they rejected)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('account_status', ['pending', 'approved', 'rejected'])
                  ->default('pending')
                  ->after('is_active');

            $table->text('rejection_reason')
                  ->nullable()
                  ->after('account_status');
        });

        // Existing users (admins + seeded data) should be immediately approved
        // so we don't lock out existing accounts after migration.
        DB::table('users')->update(['account_status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['account_status', 'rejection_reason']);
        });
    }
};
