<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

interface StatusCount { count: number; percent: number }
interface RevenuePoint { label: string; total: number }
interface RecentInvoice {
    id: number;
    invoice_number: string;
    status: string;
    total: string;
    currency: string;
    client: { name: string };
}
interface ActivityItem {
    id: number;
    action: string;
    subject_type: string | null;
    created_at: string;
    user: { name: string } | null;
}

const props = defineProps<{
    tenant: { name: string; subdomain: string };
    stats: {
        outstanding: number;
        paidThisMonth: number;
        overdue: number;
        overdueCount: number;
        activeClients: number;
    };
    revenueChart: RevenuePoint[];
    statusBreakdown: Record<string, StatusCount>;
    collectionScore: number;
    plan: {
        name: string;
        invoicesUsed: number;
        invoiceLimit: number | null;
        seatsUsed: number;
        seatLimit: number | null;
    };
    recentInvoices: RecentInvoice[];
    recentActivity: ActivityItem[];
}>();

const fmt = (n: number) => n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const statusStyle: Record<string, string> = {
    paid: 'bg-mint text-mint-strong',
    sent: 'bg-amber/20 text-amber',
    overdue: 'bg-red/15 text-red',
    draft: 'bg-canvas text-muted',
    cancelled: 'bg-canvas text-muted',
};

const actionLabels: Record<string, string> = {
    'client.created': 'added a new client',
    'invoice.created': 'created an invoice',
    'invoice.sent': 'sent an invoice',
    'invoice.paid': 'marked an invoice paid',
    'invoice.cancelled': 'cancelled an invoice',
    'team.invited': 'invited a teammate',
};

// Revenue line chart — plots the 6 monthly totals across a 460x140 viewBox.
const revenuePoints = computed(() => {
    const values = props.revenueChart.map((p) => p.total);
    const max = Math.max(...values, 1);
    const step = 460 / (values.length - 1 || 1);
    return values.map((v, i) => `${i * step},${130 - (v / max) * 110}`).join(' ');
});

// Invoice status donut — cumulative dasharray/dashoffset per status segment.
const circumference = 2 * Math.PI * 58;
const donutSegments = computed(() => {
    let offset = 0;
    const colors: Record<string, string> = {
        paid: 'var(--color-mint-strong)',
        sent: 'var(--color-amber)',
        overdue: 'var(--color-red)',
        draft: '#D8DEDA',
        cancelled: '#D8DEDA',
    };
    return Object.entries(props.statusBreakdown)
        .filter(([, v]) => v.count > 0)
        .map(([status, v]) => {
            const length = (v.percent / 100) * circumference;
            const segment = { status, color: colors[status], length, offset: -offset };
            offset += length;
            return segment;
        });
});

