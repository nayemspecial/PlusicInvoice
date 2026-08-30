<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; background:#F4F9F6; padding: 32px;">
    <div style="max-width: 480px; margin: 0 auto; background:#fff; border-radius: 16px; padding: 32px; border: 1px solid #E9F1EC;">
        <p style="font-weight: 800; color:#173B28; font-size: 17px;">PlusicInvoice</p>
        <h1 style="font-size: 19px; color:#132A1E;">You're invited to {{ $tenant->name }}</h1>
        <p style="color:#77897E; font-size: 14px;">
            {{ $invitation->invitedBy->name }} invited you to join <strong>{{ $tenant->name }}</strong>
            on PlusicInvoice as a <strong>{{ ucfirst($invitation->role) }}</strong>.
        </p>
        <p style="margin: 24px 0;">
            <a href="{{ $signedUrl }}" style="background:#173B28; color:#fff; padding: 12px 20px; border-radius: 12px; text-decoration: none; font-weight: 600; font-size: 14px;">
                Accept invitation
            </a>
        </p>
        <p style="color:#9BAAA1; font-size: 12px;">This invitation link expires in 7 days.</p>
    </div>
</body>
</html>
