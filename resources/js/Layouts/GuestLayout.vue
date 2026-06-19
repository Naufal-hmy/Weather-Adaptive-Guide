<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-slate-50 via-slate-100 to-indigo-50/30 p-4 font-sans text-slate-900"
    >
        <div class="mb-6">
            <Link href="/" class="focus:outline-none focus:ring-2 focus:ring-indigo-500/20 rounded-xl p-2 inline-block">
                <ApplicationLogo class="h-12 w-auto" />
            </Link>
        </div>

        <div
            class="w-full overflow-hidden bg-white px-8 py-8 shadow-xl border border-slate-100 rounded-2xl sm:max-w-md"
        >
            <slot />
        </div>
    </div>
</template>
