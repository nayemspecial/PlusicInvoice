<script setup lang="ts">
import GuestLayout from '@/layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Register" />
    <GuestLayout>
        <h1 class="text-[19px] font-bold text-ink mb-1">Create your account</h1>
        <p class="text-[13.5px] text-muted mb-6">The first account created here becomes the workspace owner.</p>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="block text-[13px] font-semibold text-ink mb-1.5">Name</label>
                <input
                    v-model="form.name"
                    type="text"
                    autofocus
                    autocomplete="name"
                    class="w-full rounded-xl border border-border px-3.5 py-2.5 text-[14px] outline-none focus:border-primary"
                />
                <p v-if="form.errors.name" class="text-[12px] text-red mt-1">{{ form.errors.name }}</p>
            </div>

            <div>
                <label class="block text-[13px] font-semibold text-ink mb-1.5">Email</label>
                <input
                    v-model="form.email"
                    type="email"
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
                    autocomplete="new-password"
                    class="w-full rounded-xl border border-border px-3.5 py-2.5 text-[14px] outline-none focus:border-primary"
                />
                <p v-if="form.errors.password" class="text-[12px] text-red mt-1">{{ form.errors.password }}</p>
            </div>

            <div>
                <label class="block text-[13px] font-semibold text-ink mb-1.5">Confirm password</label>
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
                Create account
            </button>
        </form>

        <p class="text-center mt-6 text-[12.5px] text-muted">
            Already have an account?
            <Link href="/login" class="text-ink font-semibold hover:underline">Log in</Link>
        </p>
    </GuestLayout>
</template>
