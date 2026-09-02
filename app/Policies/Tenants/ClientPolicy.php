<?php

namespace App\Policies\Tenants;

use App\Models\Tenants\Client;
use App\Models\Tenants\User;

/**
 * Namespaced App\Policies\Tenants\ClientPolicy to mirror App\Models\Tenants\Client —
 * Laravel's policy auto-discovery guesses this exact path from the model's namespace,
 * so no manual Gate::policy() registration is needed (though AppServiceProvider
 * registers it explicitly too, as a defensive backup — see configureTenantGates()).
 */
class ClientPolicy
{
    /** Any authenticated tenant user can browse the client list. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Client $client): bool
    {
        return true;
    }

    /** Viewers are read-only — everyone else can add/edit clients. */
    public function create(User $user): bool
    {
        return in_array($user->role, ['owner', 'admin', 'accountant'], true);
    }

    public function update(User $user, Client $client): bool
    {
        return in_array($user->role, ['owner', 'admin', 'accountant'], true);
    }

    /**
     * Deleting is more destructive than editing — deliberately narrower than update().
     * An accountant can fix a typo in a client's address but shouldn't be able to
     * delete the client (and, via cascade, every invoice ever billed to them).
     */
    public function delete(User $user, Client $client): bool
    {
        return in_array($user->role, ['owner', 'admin'], true);
    }
}
