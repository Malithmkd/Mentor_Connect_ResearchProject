<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Gig;
use App\Models\User;
use App\Policies\BookingPolicy;
use App\Policies\GigPolicy;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * AppServiceProvider
 * Registers role-based Gates and Blade directives for RBAC.
 * Also registers Policies for resource-level authorization.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Policy mapping for resource-level authorization.
     */
    protected $policies = [
        Gig::class => GigPolicy::class,
        Booking::class => BookingPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerGates();
        $this->registerBladeDirectives();
    }

    /**
     * Register role-based Gates for authorization checks.
     */
    private function registerGates(): void
    {
        // Generic role gates
        Gate::define('is-freelancer', fn(User $user) => $user->isFreelancer());
        Gate::define('is-mentor', fn(User $user) => $user->isMentor());
        Gate::define('is-admin', fn(User $user) => $user->isAdmin());

        // Feature-level gates
        Gate::define('browse-gigs', fn(User $user) => true); // All roles can browse

        Gate::define('book-session', fn(User $user) =>
            $user->isFreelancer() && $user->hasVerifiedEmail()
        );

        Gate::define('create-gig', fn(User $user) =>
            $user->isMentor() && $user->mentorProfile?->verification_status === 'verified'
        );

        Gate::define('manage-gigs', fn(User $user) => $user->isMentor());

        Gate::define('respond-to-booking', fn(User $user, Booking $booking) =>
            $user->isMentor() && $user->id === $booking->mentor_id
        );

        Gate::define('leave-review', fn(User $user, Booking $booking) =>
            $booking->canBeReviewedBy($user)
        );

        Gate::define('cancel-booking', fn(User $user, Booking $booking) =>
            ($user->id === $booking->freelancer_id || $user->id === $booking->mentor_id)
            && $booking->status->canCancel()
        );

        // Admin gates
        Gate::define('manage-users', fn(User $user) => $user->isAdmin());
        Gate::define('verify-mentors', fn(User $user) => $user->isAdmin());
        Gate::define('view-dashboard', fn(User $user) => $user->isAdmin());
    }

    /**
     * Register Blade directives for role-based UI rendering.
     */
    private function registerBladeDirectives(): void
    {
        // @role('mentor') ... @endrole
        Blade::directive('role', function ($expression) {
            return "<?php if(auth()->check() && auth()->user()->role->value === {$expression}): ?>";
        });

        Blade::directive('endrole', function () {
            return '<?php endif; ?>';
        });

        // @anyrole('mentor','admin') ... @endanyrole
        Blade::directive('anyrole', function ($expression) {
            $roles = array_map('trim', explode(',', $expression));
            $roleChecks = implode(' || ', array_map(
                fn($r) => "auth()->user()->role->value === {$r}",
                $roles
            ));
            return "<?php if(auth()->check() && ({$roleChecks})): ?>";
        });

        Blade::directive('endanyrole', function () {
            return '<?php endif; ?>';
        });
    }
}
