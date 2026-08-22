<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gigs', function (Blueprint $table) {
            // Nullable path to an uploaded cover image stored in storage/app/public/gigs/
            $table->string('cover_image')->nullable()->after('prerequisites');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gigs', function (Blueprint $table) {
            $table->dropColumn('cover_image');
        });
    }
};
