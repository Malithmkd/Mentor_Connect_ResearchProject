<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add 'booking_note_reply' to the notifications.type ENUM.
 * MySQL requires re-declaring the full ENUM to add a new value.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `notifications` MODIFY `type` ENUM(
            'booking_requested',
            'booking_accepted',
            'booking_rejected',
            'booking_scheduled',
            'booking_completed',
            'booking_cancelled',
            'booking_note_reply',
            'review_received',
            'mentor_verified',
            'system'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `notifications` MODIFY `type` ENUM(
            'booking_requested',
            'booking_accepted',
            'booking_rejected',
            'booking_scheduled',
            'booking_completed',
            'booking_cancelled',
            'review_received',
            'mentor_verified',
            'system'
        ) NOT NULL");
    }
};
