<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        if ($request->is('admin*', 'pendaftaran*')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        } elseif ($request->isMethod('GET') && $response->isSuccessful()
            && $request->routeIs('home', 'about', 'program', 'facilities', 'teachers', 'gallery', 'privacy', 'terms')) {
            $response->headers->set('Cache-Control', 'public, max-age=0, must-revalidate');
            $response->headers->set('Vercel-CDN-Cache-Control', 'public, s-maxage=30, stale-while-revalidate=60');
        } elseif ($request->isMethod('GET') && $response->isSuccessful()
            && $request->routeIs('sitemap', 'robots')) {
            $response->headers->set('Cache-Control', 'public, max-age=3600');
        }
        return $response;
    }
}
