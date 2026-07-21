<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lesson progress table.
 * One row per (enrollment, lesson) pair. completed_at null = not started/done.
 * freelancer_id is denormalised for direct queries without joining enrollments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enrollment_id')
                ->constrained('enrollments')
                ->onDelete('cascade');

            $table->foreignId('lesson_id')
                ->constrained('lessons')
                ->onDelete('cascade');

            $table->foreignId('freelancer_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // One progress record per lesson per enrollment
            $table->unique(['enrollment_id', 'lesson_id'], 'uq_progress_enrollment_lesson');

            $table->index('freelancer_id', 'idx_progress_freelancer');
            $table->index('lesson_id', 'idx_progress_lesson');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_progress');
    }
};
