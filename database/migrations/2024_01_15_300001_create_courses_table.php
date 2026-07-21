<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Courses table.
 * A course is scoped to a single mentorship_relationship so it remains
 * private between that mentor–freelancer pair. The mentor_id column is
 * denormalised for simpler "show all my courses" queries.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('relationship_id')
                ->constrained('mentorship_relationships')
                ->onDelete('cascade');

            $table->foreignId('mentor_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->string('title');
            $table->text('description')->nullable();

            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');

            $table->timestamps();

            $table->index('relationship_id', 'idx_courses_relationship');
            $table->index('mentor_id', 'idx_courses_mentor');
            $table->index('status', 'idx_courses_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
