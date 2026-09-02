<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface ClientRow {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    invoices_count: number;
}
interface PaginatedClients {
    data: ClientRow[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
}

const props = defineProps<{
    clients: PaginatedClients;
    filters: { search: string };
    can: { create: boolean };
}>();

const search = ref(props.filters.search ?? '');
let debounceTimer: ReturnType<typeof setTimeout>;
const onSearchInput = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get('/clients', { search: search.value }, { preserveState: true, replace: true });
    }, 300);
};
</script>

<template>
    <Head title="Clients" />
    <AppLayout>
        <template #title>Clients</template>

        <div class="flex items-center justify-between gap-4 mb-6">
            <input
                v-model="search"
                @input="onSearchInput"
                type="text"
                placeholder="Search clients…"
                class="w-full max-w-xs rounded-xl border border-border px-3.5 py-2.5 text-[13.5px] outline-none focus:border-primary bg-card"
            />
            <Link
                v-if="can.create"
                href="/clients/create"
                class="bg-primary text-white font-semibold text-[13.5px] rounded-xl px-4 py-2.5 whitespace-nowrap"
            >
                + New Client
            </Link>
        </div>

        <div class="bg-card border border-border rounded-2xl overflow-hidden">
            <table class="w-full text-[13.5px]">
                <thead>
                    <tr class="text-[10.5px] uppercase tracking-wide text-muted bg-canvas">
                        <td class="px-6 py-3">Name</td>
                        <td class="px-6 py-3">Email</td>
                        <td class="px-6 py-3">Phone</td>
                        <td class="px-6 py-3">Invoices</td>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="client in clients.data"
                        :key="client.id"
                        class="border-t border-border hover:bg-canvas cursor-pointer transition"
                        @click="router.visit(`/clients/${client.id}`)"
                    >
                        <td class="px-6 py-3.5 font-semibold text-ink">{{ client.name }}</td>
                        <td class="px-6 py-3.5 text-muted">{{ client.email ?? '—' }}</td>
                        <td class="px-6 py-3.5 text-muted">{{ client.phone ?? '—' }}</td>
                        <td class="px-6 py-3.5 font-mono text-muted">{{ client.invoices_count }}</td>
                    </tr>
                    <tr v-if="clients.data.length === 0">
                        <td colspan="4" class="px-6 py-10 text-center text-muted text-[13.5px]">
                            No clients yet{{ filters.search ? ' matching your search' : '' }}.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="clients.links.length > 3" class="flex items-center gap-1.5 mt-5">
            <template v-for="(link, i) in clients.links" :key="i">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    v-html="link.label"
                    :class="[
                        'px-3 py-1.5 rounded-lg text-[12.5px] font-medium',
                        link.active ? 'bg-primary text-white' : 'bg-card border border-border text-muted hover:text-ink',
                    ]"
                />
                <span v-else v-html="link.label" class="px-3 py-1.5 text-[12.5px] text-muted/40" />
            </template>
        </div>
    </AppLayout>
</template>
