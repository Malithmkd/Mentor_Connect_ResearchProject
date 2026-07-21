<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Users table with role-based access control.
 * Role enum: freelancer (default), mentor, admin.
 * Email verification required before booking sessions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 255)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 255);
            $table->enum('role', ['freelancer', 'mentor', 'admin'])->default('freelancer');
            $table->string('avatar', 255)->nullable();
            $table->text('bio')->nullable();
            $table->string('location', 100)->nullable();
            $table->string('timezone', 50)->default('UTC');
            $table->decimal('average_rating', 3, 1)->default(0.0);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for RBAC and common queries
            $table->index('role', 'idx_users_role');
            $table->index(['role', 'is_active'], 'idx_users_role_active');
            $table->index('email', 'idx_users_email');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 255)->primary();
            $table->string('token', 255);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id', 128)->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
