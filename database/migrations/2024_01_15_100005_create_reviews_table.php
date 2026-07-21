<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reviews table: post-session feedback from freelancers.
 * One-to-one with bookings (completed status only).
 * Triggers recalculation of mentor and gig average ratings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->onDelete('cascade');
            $table->foreignId('reviewer_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('reviewee_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('freelancer_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('mentor_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('gig_id')
                ->constrained('gigs')
                ->onDelete('cascade');
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->unique(['booking_id', 'reviewer_id'], 'idx_reviews_booking_reviewer_unique');
            $table->index('reviewer_id', 'idx_reviews_reviewer');
            $table->index('reviewee_id', 'idx_reviews_reviewee');
            $table->index('mentor_id', 'idx_reviews_mentor');
            $table->index('gig_id', 'idx_reviews_gig');
            $table->index('rating', 'idx_reviews_rating');
            $table->index(['mentor_id', 'rating'], 'idx_reviews_mentor_rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
