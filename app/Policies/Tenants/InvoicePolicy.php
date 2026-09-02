<?php

namespace App\Policies\Tenants;

use App\Models\Tenants\Invoice;
use App\Models\Tenants\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['owner', 'admin', 'accountant'], true);
    }

    /** Only draft invoices are editable — enforced in the controller, not here. */
    public function update(User $user, Invoice $invoice): bool
    {
        return in_array($user->role, ['owner', 'admin', 'accountant'], true);
    }

    /** Only draft invoices are deletable — enforced in the controller, not here. */
    public function delete(User $user, Invoice $invoice): bool
    {
        return in_array($user->role, ['owner', 'admin'], true);
    }

    /** Same roles as create/update — sending is a routine bookkeeping action. */
    public function send(User $user, Invoice $invoice): bool
    {
        return in_array($user->role, ['owner', 'admin', 'accountant'], true);
    }

    /** Accountants routinely reconcile payments — allowed here too. */
    public function markAsPaid(User $user, Invoice $invoice): bool
    {
        return in_array($user->role, ['owner', 'admin', 'accountant'], true);
    }

    /** Cancelling is more consequential than sending — narrower than send(). */
    public function cancel(User $user, Invoice $invoice): bool
    {
        return in_array($user->role, ['owner', 'admin'], true);
    }
}
