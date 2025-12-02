<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class SecurityServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Global API rate limiting
        RateLimiter::for('api', function (Request $request) {
            return [
                Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()),
                Limit::perHour(1000)->by($request->user()?->id ?: $request->ip()),
            ];
        });

        // Authentication rate limiting (login, registration)
        RateLimiter::for('auth', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perHour(20)->by($request->ip()),
                Limit::perDay(100)->by($request->ip()),
            ];
        });

        // Partner registration specific rate limiting
        RateLimiter::for('partner-registration', function (Request $request) {
            return [
                Limit::perMinute(2)->by($request->ip()),
                Limit::perHour(5)->by($request->ip()),
                Limit::perDay(10)->by($request->ip()),
            ];
        });

        // Password reset rate limiting
        RateLimiter::for('password-reset', function (Request $request) {
            return [
                Limit::perMinute(1)->by($request->ip()),
                Limit::perHour(3)->by($request->ip()),
                Limit::perDay(10)->by($request->ip()),
            ];
        });

        // Contact form rate limiting
        RateLimiter::for('contact', function (Request $request) {
            return [
                Limit::perMinute(2)->by($request->ip()),
                Limit::perHour(10)->by($request->ip()),
                Limit::perDay(50)->by($request->ip()),
            ];
        });

        // File upload rate limiting
        RateLimiter::for('uploads', function (Request $request) {
            return [
                Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()),
                Limit::perHour(100)->by($request->user()?->id ?: $request->ip()),
            ];
        });

        // Search rate limiting
        RateLimiter::for('search', function (Request $request) {
            return [
                Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()),
                Limit::perHour(500)->by($request->user()?->id ?: $request->ip()),
            ];
        });

        // Admin panel rate limiting (more restrictive)
        RateLimiter::for('admin', function (Request $request) {
            return [
                Limit::perMinute(100)->by($request->user()?->id ?: $request->ip()),
                Limit::perHour(2000)->by($request->user()?->id ?: $request->ip()),
            ];
        });

        // Guest panel rate limiting (public access)
        RateLimiter::for('guest', function (Request $request) {
            return [
                Limit::perMinute(20)->by($request->ip()),
                Limit::perHour(200)->by($request->ip()),
                Limit::perDay(1000)->by($request->ip()),
            ];
        });

        // Strict rate limiting for suspicious activity
        RateLimiter::for('strict', function (Request $request) {
            return [
                Limit::perMinute(1)->by($request->ip()),
                Limit::perHour(5)->by($request->ip()),
                Limit::perDay(20)->by($request->ip()),
            ];
        });

        // Email verification rate limiting
        RateLimiter::for('email-verification', function (Request $request) {
            return [
                Limit::perMinute(1)->by($request->ip()),
                Limit::perHour(5)->by($request->ip()),
                Limit::perDay(20)->by($request->ip()),
            ];
        });

        // Profile update rate limiting
        RateLimiter::for('profile-update', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->user()?->id ?: $request->ip()),
                Limit::perHour(20)->by($request->user()?->id ?: $request->ip()),
            ];
        });

        // Appointment booking rate limiting
        RateLimiter::for('appointments', function (Request $request) {
            return [
                Limit::perMinute(3)->by($request->user()?->id ?: $request->ip()),
                Limit::perHour(15)->by($request->user()?->id ?: $request->ip()),
                Limit::perDay(50)->by($request->user()?->id ?: $request->ip()),
            ];
        });

        // Billing operations rate limiting
        RateLimiter::for('billing', function (Request $request) {
            return [
                Limit::perMinute(2)->by($request->user()?->id ?: $request->ip()),
                Limit::perHour(10)->by($request->user()?->id ?: $request->ip()),
                Limit::perDay(30)->by($request->user()?->id ?: $request->ip()),
            ];
        });
    }
}
