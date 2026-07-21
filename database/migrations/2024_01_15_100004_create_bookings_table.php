<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bookings table: the core session booking state machine.
 * Tracks the full lifecycle from request to completion/review.
 * Status transitions are enforced at application level via SessionController.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freelancer_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('mentor_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('gig_id')
                ->constrained('gigs')
                ->onDelete('cascade');
            $table->string('booking_reference', 20)->unique();
            $table->enum('status', [
                'draft',
                'requested',
                'accepted',
                'rejected',
                'scheduled',
                'completed',
                'cancelled',
                'reviewed'
            ])->default('draft');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('freelancer_note')->nullable();
            $table->text('mentor_note')->nullable();
            $table->date('proposed_date')->nullable(); // Freelancer's preferred date for the session
            $table->decimal('price_paid', 10, 2);
            $table->string('meeting_link', 255)->nullable();
            $table->string('meeting_provider', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('freelancer_id', 'idx_bookings_freelancer');
            $table->index('mentor_id', 'idx_bookings_mentor');
            $table->index('gig_id', 'idx_bookings_gig');
            $table->index('status', 'idx_bookings_status');
            $table->index('booking_reference', 'idx_bookings_reference');
            $table->index(['freelancer_id', 'status'], 'idx_bookings_freelancer_status');
            $table->index(['mentor_id', 'status'], 'idx_bookings_mentor_status');
            $table->index('requested_at', 'idx_bookings_requested');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
