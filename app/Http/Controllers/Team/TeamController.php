<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Mail\TeamInvitationMail;
use App\Models\Tenants\Invitation;
use App\Models\Tenants\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Every action here is behind 'role:owner' route middleware (see routes/web.php) —
 * this controller doesn't re-check the role itself, since the route already guarantees
 * only an owner reaches it. That's a deliberate split: middleware answers "can this
 * request be here at all", the controller focuses on the actual business logic.
 */
class TeamController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Team/Index', [
            'members' => User::orderBy('created_at')->get(['id', 'name', 'email', 'role']),
            'pendingInvitations' => Invitation::whereNull('accepted_at')
                ->with('invitedBy:id,name')
                ->latest()
                ->get(['id', 'email', 'role', 'invited_by', 'created_at']),
        ]);
    }

    public function invite(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => [
                'required', 'email',
                Rule::unique('tenant.users', 'email'),
                Rule::unique('tenant.invitations', 'email')->where('accepted_at', null),
            ],
            'role' => ['required', Rule::in(['admin', 'accountant', 'viewer'])],
        ]);

        $invitation = Invitation::create([
            'email' => $validated['email'],
            'role' => $validated['role'],
            'invited_by' => $request->user()->id,
        ]);

        // Signed URL: the link itself is tamper-proof (Laravel signs it with APP_KEY)
        // and self-expiring — no separate token column needed, unlike password reset
        // where we wanted a token stored server-side too. 7 days felt right for
        // "invite a teammate", vs. password reset's tighter 60-minute window.
        $signedUrl = URL::temporarySignedRoute(
            'invitations.accept',
            now()->addDays(7),
            ['invitation' => $invitation->id]
        );

        Mail::to($invitation->email)->send(
            new TeamInvitationMail($invitation, app('currentTenant'), $signedUrl)
        );

        return back()->with('status', "Invitation sent to {$invitation->email}.");
    }

    public function revokeInvitation(Invitation $invitation): RedirectResponse
    {
        $invitation->delete();

        return back()->with('status', 'Invitation revoked.');
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(['owner', 'admin', 'accountant', 'viewer'])],
        ]);

        // Prevent an owner from locking themselves (or the workspace) out: refuse to
        // demote the LAST remaining owner. Without this check, a workspace could end
        // up with zero owners and no way to manage billing or the team ever again.
        if ($user->role === 'owner' && $validated['role'] !== 'owner') {
            $ownerCount = User::where('role', 'owner')->count();

            if ($ownerCount <= 1) {
                return back()->withErrors([
                    'role' => 'Cannot remove the last owner. Promote someone else to owner first.',
                ]);
            }
        }

        $user->update(['role' => $validated['role']]);

        return back()->with('status', "{$user->name}'s role updated to {$validated['role']}.");
    }
}
