<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DebugMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only allow debug endpoints in local development environment
        if (config('app.env') !== 'local') {
            Log::warning('Debug endpoint accessed in non-local environment', [
                'environment' => config('app.env'),
                'url' => $request->url(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'user_id' => auth()->id(),
            ]);
            
            abort(404, 'Debug endpoints are only available in development mode');
        }

        // Log debug endpoint usage
        Log::info('Debug endpoint accessed', [
            'url' => $request->url(),
            'method' => $request->method(),
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }
}