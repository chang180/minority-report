<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <main class="min-h-screen bg-neutral-50 text-neutral-950 dark:bg-neutral-950 dark:text-neutral-50">
        <div class="mx-auto flex min-h-screen w-full max-w-md flex-col justify-center gap-8 px-5 py-10">
            <Link href="/" class="text-center text-lg font-semibold tracking-normal">
                關鍵報告
            </Link>
            <slot />
        </div>
    </main>
</template>
