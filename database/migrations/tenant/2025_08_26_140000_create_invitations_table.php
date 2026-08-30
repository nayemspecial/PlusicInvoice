<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pending team invitations. An invitation becomes a real 'users' row only when
     * accepted (see AcceptInvitationController) — until then it's just an intent,
     * revocable by the owner and expiring after 7 days (baked into the signed URL
     * itself, not tracked separately here — see docs/CONTEXT.md Phase 5 notes).
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            // Deliberately no 'owner' option — you can't invite someone directly as
            // owner; ownership only transfers via an explicit, separate action later.
            $table->enum('role', ['admin', 'accountant', 'viewer'])->default('viewer');
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('invitations');
    }
};
