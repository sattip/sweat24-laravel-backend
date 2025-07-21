<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Map roles to membership types
        $userRole = $this->mapMembershipTypeToRole($request->user()->membership_type);
        
        if (!in_array($userRole, $roles)) {
            return response()->json(['message' => 'Forbidden. You do not have permission to access this resource.'], 403);
        }

        return $next($request);
    }

    /**
     * Map membership type to role
     */
    private function mapMembershipTypeToRole(?string $membershipType): string
    {
        if ($membershipType === 'Admin') {
            return 'admin';
        }
        
        return 'user'; // Default role for non-admin users
    }
}