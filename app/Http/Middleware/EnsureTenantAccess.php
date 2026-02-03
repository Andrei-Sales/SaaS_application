<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to ensure users have a valid company association.
 *
 * This middleware checks that authenticated users belong to a company
 * before allowing access to tenant-specific resources.
 */
class EnsureTenantAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();

            // Ensure user has a company
            if (!$user->company_id) {
                abort(403, 'You must belong to a company to access this resource.');
            }

            // Ensure the company exists
            if (!$user->company) {
                abort(403, 'Your company account is invalid.');
            }
        }

        return $next($request);
    }
}
