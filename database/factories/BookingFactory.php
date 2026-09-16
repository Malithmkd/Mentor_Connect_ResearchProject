<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Gig;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $gig        = Gig::factory()->published()->create();
        $freelancer = User::factory()->freelancer()->create();

        return [
            'freelancer_id'     => $freelancer->id,
            'mentor_id'         => $gig->mentor_id,
            'gig_id'            => $gig->id,
            'booking_reference' => 'MC-' . strtoupper(uniqid()),
            'status'            => BookingStatus::REQUESTED,
            'requested_at'      => now(),
            'price_paid'        => $gig->price,
            'freelancer_note'   => $this->faker->sentence(10),
            'proposed_date'     => now()->addDays(7)->toDateString(),
            'proposed_time'     => '10:00',
        ];
    }

    /** Booking in requested state */
    public function requested(): static
    {
        return $this->state(fn () => [
            'status'       => BookingStatus::REQUESTED,
            'requested_at' => now(),
        ]);
    }

    /** Booking in accepted state */
    public function accepted(): static
    {
        return $this->state(fn () => [
            'status'       => BookingStatus::ACCEPTED,
            'requested_at' => now()->subHours(2),
            'responded_at' => now()->subHour(),
        ]);
    }

    /** Booking in scheduled state */
    public function scheduled(): static
    {
        return $this->state(fn () => [
            'status'        => BookingStatus::SCHEDULED,
            'requested_at'  => now()->subDays(2),
            'responded_at'  => now()->subDay(),
            'scheduled_at'  => now()->subHours(1),
            'proposed_date' => now()->subDays(1)->toDateString(),
            'proposed_time' => '08:00',
        ]);
    }

    /** Booking in completed state */
    public function completed(): static
    {
        return $this->state(fn () => [
            'status'        => BookingStatus::COMPLETED,
            'requested_at'  => now()->subDays(5),
            'responded_at'  => now()->subDays(4),
            'scheduled_at'  => now()->subDays(3),
            'completed_at'  => now()->subDay(),
            'proposed_date' => now()->subDays(2)->toDateString(),
            'proposed_time' => '10:00',
        ]);
    }

    /** Booking in reviewed state */
    public function reviewed(): static
    {
        return $this->state(fn () => [
            'status'        => BookingStatus::REVIEWED,
            'requested_at'  => now()->subDays(7),
            'responded_at'  => now()->subDays(6),
            'scheduled_at'  => now()->subDays(5),
            'completed_at'  => now()->subDays(3),
            'proposed_date' => now()->subDays(4)->toDateString(),
            'proposed_time' => '10:00',
        ]);
    }

    /** Booking with a proposed time in the past (acceptance expired) */
    public function expired(): static
    {
        return $this->state(fn () => [
            'status'        => BookingStatus::REQUESTED,
            'proposed_date' => now()->subDay()->toDateString(),
            'proposed_time' => '08:00',
        ]);
    }

    /** Booking with specific freelancer and mentor */
    public function between(User $freelancer, User $mentor, Gig $gig): static
    {
        return $this->state(fn () => [
            'freelancer_id' => $freelancer->id,
            'mentor_id'     => $mentor->id,
            'gig_id'        => $gig->id,
            'price_paid'    => $gig->price,
        ]);
    }
}
