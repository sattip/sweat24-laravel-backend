<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Security headers to apply to all responses.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Enable XSS protection in older browsers
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy (replaces Feature-Policy)
        $response->headers->set('Permissions-Policy', $this->buildPermissionsPolicy());

        // Content Security Policy - configurable via config/security.php
        if (config('app.env') === 'production' && config('security.csp.enabled', true)) {
            $cspHeader = config('security.csp.report_only', false)
                ? 'Content-Security-Policy-Report-Only'
                : 'Content-Security-Policy';

            $response->headers->set($cspHeader, $this->buildContentSecurityPolicy());
        }

        // HSTS - only in production with HTTPS
        if (config('app.env') === 'production' && $request->secure() && config('security.hsts.enabled', true)) {
            $response->headers->set('Strict-Transport-Security', $this->buildHstsHeader());
        }

        // Remove server identification headers
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }

    /**
     * Build the Content Security Policy header value from config.
     */
    protected function buildContentSecurityPolicy(): string
    {
        $directives = config('security.csp.directives', []);
        $parts = [];

        foreach ($directives as $directive => $sources) {
            if (!empty($sources)) {
                $parts[] = $directive . ' ' . implode(' ', $sources);
            }
        }

        // Add report-uri if configured
        if ($reportUri = config('security.csp.report_uri')) {
            $parts[] = 'report-uri ' . $reportUri;
        }

        return implode('; ', $parts);
    }

    /**
     * Build the HSTS header value from config.
     */
    protected function buildHstsHeader(): string
    {
        $maxAge = config('security.hsts.max_age', 31536000);
        $header = "max-age={$maxAge}";

        if (config('security.hsts.include_subdomains', true)) {
            $header .= '; includeSubDomains';
        }

        if (config('security.hsts.preload', false)) {
            $header .= '; preload';
        }

        return $header;
    }

    /**
     * Build the Permissions Policy header value from config.
     */
    protected function buildPermissionsPolicy(): string
    {
        $policies = config('security.permissions_policy', [
            'camera' => [],
            'microphone' => [],
            'geolocation' => [],
            'payment' => [],
        ]);

        $parts = [];
        foreach ($policies as $feature => $allowlist) {
            if (empty($allowlist)) {
                $parts[] = "{$feature}=()";
            } else {
                $quoted = array_map(fn($origin) => "\"{$origin}\"", $allowlist);
                $parts[] = "{$feature}=(" . implode(' ', $quoted) . ')';
            }
        }

        return implode(', ', $parts);
    }
}
