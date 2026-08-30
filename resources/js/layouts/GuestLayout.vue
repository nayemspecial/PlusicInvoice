<script setup lang="ts">
/**
 * Minimal layout for unauthenticated pages: login, register, password reset.
 * Deliberately NOT the full app shell (no sidebar) — those pages don't need it,
 * and building this small layout now unblocks Phase 4 (Custom Authentication)
 * without waiting on the full AppLayout (built later, in Phase 6, alongside the
 * dashboard/invoice pages that actually need the sidebar).
 *
 * Colors/fonts here match design_references/index.html exactly — see
 * resources/css/app.css for the shared theme tokens (--color-*, font-sans, font-mono).
 */
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-canvas px-6">
        <div class="w-full max-w-sm">
            <div class="flex items-center justify-center gap-2.5 mb-8">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center font-extrabold text-[13px] text-white bg-primary">
                    P
                </div>
                <span class="font-extrabold text-[17px] text-ink">PlusicInvoice</span>
            </div>

            <div class="bg-card border border-border rounded-2xl p-8 shadow-sm">
                <slot />
            </div>

            <p class="text-center text-[12px] font-mono text-muted mt-6">
                &copy; {{ new Date().getFullYear() }} PlusicInvoice
            </p>
        </div>
    </div>
</template>
