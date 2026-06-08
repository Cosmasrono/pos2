<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $superAdminCount = User::whereHas('roles', function($q) {
            $q->where('name', 'super_admin');
        })->count();

        return view('auth.register', [
            'registrationDisabled' => $superAdminCount >= 2
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $superAdminCount = User::whereHas('roles', function($q) {
            $q->where('name', 'super_admin');
        })->count();

        if ($superAdminCount >= 2) {
            return redirect()->route('login')->withErrors([
                'email' => 'Registration is currently disabled because the maximum number of Super Admin accounts has been reached.'
            ]);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Assign role
        $role = \App\Models\Role::where('name', $request->role)->first();
        if ($role) {
            $user->roles()->attach($role->id);
        }

        event(new Registered($user));

        return redirect()->route('login')->with('success', 'Registration successful! Please sign in with your new account.');
    }
}
