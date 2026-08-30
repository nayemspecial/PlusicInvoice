<script setup lang="ts">
import GuestLayout from '@/layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{ status?: string }>();

// useForm gives us .processing (disables the button while the request is in flight),
// .errors (populated automatically from Laravel's validation response — a 422 with
// {errors: {...}} — no manual error-handling code needed), and reset() for clearing
// the password field after a failed attempt (so it's never left sitting in the DOM).
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />
    <GuestLayout>
        <h1 class="text-[19px] font-bold text-ink mb-1">Welcome back</h1>
        <p class="text-[13.5px] text-muted mb-6">Log in to your workspace.</p>

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
                    autocomplete="username"
                    class="w-full rounded-xl border border-border px-3.5 py-2.5 text-[14px] outline-none focus:border-primary"
                />
                <p v-if="form.errors.email" class="text-[12px] text-red mt-1">{{ form.errors.email }}</p>
            </div>

            <div>
                <label class="block text-[13px] font-semibold text-ink mb-1.5">Password</label>
                <input
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    class="w-full rounded-xl border border-border px-3.5 py-2.5 text-[14px] outline-none focus:border-primary"
                />
                <p v-if="form.errors.password" class="text-[12px] text-red mt-1">{{ form.errors.password }}</p>
            </div>

            <label class="flex items-center gap-2 text-[13px] text-muted">
                <input v-model="form.remember" type="checkbox" class="rounded border-border" />
                Remember me
            </label>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full bg-primary text-white font-semibold text-[14px] rounded-xl py-2.5 disabled:opacity-60"
            >
                Log in
            </button>
        </form>

        <div class="flex items-center justify-between mt-6 text-[12.5px]">
            <Link href="/forgot-password" class="text-muted hover:text-ink">Forgot password?</Link>
            <Link href="/register" class="text-muted hover:text-ink">Create an account</Link>
        </div>
    </GuestLayout>
</template>
