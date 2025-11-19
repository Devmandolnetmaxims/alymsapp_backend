<?php

namespace App\Http\Middleware;

use Closure;
use GeoIP;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTimezone
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get user's location data based on IP
        $location = GeoIP::getLocation($request->ip());

        // Set application timezone
        if ($location && isset($location['timezone'])) {
            config(['app.timezone' => $location['timezone']]);
            date_default_timezone_set($location['timezone']);
        }
        return $next($request);
    }
}
