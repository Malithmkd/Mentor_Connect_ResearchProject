<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RequireRole Middleware
 * Route-level RBAC enforcement.
 * Rejects requests if authenticated user does not have the required role.
 * Usage: Route::middleware(['auth', 'role:mentor'])
 * Multiple roles: Route::middleware(['auth', 'role:mentor,admin'])
 */
class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $allowedRoles = array_map(
            fn(string $role) => UserRole::from($role),
            $roles
        );

        if (!in_array($user->role, $allowedRoles, true)) {
            // Log unauthorized access attempt for security monitoring
            logger()->warning('RBAC violation', [
                'user_id' => $user->id,
                'user_role' => $user->role->value,
                'required_roles' => $roles,
                'url' => $request->url(),
                'ip' => $request->ip(),
            ]);

            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
