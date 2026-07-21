<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master skills table for mentor specializations and gig categorization.
 * Pre-seeded with popular tech/business skills.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 120)->unique();
            $table->string('category', 50)->default('technology');
            $table->string('icon', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('slug', 'idx_skills_slug');
            $table->index('category', 'idx_skills_category');
            $table->index(['is_active', 'category'], 'idx_skills_active_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skills');
    }
};
