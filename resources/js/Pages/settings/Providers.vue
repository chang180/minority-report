<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

type PresetProvider = {
    provider_key: string;
    label: string;
    cloud: boolean;
    has_key: boolean;
    configured: boolean;
    api_url: string | null;
    model: string | null;
    enabled: boolean;
};

type CustomProvider = {
    id: number;
    label: string;
    api_url: string;
    model: string | null;
    has_key: boolean;
    configured: boolean;
    enabled: boolean;
};

type SlotDef = { type: 'preset'; provider_key: string } | { type: 'custom'; custom_provider_id: number } | null;

const props = defineProps<{
    presets: PresetProvider[];
    customProviders: CustomProvider[];
    consensusSlots: Record<string, SlotDef>;
}>();

const flashStatus = usePage().props.status as string | undefined;
const SLOT_NAMES = ['openai', 'anthropic', 'gemini'] as const;

// Preset edit state
const editingPreset = ref<string | null>(null);

function presetForm(p: PresetProvider) {
    return useForm({
        provider_key: p.provider_key,
        api_key: '',
        api_url: p.api_url ?? '',
        model: p.model ?? '',
        enabled: p.enabled,
    });
}

const presetForms = Object.fromEntries(props.presets.map((p) => [p.provider_key, presetForm(p)]));

function savePreset(providerKey: string) {
    presetForms[providerKey].put('/settings/providers/preset', {
        preserveScroll: true,
        onSuccess: () => {
            editingPreset.value = null;
        },
    });
}

// Custom provider form
const addCustomForm = useForm({
    label: '',
    api_url: '',
    api_key: '',
    model: '',
    enabled: true,
});

function saveCustom() {
    addCustomForm.post('/settings/providers/custom', {
        preserveScroll: true,
        onSuccess: () => addCustomForm.reset(),
    });
}

function deleteCustom(id: number) {
    if (!confirm('確定刪除此自訂供應端？')) return;
    useForm({}).delete(`/settings/providers/custom/${id}`, { preserveScroll: true });
}

// Consensus slots form
const slotsForm = useForm({
    consensus_slots: { ...props.consensusSlots } as Record<string, SlotDef>,
});

function saveSlots() {
    slotsForm.put('/settings/providers/slots', { preserveScroll: true });
}
</script>

