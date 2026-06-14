<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

type Verification = {
    id: number;
    question: string;
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

const props = defineProps<{
    verification: Verification;
    providerResponses: ProviderResponse[];
    consensusResult: ConsensusResult | null;
}>();

const consensusStatus = computed(() => stringValue(props.consensusResult?.consensus?.status) ?? 'Unknown');
const minorityProvider = computed(() => stringValue(props.consensusResult?.consensus?.minority_provider));
const verdictSummary = computed(() => stringValue(props.consensusResult?.verdict_report?.summary));
const verdictLines = computed(() => (props.verification.final_verdict ?? '').split('\n').filter(Boolean));
const verdictMetadata = computed(() => props.consensusResult?.verdict_report?.metadata as Record<string, unknown> | undefined);
const hasMinorityReport = computed(() => Boolean(verdictMetadata.value?.has_minority_report));

function stringValue(value: unknown): string | null {
    return typeof value === 'string' && value !== '' ? value : null;
}

function normalizedSummary(response: ProviderResponse): string {
    return stringValue(response.normalized?.summary) ?? 'No extracted summary.';
}

function directAnswer(response: ProviderResponse): string {
    return stringValue(response.normalized?.direct_answer) ?? 'not available';
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
</script>

<template>
    <main class="min-h-screen bg-neutral-950 text-neutral-100">
        <section class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-5 py-8 sm:px-8 lg:px-10">
            <header class="flex flex-col gap-4 border-b border-white/10 pb-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-3">
                    <Link href="/demo" class="text-sm font-medium text-teal-300 hover:text-teal-200">
                        New verification
                    </Link>
                    <h1 class="text-2xl font-semibold tracking-normal text-white sm:text-3xl">
                        Verification #{{ verification.id }}
                    </h1>
                    <p class="max-w-3xl text-base leading-7 text-neutral-300">
                        {{ verification.question }}
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-2 text-sm sm:grid-cols-4 lg:min-w-xl">
                    <div class="rounded border border-white/10 bg-white/5 p-3">
                        <p class="text-xs uppercase text-neutral-500">Type</p>
                        <p class="mt-1 font-semibold text-white">{{ verification.classified_type }}</p>
                    </div>
                    <div class="rounded border border-white/10 bg-white/5 p-3">
                        <p class="text-xs uppercase text-neutral-500">Shape</p>
                        <p class="mt-1 font-semibold text-white">{{ verification.answer_shape }}</p>
                    </div>
                    <div class="rounded border border-white/10 bg-white/5 p-3">
                        <p class="text-xs uppercase text-neutral-500">Consensus</p>
                        <p class="mt-1 font-semibold text-white">{{ consensusStatus }}</p>
                    </div>
                    <div class="rounded border border-white/10 bg-white/5 p-3">
                        <p class="text-xs uppercase text-neutral-500">Trust</p>
                        <p class="mt-1 font-semibold text-white">{{ verification.final_trust }}</p>
                    </div>
                </div>
            </header>

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
                            Minority: {{ minorityProvider }}
                        </span>
                    </div>

                    <h2 class="mt-5 text-lg font-semibold text-white">Verdict</h2>
                    <p v-if="verdictSummary" class="mt-2 text-sm text-neutral-300">
                        {{ verdictSummary }}
                    </p>
                    <div class="mt-4 space-y-2 text-sm leading-6 text-neutral-200">
                        <p v-for="line in verdictLines" :key="line" class="rounded bg-neutral-900 px-3 py-2">
                            {{ line }}
                        </p>
                        <p v-if="verdictLines.length === 0" class="rounded bg-neutral-900 px-3 py-2 text-neutral-400">
                            No final verdict was produced.
                        </p>
                    </div>
                </article>

                <article class="rounded border border-white/10 bg-white/5 p-5">
                    <h2 class="text-lg font-semibold text-white">Decision Basis</h2>
                    <dl class="mt-4 grid gap-3 text-sm">
                        <div class="flex items-center justify-between gap-3 rounded bg-neutral-900 px-3 py-2">
                            <dt class="text-neutral-400">Decision key</dt>
                            <dd class="font-medium text-white">{{ consensusResult?.decision_key }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 rounded bg-neutral-900 px-3 py-2">
                            <dt class="text-neutral-400">Trust base</dt>
                            <dd class="font-medium text-white">{{ consensusResult?.trust_base }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 rounded bg-neutral-900 px-3 py-2">
                            <dt class="text-neutral-400">Minority report</dt>
                            <dd class="font-medium text-white">{{ hasMinorityReport ? 'Present' : 'None' }}</dd>
                        </div>
                    </dl>
                    <pre class="mt-4 max-h-56 overflow-auto rounded bg-neutral-900 p-3 text-xs leading-5 text-neutral-300">{{ formatJson(consensusResult?.decision_basis) }}</pre>
                </article>
            </section>

            <section class="grid gap-4 lg:grid-cols-3">
                <article
                    v-for="response in providerResponses"
                    :key="response.id"
                    class="flex min-h-96 flex-col rounded border border-white/10 bg-white/5"
                >
                    <header class="space-y-3 border-b border-white/10 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold text-white">{{ response.provider }}</h2>
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
                            <h3 class="text-sm font-semibold text-neutral-200">Extracted</h3>
                            <p class="mt-2 text-sm leading-6 text-neutral-300">{{ normalizedSummary(response) }}</p>
                            <pre class="mt-3 max-h-36 overflow-auto rounded bg-neutral-900 p-3 text-xs leading-5 text-neutral-300">{{ formatJson(response.claims) }}</pre>
                        </section>

                        <section>
                            <h3 class="text-sm font-semibold text-neutral-200">Raw</h3>
                            <pre class="mt-2 max-h-52 overflow-auto rounded bg-neutral-900 p-3 text-xs leading-5 text-neutral-300">{{ response.raw_answer }}</pre>
                        </section>

                        <section v-if="response.error">
                            <h3 class="text-sm font-semibold text-rose-200">Error</h3>
                            <pre class="mt-2 max-h-32 overflow-auto rounded bg-rose-950/60 p-3 text-xs leading-5 text-rose-100">{{ formatJson(response.error) }}</pre>
                        </section>
                    </div>
                </article>
            </section>
        </section>
    </main>
</template>
