<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Allow owners to always access the system (to fix subscription if needed)
        if (auth()->check() && auth()->user()->isOwner()) {
            return $next($request);
        }

        // 2. Allow access to subscription expired page, login, and logout
        $allowedRoutes = ['subscription.expired', 'login', 'logout'];
        if (in_array($request->route()->getName(), $allowedRoutes)) {
            return $next($request);
        }

        // 3. Check if subscription is active
        if (!\App\Models\Setting::isSubscriptionActive()) {
            return redirect()->route('subscription.expired');
        }

        return $next($request);
    }
}