<template>
    <AppLayout>
        <div class="space-y-8">
            <div>
                <h1 class="text-2xl font-semibold tracking-normal">供應端設定</h1>
                <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">管理您的 AI 供應端憑證與共識槽配置。</p>
            </div>

            <div v-if="flashStatus" class="rounded border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800 dark:border-teal-800 dark:bg-teal-950 dark:text-teal-200">
                {{ flashStatus }}
            </div>

            <!-- Preset Providers -->
            <Card>
                <CardHeader>
                    <CardTitle>SDK 預設供應端</CardTitle>
                    <CardDescription>設定各供應端的 API 金鑰。金鑰以加密方式儲存，頁面不顯示原始金鑰。</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div v-for="preset in presets" :key="preset.provider_key" class="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="font-medium">{{ preset.label }}</span>
                                <span
                                    :class="preset.configured ? 'bg-teal-100 text-teal-700 dark:bg-teal-900 dark:text-teal-300' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400'"
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                >
                                    {{ preset.configured ? '已設定' : '未設定' }}
                                </span>
                            </div>
                            <Button variant="outline" size="sm" @click="editingPreset = editingPreset === preset.provider_key ? null : preset.provider_key">
                                {{ editingPreset === preset.provider_key ? '取消' : '編輯' }}
                            </Button>
                        </div>

                        <form v-if="editingPreset === preset.provider_key" class="mt-4 space-y-3" @submit.prevent="savePreset(preset.provider_key)">
                            <div class="space-y-1">
                                <Label :for="`key-${preset.provider_key}`">API 金鑰（留空保留原始值）</Label>
                                <Input :id="`key-${preset.provider_key}`" v-model="presetForms[preset.provider_key].api_key" type="password" autocomplete="off" placeholder="sk-..." />
                                <InputError :message="presetForms[preset.provider_key].errors.api_key" />
                            </div>
                            <div v-if="!preset.cloud" class="space-y-1">
                                <Label :for="`url-${preset.provider_key}`">API URL</Label>
                                <Input :id="`url-${preset.provider_key}`" v-model="presetForms[preset.provider_key].api_url" type="url" placeholder="http://localhost:11434" />
                                <InputError :message="presetForms[preset.provider_key].errors.api_url" />
                            </div>
                            <div class="space-y-1">
                                <Label :for="`model-${preset.provider_key}`">模型（選填）</Label>
                                <Input :id="`model-${preset.provider_key}`" v-model="presetForms[preset.provider_key].model" type="text" placeholder="gpt-4o" />
                            </div>
                            <div class="flex items-center gap-2">
                                <input :id="`enabled-${preset.provider_key}`" v-model="presetForms[preset.provider_key].enabled" type="checkbox" class="rounded" />
                                <Label :for="`enabled-${preset.provider_key}`">啟用此供應端</Label>
                            </div>
                            <Button type="submit" :disabled="presetForms[preset.provider_key].processing">儲存</Button>
                        </form>
                    </div>
                </CardContent>
            </Card>

            <!-- Custom Providers -->
            <Card>
                <CardHeader>
                    <CardTitle>自訂供應端（OpenAI 相容）</CardTitle>
                    <CardDescription>新增任何支援 OpenAI API 格式的自訂端點。</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div v-for="custom in customProviders" :key="custom.id" class="flex items-center justify-between rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
                        <div>
                            <p class="font-medium">{{ custom.label }}</p>
                            <p class="text-sm text-neutral-500">{{ custom.api_url }}</p>
                            <p v-if="custom.model" class="text-xs text-neutral-400">模型：{{ custom.model }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span :class="custom.configured ? 'text-teal-600' : 'text-neutral-400'" class="text-xs">{{ custom.configured ? '已設定' : '未設定' }}</span>
                            <Button variant="danger" size="sm" @click="deleteCustom(custom.id)">刪除</Button>
                        </div>
                    </div>

                    <form class="space-y-3 rounded-lg border border-dashed border-neutral-300 p-4 dark:border-neutral-700" @submit.prevent="saveCustom">
                        <p class="text-sm font-medium">新增自訂供應端</p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="space-y-1">
                                <Label for="custom-label">名稱</Label>
                                <Input id="custom-label" v-model="addCustomForm.label" type="text" placeholder="我的 Ollama" />
                                <InputError :message="addCustomForm.errors.label" />
                            </div>
                            <div class="space-y-1">
                                <Label for="custom-url">API URL</Label>
                                <Input id="custom-url" v-model="addCustomForm.api_url" type="url" placeholder="http://localhost:11434/v1" />
                                <InputError :message="addCustomForm.errors.api_url" />
                            </div>
                            <div class="space-y-1">
                                <Label for="custom-key">API 金鑰（選填）</Label>
                                <Input id="custom-key" v-model="addCustomForm.api_key" type="password" autocomplete="off" />
                            </div>
                            <div class="space-y-1">
                                <Label for="custom-model">模型（選填）</Label>
                                <Input id="custom-model" v-model="addCustomForm.model" type="text" placeholder="llama3" />
                            </div>
                        </div>
                        <Button type="submit" :disabled="addCustomForm.processing">新增</Button>
                    </form>
                </CardContent>
            </Card>

            <!-- Consensus Slots -->
            <Card>
                <CardHeader>
                    <CardTitle>共識槽配置</CardTitle>
                    <CardDescription>三個共識槽分別對應 openai / anthropic / gemini 邏輯名稱，可指向任意供應端。</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="space-y-4" @submit.prevent="saveSlots">
                        <div v-for="slot in SLOT_NAMES" :key="slot" class="space-y-2">
                            <Label>{{ slot }} 槽</Label>
                            <div class="flex flex-wrap gap-2">
                                <label class="flex cursor-pointer items-center gap-2 rounded border border-neutral-200 px-3 py-2 text-sm dark:border-neutral-700">
                                    <input
                                        v-model="slotsForm.consensus_slots[slot]"
                                        :value="null"
                                        type="radio"
                                        class="accent-teal-500"
                                    />
                                    <span>未指定</span>
                                </label>
                                <label
                                    v-for="p in presets"
                                    :key="p.provider_key"
                                    class="flex cursor-pointer items-center gap-2 rounded border border-neutral-200 px-3 py-2 text-sm dark:border-neutral-700"
                                >
                                    <input
                                        v-model="slotsForm.consensus_slots[slot]"
                                        :value="{ type: 'preset', provider_key: p.provider_key }"
                                        type="radio"
                                        class="accent-teal-500"
                                    />
                                    <span>{{ p.label }}</span>
                                    <span v-if="!p.configured" class="text-xs text-rose-500">未設定</span>
                                </label>
                                <label
                                    v-for="c in customProviders"
                                    :key="c.id"
                                    class="flex cursor-pointer items-center gap-2 rounded border border-neutral-200 px-3 py-2 text-sm dark:border-neutral-700"
                                >
                                    <input
                                        v-model="slotsForm.consensus_slots[slot]"
                                        :value="{ type: 'custom', custom_provider_id: c.id }"
                                        type="radio"
                                        class="accent-teal-500"
                                    />
                                    <span>{{ c.label }}（自訂）</span>
                                    <span v-if="!c.configured" class="text-xs text-rose-500">未設定</span>
                                </label>
                            </div>
                        </div>

                        <Button type="submit" :disabled="slotsForm.processing">儲存共識槽</Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
