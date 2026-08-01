<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds mentorship duration and expiry tracking to allow:
 * - Displaying "N days remaining" on the freelancer LMS dashboard
 * - Renewal flow with 1/3/6/12 month options
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mentorship_relationships', function (Blueprint $table) {
            $table->unsignedTinyInteger('duration_months')->nullable()->after('ended_at')
                ->comment('Chosen duration: 1, 3, 6, or 12 months');
            $table->timestamp('expires_at')->nullable()->after('duration_months')
                ->comment('Computed from accepted_at + duration_months');
        });
    }

    public function down(): void
    {
        Schema::table('mentorship_relationships', function (Blueprint $table) {
            $table->dropColumn(['duration_months', 'expires_at']);
        });
    }
};
