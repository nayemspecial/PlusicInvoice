<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\Tenants\Invitation;
use App\Models\Tenants\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Reached only via a Laravel-signed URL (see 'signed' middleware on these routes in
 * routes/web.php) — the signature itself proves the link came from our own
 * URL::temporarySignedRoute() call and hasn't expired, so we don't need a separate
 * hashed token column the way password reset does.
 */
class AcceptInvitationController extends Controller
{
    public function create(Invitation $invitation): Response|RedirectResponse
    {
        if ($invitation->isAccepted()) {
            return redirect()->route('login')->with('status', 'This invitation has already been used.');
        }

        return Inertia::render('Team/AcceptInvitation', [
            'invitation' => $invitation->only(['id', 'email', 'role']),
        ]);
    }

    public function store(Request $request, Invitation $invitation): RedirectResponse
    {
        if ($invitation->isAccepted()) {
            return redirect()->route('login')->with('status', 'This invitation has already been used.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $invitation->email,
            'password' => Hash::make($validated['password']),
            'role' => $invitation->role,
        ]);

        $invitation->update(['accepted_at' => now()]);

        event(new Registered($user));

        Auth::guard('tenant')->login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Welcome to the team!');
    }
}
