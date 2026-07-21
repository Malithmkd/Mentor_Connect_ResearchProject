<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mentor weekly recurring availability slots.
 * Day-based schedule with start/end times.
 * Used for scheduling and conflict detection.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availability_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->enum('day_of_week', [
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday'
            ]);
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->index(['mentor_id', 'day_of_week'], 'idx_slots_mentor_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_slots');
    }
};
