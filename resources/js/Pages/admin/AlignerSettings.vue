<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { useForm, usePage } from '@inertiajs/vue3';

type AlignerSettings = {
    mode: 'string' | 'semantic_llm';
    enabled: boolean;
    local_api_url: string | null;
    local_model: string | null;
    has_local_api_key: boolean;
    timeout_seconds: number;
    min_confidence: 'high' | 'medium';
};

const props = defineProps<{
    settings: AlignerSettings;
}>();

const flashStatus = usePage().props.status as string | undefined;

const form = useForm({
    mode: props.settings.mode,
    enabled: props.settings.enabled,
    local_api_url: props.settings.local_api_url ?? '',
    local_model: props.settings.local_model ?? '',
    local_api_key: '',
    timeout_seconds: String(props.settings.timeout_seconds),
    min_confidence: props.settings.min_confidence,
});

function submit() {
    form.put('/admin/aligner', { preserveScroll: true });
}
</script>

<template>
    <AppLayout>
        <div class="space-y-8">
            <div>
                <h1 class="text-2xl font-semibold tracking-normal">Aligner 設定</h1>
                <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">管理語意 key 對齊後端，減少同義 canonical_key 造成的 false No Consensus。</p>
            </div>

            <div v-if="flashStatus" class="rounded border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800 dark:border-teal-800 dark:bg-teal-950 dark:text-teal-200">
                {{ flashStatus }}
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <Card>
                    <CardHeader>
                        <CardTitle>基本設定</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center gap-2">
                            <input id="aligner-enabled" v-model="form.enabled" type="checkbox" class="rounded accent-teal-500" />
                            <Label for="aligner-enabled">啟用語意對齊</Label>
                        </div>

                        <div class="space-y-2">
                            <Label>對齊模式</Label>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <label class="flex cursor-pointer items-center gap-2 rounded border border-neutral-200 px-4 py-3 text-sm dark:border-neutral-700">
                                    <input v-model="form.mode" type="radio" value="string" class="accent-teal-500" />
                                    <div>
                                        <p class="font-medium">字串對齊（預設）</p>
                                        <p class="text-xs text-neutral-500">僅正規化字串，不呼叫 LLM</p>
                                    </div>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 rounded border border-neutral-200 px-4 py-3 text-sm dark:border-neutral-700">
                                    <input v-model="form.mode" type="radio" value="semantic_llm" class="accent-teal-500" />
                                    <div>
                                        <p class="font-medium">語意 LLM 對齊</p>
                                        <p class="text-xs text-neutral-500">使用本機 OpenAI 相容端點進行 key 語意聚類</p>
                                    </div>
                                </label>
                            </div>
                            <InputError :message="form.errors.mode" />
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="form.mode === 'semantic_llm'">
                    <CardHeader>
                        <CardTitle>本機 LLM 設定</CardTitle>
                        <CardDescription>指向支援 JSON structured output 的 OpenAI 相容端點（如 llama.cpp / Ollama）。</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-1">
                            <Label for="local-url">API URL</Label>
                            <Input id="local-url" v-model="form.local_api_url" type="url" placeholder="http://localhost:8080" />
                            <InputError :message="form.errors.local_api_url" />
                        </div>
                        <div class="space-y-1">
                            <Label for="local-model">模型名稱</Label>
                            <Input id="local-model" v-model="form.local_model" type="text" placeholder="gemma3" />
                            <InputError :message="form.errors.local_model" />
                        </div>
                        <div class="space-y-1">
                            <Label for="local-key">
                                API 金鑰（留空保留原始值；{{ settings.has_local_api_key ? '已設定' : '未設定' }}）
                            </Label>
                            <Input id="local-key" v-model="form.local_api_key" type="password" autocomplete="off" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <Label for="timeout">逾時（秒）</Label>
                                <Input id="timeout" v-model="form.timeout_seconds" type="number" min="5" max="120" />
                                <InputError :message="form.errors.timeout_seconds" />
                            </div>
                            <div class="space-y-1">
                                <Label>最低可信度門檻</Label>
                                <div class="flex gap-4 pt-1">
                                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                                        <input v-model="form.min_confidence" type="radio" value="high" class="accent-teal-500" />
                                        高（保守）
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                                        <input v-model="form.min_confidence" type="radio" value="medium" class="accent-teal-500" />
                                        中（寬鬆）
                                    </label>
                                </div>
                                <InputError :message="form.errors.min_confidence" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="form.processing">儲存設定</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
