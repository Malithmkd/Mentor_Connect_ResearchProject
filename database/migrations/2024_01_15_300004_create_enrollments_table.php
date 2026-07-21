<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enrollments table.
 * Links a freelancer to a specific course within a mentorship relationship.
 * Auto-created when the mentor publishes a course (see CourseController::publish).
 * completed_at is set automatically when all lessons are marked done.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_id')
                ->constrained('courses')
                ->onDelete('cascade');

            $table->foreignId('relationship_id')
                ->constrained('mentorship_relationships')
                ->onDelete('cascade');

            $table->foreignId('freelancer_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->timestamp('enrolled_at');
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // A freelancer can only be enrolled once per course
            $table->unique(['course_id', 'freelancer_id'], 'uq_enrollment_course_freelancer');

            $table->index('freelancer_id', 'idx_enrollments_freelancer');
            $table->index('relationship_id', 'idx_enrollments_relationship');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
