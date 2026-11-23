<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan respons (blue-team hardening).
 *
 * Dipasang sebagai middleware GLOBAL supaya halaman error (404/419/500) juga
 * membawa header; middleware grup `web` berjalan setelah exception sehingga
 * halaman error tidak pernah mencapainya.
 *
 * CSP: script memakai nonce (bukan 'unsafe-inline'); style masih
 * 'unsafe-inline' karena view memakai atribut style= yang tidak bisa di-nonce.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Str::random(24);

        // Nonce harus tersedia sebelum view dirender.
        View::share('cspNonce', $nonce);
        $request->attributes->set('csp_nonce', $nonce);

        $response = $next($request);

        $headers = [
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'same-origin',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Content-Security-Policy' => "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'nonce-{$nonce}'; font-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'",
        ];

        // HSTS hanya bermakna di HTTPS; jangan dikirim lewat HTTP lokal.
        if ($request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        // Versi runtime tidak perlu diumumkan ke klien.
        $response->headers->remove('X-Powered-By');

        return $response;
    }
}
