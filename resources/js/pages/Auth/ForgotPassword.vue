<script setup lang="ts">
import GuestLayout from '@/layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{ status?: string }>();

const form = useForm({ email: '' });

const submit = () => {
    form.post('/forgot-password');
};
</script>

<template>
    <Head title="Forgot Password" />
    <GuestLayout>
        <h1 class="text-[19px] font-bold text-ink mb-1">Reset your password</h1>
        <p class="text-[13.5px] text-muted mb-6">
            Enter your email and we'll send you a link to reset it.
        </p>

        <div v-if="status" class="mb-4 text-[13px] font-medium text-mint-strong">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="block text-[13px] font-semibold text-ink mb-1.5">Email</label>
                <input
                    v-model="form.email"
                    type="email"
                    autofocus
                    class="w-full rounded-xl border border-border px-3.5 py-2.5 text-[14px] outline-none focus:border-primary"
                />
                <p v-if="form.errors.email" class="text-[12px] text-red mt-1">{{ form.errors.email }}</p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full bg-primary text-white font-semibold text-[14px] rounded-xl py-2.5 disabled:opacity-60"
            >
                Email password reset link
            </button>
        </form>

        <p class="text-center mt-6 text-[12.5px]">
            <Link href="/login" class="text-muted hover:text-ink">Back to login</Link>
        </p>
    </GuestLayout>
</template>
