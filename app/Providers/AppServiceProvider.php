<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        // Pagination custom (compact, responsif) menggantikan default Tailwind.
        Paginator::defaultView('pagination.custom');

        // Anti brute-force login: batasi per kombinasi identifier+IP dan per IP.
        RateLimiter::for('login', function (Request $request) {
            $identifier = Str::lower(\App\Support\Scalar::str($request->input('email')));

            return [
                Limit::perMinute(5)->by($identifier.'|'.$request->ip()),
                Limit::perMinute(30)->by($request->ip()),
            ];
        });

        // Formulir publik: cegah banjir pesanan palsu / pengurasan database.
        RateLimiter::for('pesan-publik', function (Request $request) {
            return [
                Limit::perMinute(6)->by($request->ip()),
                Limit::perDay(60)->by($request->ip()),
            ];
        });
    }
}
