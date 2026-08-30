<script setup lang="ts">
import GuestLayout from '@/layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    invitation: { id: number; email: string; role: string };
}>();

const form = useForm({
    name: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    // Posts back to the SAME (signed) URL the browser is currently on, so the
    // signature query params are preserved automatically — no need to re-attach them.
    form.post(window.location.pathname + window.location.search);
};
</script>

<template>
    <Head title="Accept Invitation" />
    <GuestLayout>
        <h1 class="text-[19px] font-bold text-ink mb-1">Join the team</h1>
        <p class="text-[13.5px] text-muted mb-6">
            You've been invited as <span class="font-semibold text-ink">{{ props.invitation.role }}</span>
            using <span class="font-semibold text-ink">{{ props.invitation.email }}</span>.
        </p>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="block text-[13px] font-semibold text-ink mb-1.5">Your name</label>
                <input
                    v-model="form.name"
                    type="text"
                    autofocus
                    class="w-full rounded-xl border border-border px-3.5 py-2.5 text-[14px] outline-none focus:border-primary"
                />
                <p v-if="form.errors.name" class="text-[12px] text-red mt-1">{{ form.errors.name }}</p>
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
                Join workspace
            </button>
        </form>
    </GuestLayout>
</template>
