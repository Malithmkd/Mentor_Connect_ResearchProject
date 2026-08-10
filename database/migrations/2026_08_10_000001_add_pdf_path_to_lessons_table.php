<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add pdf_path column to lessons table.
 * Mentors can attach one PDF (max 10 MB) per lesson as downloadable notes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('pdf_path', 500)->nullable()->after('video_url');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('pdf_path');
        });
    }
};
