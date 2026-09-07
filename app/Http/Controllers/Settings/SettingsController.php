<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Tenants\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Owner-only (see routes/web.php 'role:owner' group) — these settings affect every
 * invoice and every teammate's workflow, so changing them is treated the same as
 * billing/team management, not something an admin/accountant can casually alter.
 *
 * Backed by the generic key-value Setting model (built in Phase 2, unused until now
 * — same story as ActivityLog in Phase 11). A dedicated 'settings' table with real
 * columns would be more type-safe, but for a handful of simple values the key-value
 * store avoids a migration every time a new setting is added — a deliberate trade-off,
 * not an oversight.
 */
class SettingsController extends Controller
{
    /**
     * Central place defining every setting this app knows about — its storage key,
     * default value, and label. Adding a new setting later means adding one line here
     * plus a form field in Settings/Index.vue, not a new migration.
     */
    protected const DEFAULTS = [
        'invoice_prefix' => 'INV',
        'default_currency' => 'USD',
        'default_tax_rate' => '0',
        'invoice_footer_note' => '',
    ];

    public function index(): Response
    {
        return Inertia::render('Settings/Index', [
            'settings' => collect(self::DEFAULTS)
                ->mapWithKeys(fn ($default, $key) => [$key => Setting::get($key, $default)]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_prefix' => ['required', 'string', 'max:10', 'alpha_dash'],
            'default_currency' => ['required', 'string', 'size:3'],
            'default_tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'invoice_footer_note' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value);
        }

        return back()->with('status', 'Settings saved.');
    }
}
