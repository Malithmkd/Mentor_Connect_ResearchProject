<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot table: gigs to skills (many-to-many).
 * Enables filtering gigs by multiple skills simultaneously.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gig_skill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gig_id')
                ->constrained('gigs')
                ->onDelete('cascade');
            $table->foreignId('skill_id')
                ->constrained('skills')
                ->onDelete('cascade');
            $table->timestamps();

            $table->unique(['gig_id', 'skill_id'], 'uniq_gig_skill');
            $table->index('skill_id', 'idx_gig_skill_skill');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gig_skill');
    }
};
