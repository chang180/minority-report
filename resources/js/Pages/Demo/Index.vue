<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed, watch } from 'vue';

type FixtureOption = {
    id: string;
    label: string;
    description: string;
    sample_question: string;
    expected_consensus: string;
    expected_trust: string;
};

const props = defineProps<{
    fixtures: FixtureOption[];
    defaultFixtureId: string;
}>();

const defaultFixture = props.fixtures.find((fixture) => fixture.id === props.defaultFixtureId)
    ?? props.fixtures[0];

const form = useForm({
    question: defaultFixture?.sample_question ?? '產品發布日期是否通過共識驗證？',
    fixture_id: props.defaultFixtureId,
});

const selectedFixture = computed(() => props.fixtures.find((fixture) => fixture.id === form.fixture_id));

watch(
    () => form.fixture_id,
    (fixtureId) => {
        const fixture = props.fixtures.find((item) => item.id === fixtureId);

        if (fixture?.sample_question) {
            form.question = fixture.sample_question;
        }
    },
);

function submit(): void {
    form.post('/demo/verifications');
}
</script>

<template>
    <main class="min-h-screen bg-neutral-950 text-neutral-100">
        <section class="mx-auto flex min-h-screen w-full max-w-5xl flex-col gap-8 px-5 py-8 sm:px-8 lg:px-10">
            <header class="flex flex-col gap-3 border-b border-white/10 pb-6 sm:flex-row sm:items-end sm:justify-between">
                <div class="space-y-2">
                    <p class="text-sm font-medium text-teal-300">關鍵報告 · Minority Report</p>
                    <h1 class="text-3xl font-semibold tracking-normal text-white sm:text-4xl">
                        問題驗證
                    </h1>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-sm text-neutral-300 sm:justify-end">
                    <Link href="/" class="inline-flex min-h-10 items-center gap-2 rounded border border-white/15 bg-white/10 px-3 font-medium text-white transition hover:bg-white/15">
                        <ArrowLeft class="size-4" />
                        回首頁
                    </Link>
                    <span class="rounded bg-white/10 px-3 py-2">模擬供應端</span>
                    <span class="rounded bg-white/10 px-3 py-2">已保存稽核紀錄</span>
                </div>
            </header>

            <form class="grid gap-6 lg:grid-cols-[1fr_18rem]" @submit.prevent="submit">
                <section class="space-y-4">
                    <div class="space-y-2">
                        <label for="question" class="text-sm font-medium text-neutral-200">問題</label>
                        <textarea
                            id="question"
                            v-model="form.question"
                            name="question"
                            rows="9"
                            class="w-full resize-y rounded border border-white/15 bg-white px-4 py-3 text-base leading-7 text-neutral-950 outline-none transition focus:border-teal-300 focus:ring-4 focus:ring-teal-300/20"
                        />
                        <p v-if="form.errors.question" class="text-sm text-rose-300">
                            {{ form.errors.question }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="submit"
                            class="inline-flex min-h-11 items-center justify-center rounded bg-teal-300 px-5 text-sm font-semibold text-neutral-950 transition hover:bg-teal-200 disabled:cursor-not-allowed disabled:bg-neutral-600 disabled:text-neutral-300"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? '驗證中...' : '執行驗證' }}
                        </button>
                    </div>
                </section>

                <aside class="space-y-4 rounded border border-white/10 bg-white/5 p-4">
                    <div class="space-y-2">
                        <label for="fixture_id" class="text-sm font-medium text-neutral-200">示範範例</label>
                        <select
                            id="fixture_id"
                            v-model="form.fixture_id"
                            name="fixture_id"
                            class="w-full rounded border border-white/15 bg-neutral-900 px-3 py-2 text-sm text-white outline-none transition focus:border-teal-300 focus:ring-4 focus:ring-teal-300/20"
                        >
                            <option v-for="fixture in fixtures" :key="fixture.id" :value="fixture.id">
                                {{ fixture.label }}
                            </option>
                        </select>
                        <p v-if="form.errors.fixture_id" class="text-sm text-rose-300">
                            {{ form.errors.fixture_id }}
                        </p>
                    </div>

                    <div v-if="selectedFixture" class="space-y-4 text-sm">
                        <p class="leading-6 text-neutral-300">
                            {{ selectedFixture.description }}
                        </p>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="rounded border border-white/10 bg-neutral-900 p-3">
                                <p class="text-xs uppercase text-neutral-500">共識</p>
                                <p class="mt-1 font-semibold text-white">{{ selectedFixture.expected_consensus }}</p>
                            </div>
                            <div class="rounded border border-white/10 bg-neutral-900 p-3">
                                <p class="text-xs uppercase text-neutral-500">信任等級</p>
                                <p class="mt-1 font-semibold text-white">{{ selectedFixture.expected_trust }}</p>
                            </div>
                        </div>
                    </div>
                </aside>
            </form>
        </section>
    </main>
</template>
