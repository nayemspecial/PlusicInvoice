<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Models\Tenants\Invoice;
use Inertia\Inertia;
use Inertia\Response;

/**
 * No auth required — reached via the invoice's public_token (a UUID), not a
 * sequential ID, so it can't be guessed by incrementing a number. Still lives inside
 * the 'tenant' + 'signed'-less middleware group (needs the tenant DB connection, but
 * NOT 'tenant.auth' — the whole point is a client can open this without an account).
 */
class InvoicePublicController extends Controller
{
    public function show(Invoice $invoice): Response
    {
        $invoice->load(['client', 'items']);

        return Inertia::render('Invoices/PublicShow', [
            'invoice' => $invoice,
            'tenantName' => app('currentTenant')->name,
        ]);
    }
}
