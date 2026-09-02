<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

/**
 * The full app shell — sidebar + topbar — matching design_references/dashboard.html.
 * Used by every authenticated tenant page (Dashboard, Team, and Client/Invoice pages
 * once Phase 6+ build them). GuestLayout (login/register/etc.) is deliberately
 * separate and much simpler — see resources/js/layouts/GuestLayout.vue.
 */

const page = usePage();
const collapsed = ref(false);
const mobileOpen = ref(false);

const navItems = [
    { label: 'Dashboard', href: '/dashboard', icon: 'dashboard', available: true },
    { label: 'Invoices', href: '/invoices', icon: 'invoices', available: true },
    { label: 'Clients', href: '/clients', icon: 'clients', available: true },
    { label: 'Billing', href: '/billing', icon: 'billing', available: false },
    { label: 'Team', href: '/team', icon: 'team', available: true, ownerOnly: true },
    { label: 'Settings', href: '/settings', icon: 'settings', available: false },
];

const isActive = (href: string) => page.url.startsWith(href);

const logout = () => router.post('/logout');
</script>

<template>
    <div class="flex min-h-screen bg-canvas">
        <!-- Mobile overlay -->
        <div
            v-if="mobileOpen"
            @click="mobileOpen = false"
            class="fixed inset-0 bg-black/30 z-30 lg:hidden"
        ></div>

        <!-- Sidebar -->
        <aside
            :class="[
                'bg-card border-r border-border flex flex-col justify-between py-6 transition-all duration-200 z-40',
                collapsed ? 'lg:w-[84px]' : 'lg:w-[248px]',
                'fixed lg:static inset-y-0 left-0 w-[248px]',
                mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
            ]"
        >
            <div>
                <div class="flex items-center justify-between px-5 mb-8">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center font-extrabold text-[13px] text-white bg-primary shrink-0">
                            P
                        </div>
                        <span v-if="!collapsed" class="font-bold text-[16px] text-ink whitespace-nowrap">PlusicInvoice</span>
                    </div>
                    <button @click="mobileOpen = false" class="lg:hidden text-[20px] text-muted">&times;</button>
                </div>

                <nav class="px-3 space-y-1 text-[13.5px]">
                    <template v-for="item in navItems" :key="item.href">
                        <Link
                            v-if="item.available && (!item.ownerOnly || page.props.auth.user?.role === 'owner')"
                            :href="item.href"
                            :class="[
                                'flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-semibold transition',
                                collapsed ? 'justify-center' : '',
                                isActive(item.href) ? 'bg-mint text-primary' : 'text-muted hover:bg-canvas',
                            ]"
                        >
                            <span class="w-[18px] h-[18px] rounded-full border-2 border-current shrink-0"></span>
                            <span v-if="!collapsed" class="whitespace-nowrap">{{ item.label }}</span>
                        </Link>
                        <span
                            v-else-if="!item.ownerOnly || page.props.auth.user?.role === 'owner'"
                            :class="[
                                'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-muted/50 cursor-not-allowed',
                                collapsed ? 'justify-center' : '',
                            ]"
                            :title="`${item.label} — coming soon`"
                        >
                            <span class="w-[18px] h-[18px] rounded-full border-2 border-current shrink-0"></span>
                            <span v-if="!collapsed" class="whitespace-nowrap flex-1">{{ item.label }}</span>
                            <span v-if="!collapsed" class="text-[9px] font-mono px-1.5 py-0.5 rounded bg-canvas">soon</span>
                        </span>
                    </template>
                </nav>
            </div>

            <div class="px-3">
                <button
                    @click="collapsed = !collapsed"
                    class="hidden lg:flex w-full items-center justify-center gap-2 py-2 rounded-lg text-[12px] font-medium text-muted border border-border hover:bg-canvas transition"
                >
                    {{ collapsed ? '»' : '« Collapse' }}
                </button>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-1 min-w-0 flex flex-col">
            <header class="flex items-center justify-between gap-4 px-5 lg:px-9 py-5 border-b border-border bg-card">
                <div class="flex items-center gap-3">
                    <button @click="mobileOpen = true" class="lg:hidden text-ink">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div>
                        <p class="text-[11px] font-mono uppercase tracking-wide text-muted">
                            {{ page.props.currentTenant?.name }} · {{ page.props.currentTenant?.subdomain }}.plusicinvoice
                        </p>
                        <h1 class="font-bold text-[19px] lg:text-[21px] text-ink">
                            <slot name="title">Dashboard</slot>
                        </h1>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="hidden sm:flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full bg-primary text-white text-[12px] font-bold flex items-center justify-center">
                            {{ page.props.auth.user?.name?.charAt(0) }}
                        </div>
                        <div class="text-[13px]">
                            <p class="font-semibold text-ink leading-tight">{{ page.props.auth.user?.name }}</p>
                            <p class="text-[11px] text-muted leading-tight">{{ page.props.auth.user?.role }}</p>
                        </div>
                    </div>
                    <button
                        @click="logout"
                        class="text-[12.5px] font-semibold text-muted hover:text-ink border border-border rounded-lg px-3 py-1.5 transition"
                    >
                        Log out
                    </button>
                </div>
            </header>

            <main class="flex-1 p-5 lg:p-9">
                <div
                    v-if="page.props.flash?.status"
                    class="mb-6 text-[13px] font-medium text-mint-strong bg-mint border border-mint-strong/30 rounded-xl px-4 py-3"
                >
                    {{ page.props.flash.status }}
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
