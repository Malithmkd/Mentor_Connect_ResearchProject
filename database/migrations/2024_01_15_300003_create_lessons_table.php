<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lessons table.
 * Each lesson belongs to a module and contains the actual content.
 * Content is stored as longText (raw HTML/Markdown). video_url is optional.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();

            $table->foreignId('module_id')
                ->constrained('course_modules')
                ->onDelete('cascade');

            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('video_url', 500)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['module_id', 'sort_order'], 'idx_lessons_module_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
