<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

type Verification = {
    id: number;
    question: string;
    processing_status: string;
    final_trust: string | null;
    created_at: string;
};

type PaginatedVerifications = {
    data: Verification[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: { url: string | null; label: string; active: boolean }[];
};

defineProps<{
    verifications: PaginatedVerifications;
}>();

function statusLabel(status: string): string {
    const map: Record<string, string> = {
        pending: '等待處理',
        running: '分析中',
        completed: '已完成',
        failed: '處理失敗',
    };
    return map[status] ?? status;
}

function statusClass(status: string): string {
    if (status === 'completed') {
        return 'bg-teal-100 text-teal-700 dark:bg-teal-900 dark:text-teal-300';
    }
    if (status === 'running') {
        return 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300';
    }
    if (status === 'failed') {
        return 'bg-rose-100 text-rose-700 dark:bg-rose-900 dark:text-rose-300';
    }
    return 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400';
}

function trustClass(trust: string | null): string {
    if (!trust) { return 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400'; }
    if (trust === 'High') { return 'bg-teal-100 text-teal-700 dark:bg-teal-900 dark:text-teal-300'; }
    if (trust === 'Medium') { return 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300'; }
    return 'bg-rose-100 text-rose-700 dark:bg-rose-900 dark:text-rose-300';
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-normal">我的驗證</h1>
                    <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">共 {{ verifications.total }} 筆</p>
                </div>
                <Link
                    href="/verifications/create"
                    class="inline-flex min-h-10 items-center gap-2 rounded-md bg-teal-600 px-4 text-sm font-semibold text-white transition hover:bg-teal-700"
                >
                    新建驗證
                </Link>
            </div>

            <div v-if="verifications.data.length === 0" class="rounded-lg border border-dashed border-neutral-300 p-8 text-center text-sm text-neutral-500 dark:border-neutral-700">
                尚無驗證紀錄。
                <Link href="/verifications/create" class="ml-1 text-teal-600 underline dark:text-teal-400">立即新建</Link>
            </div>

            <div v-else class="divide-y divide-neutral-200 overflow-hidden rounded-lg border border-neutral-200 dark:divide-neutral-800 dark:border-neutral-800">
                <Link
                    v-for="v in verifications.data"
                    :key="v.id"
                    :href="`/verifications/${v.id}`"
                    class="flex items-start justify-between gap-4 p-4 transition hover:bg-neutral-50 dark:hover:bg-neutral-900"
                >
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">{{ v.question }}</p>
                        <p class="mt-0.5 text-xs text-neutral-500">{{ v.created_at }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <span :class="statusClass(v.processing_status)" class="rounded-full px-2 py-0.5 text-xs font-medium">
                            {{ statusLabel(v.processing_status) }}
                        </span>
                        <span v-if="v.final_trust" :class="trustClass(v.final_trust)" class="rounded-full px-2 py-0.5 text-xs font-medium">
                            {{ v.final_trust }}
                        </span>
                    </div>
                </Link>
            </div>

            <!-- Pagination -->
            <nav v-if="verifications.last_page > 1" class="flex flex-wrap items-center justify-center gap-1">
                <template v-for="link in verifications.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="inline-flex min-h-9 min-w-9 items-center justify-center rounded-md px-3 text-sm transition"
                        :class="link.active ? 'bg-teal-600 text-white' : 'hover:bg-neutral-100 dark:hover:bg-neutral-800'"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="inline-flex min-h-9 min-w-9 items-center justify-center rounded-md px-3 text-sm text-neutral-400"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </div>
    </AppLayout>
</template>
