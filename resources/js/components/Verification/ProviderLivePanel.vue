<script setup lang="ts">
import { useStreamText } from '@/composables/useStreamText';
import { computed } from 'vue';

type SlotState = 'waiting' | 'running' | 'done' | 'failed';

const props = defineProps<{
    slotName: string;
    state: SlotState;
    model: string | null;
    providerStatus: string | null;
    rawAnswer: string | null;
    error: Record<string, unknown> | null;
}>();

const rawSource = computed(() => props.rawAnswer ?? '');
const streamedText = useStreamText(rawSource);

const stateLabel = computed((): string => {
    const map: Record<SlotState, string> = {
        waiting: '等待中',
        running: '呼叫 API…',
        done: '已完成',
        failed: '失敗',
    };

    return map[props.state];
});

const stateClass = computed((): string => {
    if (props.state === 'running') {
        return 'border-teal-300/30 bg-teal-300/10 text-teal-100';
    }

    if (props.state === 'done') {
        return 'border-emerald-300/30 bg-emerald-300/10 text-emerald-200';
    }

    if (props.state === 'failed') {
        return 'border-rose-300/30 bg-rose-300/10 text-rose-200';
    }

    return 'border-white/10 bg-white/5 text-neutral-300';
});
</script>

<template>
    <article class="flex min-h-80 flex-col rounded border border-white/10 bg-white/5">
        <header class="space-y-2 border-b border-white/10 p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-white">{{ slotName }}</h2>
                    <p v-if="model" class="text-xs text-neutral-500">{{ model }}</p>
                </div>
                <span class="rounded border px-2 py-1 text-xs font-medium" :class="stateClass">
                    {{ stateLabel }}
                </span>
            </div>
            <p v-if="providerStatus" class="text-xs text-neutral-400">{{ providerStatus }}</p>
        </header>

        <div class="flex flex-1 flex-col p-4">
            <div v-if="state === 'waiting'" class="flex flex-1 flex-col items-center justify-center gap-3 text-center text-neutral-500">
                <div class="size-8 rounded-full border-2 border-white/10 border-t-neutral-400" />
                <p class="text-sm">排隊中</p>
            </div>

            <div v-else-if="state === 'running' && !rawAnswer" class="flex flex-1 flex-col items-center justify-center gap-3 text-center">
                <div class="size-8 animate-spin rounded-full border-2 border-white/20 border-t-teal-400" />
                <p class="text-sm text-neutral-300">模型推理中…</p>
            </div>

            <div v-else class="flex flex-1 flex-col gap-3">
                <h3 class="text-sm font-semibold text-neutral-200">原始回應</h3>
                <pre class="max-h-72 flex-1 overflow-auto whitespace-pre-wrap rounded bg-neutral-900 p-3 text-xs leading-5 text-neutral-200">{{ streamedText }}<span v-if="state === 'running' && streamedText.length < (rawAnswer?.length ?? 0)" class="animate-pulse text-teal-300">▍</span></pre>

                <section v-if="error" class="rounded bg-rose-950/60 p-3 text-xs text-rose-100">
                    {{ typeof error.message === 'string' ? error.message : JSON.stringify(error) }}
                </section>
            </div>
        </div>
    </article>
</template>
