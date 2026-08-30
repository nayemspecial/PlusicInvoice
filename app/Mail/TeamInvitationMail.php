<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\Tenants\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TeamInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invitation $invitation,
        public Tenant $tenant,
        public string $signedUrl,
    ) {}

    public function build(): self
    {
        return $this->subject("You're invited to join {$this->tenant->name} on PlusicInvoice")
            ->view('emails.team-invitation');
    }
}
