<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extended profile for mentors with professional details.
 * One-to-one with users table where role = 'mentor'.
 * Tracks verification status for admin approval workflow.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mentor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->onDelete('cascade');
            $table->string('headline', 200)->nullable();
            $table->text('about')->nullable();
            $table->string('company', 100)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('linkedin_url', 255)->nullable();
            $table->string('github_url', 255)->nullable();
            $table->integer('years_experience')->unsigned()->default(0);
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->enum('availability', [
                'full_time',
                'part_time',
                'weekends_only',
                'flexible'
            ])->default('flexible');
            $table->enum('verification_status', [
                'pending',
                'verified',
                'rejected'
            ])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('average_rating', 2, 1)->default(0.0);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->unsignedInteger('total_sessions')->default(0);
            $table->unsignedInteger('total_earnings_cents')->default(0);
            $table->timestamps();

            $table->index('verification_status', 'idx_mentor_profiles_verification');
            $table->index('availability', 'idx_mentor_profiles_availability');
            $table->index(['average_rating', 'total_reviews'], 'idx_mentor_profiles_rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentor_profiles');
    }
};
