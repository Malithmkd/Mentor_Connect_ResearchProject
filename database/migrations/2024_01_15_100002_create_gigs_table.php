<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gigs represent mentor service offerings.
 * Each gig belongs to a mentor and can have multiple skills.
 * Status controls visibility: draft, published, paused, archived.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gigs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->string('title', 200);
            $table->string('slug', 250)->unique();
            $table->text('description');
            $table->text('what_to_expect')->nullable();
            $table->text('prerequisites')->nullable();
            $table->string('delivery_format', 50)->default('video_call');
            $table->enum('experience_level', [
                'beginner',
                'intermediate',
                'advanced',
                'all_levels'
            ])->default('all_levels');
            $table->integer('duration_minutes')->unsigned()->default(60);
            $table->decimal('price', 10, 2)->unsigned();
            $table->enum('status', [
                'draft',
                'published',
                'paused',
                'archived'
            ])->default('draft');
            $table->unsignedInteger('max_sessions_per_week')->default(10);
            $table->unsignedInteger('booking_lead_time_hours')->default(24);
            $table->unsignedInteger('total_bookings')->default(0);
            $table->unsignedInteger('total_views')->default(0);
            $table->decimal('average_rating', 2, 1)->default(0.0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('mentor_id', 'idx_gigs_mentor');
            $table->index('status', 'idx_gigs_status');
            $table->index('slug', 'idx_gigs_slug');
            $table->index('price', 'idx_gigs_price');
            $table->index(['status', 'experience_level'], 'idx_gigs_status_level');
            $table->index(['status', 'price'], 'idx_gigs_status_price');
            // Fulltext index only supported on MySQL/MariaDB
            if (DB::getDriverName() === 'mysql') {
                $table->fullText(['title', 'description'], 'idx_gigs_search');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gigs');
    }
};
