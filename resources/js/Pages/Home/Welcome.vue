<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import GuestLayout from '@/layouts/GuestLayout.vue';
import { Link } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowRight,
    FileText,
    Gauge,
    Globe,
    KeyRound,
    LogIn,
    MessageCircleQuestion,
    Split,
    TestTubeDiagonal,
    Users,
} from '@lucide/vue';

const steps = [
    {
        icon: MessageCircleQuestion,
        title: '提出問題',
        description: '輸入你想驗證的是非題或開放式問題，系統會自動分類題型。',
    },
    {
        icon: Users,
        title: '多模型驗證',
        description: '同時向多個 LLM 供應端提問，各自獨立抽取結構化答案。',
    },
    {
        icon: FileText,
        title: '共識報告',
        description: '彙整共識、分歧與少數意見，並依規則給出信任等級與裁決說明。',
    },
];

const features = [
    {
        icon: Split,
        iconClass: 'text-teal-700 dark:text-teal-300',
        title: '保留分歧',
        description: '多數意見、少數意見與無共識狀態都會保存並呈現，而不是只輸出單一答案。',
    },
    {
        icon: Gauge,
        iconClass: 'text-amber-600 dark:text-amber-300',
        title: '信任等級封頂',
        description: '共識不等於正確；系統會依外部證據、有效表態與衝突類型限制信任等級。',
    },
    {
        icon: Globe,
        iconClass: 'text-sky-600 dark:text-sky-300',
        title: 'Grounding 輔助',
        description: '對需要即時資料的問題，可搭配外部搜尋摘要輔助判斷，並反映在信任封頂規則中。',
    },
    {
        icon: KeyRound,
        iconClass: 'text-violet-600 dark:text-violet-300',
        title: 'BYOK 自管 API',
        description: '登入後可自備 API 金鑰，在三個供應端槽位自由組合 preset 或自訂 endpoint。',
    },
];
</script>

