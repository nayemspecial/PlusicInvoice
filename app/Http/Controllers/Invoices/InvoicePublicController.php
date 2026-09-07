<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Models\Tenants\Invoice;
use App\Models\Tenants\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * No auth required — reached via the invoice's public_token (a UUID), not a
 * sequential ID, so it can't be guessed by incrementing a number. Still lives inside
 * the 'tenant' middleware group (needs the tenant DB connection), but NOT
 * 'tenant.auth' — the whole point is a client can open this without an account.
 */
class InvoicePublicController extends Controller
{
    public function show(Invoice $invoice): Response
    {
        $invoice->load(['client', 'items']);

        return Inertia::render('Invoices/PublicShow', [
            'invoice' => $invoice,
            'tenantName' => app('currentTenant')->name,
            'footerNote' => Setting::get('invoice_footer_note'),
        ]);
    }

    public function pdf(Invoice $invoice): HttpResponse
    {
        $invoice->load(['client', 'items']);

        return Pdf::loadView('pdfs.invoice', [
            'invoice' => $invoice,
            'tenantName' => app('currentTenant')->name,
            'footerNote' => Setting::get('invoice_footer_note'),
        ])->download("{$invoice->invoice_number}.pdf");
    }
}
