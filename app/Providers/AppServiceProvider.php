<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;
use App\Models\Order;
use App\Observers\OrderObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Set default password rules
        Password::defaults(function () {
            $rule = Password::min(8);

            return $this->app->isProduction()
                ? $rule->mixedCase()->numbers()->symbols()->uncompromised()
                : $rule;
        });

        // Register model observers
        Order::observe(OrderObserver::class);

        // Configure rate limiters
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // General API rate limiter - 60 requests per minute
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limiter for authentication endpoints - 30 attempts per minute
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip())->response(function () {
                return response()->json([
                    'message' => 'Too many login attempts. Please try again later.',
                ], 429);
            });
        });

        // Rate limiter for password reset - 10 attempts per minute
        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip())->response(function () {
                return response()->json([
                    'message' => 'Too many password reset attempts. Please try again later.',
                ], 429);
            });
        });

        // Rate limiter for registration - 15 attempts per minute
        RateLimiter::for('registration', function (Request $request) {
            return Limit::perMinute(15)->by($request->ip())->response(function () {
                return response()->json([
                    'message' => 'Too many registration attempts. Please try again later.',
                ], 429);
            });
        });

        // Admin endpoints - higher limit for authenticated admins
        RateLimiter::for('admin', function (Request $request) {
            return $request->user()?->role === 'admin'
                ? Limit::perMinute(120)->by($request->user()->id)
                : Limit::perMinute(60)->by($request->ip());
        });
    }
}
