<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';

defineProps<{ tenant: { name: string; subdomain: string } }>();

const page = usePage();

// Logout must be a POST (a GET link that logs someone out is a classic CSRF footgun —
// any <img src="/logout"> on a malicious page could trigger it). router.post() sends
// the CSRF token automatically, same as useForm() does.
const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <Head title="Dashboard" />
    <div class="min-h-screen bg-canvas flex items-center justify-center px-6">
        <div class="max-w-md w-full bg-card border border-border rounded-2xl p-8 text-center">
            <div class="w-10 h-10 rounded-lg bg-primary text-white font-extrabold flex items-center justify-center mx-auto mb-5">
                P
            </div>
            <h1 class="text-[19px] font-bold text-ink">You're in 🎉</h1>
            <p class="text-[13.5px] text-muted mt-2">
                Logged in as <span class="font-semibold text-ink">{{ page.props.auth.user?.name }}</span>
                ({{ page.props.auth.user?.role }})
            </p>
            <p class="text-[12px] font-mono text-muted mt-1">
                Workspace: {{ tenant.name }} — {{ tenant.subdomain }}
            </p>

            <p class="text-[12.5px] text-muted mt-6 leading-relaxed">
                This is a placeholder — the real dashboard (stats, chart, recent invoices,
                matching <code>design_references/dashboard.html</code>) gets built in Phase 11,
                once Client/Invoice CRUD exist. This page only exists to prove the full
                auth + tenancy loop works end to end.
            </p>

            <button
                @click="logout"
                class="mt-6 w-full border border-border text-ink font-semibold text-[13.5px] rounded-xl py-2.5 hover:bg-canvas transition"
            >
                Log out
            </button>

            <!-- role-gated in the UI too (not just the backend) — a nicety, not the
                 real security boundary. The 'role:owner' route middleware is what
                 actually protects /team; hiding the link is just good UX. -->
            <Link
                v-if="page.props.auth.user?.role === 'owner'"
                href="/team"
                class="block mt-3 text-[13px] font-semibold text-muted hover:text-ink"
            >
                Manage team →
            </Link>
        </div>
    </div>
</template>
