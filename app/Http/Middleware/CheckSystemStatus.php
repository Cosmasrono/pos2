<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Setting;

class CheckSystemStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow the owner to always bypass the check
        if (auth()->check() && auth()->user()->isOwner()) {
            return $next($request);
        }

        // Check if the system is deactivated
        if (!Setting::isSystemActive()) {
            // Check if it's an AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'System is temporarily unavailable.'], 503);
            }

            // Always allow the login page and logout route even if deactivated (so owner can login)
            if ($request->is('login') || $request->is('logout') || $request->routeIs('login') || $request->routeIs('logout')) {
                return $next($request);
            }

            // Redirect others to a "System Unavailable" page if not already there
            if (!$request->routeIs('system.unavailable')) {
                return redirect()->route('system.unavailable');
            }
        }

        return $next($request);
    }
}
