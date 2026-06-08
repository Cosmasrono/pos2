<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = \App\Models\User::where('email', $credentials['email'])->first();
        $remember = $request->boolean('remember') || ($user && $user->isOwner());

        if (Auth::attempt($credentials, $remember)) {
            // Check if the system is deactivated
            if (!\App\Models\Setting::isSystemActive() && !Auth::user()->isOwner()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                return back()->withErrors([
                    'email' => 'The system is currently deactivated. Only the owner can log in.',
                ])->onlyInput('email');
            }

            // Capture guest session ID BEFORE regeneration
            $guestSessionId = $request->session()->getId();
            
            $request->session()->regenerate();

            // Merge guest cart items into user's cart
            app(CartService::class)->mergeGuestCart($guestSessionId, Auth::id());

            event(new Authenticated('web', Auth::user()));
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}