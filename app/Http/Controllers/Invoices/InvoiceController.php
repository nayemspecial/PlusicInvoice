<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Mail\InvoiceSentMail;
use App\Models\Tenants\Client;
use App\Models\Tenants\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class InvoiceController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = Invoice::query()
            ->with('client:id,name')
            ->when($request->string('status')->toString(), fn ($q, $status) => $q->where('status', $status))
            ->when($request->string('search')->toString(), function ($q, $search) {
                $q->where(fn ($sub) => $sub
                    ->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$search}%"))
                );
            })
            ->latest('issue_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
            ],
            'can' => ['create' => $request->user()->can('create', Invoice::class)],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Invoice::class);

        return Inertia::render('Invoices/Create', [
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'nextInvoiceNumber' => Invoice::nextInvoiceNumber(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $validated = $this->validateInvoice($request);

        $invoice = DB::connection('tenant')->transaction(function () use ($validated, $request) {
            $invoice = Invoice::create([
                'invoice_number' => Invoice::nextInvoiceNumber(),
                'public_token' => (string) \Illuminate\Support\Str::uuid(),
                'client_id' => $validated['client_id'],
                'status' => 'draft',
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'discount' => $validated['discount'] ?? 0,
                'currency' => $validated['currency'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $this->syncItems($invoice, $validated['items']);

            $invoice->recalculateTotals();
            $invoice->save();

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)->with('status', 'Invoice created as draft.');
    }

    public function show(Request $request, Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $invoice->load(['client', 'items', 'createdBy:id,name']);

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice,
            'can' => [
                'update' => $request->user()->can('update', $invoice) && $invoice->status === 'draft',
                'delete' => $request->user()->can('delete', $invoice) && $invoice->status === 'draft',
                'send' => $request->user()->can('send', $invoice) && $invoice->status === 'draft',
                'markAsPaid' => $request->user()->can('markAsPaid', $invoice) && in_array($invoice->effectiveStatus(), ['sent', 'overdue'], true),
                'cancel' => $request->user()->can('cancel', $invoice) && in_array($invoice->status, ['draft', 'sent'], true),
            ],
        ]);
    }

    public function pdf(Invoice $invoice): HttpResponse
    {
        $this->authorize('view', $invoice);

        $invoice->load(['client', 'items']);

        return Pdf::loadView('pdfs.invoice', [
            'invoice' => $invoice,
            'tenantName' => app('currentTenant')->name,
        ])->download("{$invoice->invoice_number}.pdf");
    }

    public function edit(Request $request, Invoice $invoice): Response|RedirectResponse
    {
        $this->authorize('update', $invoice);

        if ($invoice->status !== 'draft') {
            return redirect()->route('invoices.show', $invoice)
                ->withErrors(['status' => 'Only draft invoices can be edited.']);
        }

        $invoice->load('items');

        return Inertia::render('Invoices/Edit', [
            'invoice' => $invoice,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        if ($invoice->status !== 'draft') {
            return redirect()->route('invoices.show', $invoice)
                ->withErrors(['status' => 'Only draft invoices can be edited.']);
        }

        $validated = $this->validateInvoice($request);

        DB::connection('tenant')->transaction(function () use ($invoice, $validated) {
            $invoice->update([
                'client_id' => $validated['client_id'],
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'discount' => $validated['discount'] ?? 0,
                'currency' => $validated['currency'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $invoice->items()->delete();
            $this->syncItems($invoice, $validated['items']);

            $invoice->recalculateTotals();
            $invoice->save();
        });

        return redirect()->route('invoices.show', $invoice)->with('status', 'Invoice updated.');
    }

    public function destroy(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        if ($invoice->status !== 'draft') {
            return redirect()->route('invoices.show', $invoice)
                ->withErrors(['status' => 'Only draft invoices can be deleted. Cancel it instead.']);
        }

        $invoice->delete();

        return redirect()->route('invoices.index')->with('status', 'Invoice deleted.');
    }

    /**
     * Status transitions — each is its own tiny endpoint rather than a generic
     * "update status" action, so every transition can have its OWN authorization rule
     * (see InvoicePolicy) and its own validation of which prior states are allowed.
     */
    public function send(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('send', $invoice);

        if ($invoice->status !== 'draft') {
            return back()->withErrors(['status' => 'Only draft invoices can be sent.']);
        }

        $invoice->update(['status' => 'sent']);

        $invoice->load('client');

        if (blank($invoice->client->email)) {
            return back()->with('status', 'Invoice marked as sent — add an email to this client to also email them a copy.');
        }

        Mail::to($invoice->client->email)->queue(new InvoiceSentMail(
            $invoice,
            app('currentTenant'),
            route('invoices.public', $invoice->public_token),
        ));

        return back()->with('status', 'Invoice marked as sent and emailed to the client.');
    }

    public function markAsPaid(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('markAsPaid', $invoice);

        if (! in_array($invoice->effectiveStatus(), ['sent', 'overdue'], true)) {
            return back()->withErrors(['status' => 'Only sent or overdue invoices can be marked paid.']);
        }

        $invoice->update(['status' => 'paid']);

        return back()->with('status', 'Invoice marked as paid.');
    }

    public function cancel(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('cancel', $invoice);

        if (! in_array($invoice->status, ['draft', 'sent'], true)) {
            return back()->withErrors(['status' => 'This invoice can no longer be cancelled.']);
        }

        $invoice->update(['status' => 'cancelled']);

        return back()->with('status', 'Invoice cancelled.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateInvoice(Request $request): array
    {
        return $request->validate([
            'client_id' => ['required', Rule::exists('tenant.clients', 'id')],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'currency' => ['required', 'string', 'size:3'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);
    }

    /**
     * @param  array<int, array{description: string, quantity: float, unit_price: float}>  $items
     */
    protected function syncItems(Invoice $invoice, array $items): void
    {
        foreach ($items as $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'line_total' => round($item['quantity'] * $item['unit_price'], 2),
            ]);
        }
    }
}
