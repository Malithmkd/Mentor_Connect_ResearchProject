<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the skill_user pivot table for user skill preferences.
 * Many-to-many between users and skills.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_user', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('skill_id')
                ->constrained('skills')
                ->onDelete('cascade');
            $table->primary(['user_id', 'skill_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_user');
    }
};
