<script setup lang="ts">
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowRight,
    Globe,
    KeyRound,
    Layers,
    PlusCircle,
    RefreshCw,
    ServerCog,
    Split,
    TestTubeDiagonal,
} from '@lucide/vue';

const workflowSteps = [
    { label: 'Question', title: '提問', description: '使用者輸入待驗證問題，系統分類題型（Type A / B / C）。' },
    { label: 'Verification', title: '驗證', description: '向多個 LLM 供應端並行提問，各自獨立抽取結構化答案。' },
    { label: 'Consensus', title: '共識', description: '對齊 claim、偵測衝突，判定 Full / Majority / Minority / None 等狀態。' },
    { label: 'Verdict', title: '裁決', description: '產出信任等級、封頂原因與繁中裁決報告，完整保留 audit trail。' },
];

const trustLevels = [
    { level: 'High', description: '多數有效表態一致，且無重大封頂條件。' },
    { level: 'Medium', description: '有一定共識，但存在低區分度、部分缺席或輕度限制。' },
    { level: 'Low', description: '共識薄弱、衝突明顯，或缺乏外部證據支撐。' },
    { level: 'Unknown', description: '無法形成可靠判斷（如供應端大量失敗或證據不足）。' },
];

const capabilities = [
    { icon: KeyRound, title: '三槽 BYOK', description: '每位使用者自管 API 金鑰，在三個槽位配置 preset 或自訂 endpoint。' },
    { icon: Layers, title: '非同步驗證', description: '提交後背景執行，Show 頁可輪詢各供應端回應進度。' },
    { icon: TestTubeDiagonal, title: '訪客 Demo', description: '無需 API key，以 fixture 範例體驗完整共識流程與 Minority Report。' },
    { icon: Globe, title: 'Grounding', description: '對 Type C 等需即時資料的問題，可搭配外部搜尋摘要輔助判斷。' },
    { icon: RefreshCw, title: 'Replay', description: '以相同問題與供應端設定重跑驗證，保留 audit 可比對性。' },
];

const quickLinks = [
    { href: '/verifications/create', icon: PlusCircle, label: '新建驗證' },
    { href: '/settings/providers', icon: ServerCog, label: '供應端設定' },
    { href: '/demo', icon: TestTubeDiagonal, label: '訪客示範' },
];
</script>