<template>
    <GuestLayout>
        <!-- Hero -->
        <section class="border-b border-neutral-200 bg-gradient-to-b from-teal-50/80 to-neutral-50 dark:border-neutral-800 dark:from-teal-950/30 dark:to-neutral-950">
            <div class="mx-auto grid w-full max-w-6xl gap-10 px-5 py-16 lg:grid-cols-[1.15fr_0.85fr] lg:items-center lg:py-20">
                <div class="space-y-7">
                    <div class="space-y-4">
                        <p class="text-sm font-semibold uppercase tracking-normal text-teal-700 dark:text-teal-300">
                            多模型共識引擎
                        </p>
                        <h1 class="max-w-3xl text-4xl font-semibold tracking-normal text-neutral-950 dark:text-white sm:text-5xl">
                            關鍵報告
                        </h1>
                        <p class="max-w-2xl text-lg leading-8 text-neutral-700 dark:text-neutral-300">
                            用多個模型的共識、分歧與少數意見，降低單一模型幻覺風險。不只給你答案，也告訴你模型們為何一致或不一致。
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <Link href="/demo" class="inline-flex">
                            <Button size="lg" class="w-full sm:w-auto">
                                <TestTubeDiagonal class="size-4" />
                                開啟示範
                            </Button>
                        </Link>
                        <Link href="/login" class="inline-flex">
                            <Button variant="outline" size="lg" class="w-full sm:w-auto">
                                <LogIn class="size-4" />
                                登入
                            </Button>
                        </Link>
                        <Link href="/register" class="inline-flex">
                            <Button variant="secondary" size="lg" class="w-full sm:w-auto">
                                建立帳號
                                <ArrowRight class="size-4" />
                            </Button>
                        </Link>
                    </div>
                </div>

                <div class="relative hidden lg:block">
                    <div class="absolute -inset-4 rounded-2xl bg-teal-100/50 blur-2xl dark:bg-teal-900/20" />
                    <Card class="relative overflow-hidden shadow-md">
                        <CardHeader class="border-b border-neutral-200 bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900">
                            <p class="text-xs font-medium uppercase tracking-wide text-neutral-500">驗證流程預覽</p>
                            <p class="text-sm font-semibold">水的沸點在海平面是 100 度嗎？</p>
                        </CardHeader>
                        <CardContent class="space-y-3 pt-4">
                            <div class="flex items-center justify-between rounded-md bg-teal-50 px-3 py-2 text-sm dark:bg-teal-950">
                                <span class="text-neutral-600 dark:text-neutral-400">共識狀態</span>
                                <span class="font-medium text-teal-700 dark:text-teal-300">Full Consensus</span>
                            </div>
                            <div class="flex items-center justify-between rounded-md bg-amber-50 px-3 py-2 text-sm dark:bg-amber-950">
                                <span class="text-neutral-600 dark:text-neutral-400">信任等級</span>
                                <span class="font-medium text-amber-700 dark:text-amber-300">High</span>
                            </div>
                            <p class="text-xs leading-5 text-neutral-500">
                                三個供應端一致認同，且無需即時外部證據封頂。
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section class="mx-auto w-full max-w-6xl px-5 py-14">
            <div class="mb-8 space-y-2 text-center">
                <h2 class="text-2xl font-semibold tracking-normal">如何運作</h2>
                <p class="text-sm text-neutral-600 dark:text-neutral-400">
                    從提問到裁決報告，三步完成多模型驗證。
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <article
                    v-for="(step, index) in steps"
                    :key="step.title"
                    class="relative rounded-lg border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <span class="mb-3 inline-flex size-8 items-center justify-center rounded-full bg-teal-100 text-sm font-semibold text-teal-700 dark:bg-teal-900 dark:text-teal-300">
                        {{ index + 1 }}
                    </span>
                    <component :is="step.icon" class="mb-3 size-5 text-teal-600 dark:text-teal-400" />
                    <h3 class="font-semibold">{{ step.title }}</h3>
                    <p class="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                        {{ step.description }}
                    </p>
                </article>
            </div>
        </section>

        <!-- Features -->
        <section class="border-t border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
            <div class="mx-auto w-full max-w-6xl px-5 py-14">
                <div class="mb-8 space-y-2">
                    <h2 class="text-2xl font-semibold tracking-normal">為什麼需要關鍵報告</h2>
                    <p class="text-sm text-neutral-600 dark:text-neutral-400">
                        模型間的不一致本身就是資訊——我們把它留下來，而不是藏起來。
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <article
                        v-for="feature in features"
                        :key="feature.title"
                        class="rounded-lg border border-neutral-200 bg-neutral-50 p-5 transition hover:shadow-sm dark:border-neutral-800 dark:bg-neutral-950"
                    >
                        <div class="flex items-start gap-3">
                            <component :is="feature.icon" :class="feature.iconClass" class="mt-0.5 size-5 shrink-0" />
                            <div>
                                <h3 class="font-semibold">{{ feature.title }}</h3>
                                <p class="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                                    {{ feature.description }}
                                </p>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- Trust disclaimer -->
        <section class="mx-auto w-full max-w-6xl px-5 py-10">
            <div class="flex gap-3 rounded-lg border border-amber-200 bg-amber-50 p-5 dark:border-amber-900 dark:bg-amber-950/50">
                <AlertTriangle class="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-400" />
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">共識 ≠ 正確</p>
                    <p class="text-sm leading-6 text-amber-800 dark:text-amber-300/90">
                        多個模型同意，不代表答案一定正確。系統會依題型、證據與衝突類型給出 High / Medium / Low / Unknown 信任等級，而非百分比分數。
                    </p>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-neutral-200 dark:border-neutral-800">
            <div class="mx-auto flex w-full max-w-6xl flex-wrap items-center justify-between gap-3 px-5 py-6 text-sm text-neutral-500">
                <p>關鍵報告 · Minority Report</p>
                <Link href="/demo" class="text-teal-600 hover:underline dark:text-teal-400">
                    立即體驗訪客示範
                </Link>
            </div>
        </footer>
    </GuestLayout>
</template>
