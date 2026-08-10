<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('booking_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('note');
            $table->timestamps();
        });

        // Migrate existing notes
        $bookings = DB::table('bookings')->whereNotNull('freelancer_note')->orWhereNotNull('mentor_note')->get();
        foreach ($bookings as $booking) {
            if ($booking->freelancer_note) {
                DB::table('booking_notes')->insert([
                    'booking_id' => $booking->id,
                    'user_id' => $booking->freelancer_id,
                    'note' => $booking->freelancer_note,
                    'created_at' => $booking->created_at,
                    'updated_at' => $booking->created_at,
                ]);
            }
            if ($booking->mentor_note) {
                DB::table('booking_notes')->insert([
                    'booking_id' => $booking->id,
                    'user_id' => $booking->mentor_id,
                    'note' => $booking->mentor_note,
                    // If we have responded_at, use that, else created_at
                    'created_at' => $booking->responded_at ?? $booking->created_at,
                    'updated_at' => $booking->responded_at ?? $booking->created_at,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_notes');
    }
};
