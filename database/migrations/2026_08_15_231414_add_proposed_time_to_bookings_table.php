<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add proposed_time to bookings so time-based accept/complete rules can be enforced.
     * proposed_time is nullable — existing bookings without a time are treated as unrestricted.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->time('proposed_time')->nullable()->after('proposed_date')
                ->comment('Freelancer\'s preferred session start time (HH:MM)');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('proposed_time');
        });
    }
};
