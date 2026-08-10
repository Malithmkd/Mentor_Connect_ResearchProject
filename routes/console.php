<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
use App\Models\Booking;
use App\Enums\BookingStatus;
use App\Models\Notification;

Schedule::call(function () {
    $expiredBookings = Booking::where('status', BookingStatus::REQUESTED)
        ->whereNotNull('proposed_date')
        ->whereDate('proposed_date', '<', now()->toDateString())
        ->get();

    foreach ($expiredBookings as $booking) {
        $booking->transitionTo(BookingStatus::CANCELLED, 'Automatically cancelled because the requested date passed without mentor response.');
        
        // Notify freelancer
        Notification::create([
            'user_id' => $booking->freelancer_id,
            'type' => 'booking_cancelled',
            'title' => 'Session Request Expired',
            'message' => "Your request for '{$booking->gig->title}' expired because the mentor didn't respond by the requested date.",
            'related_booking_id' => $booking->id,
        ]);

        // Notify mentor
        Notification::create([
            'user_id' => $booking->mentor_id,
            'type' => 'booking_cancelled',
            'title' => 'Session Request Expired',
            'message' => "A request for '{$booking->gig->title}' expired because you didn't respond by the requested date.",
            'related_booking_id' => $booking->id,
        ]);
    }
})->daily();
