<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * In-app notifications table.
 * Separate from Laravel's default notifications (which use JSON).
 * Provides structured notification storage with read tracking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->enum('type', [
                'booking_requested',
                'booking_accepted',
                'booking_rejected',
                'booking_scheduled',
                'booking_completed',
                'booking_cancelled',
                'review_received',
                'mentor_verified',
                'system'
            ]);
            $table->string('title', 200);
            $table->text('message');
            $table->string('action_url', 500)->nullable();
            $table->string('action_text', 50)->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->foreignId('related_booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'is_read'], 'idx_notifications_user_unread');
            $table->index('type', 'idx_notifications_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
