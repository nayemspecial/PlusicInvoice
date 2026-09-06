<?php

namespace App\Http\Controllers;

use App\Models\Tenants\ActivityLog;
use App\Models\Tenants\Client;
use App\Models\Tenants\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * All numbers here are computed live from real Invoice/Client data — no seeded/fake
 * display values. A few metrics are documented approximations given what we
 * currently track (see inline comments); a real production version might add
 * dedicated columns (e.g. `paid_at`) rather than inferring from `updated_at`.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $tenant = app('currentTenant');

        return Inertia::render('Dashboard', [
            'tenant' => $tenant->only(['name', 'subdomain']),
            'stats' => $this->stats(),
            'revenueChart' => $this->revenueChart(),
            'statusBreakdown' => $this->statusBreakdown(),
            'collectionScore' => $this->collectionScore(),
            'plan' => $this->planUsage($tenant),
            'recentInvoices' => Invoice::with('client:id,name')->latest('issue_date')->limit(5)->get(),
            'recentActivity' => ActivityLog::with('user:id,name')->latest()->limit(6)->get(),
        ]);
    }

    protected function stats(): array
    {
        return [
            'outstanding' => (float) Invoice::where('status', 'sent')->sum('total'),
            // Approximation: we don't store a separate paid_at timestamp, so "this
            // month" uses updated_at, which changes whenever status flips to 'paid'.
            'paidThisMonth' => (float) Invoice::where('status', 'paid')
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->sum('total'),
            'overdue' => (float) Invoice::where('status', 'sent')
                ->where('due_date', '<', now()->toDateString())
                ->sum('total'),
            'overdueCount' => Invoice::where('status', 'sent')
                ->where('due_date', '<', now()->toDateString())
                ->count(),
            'activeClients' => Client::has('invoices')->count(),
        ];
    }

    /**
     * Last 6 months of paid revenue, grouped by the month it was marked paid
     * (same updated_at approximation as stats() — see that method's comment).
     */
    protected function revenueChart(): array
    {
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->startOfMonth());

        $paidByMonth = Invoice::where('status', 'paid')
            ->where('updated_at', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw('YEAR(updated_at) as y, MONTH(updated_at) as m, SUM(total) as total')
            ->groupBy('y', 'm')
            ->get()
            ->keyBy(fn ($row) => "{$row->y}-{$row->m}");

        return $months->map(function ($month) use ($paidByMonth) {
            $key = "{$month->year}-{$month->month}";

            return [
                'label' => $month->format('M'),
                'total' => (float) ($paidByMonth[$key]->total ?? 0),
            ];
        })->values()->all();
    }

    protected function statusBreakdown(): array
    {
        $counts = Invoice::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $total = max($counts->sum(), 1); // avoid div-by-zero when there are no invoices yet

        return collect(['paid', 'sent', 'overdue', 'draft', 'cancelled'])
            ->mapWithKeys(fn ($status) => [$status => [
                'count' => $counts[$status] ?? 0,
                'percent' => round((($counts[$status] ?? 0) / $total) * 100),
            ]])
            ->all();
    }

    /**
     * Simplified "on-time collection rate": of everything ever billed (paid + still
     * outstanding), what share is paid rather than overdue. We don't retroactively
     * know whether a now-paid invoice was EVER overdue before being settled (status
     * overwrites itself) — a real version would track that with a history table.
     */
    protected function collectionScore(): int
    {
        $paid = Invoice::where('status', 'paid')->count();
        $overdue = Invoice::where('status', 'sent')->where('due_date', '<', now()->toDateString())->count();
        $total = max($paid + $overdue, 1);

        return (int) round(($paid / $total) * 100);
    }

    protected function planUsage(\App\Models\Tenant $tenant): array
    {
        $plan = $tenant->currentPlan();

        $invoicesThisMonth = Invoice::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $seatsUsed = DB::connection('tenant')->table('users')->count();

        return [
            'name' => $plan?->name ?? 'Starter',
            'invoicesUsed' => $invoicesThisMonth,
            'invoiceLimit' => $plan?->invoice_limit,
            'seatsUsed' => $seatsUsed,
            'seatLimit' => $plan?->seat_limit,
        ];
    }
}
