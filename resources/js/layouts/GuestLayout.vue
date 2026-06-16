<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LogIn, TestTubeDiagonal, UserPlus } from '@lucide/vue';
</script>

<template>
    <main class="min-h-screen bg-neutral-50 text-neutral-950 dark:bg-neutral-950 dark:text-neutral-50">
        <header class="sticky top-0 z-10 border-b border-neutral-200 bg-white/90 backdrop-blur dark:border-neutral-800 dark:bg-neutral-950/90">
            <div class="mx-auto flex min-h-16 w-full max-w-6xl flex-wrap items-center justify-between gap-3 px-5 py-3">
                <Link href="/" class="text-base font-semibold tracking-normal">
                    關鍵報告
                </Link>

                <nav class="flex flex-wrap items-center gap-1 text-sm">
                    <Link
                        href="/demo"
                        class="inline-flex min-h-9 items-center gap-2 rounded-md px-3 text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-900"
                    >
                        <TestTubeDiagonal class="size-4" />
                        示範
                    </Link>
                    <Link
                        href="/login"
                        class="inline-flex min-h-9 items-center gap-2 rounded-md px-3 text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-900"
                    >
                        <LogIn class="size-4" />
                        登入
                    </Link>
                    <Link
                        href="/register"
                        class="inline-flex min-h-9 items-center gap-2 rounded-md bg-teal-600 px-3 font-semibold text-white transition hover:bg-teal-700"
                    >
                        <UserPlus class="size-4" />
                        註冊
                    </Link>
                </nav>
            </div>
        </header>

        <slot />
    </main>
</template>
