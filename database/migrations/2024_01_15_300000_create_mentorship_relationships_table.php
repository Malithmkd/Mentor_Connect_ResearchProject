<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mentorship Relationships table.
 * Created when a freelancer invites a mentor to continue long-term
 * after a completed booking session. Stores agreed payment terms as data
 * (no gateway integration required).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mentorship_relationships', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->onDelete('cascade');

            $table->foreignId('mentor_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('freelancer_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->enum('status', ['pending', 'accepted', 'declined', 'ended'])
                ->default('pending');

            // Agreed payment terms (stored as data only — no gateway)
            $table->enum('payment_type', ['hourly', 'monthly', 'per_module', 'custom'])->nullable();
            $table->decimal('payment_amount', 10, 2)->nullable();
            $table->text('payment_notes')->nullable();

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('mentor_id', 'idx_mr_mentor');
            $table->index('freelancer_id', 'idx_mr_freelancer');
            $table->index('status', 'idx_mr_status');
            $table->index('booking_id', 'idx_mr_booking');
            $table->unique(['booking_id'], 'uq_mr_booking'); // one relationship per booking
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentorship_relationships');
    }
};
