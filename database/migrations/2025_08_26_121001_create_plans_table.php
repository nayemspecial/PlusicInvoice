<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Static-ish reference data for the pricing plans shown on the marketing site.
     * Central table — read by the subscription/checkout flow, not tenant-specific.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('stripe_price_id')->nullable();
            $table->unsignedInteger('price_cents');
            $table->unsignedInteger('invoice_limit')->nullable(); // null = unlimited
            $table->unsignedInteger('seat_limit')->nullable();    // null = unlimited
            $table->json('features')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
