<script setup lang="ts">
import type { Auth } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => (page.props.auth as Auth | undefined)?.user);
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="space-y-2">
                <h1 class="text-2xl font-semibold tracking-normal">Dashboard</h1>
                <p class="text-neutral-600 dark:text-neutral-400">
                    Signed in as {{ user?.name }}. M7-B will replace this placeholder with provider readiness and verification history.
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <Link href="/demo" class="rounded-lg border border-neutral-200 bg-white p-5 transition hover:bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:bg-neutral-800">
                    <h2 class="font-semibold">Run guest demo</h2>
                    <p class="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                        Use the M6 fake fixtures through the new `/demo` route.
                    </p>
                </Link>
                <Link href="/settings/profile" class="rounded-lg border border-neutral-200 bg-white p-5 transition hover:bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:bg-neutral-800">
                    <h2 class="font-semibold">Account settings</h2>
                    <p class="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                        Update your profile and password through Fortify-backed settings pages.
                    </p>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
