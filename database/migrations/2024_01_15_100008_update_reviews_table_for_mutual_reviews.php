<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Update reviews table for mutual (bidirectional) reviews between freelancers and mentors.
 * Adds reviewer_id and reviewee_id to reviews, and average_rating / total_reviews to users.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('reviews', 'reviewer_id')) {
            Schema::table('reviews', function (Blueprint $table) {
                // Drop old unique constraint on booking_id if it exists
                try {
                    $table->dropUnique(['booking_id']);
                } catch (\Throwable $e) {}

                $table->foreignId('reviewer_id')->after('booking_id')->nullable()->constrained('users')->onDelete('cascade');
                $table->foreignId('reviewee_id')->after('reviewer_id')->nullable()->constrained('users')->onDelete('cascade');

                // Compound unique constraint so each party can review a booking once
                $table->unique(['booking_id', 'reviewer_id'], 'idx_reviews_booking_reviewer_unique');
                $table->index('reviewer_id', 'idx_reviews_reviewer');
                $table->index('reviewee_id', 'idx_reviews_reviewee');
            });
        }

        if (!Schema::hasColumn('users', 'average_rating')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('average_rating', 3, 1)->default(0.0)->after('timezone');
                $table->unsignedInteger('total_reviews')->default(0)->after('average_rating');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('reviews', 'reviewer_id')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->dropForeign(['reviewer_id']);
                $table->dropForeign(['reviewee_id']);
                $table->dropUnique('idx_reviews_booking_reviewer_unique');
                $table->dropIndex('idx_reviews_reviewer');
                $table->dropIndex('idx_reviews_reviewee');
                $table->dropColumn(['reviewer_id', 'reviewee_id']);
                $table->unique('booking_id');
            });
        }

        if (Schema::hasColumn('users', 'average_rating')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['average_rating', 'total_reviews']);
            });
        }
    }
};
