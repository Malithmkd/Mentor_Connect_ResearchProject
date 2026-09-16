<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        $areas  = ['auth', 'bookings', 'gigs', 'approvals', 'lms', 'system'];
        $events = [
            'auth.login', 'auth.logout', 'user.registered',
            'user.approved', 'user.rejected',
            'booking.created', 'booking.status_changed',
            'gig.created', 'gig.updated', 'gig.deleted',
        ];

        $user = User::factory();

        return [
            'user_id'        => $user,
            'auditable_type' => User::class,
            'auditable_id'   => $user,
            'event'          => $this->faker->randomElement($events),
            'area'           => $this->faker->randomElement($areas),
            'description'    => $this->faker->sentence(6),
            'old_values'     => null,
            'new_values'     => null,
            'ip_address'     => $this->faker->ipv4(),
            'user_agent'     => $this->faker->userAgent(),
        ];
    }

    /** Log for a specific event */
    public function forEvent(string $event, string $area = 'system'): static
    {
        return $this->state(fn () => [
            'event' => $event,
            'area'  => $area,
        ]);
    }

    /** Log with old and new values */
    public function withDiff(array $old, array $new): static
    {
        return $this->state(fn () => [
            'old_values' => $old,
            'new_values' => $new,
        ]);
    }
}