<template>
    <AppLayout>
        <div class="space-y-10">
            <!-- Header -->
            <div class="space-y-3">
                <p class="text-sm font-semibold uppercase tracking-normal text-teal-700 dark:text-teal-300">
                    關於本專案
                </p>
                <h1 class="text-3xl font-semibold tracking-normal">關鍵報告是什麼？</h1>
                <p class="max-w-3xl text-base leading-7 text-neutral-600 dark:text-neutral-400">
                    「關鍵報告（Minority Report）」是一套 Multi-LLM 共識引擎。目標不是消除所有幻覺，而是
                    <strong class="font-medium text-neutral-950 dark:text-neutral-100">降低單一模型幻覺風險，並明確揭露多模型之間的共識、分歧與不確定性</strong>。
                </p>
                <p class="max-w-3xl text-sm leading-6 text-neutral-500">
                    靈感來自電影《關鍵報告》：不同預測者可能對未來產生不同預測，少數意見具有重要參考價值。當多個 LLM 對同一問題給出不同答案時，系統不會直接忽略少數意見，而是保留、分析並產出裁決報告。
                </p>
            </div>

            <!-- Philosophy -->
            <Card>
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <Split class="size-5 text-teal-600 dark:text-teal-400" />
                        <h2 class="text-lg font-semibold">核心哲學</h2>
                    </div>
                </CardHeader>
                <CardContent class="space-y-2">
                    <p class="text-sm font-medium text-neutral-950 dark:text-neutral-100">
                        Disagreement is a feature, not a bug.
                    </p>
                    <p class="text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                        模型間的不一致可能代表問題有歧義、知識截止日不同、搜尋來源不同、部分模型幻覺，或問題本身尚無定論。因此本系統不只追求「一致答案」，也必須呈現「為什麼不一致」。
                    </p>
                </CardContent>
            </Card>

            <!-- Workflow -->
            <section class="space-y-4">
                <h2 class="text-lg font-semibold">驗證流程</h2>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <article
                        v-for="(step, index) in workflowSteps"
                        :key="step.label"
                        class="rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <span class="text-xs font-medium uppercase tracking-wide text-teal-600 dark:text-teal-400">
                            {{ step.label }}
                        </span>
                        <p class="mt-1 text-sm font-semibold">
                            <span class="mr-1 text-neutral-400">{{ index + 1 }}.</span>
                            {{ step.title }}
                        </p>
                        <p class="mt-2 text-xs leading-5 text-neutral-500">
                            {{ step.description }}
                        </p>
                    </article>
                </div>
            </section>

            <!-- Trust levels -->
            <section class="space-y-4">
                <h2 class="text-lg font-semibold">信任等級</h2>
                <p class="text-sm text-neutral-600 dark:text-neutral-400">
                    系統輸出 High / Medium / Low / Unknown，採 base + caps 瀑布規則，<strong class="font-medium">不使用百分比分數</strong>。
                </p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div
                        v-for="item in trustLevels"
                        :key="item.level"
                        class="rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <span
                            class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold"
                            :class="{
                                'bg-teal-100 text-teal-700 dark:bg-teal-900 dark:text-teal-300': item.level === 'High',
                                'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300': item.level === 'Medium',
                                'bg-rose-100 text-rose-700 dark:bg-rose-900 dark:text-rose-300': item.level === 'Low',
                                'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400': item.level === 'Unknown',
                            }"
                        >
                            {{ item.level }}
                        </span>
                        <p class="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                            {{ item.description }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Capabilities -->
            <section class="space-y-4">
                <h2 class="text-lg font-semibold">目前能力</h2>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="cap in capabilities"
                        :key="cap.title"
                        class="flex gap-3 rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <component :is="cap.icon" class="mt-0.5 size-5 shrink-0 text-teal-600 dark:text-teal-400" />
                        <div>
                            <h3 class="text-sm font-semibold">{{ cap.title }}</h3>
                            <p class="mt-1 text-xs leading-5 text-neutral-500">{{ cap.description }}</p>
                        </div>
                    </article>
                </div>
            </section>

            <!-- Limitations -->
            <div class="flex gap-3 rounded-lg border border-amber-200 bg-amber-50 p-5 dark:border-amber-900 dark:bg-amber-950/50">
                <AlertTriangle class="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-400" />
                <div class="space-y-2">
                    <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">已知限制</p>
                    <ul class="list-inside list-disc space-y-1 text-sm leading-6 text-amber-800 dark:text-amber-300/90">
                        <li>多模型共識不代表事實正確，請將報告視為輔助判斷而非最終真理。</li>
                        <li>Type C（需即時資料）問題若無 grounding，信任等級會被封頂。</li>
                        <li>訪客 Demo 使用 fixture 模擬；真實三模型在簡單是非題上常高度一致，少數意見較難自然觸發。</li>
                    </ul>
                </div>
            </div>

            <!-- Quick links -->
            <section class="space-y-4">
                <h2 class="text-lg font-semibold">快速連結</h2>
                <div class="flex flex-wrap gap-3">
                    <Link
                        v-for="link in quickLinks"
                        :key="link.href"
                        :href="link.href"
                        class="inline-flex min-h-10 items-center gap-2 rounded-md border border-neutral-200 bg-white px-4 text-sm font-medium transition hover:bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:bg-neutral-800"
                    >
                        <component :is="link.icon" class="size-4 text-teal-600 dark:text-teal-400" />
                        {{ link.label }}
                        <ArrowRight class="size-3.5 text-neutral-400" />
                    </Link>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
