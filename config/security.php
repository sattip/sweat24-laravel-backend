<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Content Security Policy Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the Content Security Policy directives for your application.
    | Set to false to disable CSP entirely. Set to true to use defaults.
    | Or provide an array of directives for full customization.
    |
    */
    'csp' => [
        'enabled' => env('CSP_ENABLED', true),

        // Report-only mode sends violations to the report-uri without blocking
        // Useful for testing CSP before enforcing it
        'report_only' => env('CSP_REPORT_ONLY', false),

        // URL to send CSP violation reports to (optional)
        'report_uri' => env('CSP_REPORT_URI'),

        // CSP Directives
        'directives' => [
            'default-src' => ["'self'"],
            'script-src' => array_filter([
                "'self'",
                // Only include unsafe-inline/unsafe-eval if explicitly enabled
                // These are security risks but may be needed for some frontends
                env('CSP_ALLOW_UNSAFE_INLINE', false) ? "'unsafe-inline'" : null,
                env('CSP_ALLOW_UNSAFE_EVAL', false) ? "'unsafe-eval'" : null,
            ]),
            'style-src' => array_filter([
                "'self'",
                // unsafe-inline is often needed for inline styles and many CSS frameworks
                env('CSP_ALLOW_UNSAFE_INLINE_STYLES', true) ? "'unsafe-inline'" : null,
            ]),
            'img-src' => ["'self'", 'data:', 'https:'],
            'font-src' => ["'self'", 'data:'],
            'connect-src' => ["'self'", 'wss:', 'https:'],
            'frame-ancestors' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'object-src' => ["'none'"],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | HSTS (HTTP Strict Transport Security)
    |--------------------------------------------------------------------------
    */
    'hsts' => [
        'enabled' => env('HSTS_ENABLED', true),
        'max_age' => env('HSTS_MAX_AGE', 31536000), // 1 year in seconds
        'include_subdomains' => env('HSTS_INCLUDE_SUBDOMAINS', true),
        'preload' => env('HSTS_PRELOAD', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions Policy
    |--------------------------------------------------------------------------
    */
    'permissions_policy' => [
        'camera' => [],
        'microphone' => [],
        'geolocation' => [],
        'payment' => [],
    ],
];
