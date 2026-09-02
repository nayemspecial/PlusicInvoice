<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

interface ClientData {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    tax_id: string | null;
}

const props = defineProps<{ client: ClientData }>();

const form = useForm({
    name: props.client.name,
    email: props.client.email ?? '',
    phone: props.client.phone ?? '',
    address: props.client.address ?? '',
    tax_id: props.client.tax_id ?? '',
});

const submit = () => form.put(`/clients/${props.client.id}`);
</script>

<template>
    <Head title="Edit Client" />
    <AppLayout>
        <template #title>Edit Client</template>

        <div class="bg-card border border-border rounded-2xl p-6 max-w-lg">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-[13px] font-semibold text-ink mb-1.5">Name</label>
                    <input v-model="form.name" type="text" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-[14px] outline-none focus:border-primary" />
                    <p v-if="form.errors.name" class="text-[12px] text-red mt-1">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="block text-[13px] font-semibold text-ink mb-1.5">Email</label>
                    <input v-model="form.email" type="email" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-[14px] outline-none focus:border-primary" />
                    <p v-if="form.errors.email" class="text-[12px] text-red mt-1">{{ form.errors.email }}</p>
                </div>
                <div>
                    <label class="block text-[13px] font-semibold text-ink mb-1.5">Phone</label>
                    <input v-model="form.phone" type="text" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-[14px] outline-none focus:border-primary" />
                </div>
                <div>
                    <label class="block text-[13px] font-semibold text-ink mb-1.5">Address</label>
                    <textarea v-model="form.address" rows="3" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-[14px] outline-none focus:border-primary"></textarea>
                </div>
                <div>
                    <label class="block text-[13px] font-semibold text-ink mb-1.5">Tax ID</label>
                    <input v-model="form.tax_id" type="text" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-[14px] outline-none focus:border-primary" />
                </div>
                <button type="submit" :disabled="form.processing" class="w-full bg-primary text-white font-semibold text-[14px] rounded-xl py-2.5 disabled:opacity-60">
                    Save Changes
                </button>
            </form>
        </div>
    </AppLayout>
</template>
