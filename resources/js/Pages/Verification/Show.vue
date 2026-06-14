<script setup lang="ts">
import ProviderLivePanel from '@/components/Verification/ProviderLivePanel.vue';
import { CONSENSUS_SLOT_KEYS, consensusSlotLabel } from '@/lib/consensusSlots';
import { Link, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

type GroundingSource = {
    title: string;
    url: string;
    snippet: string;
};

type GroundingMeta = {
    status: string;
    provider_mode: string;
    query: string;
    summary: string;
    sources: GroundingSource[];
};

type Verification = {
    id: number;
    question: string;
    processing_status: string;
    classified_type: string | null;
    classifier_confidence: string | null;
    answer_shape: string | null;
    requires_grounding: boolean;
    grounding_available: boolean;
    consensus_summary: Record<string, unknown> | null;
    final_trust: string | null;
    final_verdict: string | null;
    errors: unknown[] | null;
    metadata: Record<string, unknown> | null;
    created_at: string | null;
};

type ProviderResponse = {
    id: number;
    provider: string;
    model: string | null;
    provider_status: string;
    extraction_status: string;
    raw_answer: string | null;
    normalized: Record<string, unknown> | null;
    claims: unknown[];
    error: Record<string, unknown> | null;
};

type ConsensusResult = {
    alignment: Record<string, unknown> | null;
    conflict_detection: unknown[] | null;
    consensus: Record<string, unknown> | null;
    decision_key: string | null;
    decision_basis: Record<string, unknown> | null;
    trust_base: string | null;
    applied_caps: unknown[] | null;
    trust_level: string | null;
    verdict_report: Record<string, unknown> | null;
    errors: unknown[] | null;
    metadata: Record<string, unknown> | null;
};

const CONSENSUS_SLOTS = CONSENSUS_SLOT_KEYS;

type SlotState = 'waiting' | 'running' | 'done' | 'failed';

type StatusPayload = {
    processing_status: string;
    workflow_phase: string | null;
    processing_error: string | null;
    provider_responses: ProviderResponse[];
};

const props = defineProps<{
    verification: Verification;
    providerResponses: ProviderResponse[];
    consensusResult: ConsensusResult | null;
}>();

const isDemo = computed(() => {
    const meta = props.verification.metadata;
    return meta && meta['source'] === 'demo';
});

const processingStatus = computed(() => props.verification.processing_status ?? 'pending');
const isPending = computed(() => processingStatus.value === 'pending');
const isRunning = computed(() => processingStatus.value === 'running');
const isFailed = computed(() => processingStatus.value === 'failed');
const isCompleted = computed(() => {
    if (processingStatus.value === 'completed') {
        return true;
    }

    // Demo is synchronous; treat finished consensus as completed even if DB still says pending.
    return isDemo.value && props.consensusResult !== null && ! isFailed.value;
});
const isProcessing = computed(() => ! isDemo.value && (isPending.value || isRunning.value));

const processingError = computed(() => props.verification.metadata?.['processing_error'] as string | null ?? null);

const groundingMeta = computed(() => props.verification.metadata?.grounding as GroundingMeta | undefined);
const groundingSources = computed(() => groundingMeta.value?.sources ?? []);
const groundingSummary = computed(() => groundingMeta.value?.summary ?? '');

const consensusStatus = computed(() => stringValue(props.consensusResult?.consensus?.status) ?? 'Unknown');
const minorityProvider = computed(() => {
    const key = stringValue(props.consensusResult?.consensus?.minority_provider);

    return key ? consensusSlotLabel(key) : null;
});
const verdictSummary = computed(() => stringValue(props.consensusResult?.verdict_report?.summary));
const verdictLines = computed(() => (props.verification.final_verdict ?? '').split('\n').filter(Boolean));
const verdictMetadata = computed(() => props.consensusResult?.verdict_report?.metadata as Record<string, unknown> | undefined);
const hasMinorityReport = computed(() => Boolean(verdictMetadata.value?.has_minority_report));

const replayProcessing = ref(false);
const liveStatus = ref(props.verification.processing_status);
const liveWorkflowPhase = ref<string | null>(
    typeof props.verification.metadata?.workflow_phase === 'string'
        ? props.verification.metadata.workflow_phase
        : null,
);
const liveProviderResponses = ref<ProviderResponse[]>(dedupeProviderResponses(props.providerResponses));

let pollTimer: ReturnType<typeof setInterval> | null = null;

function dedupeProviderResponses(responses: ProviderResponse[]): ProviderResponse[] {
    const byProvider = new Map<string, ProviderResponse>();

    for (const response of responses) {
        const existing = byProvider.get(response.provider);

        if (existing === undefined || response.id > existing.id) {
            byProvider.set(response.provider, response);
        }
    }

    return CONSENSUS_SLOTS
        .map((slot) => byProvider.get(slot))
        .filter((response): response is ProviderResponse => response !== undefined);
}

function mergeLiveResponses(incoming: ProviderResponse[]): void {
    liveProviderResponses.value = dedupeProviderResponses([...liveProviderResponses.value, ...incoming]);
}

function responseForSlot(slot: (typeof CONSENSUS_SLOTS)[number]): ProviderResponse | null {
    return liveProviderResponses.value.find((response) => response.provider === slot) ?? null;
}

function isAnalyzable(response: ProviderResponse): boolean {
    return response.provider_status === 'success' && response.extraction_status === 'success';
}

function slotState(slot: (typeof CONSENSUS_SLOTS)[number]): SlotState {
    const response = responseForSlot(slot);
    const phase = liveWorkflowPhase.value;

    if (response) {
        if (isAnalyzable(response)) {
            return 'done';
        }

        if (response.provider_status !== 'success') {
            return 'failed';
        }

        if (phase === 'dispatching' || phase === 'extracting' || phase === 'synthesizing' || response.extraction_status === 'not_started') {
            return 'running';
        }

        return response.extraction_status === 'success' ? 'done' : 'failed';
    }

    if (liveStatus.value === 'running' && (phase === 'dispatching' || phase === null)) {
        return 'running';
    }

    return 'waiting';
}

const processingPhaseMessage = computed((): string => {
    const phase = liveWorkflowPhase.value;

    if (phase === 'dispatching') {
        return '三個共識席同時呼叫模型，任一完成就會在下方顯示。';
    }

    if (phase === 'extracting') {
        return '正在抽取各席結構化 claims…';
    }

    if (phase === 'analyzing') {
        return '三席回應已完成，正在對齊 claims 與產生判定…';
    }

    if (phase === 'synthesizing') {
        return '系統判定已完成，指定整理席正在撰寫最終報告…';
    }

    return '三個共識席並行處理中，完成一個就會在下方顯示。';
});

const processingPhaseLabel = computed((): string => {
    const phase = liveWorkflowPhase.value;

    if (phase === 'dispatching') {
        return '呼叫模型中…';
    }

    if (phase === 'extracting') {
        return '抽取 claims 中…';
    }

    if (phase === 'analyzing') {
        return '裁決分析中…';
    }

    if (phase === 'synthesizing') {
        return '整理報告中…';
    }

    return isPending.value ? '等待處理…' : '分析中…';
});

function startPolling(): void {
    if (pollTimer) { return; }

    pollTimer = setInterval(async () => {
        try {
            const res = await fetch(`/verifications/${props.verification.id}/status`, {
                headers: { Accept: 'application/json' },
            });
            if (!res.ok) { return; }

            const data = await res.json() as StatusPayload;
            liveStatus.value = data.processing_status;
            liveWorkflowPhase.value = data.workflow_phase ?? null;

            if (Array.isArray(data.provider_responses)) {
                mergeLiveResponses(data.provider_responses);
            }

            if (data.processing_status === 'completed' || data.processing_status === 'failed') {
                stopPolling();
                router.reload({ only: ['verification', 'providerResponses', 'consensusResult'] });
            }
        } catch {
            // ignore network errors during polling
        }
    }, 1500);
}

function stopPolling(): void {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
}

onMounted(() => {
    if (isProcessing.value) {
        startPolling();
    }
});

onUnmounted(() => {
    stopPolling();
});

function stringValue(value: unknown): string | null {
    return typeof value === 'string' && value !== '' ? value : null;
}

function normalizedSummary(response: ProviderResponse): string {
    return stringValue(response.normalized?.summary) ?? '沒有抽取到摘要。';
}

function directAnswer(response: ProviderResponse): string {
    return stringValue(response.normalized?.direct_answer) ?? '無法取得';
}

function formatJson(value: unknown): string {
    return JSON.stringify(value ?? null, null, 2);
}

function badgeClass(value: string | null): string {
    if (value === 'High' || value === 'Full') {
        return 'border-emerald-300/30 bg-emerald-300/10 text-emerald-200';
    }

    if (value === 'Medium' || value === 'Majority' || value === 'Full (2-only)') {
        return 'border-amber-300/30 bg-amber-300/10 text-amber-100';
    }

    if (value === 'Low' || value === 'None') {
        return 'border-orange-300/30 bg-orange-300/10 text-orange-100';
    }

    return 'border-neutral-300/20 bg-white/10 text-neutral-200';
}

function backHref(): string {
    return isDemo.value ? '/demo' : '/verifications';
}

function backLabel(): string {
    return isDemo.value ? '新增驗證' : '我的驗證';
}

function statusLabel(status: string): string {
    const map: Record<string, string> = {
        pending: '等待處理',
        running: '分析中',
        completed: '已完成',
        failed: '處理失敗',
    };
    return map[status] ?? status;
}
</script>

<template>
    <main class="min-h-screen bg-neutral-950 text-neutral-100">
        <section class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-5 py-8 sm:px-8 lg:px-10">
            <header class="flex flex-col gap-4 border-b border-white/10 pb-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-3">
                    <Link :href="backHref()" class="text-sm font-medium text-teal-300 hover:text-teal-200">
                        {{ backLabel() }}
                    </Link>
                    <h1 class="text-2xl font-semibold tracking-normal text-white sm:text-3xl">
                        驗證 #{{ verification.id }}
                    </h1>
                    <p class="max-w-3xl text-base leading-7 text-neutral-300">
                        {{ verification.question }}
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-2 text-sm sm:grid-cols-4 lg:min-w-xl">
                    <div class="rounded border border-white/10 bg-white/5 p-3">
                        <p class="text-xs uppercase text-neutral-500">狀態</p>
                        <p class="mt-1 font-semibold text-white">{{ statusLabel(verification.processing_status) }}</p>
                    </div>
                    <div class="rounded border border-white/10 bg-white/5 p-3">
                        <p class="text-xs uppercase text-neutral-500">類型</p>
                        <p class="mt-1 font-semibold text-white">{{ verification.classified_type ?? '—' }}</p>
                    </div>
                    <div class="rounded border border-white/10 bg-white/5 p-3">
                        <p class="text-xs uppercase text-neutral-500">共識</p>
                        <p class="mt-1 font-semibold text-white">{{ isCompleted ? consensusStatus : '—' }}</p>
                    </div>
                    <div class="rounded border border-white/10 bg-white/5 p-3">
                        <p class="text-xs uppercase text-neutral-500">信任等級</p>
                        <p class="mt-1 font-semibold text-white">{{ verification.final_trust ?? '—' }}</p>
                    </div>
                </div>
            </header>

            <!-- Pending / Running state -->
            <section v-if="isProcessing" class="space-y-4">
                <div class="rounded border border-white/10 bg-white/5 p-4 text-center sm:text-left">
                    <div class="flex flex-col items-center gap-3 sm:flex-row sm:items-center sm:gap-4">
                        <div class="size-9 shrink-0 animate-spin rounded-full border-4 border-white/20 border-t-teal-400" />
                        <div>
                            <p class="text-lg font-medium text-white">
                                {{ processingPhaseLabel }}
                            </p>
                            <p class="mt-1 text-sm text-neutral-400">
                                {{ processingPhaseMessage }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    <ProviderLivePanel
                        v-for="slot in CONSENSUS_SLOTS"
                        :key="slot"
                        :slot-name="consensusSlotLabel(slot)"
                        :state="slotState(slot)"
                        :model="responseForSlot(slot)?.model ?? null"
                        :provider-status="responseForSlot(slot)?.provider_status ?? null"
                        :raw-answer="responseForSlot(slot)?.raw_answer ?? null"
                        :error="responseForSlot(slot)?.error ?? null"
                    />
                </div>
            </section>

            <!-- Failed state -->
            <section v-else-if="isFailed" class="rounded border border-rose-300/20 bg-rose-300/5 p-6">
                <h2 class="text-lg font-semibold text-rose-200">處理失敗</h2>
                <p v-if="processingError" class="mt-2 text-sm text-rose-300">{{ processingError }}</p>
                <p v-else class="mt-2 text-sm text-neutral-400">驗證處理過程中發生未知錯誤。</p>
                <Link
                    v-if="!isDemo"
                    href="/verifications/create"
                    class="mt-4 inline-flex items-center gap-2 rounded border border-white/15 bg-white/10 px-4 py-2 text-sm font-medium text-white hover:bg-white/15"
                >
                    重新建立驗證
                </Link>
            </section>

            <!-- Completed state -->
            <template v-else-if="isCompleted">
                <section class="grid gap-4 md:grid-cols-[1.2fr_0.8fr]">
                    <article class="rounded border border-white/10 bg-white/5 p-5">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded border px-3 py-1 text-sm font-semibold" :class="badgeClass(consensusStatus)">
                                {{ consensusStatus }}
                            </span>
                            <span class="rounded border px-3 py-1 text-sm font-semibold" :class="badgeClass(verification.final_trust)">
                                {{ verification.final_trust }}
                            </span>
                            <span v-if="minorityProvider" class="rounded border border-rose-300/30 bg-rose-300/10 px-3 py-1 text-sm font-semibold text-rose-100">
                                少數意見：{{ minorityProvider }}
                            </span>
                        </div>

                        <h2 class="mt-5 text-lg font-semibold text-white">判定結果</h2>
                        <p v-if="verdictSummary" class="mt-2 text-sm text-neutral-300">
                            {{ verdictSummary }}
                        </p>
                        <div class="mt-4 space-y-2 text-sm leading-6 text-neutral-200">
                            <p v-for="line in verdictLines" :key="line" class="rounded bg-neutral-900 px-3 py-2">
                                {{ line }}
                            </p>
                            <p v-if="verdictLines.length === 0" class="rounded bg-neutral-900 px-3 py-2 text-neutral-400">
                                未產生最終判定。
                            </p>
                        </div>

                        <!-- Replay button -->
                        <div v-if="!isDemo" class="mt-5">
                            <Link
                                :href="`/verifications/${verification.id}/replay`"
                                method="post"
                                as="button"
                                :disabled="replayProcessing"
                                class="inline-flex min-h-9 items-center gap-2 rounded border border-white/15 bg-white/10 px-4 text-sm font-medium text-white transition hover:bg-white/15 disabled:cursor-not-allowed disabled:opacity-50"
                                @start="replayProcessing = true"
                                @finish="replayProcessing = false"
                            >
                                {{ replayProcessing ? '重新分析中…' : '重新分析' }}
                            </Link>
                        </div>
                    </article>

                    <article class="rounded border border-white/10 bg-white/5 p-5">
                        <h2 class="text-lg font-semibold text-white">決策依據</h2>
                        <dl class="mt-4 grid gap-3 text-sm">
                            <div class="flex items-center justify-between gap-3 rounded bg-neutral-900 px-3 py-2">
                                <dt class="text-neutral-400">決策鍵</dt>
                                <dd class="font-medium text-white">{{ consensusResult?.decision_key }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 rounded bg-neutral-900 px-3 py-2">
                                <dt class="text-neutral-400">信任基準</dt>
                                <dd class="font-medium text-white">{{ consensusResult?.trust_base }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 rounded bg-neutral-900 px-3 py-2">
                                <dt class="text-neutral-400">少數意見報告</dt>
                                <dd class="font-medium text-white">{{ hasMinorityReport ? '存在' : '無' }}</dd>
                            </div>
                        </dl>
                        <pre class="mt-4 max-h-56 overflow-auto rounded bg-neutral-900 p-3 text-xs leading-5 text-neutral-300">{{ formatJson(consensusResult?.decision_basis) }}</pre>
                    </article>
                </section>

                <section v-if="verification.requires_grounding" class="rounded border border-white/10 bg-white/5 p-5">
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-lg font-semibold text-white">外部查證（Grounding）</h2>
                        <span class="rounded border px-2 py-0.5 text-xs" :class="verification.grounding_available ? 'border-emerald-300/30 bg-emerald-300/10 text-emerald-200' : 'border-neutral-300/20 bg-white/10 text-neutral-300'">
                            {{ verification.grounding_available ? '已取得' : '未取得' }}
                        </span>
                        <span v-if="groundingMeta?.provider_mode" class="rounded bg-white/10 px-2 py-0.5 text-xs text-neutral-300">
                            {{ groundingMeta.provider_mode }}
                        </span>
                    </div>

                    <div v-if="groundingSummary" class="mt-3">
                        <p class="text-xs uppercase text-neutral-500">摘要</p>
                        <p class="mt-1 text-sm leading-6 text-neutral-300">{{ groundingSummary }}</p>
                    </div>

                    <div v-if="groundingSources.length > 0" class="mt-4">
                        <p class="text-xs uppercase text-neutral-500">來源</p>
                        <ul class="mt-2 space-y-2">
                            <li v-for="source in groundingSources" :key="source.url" class="rounded bg-neutral-900 px-3 py-2 text-sm">
                                <a :href="source.url" target="_blank" rel="noopener noreferrer" class="font-medium text-teal-300 hover:text-teal-200">
                                    {{ source.title || source.url }}
                                </a>
                                <p v-if="source.snippet" class="mt-0.5 text-xs text-neutral-400">{{ source.snippet }}</p>
                            </li>
                        </ul>
                    </div>

                    <p v-else-if="!groundingSummary" class="mt-3 text-sm text-neutral-500">
                        {{ verification.grounding_available ? '無來源資訊。' : '此次查證未取得外部來源。' }}
                    </p>
                </section>

                <section class="grid gap-4 lg:grid-cols-3">
                    <article
                        v-for="response in liveProviderResponses"
                        :key="response.provider"
                        class="flex min-h-96 flex-col rounded border border-white/10 bg-white/5"
                    >
                        <header class="space-y-3 border-b border-white/10 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h2 class="text-lg font-semibold text-white">{{ consensusSlotLabel(response.provider) }}</h2>
                                    <p class="text-xs text-neutral-500">{{ response.model }}</p>
                                </div>
                                <span class="rounded border border-white/10 bg-neutral-900 px-2 py-1 text-xs text-neutral-300">
                                    {{ directAnswer(response) }}
                                </span>
                            </div>
                            <div class="flex flex-wrap gap-2 text-xs">
                                <span class="rounded bg-white/10 px-2 py-1 text-neutral-200">{{ response.provider_status }}</span>
                                <span class="rounded bg-white/10 px-2 py-1 text-neutral-200">{{ response.extraction_status }}</span>
                            </div>
                        </header>

                        <div class="flex flex-1 flex-col gap-4 p-4">
                            <section>
                                <h3 class="text-sm font-semibold text-neutral-200">抽取結果</h3>
                                <p class="mt-2 text-sm leading-6 text-neutral-300">{{ normalizedSummary(response) }}</p>
                                <pre class="mt-3 max-h-36 overflow-auto rounded bg-neutral-900 p-3 text-xs leading-5 text-neutral-300">{{ formatJson(response.claims) }}</pre>
                            </section>

                            <section>
                                <h3 class="text-sm font-semibold text-neutral-200">原始回應</h3>
                                <pre class="mt-2 max-h-52 overflow-auto rounded bg-neutral-900 p-3 text-xs leading-5 text-neutral-300">{{ response.raw_answer }}</pre>
                            </section>

                            <section v-if="response.error">
                                <h3 class="text-sm font-semibold text-rose-200">錯誤</h3>
                                <pre class="mt-2 max-h-32 overflow-auto rounded bg-rose-950/60 p-3 text-xs leading-5 text-rose-100">{{ formatJson(response.error) }}</pre>
                            </section>
                        </div>
                    </article>
                </section>
            </template>
        </section>
    </main>
</template>
