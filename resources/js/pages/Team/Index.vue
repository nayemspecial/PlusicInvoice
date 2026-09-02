<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

interface Member {
    id: number;
    name: string;
    email: string;
    role: string;
}
interface PendingInvitation {
    id: number;
    email: string;
    role: string;
    invited_by: { name: string };
    created_at: string;
}

defineProps<{
    members: Member[];
    pendingInvitations: PendingInvitation[];
}>();

const roles = ['admin', 'accountant', 'viewer'];

const inviteForm = useForm({ email: '', role: 'viewer' });
const sendInvite = () => {
    inviteForm.post('/team/invite', {
        onSuccess: () => inviteForm.reset(),
    });
};

const revokeInvite = (id: number) => {
    router.delete(`/team/invitations/${id}`);
};

const changeRole = (userId: number, role: string) => {
    router.patch(`/team/members/${userId}/role`, { role });
};
</script>

<template>
    <Head title="Team" />
    <AppLayout>
        <template #title>Team</template>

        <div class="max-w-2xl space-y-6">
            <!-- Invite form -->
            <div class="bg-card border border-border rounded-2xl p-6">
                <h2 class="text-[15px] font-bold text-ink mb-4">Invite a teammate</h2>
                <form @submit.prevent="sendInvite" class="flex flex-col sm:flex-row gap-3">
                    <input
                        v-model="inviteForm.email"
                        type="email"
                        placeholder="teammate@example.com"
                        class="flex-1 rounded-xl border border-border px-3.5 py-2.5 text-[14px] outline-none focus:border-primary"
                    />
                    <select
                        v-model="inviteForm.role"
                        class="rounded-xl border border-border px-3.5 py-2.5 text-[14px] outline-none"
                    >
                        <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
                    </select>
                    <button
                        type="submit"
                        :disabled="inviteForm.processing"
                        class="bg-primary text-white font-semibold text-[13.5px] rounded-xl px-5 py-2.5 disabled:opacity-60"
                    >
                        Send invite
                    </button>
                </form>
                <p v-if="inviteForm.errors.email" class="text-[12px] text-red mt-2">{{ inviteForm.errors.email }}</p>
            </div>

            <!-- Pending invitations -->
            <div v-if="pendingInvitations.length" class="bg-card border border-border rounded-2xl p-6">
                <h2 class="text-[15px] font-bold text-ink mb-4">Pending invitations</h2>
                <div class="space-y-3">
                    <div v-for="inv in pendingInvitations" :key="inv.id" class="flex items-center justify-between text-[13.5px]">
                        <div>
                            <p class="font-medium text-ink">{{ inv.email }}</p>
                            <p class="text-[11.5px] text-muted">{{ inv.role }} · invited by {{ inv.invited_by.name }}</p>
                        </div>
                        <button @click="revokeInvite(inv.id)" class="text-[12px] text-red font-semibold hover:underline">
                            Revoke
                        </button>
                    </div>
                </div>
            </div>

            <!-- Members -->
            <div class="bg-card border border-border rounded-2xl p-6">
                <h2 class="text-[15px] font-bold text-ink mb-4">Members</h2>
                <div class="space-y-4">
                    <div v-for="member in members" :key="member.id" class="flex items-center justify-between text-[13.5px]">
                        <div>
                            <p class="font-medium text-ink">{{ member.name }}</p>
                            <p class="text-[11.5px] text-muted">{{ member.email }}</p>
                        </div>
                        <select
                            :value="member.role"
                            @change="changeRole(member.id, ($event.target as HTMLSelectElement).value)"
                            class="text-[12.5px] rounded-lg border border-border px-2.5 py-1.5 outline-none"
                        >
                            <option value="owner">owner</option>
                            <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