const totalInvoices = computed(() => Object.values(props.statusBreakdown).reduce((s, v) => s + v.count, 0));
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout>
        <template #title>Dashboard</template>

        <!-- Stat cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-5">
            <div class="bg-card border border-border rounded-2xl p-5">
                <p class="text-[12px] text-muted">Outstanding</p>
                <p class="font-mono text-[20px] lg:text-[22px] font-bold text-ink mt-1">${{ fmt(stats.outstanding) }}</p>
            </div>
            <div class="bg-card border border-border rounded-2xl p-5">
                <p class="text-[12px] text-muted">Paid — this month</p>
                <p class="font-mono text-[20px] lg:text-[22px] font-bold text-mint-strong mt-1">${{ fmt(stats.paidThisMonth) }}</p>
            </div>
            <div class="bg-card border border-border rounded-2xl p-5">
                <p class="text-[12px] text-muted">Overdue</p>
                <p class="font-mono text-[20px] lg:text-[22px] font-bold text-red mt-1">${{ fmt(stats.overdue) }}</p>
                <p class="text-[11px] text-muted mt-1">{{ stats.overdueCount }} invoice(s)</p>
            </div>
            <div class="bg-card border border-border rounded-2xl p-5">
                <p class="text-[12px] text-muted">Active clients</p>
                <p class="font-mono text-[20px] lg:text-[22px] font-bold text-ink mt-1">{{ stats.activeClients }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mt-5">
            <!-- Revenue chart -->
            <div class="lg:col-span-2 bg-card border border-border rounded-2xl p-6">
                <p class="font-bold text-[15px] text-ink mb-4">Revenue — last 6 months</p>
                <svg viewBox="0 0 460 140" class="w-full h-[140px]">
                    <line x1="0" y1="35" x2="460" y2="35" stroke="var(--color-border)" stroke-width="1" />
                    <line x1="0" y1="70" x2="460" y2="70" stroke="var(--color-border)" stroke-width="1" />
                    <line x1="0" y1="105" x2="460" y2="105" stroke="var(--color-border)" stroke-width="1" />
                    <polyline :points="revenuePoints" fill="none" stroke="var(--color-mint-strong)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <div class="flex justify-between mt-2 text-[11px] font-mono text-muted">
                    <span v-for="p in revenueChart" :key="p.label">{{ p.label }}</span>
                </div>
            </div>

            <!-- Status donut -->
            <div class="bg-card border border-border rounded-2xl p-6">
                <p class="font-bold text-[15px] text-ink mb-4">Invoice Status</p>
                <div class="flex justify-center my-2">
                    <svg width="140" height="140" viewBox="0 0 140 140">
                        <circle cx="70" cy="70" r="58" fill="none" stroke="var(--color-border)" stroke-width="16" />
                        <circle
                            v-for="seg in donutSegments"
                            :key="seg.status"
                            cx="70" cy="70" r="58" fill="none"
                            :stroke="seg.color" stroke-width="16"
                            :stroke-dasharray="`${seg.length} ${circumference}`"
                            :stroke-dashoffset="seg.offset"
                            stroke-linecap="round"
                            transform="rotate(-90 70 70)"
                        />
                        <text x="70" y="65" text-anchor="middle" class="font-mono" style="font-size:20px; font-weight:700; fill:var(--color-ink)">{{ totalInvoices }}</text>
                        <text x="70" y="82" text-anchor="middle" style="font-size:10px; fill:var(--color-muted)">invoices</text>
                    </svg>
                </div>
                <div class="space-y-2 text-[12px] mt-2">
                    <div v-for="(v, status) in statusBreakdown" :key="status" v-show="v.count > 0" class="flex justify-between capitalize">
                        <span class="text-muted">{{ status }}</span>
                        <span class="font-semibold text-ink">{{ v.percent }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mt-5">
            <!-- Collection score + plan -->
            <div class="space-y-5">
                <div class="bg-mint rounded-2xl p-5">
                    <p class="font-bold text-[13.5px] text-ink">Collection Score</p>
                    <p class="text-[22px] font-extrabold text-primary mt-2">{{ collectionScore }}%</p>
                    <div class="w-full h-2 rounded-full bg-white/60 mt-3 overflow-hidden">
                        <div class="h-2 rounded-full bg-mint-strong" :style="{ width: collectionScore + '%' }"></div>
                    </div>
                </div>

                <div class="bg-card border border-border rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-1">
                        <p class="font-bold text-[13.5px] text-ink">{{ plan.name }} plan</p>
                        <Link href="/billing" class="text-[11.5px] font-semibold text-mint-strong">Manage →</Link>
                    </div>
                    <div class="mt-4">
                        <div class="flex justify-between text-[11.5px] mb-1.5">
                            <span class="text-muted">Invoices this month</span>
                            <span class="font-mono text-muted">{{ plan.invoicesUsed }} / {{ plan.invoiceLimit ?? '∞' }}</span>
                        </div>
                        <div v-if="plan.invoiceLimit" class="w-full h-1.5 rounded-full bg-canvas">
                            <div class="h-1.5 rounded-full bg-mint-strong" :style="{ width: Math.min((plan.invoicesUsed / plan.invoiceLimit) * 100, 100) + '%' }"></div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="flex justify-between text-[11.5px] mb-1.5">
                            <span class="text-muted">Team seats</span>
                            <span class="font-mono text-muted">{{ plan.seatsUsed }} / {{ plan.seatLimit ?? '∞' }}</span>
                        </div>
                        <div v-if="plan.seatLimit" class="w-full h-1.5 rounded-full bg-canvas">
                            <div class="h-1.5 rounded-full bg-amber" :style="{ width: Math.min((plan.seatsUsed / plan.seatLimit) * 100, 100) + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent invoices -->
            <div class="lg:col-span-2 bg-card border border-border rounded-2xl overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4">
                    <p class="font-bold text-[15px] text-ink">Recent Invoices</p>
                    <Link href="/invoices" class="text-[12px] font-semibold text-mint-strong">View all →</Link>
                </div>
                <table class="w-full text-[13px]">
                    <tbody>
                        <tr
                            v-for="inv in recentInvoices"
                            :key="inv.id"
                            class="border-t border-border hover:bg-canvas cursor-pointer transition"
                            @click="router.visit(`/invoices/${inv.id}`)"
                        >
                            <td class="px-6 py-3 font-mono font-semibold text-ink">{{ inv.invoice_number }}</td>
                            <td class="px-6 py-3 text-muted">{{ inv.client.name }}</td>
                            <td class="px-6 py-3">
                                <span :class="['px-2 py-0.5 rounded-full text-[10px] font-semibold capitalize', statusStyle[inv.status]]">{{ inv.status }}</span>
                            </td>
                            <td class="px-6 py-3 text-right font-mono text-ink">{{ inv.currency }} {{ inv.total }}</td>
                        </tr>
                        <tr v-if="recentInvoices.length === 0">
                            <td class="px-6 py-8 text-center text-muted text-[13px]">No invoices yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent activity -->
        <div class="bg-card border border-border rounded-2xl p-6 mt-5">
            <p class="font-bold text-[15px] text-ink mb-4">Recent Activity</p>
            <div v-if="recentActivity.length" class="space-y-3 text-[13px]">
                <div v-for="item in recentActivity" :key="item.id" class="flex gap-3">
                    <span class="w-1.5 h-1.5 rounded-full bg-mint-strong mt-1.5 shrink-0"></span>
                    <p>
                        <span class="font-semibold text-ink">{{ item.user?.name ?? 'Someone' }}</span>
                        {{ actionLabels[item.action] ?? item.action }}
                        <span class="text-muted">· {{ item.created_at }}</span>
                    </p>
                </div>
            </div>
            <p v-else class="text-muted text-[13px]">No activity yet — actions like sending an invoice will show up here.</p>
        </div>
    </AppLayout>
</template>
