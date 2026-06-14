<script setup lang="ts">
import type { Auth } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ClipboardList, Database, Globe, LogOut, ServerCog, Settings, ShieldCheck, TestTubeDiagonal } from '@lucide/vue';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => (page.props.auth as Auth | undefined)?.user ?? null);
const isAdmin = computed(() => user.value?.role === 'admin');
</script>

<template>
    <main class="min-h-screen bg-neutral-50 text-neutral-950 dark:bg-neutral-950 dark:text-neutral-50">
        <header class="border-b border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-950">
            <div class="mx-auto flex min-h-16 w-full max-w-6xl flex-wrap items-center justify-between gap-3 px-5 py-3">
                <div class="flex items-center gap-4">
                    <Link href="/dashboard" class="text-base font-semibold tracking-normal">
                        關鍵報告
                    </Link>
                    <nav class="flex items-center gap-1 text-sm">
                        <Link href="/verifications" class="inline-flex min-h-9 items-center gap-2 rounded-md px-3 text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-900">
                            <ClipboardList class="size-4" />
                            我的驗證
                        </Link>
                        <Link href="/verifications/create" class="inline-flex min-h-9 items-center gap-2 rounded-md px-3 text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-900">
                            <ShieldCheck class="size-4" />
                            新建驗證
                        </Link>
                        <Link href="/demo" class="inline-flex min-h-9 items-center gap-2 rounded-md px-3 text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-900">
                            <TestTubeDiagonal class="size-4" />
                            訪客示範
                        </Link>
                        <Link href="/settings/providers" class="inline-flex min-h-9 items-center gap-2 rounded-md px-3 text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-900">
                            <ServerCog class="size-4" />
                            供應端設定
                        </Link>
                        <Link v-if="isAdmin" href="/admin/demo" class="inline-flex min-h-9 items-center gap-2 rounded-md px-3 text-teal-700 hover:bg-teal-50 dark:text-teal-400 dark:hover:bg-teal-950">
                            <Database class="size-4" />
                            Demo 管理
                        </Link>
                        <Link v-if="isAdmin" href="/admin/grounding" class="inline-flex min-h-9 items-center gap-2 rounded-md px-3 text-teal-700 hover:bg-teal-50 dark:text-teal-400 dark:hover:bg-teal-950">
                            <Globe class="size-4" />
                            Grounding 設定
                        </Link>
                        <Link href="/settings/profile" class="inline-flex min-h-9 items-center gap-2 rounded-md px-3 text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-900">
                            <Settings class="size-4" />
                            設定
                        </Link>
                    </nav>
                </div>

                <div class="flex items-center gap-3 text-sm">
                    <span class="hidden text-neutral-600 dark:text-neutral-400 sm:inline">{{ user?.email }}</span>
                    <Link href="/logout" method="post" as="button" class="inline-flex min-h-9 items-center gap-2 rounded-md px-3 text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-900">
                        <LogOut class="size-4" />
                        登出
                    </Link>
                </div>
            </div>
        </header>

        <section class="mx-auto w-full max-w-6xl px-5 py-8">
            <slot />
        </section>
    </main>
</template>
