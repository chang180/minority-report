<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { useForm, usePage } from '@inertiajs/vue3';

type GroundingSettings = {
    mode: 'disabled' | 'local_llm_tool_loop' | 'search_api';
    enabled: boolean;
    local_api_url: string | null;
    local_model: string | null;
    has_local_api_key: boolean;
    search_provider: 'tavily' | 'serper' | 'duckduckgo_lite' | null;
    has_search_api_key: boolean;
    search_api_url: string | null;
    max_tool_rounds: number;
    timeout_seconds: number;
};

const props = defineProps<{
    settings: GroundingSettings;
}>();

const flashStatus = usePage().props.status as string | undefined;

const form = useForm({
    mode: props.settings.mode,
    enabled: props.settings.enabled,
    local_api_url: props.settings.local_api_url ?? '',
    local_model: props.settings.local_model ?? '',
    local_api_key: '',
    search_provider: props.settings.search_provider ?? 'duckduckgo_lite',
    search_api_key: '',
    search_api_url: props.settings.search_api_url ?? '',
    max_tool_rounds: String(props.settings.max_tool_rounds),
    timeout_seconds: String(props.settings.timeout_seconds),
});

function submit() {
    form.put('/admin/grounding', { preserveScroll: true });
}
</script>

<template>
    <AppLayout>
        <div class="space-y-8">
            <div>
                <h1 class="text-2xl font-semibold tracking-normal">Grounding 設定</h1>
                <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">管理外部查證後端，提升 Type C 問題的信任等級。</p>
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
                            <input id="grounding-enabled" v-model="form.enabled" type="checkbox" class="rounded accent-teal-500" />
                            <Label for="grounding-enabled">啟用 Grounding（外部查證）</Label>
                        </div>

                        <div class="space-y-2">
                            <Label>Grounding 後端模式</Label>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <label class="flex cursor-pointer items-center gap-2 rounded border border-neutral-200 px-4 py-3 text-sm dark:border-neutral-700">
                                    <input v-model="form.mode" type="radio" value="disabled" class="accent-teal-500" />
                                    <div>
                                        <p class="font-medium">停用</p>
                                        <p class="text-xs text-neutral-500">不呼叫任何外部服務</p>
                                    </div>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 rounded border border-neutral-200 px-4 py-3 text-sm dark:border-neutral-700">
                                    <input v-model="form.mode" type="radio" value="local_llm_tool_loop" class="accent-teal-500" />
                                    <div>
                                        <p class="font-medium">本機 LLM Tool Loop</p>
                                        <p class="text-xs text-neutral-500">使用本機 OpenAI 相容端點 + web_search 工具循環</p>
                                    </div>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 rounded border border-neutral-200 px-4 py-3 text-sm dark:border-neutral-700">
                                    <input v-model="form.mode" type="radio" value="search_api" class="accent-teal-500" />
                                    <div>
                                        <p class="font-medium">Search API</p>
                                        <p class="text-xs text-neutral-500">直接呼叫 Tavily / Serper / DuckDuckGo</p>
                                    </div>
                                </label>
                            </div>
                            <InputError :message="form.errors.mode" />
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="form.mode === 'local_llm_tool_loop'">
                    <CardHeader>
                        <CardTitle>本機 LLM 設定</CardTitle>
                        <CardDescription>指向已啟動 web_search tool calling 的 OpenAI 相容端點（如 llama.cpp）。</CardDescription>
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
                                <Label for="max-rounds">最大 Tool 輪次</Label>
                                <Input id="max-rounds" v-model="form.max_tool_rounds" type="number" min="1" max="10" />
                                <InputError :message="form.errors.max_tool_rounds" />
                            </div>
                            <div class="space-y-1">
                                <Label for="timeout">逾時（秒）</Label>
                                <Input id="timeout" v-model="form.timeout_seconds" type="number" min="10" max="300" />
                                <InputError :message="form.errors.timeout_seconds" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="form.mode === 'local_llm_tool_loop' || form.mode === 'search_api'">
                    <CardHeader>
                        <CardTitle>Search API 設定</CardTitle>
                        <CardDescription>
                            {{ form.mode === 'search_api' ? '直接呼叫 Search API 取得外部來源。' : 'LLM Tool Loop 執行搜尋時使用此設定。' }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-1">
                            <Label>搜尋供應商</Label>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <label v-for="provider in ['tavily', 'serper', 'duckduckgo_lite']" :key="provider" class="flex cursor-pointer items-center gap-2 rounded border border-neutral-200 px-4 py-2 text-sm dark:border-neutral-700">
                                    <input v-model="form.search_provider" type="radio" :value="provider" class="accent-teal-500" />
                                    {{ provider === 'duckduckgo_lite' ? 'DuckDuckGo Lite（免費）' : provider === 'tavily' ? 'Tavily' : 'Serper' }}
                                </label>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <Label for="search-key">
                                Search API 金鑰（留空保留原始值；{{ settings.has_search_api_key ? '已設定' : '未設定' }}）
                            </Label>
                            <Input id="search-key" v-model="form.search_api_key" type="password" autocomplete="off" />
                        </div>
                        <div class="space-y-1">
                            <Label for="search-url">自訂 Endpoint URL（選填）</Label>
                            <Input id="search-url" v-model="form.search_api_url" type="url" placeholder="留空使用預設端點" />
                            <InputError :message="form.errors.search_api_url" />
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
