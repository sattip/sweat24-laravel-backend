<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to restrict access to admin users only.
 * This is a convenience wrapper around CheckRole middleware for admin-only routes.
 */
class AdminMiddleware
{
    public function __construct(
        protected CheckRole $checkRole
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $this->checkRole->handle($request, $next, 'admin');
    }
}