<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenants\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // 'unique:tenant.users,email' — the 'tenant.' prefix tells Laravel's unique
            // rule to check against the 'tenant' CONNECTION's users table, not the
            // central one. Without this prefix it would wrongly check central users.
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:tenant.users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // The very first person to register in a brand-new tenant database becomes the
        // owner automatically — everyone after that is a low-privilege 'viewer' by
        // default (Phase 5 role-based access will let an owner promote teammates).
        $isFirstUser = User::count() === 0;

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $isFirstUser ? 'owner' : 'viewer',
        ]);

        event(new Registered($user));

        Auth::guard('tenant')->login($user);

        // Prevents session fixation: a new session ID is issued now that the user is
        // authenticated, so an attacker who knew the pre-login session ID gains nothing.
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
