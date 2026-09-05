<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\Tenants\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * implements ShouldQueue — this is what makes ->queue() (vs ->send()) actually defer
 * the work to a queue worker instead of blocking the HTTP request while dompdf
 * renders a PDF and an SMTP connection is made. See docs/CONTEXT.md Phase 9 notes for
 * what needs to be running locally for this to actually process.
 */
class InvoiceSentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public Tenant $tenant,
        public string $publicUrl,
    ) {}

    public function build(): self
    {
        // Rendered here (not passed in from the controller) because Mailable
        // properties get serialized to the queue payload — keeping the raw PDF bytes
        // out of that payload and generating them fresh when the queued job actually
        // runs keeps the job lightweight and avoids serializing binary data.
        $pdf = Pdf::loadView('pdfs.invoice', [
            'invoice' => $this->invoice,
            'tenantName' => $this->tenant->name,
        ])->output();

        return $this->subject("Invoice {$this->invoice->invoice_number} from {$this->tenant->name}")
            ->view('emails.invoice-sent')
            ->attachData($pdf, "{$this->invoice->invoice_number}.pdf", [
                'mime' => 'application/pdf',
            ]);
    }
}
