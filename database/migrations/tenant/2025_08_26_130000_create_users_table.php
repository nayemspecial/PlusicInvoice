<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TENANT migration — runs against the 'tenant' connection only, once per tenant
     * database, via TenantProvisioningService. Never run through the normal
     * `php artisan migrate` (that command only sees database/migrations/, not this folder).
     *
     * This is a separate 'users' table from the central App\Models\User — these are the
     * agency's own staff (owner/admin/accountant/viewer), scoped entirely to one tenant DB.
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['owner', 'admin', 'accountant', 'viewer'])->default('viewer');
            $table->string('remember_token', 100)->nullable(); // hashed before storage, see Phase 4
            $table->timestamps();
        });

        Schema::connection('tenant')->create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token'); // hashed before storage, see Phase 4
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('password_reset_tokens');
        Schema::connection('tenant')->dropIfExists('users');
    }
};
