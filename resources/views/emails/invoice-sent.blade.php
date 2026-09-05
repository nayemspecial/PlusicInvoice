<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; background:#F4F9F6; padding: 32px;">
    <div style="max-width: 480px; margin: 0 auto; background:#fff; border-radius: 16px; padding: 32px; border: 1px solid #E9F1EC;">
        <p style="font-weight: 800; color:#173B28; font-size: 17px;">{{ $tenant->name }}</p>
        <h1 style="font-size: 19px; color:#132A1E;">Invoice {{ $invoice->invoice_number }}</h1>
        <p style="color:#77897E; font-size: 14px;">
            Hi {{ $invoice->client->name }}, you have a new invoice for
            <strong>{{ $invoice->currency }} {{ $invoice->total }}</strong>,
            due {{ $invoice->due_date->format('M d, Y') }}.
        </p>
        <p style="margin: 24px 0;">
            <a href="{{ $publicUrl }}" style="background:#173B28; color:#fff; padding: 12px 20px; border-radius: 12px; text-decoration: none; font-weight: 600; font-size: 14px;">
                View invoice
            </a>
        </p>
        <p style="color:#9BAAA1; font-size: 12px;">A PDF copy is attached to this email.</p>
    </div>
</body>
</html>
