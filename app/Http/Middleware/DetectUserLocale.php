<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectUserLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $country = strtoupper((string) (
            $request->header('CF-IPCountry')
            ?? $request->header('X-Country-Code')
            ?? $request->header('X-Vercel-IP-Country')
        ));

        $isJapanese = $country === 'JP'
            || str_starts_with(strtolower((string) $request->header('Accept-Language')), 'ja');

        app()->setLocale($isJapanese ? 'ja' : config('app.locale', 'en'));

        return $next($request);
    }
}
