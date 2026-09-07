<?php

namespace App\Models\Tenants;

use Database\Factories\Tenants\InvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    protected $connection = 'tenant';

    protected $fillable = [
        'invoice_number',
        'public_token',
        'client_id',
        'status',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'discount',
        'total',
        'currency',
        'is_recurring',
        'recurring_interval',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'is_recurring' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Every invoice gets a public_token the moment it's created, whether or not
        // it's ever actually shared — simpler than generating one lazily on first
        // share, and the column is unique+not-null so there's no valid state without it.
        static::creating(function (Invoice $invoice) {
            $invoice->public_token ??= (string) Str::uuid();
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The stored 'status' column only changes when someone explicitly acts (send,
     * mark paid, cancel) — nothing automatically flips it to 'overdue' yet (that would
     * need a scheduled command, a good Phase 11+ addition). This computes what the
     * status SHOULD show as right now, for display purposes, without touching the DB.
     */
    public function effectiveStatus(): string
    {
        if ($this->status === 'sent' && $this->due_date->isPast()) {
            return 'overdue';
        }

        return $this->status;
    }

    /**
     * Recalculates subtotal/total from the current items. Call this after any
     * items() change, before saving — don't trust a client-supplied total.
     */
    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum('line_total');
        $this->subtotal = $subtotal;
        $this->total = $subtotal + $this->tax_amount - $this->discount;
    }

    /**
     * Simple sequential numbering per tenant database, e.g. INV-0001, INV-0042.
     * Not race-condition-proof under heavy concurrent creation (two requests could
     * theoretically read the same count() before either inserts) — acceptable for
     * this project's scale, and a documented, explainable trade-off if asked.
     */
    public static function nextInvoiceNumber(): string
    {
        $prefix = Setting::get('invoice_prefix', 'INV');
        $next = static::count() + 1;

        return "{$prefix}-".str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
