<script setup lang="ts">
import GuestLayout from '@/layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{ email: string; token: string }>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Reset Password" />
    <GuestLayout>
        <h1 class="text-[19px] font-bold text-ink mb-1">Set a new password</h1>
        <p class="text-[13.5px] text-muted mb-6">This reset link can only be used once.</p>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="block text-[13px] font-semibold text-ink mb-1.5">Email</label>
                <input
                    v-model="form.email"
                    type="email"
                    readonly
                    class="w-full rounded-xl border border-border bg-canvas px-3.5 py-2.5 text-[14px] outline-none"
                />
                <p v-if="form.errors.email" class="text-[12px] text-red mt-1">{{ form.errors.email }}</p>
            </div>

            <div>
                <label class="block text-[13px] font-semibold text-ink mb-1.5">New password</label>
                <input
                    v-model="form.password"
                    type="password"
                    autofocus
                    autocomplete="new-password"
                    class="w-full rounded-xl border border-border px-3.5 py-2.5 text-[14px] outline-none focus:border-primary"
                />
                <p v-if="form.errors.password" class="text-[12px] text-red mt-1">{{ form.errors.password }}</p>
            </div>

            <div>
                <label class="block text-[13px] font-semibold text-ink mb-1.5">Confirm new password</label>
                <input
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    class="w-full rounded-xl border border-border px-3.5 py-2.5 text-[14px] outline-none focus:border-primary"
                />
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full bg-primary text-white font-semibold text-[14px] rounded-xl py-2.5 disabled:opacity-60"
            >
                Reset password
            </button>
        </form>
    </GuestLayout>
</template>
