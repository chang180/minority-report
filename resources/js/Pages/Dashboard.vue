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
                <h1 class="text-2xl font-semibold tracking-normal">儀表板</h1>
                <p class="text-neutral-600 dark:text-neutral-400">
                    目前以 {{ user?.name }} 身分登入。M7-B 會將此處替換為供應端就緒度與驗證紀錄。
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <Link href="/demo" class="rounded-lg border border-neutral-200 bg-white p-5 transition hover:bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:bg-neutral-800">
                    <h2 class="font-semibold">執行訪客示範</h2>
                    <p class="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                        透過新的 `/demo` 路由使用 M6 模擬範例。
                    </p>
                </Link>
                <Link href="/settings/profile" class="rounded-lg border border-neutral-200 bg-white p-5 transition hover:bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:bg-neutral-800">
                    <h2 class="font-semibold">帳號設定</h2>
                    <p class="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                        透過帳號系統支援的設定頁更新個人資料與密碼。
                    </p>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
