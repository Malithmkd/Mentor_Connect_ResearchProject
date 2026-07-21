<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Course modules table.
 * Ordered sections (chapters) within a course. sort_order is managed
 * manually — no complex drag-and-drop in v1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_modules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_id')
                ->constrained('courses')
                ->onDelete('cascade');

            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['course_id', 'sort_order'], 'idx_modules_course_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_modules');
    }
};
