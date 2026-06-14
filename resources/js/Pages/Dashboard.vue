<script setup lang="ts">
import type { Auth } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { CheckCircle, Circle, PlusCircle, Settings, TestTubeDiagonal } from '@lucide/vue';

type SlotStatus = {
    slot: string;
    type: 'preset' | 'custom' | null;
    provider_label: string;
    ready: boolean;
};

type RecentVerification = {
    id: number;
    question: string;
    final_trust: string | null;
    final_verdict: string | null;
    created_at: string;
};

const props = defineProps<{
    slotStatuses: SlotStatus[];
    recentVerifications: RecentVerification[];
    totalVerifications: number;
}>();

const page = usePage();
const user = computed(() => (page.props.auth as Auth | undefined)?.user);
const readyCount = computed(() => props.slotStatuses.filter((s) => s.ready).length);

const SLOT_LABEL: Record<string, string> = {
    openai: 'OpenAI 槽',
    anthropic: 'Anthropic 槽',
    gemini: 'Gemini 槽',
};

function trustBadgeClass(trust: string | null) {
    if (!trust) return 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400';
    if (trust === 'High') return 'bg-teal-100 text-teal-700 dark:bg-teal-900 dark:text-teal-300';
    if (trust === 'Medium') return 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300';
    return 'bg-rose-100 text-rose-700 dark:bg-rose-900 dark:text-rose-300';
}
</script>

<template>
    <AppLayout>
        <div class="space-y-8">
            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-normal">儀表板</h1>
                    <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">歡迎回來，{{ user?.name }}。</p>
                </div>
                <Link
                    href="/verifications/create"
                    class="inline-flex min-h-10 items-center gap-2 rounded-md bg-teal-600 px-4 text-sm font-semibold text-white transition hover:bg-teal-700"
                >
                    <PlusCircle class="size-4" />
                    新建驗證
                </Link>
            </div>

            <!-- Provider Readiness -->
            <section class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold">供應端就緒度</h2>
                    <Link href="/settings/providers" class="flex items-center gap-1 text-sm text-teal-600 hover:underline dark:text-teal-400">
                        <Settings class="size-4" />
                        設定供應端
                    </Link>
                </div>
                <div class="grid gap-3 sm:grid-cols-3">
                    <div
                        v-for="s in slotStatuses"
                        :key="s.slot"
                        :class="s.ready ? 'border-teal-200 bg-teal-50 dark:border-teal-800 dark:bg-teal-950' : 'border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900'"
                        class="flex items-center gap-3 rounded-lg border p-4"
                    >
                        <CheckCircle v-if="s.ready" class="size-5 shrink-0 text-teal-500" />
                        <Circle v-else class="size-5 shrink-0 text-neutral-400" />
                        <div>
                            <p class="text-sm font-medium">{{ SLOT_LABEL[s.slot] ?? s.slot }}</p>
                            <p class="text-xs text-neutral-500">{{ s.provider_label }}</p>
                        </div>
                    </div>
                </div>
                <p v-if="readyCount === 0" class="text-sm text-amber-600 dark:text-amber-400">
                    尚未設定任何供應端，請先
                    <Link href="/settings/providers" class="underline">設定供應端</Link>
                    再執行驗證。
                </p>
            </section>

            <!-- Summary Cards -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-lg border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <p class="text-sm text-neutral-500">已完成驗證</p>
                    <p class="mt-1 text-3xl font-bold">{{ totalVerifications }}</p>
                </div>
                <div class="rounded-lg border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <p class="text-sm text-neutral-500">就緒供應端</p>
                    <p class="mt-1 text-3xl font-bold">{{ readyCount }} / 3</p>
                </div>
                <Link
                    href="/demo"
                    class="rounded-lg border border-neutral-200 bg-white p-5 transition hover:bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:bg-neutral-800"
                >
                    <div class="flex items-center gap-2">
                        <TestTubeDiagonal class="size-4 text-teal-500" />
                        <p class="text-sm font-medium">前往訪客示範</p>
                    </div>
                    <p class="mt-2 text-xs text-neutral-500">使用模擬範例體驗共識流程</p>
                </Link>
            </div>

            <!-- Recent Verifications -->
            <section class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold">近期驗證</h2>
                    <Link href="/verifications" class="text-sm text-teal-600 hover:underline dark:text-teal-400">
                        查看全部
                    </Link>
                </div>

                <div v-if="recentVerifications.length === 0" class="rounded-lg border border-dashed border-neutral-300 p-6 text-center text-sm text-neutral-500 dark:border-neutral-700">
                    尚無驗證紀錄。
                    <Link href="/verifications/create" class="ml-1 text-teal-600 underline dark:text-teal-400">立即新建</Link>
                </div>

                <div v-else class="divide-y divide-neutral-200 overflow-hidden rounded-lg border border-neutral-200 dark:divide-neutral-800 dark:border-neutral-800">
                    <Link
                        v-for="v in recentVerifications"
                        :key="v.id"
                        :href="`/verifications/${v.id}`"
                        class="flex items-start justify-between gap-4 p-4 transition hover:bg-neutral-50 dark:hover:bg-neutral-900"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">{{ v.question }}</p>
                            <p class="mt-0.5 text-xs text-neutral-500">{{ v.created_at }}</p>
                        </div>
                        <span v-if="v.final_trust" :class="trustBadgeClass(v.final_trust)" class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium">
                            {{ v.final_trust }}
                        </span>
                    </Link>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
