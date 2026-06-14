<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { useForm, usePage } from '@inertiajs/vue3';

type FixtureOption = {
    id: string;
    label: string;
    description: string;
    expected_consensus: string;
    expected_trust: string;
};

type DemoSettings = {
    mode: 'fake_fixtures' | 'shared_local_api';
    demo_enabled: boolean;
    shared_api_url: string | null;
    has_shared_api_key: boolean;
    default_fixture_id: string;
    enabled_fixture_ids: string[];
};

const props = defineProps<{
    settings: DemoSettings;
    allFixtures: FixtureOption[];
}>();

const flashStatus = usePage().props.status as string | undefined;

const form = useForm({
    mode: props.settings.mode,
    demo_enabled: props.settings.demo_enabled,
    shared_api_url: props.settings.shared_api_url ?? '',
    shared_api_key: '',
    default_fixture_id: props.settings.default_fixture_id,
    enabled_fixture_ids: [...props.settings.enabled_fixture_ids],
});

function submit() {
    form.put('/admin/demo', { preserveScroll: true });
}

function toggleFixture(id: string) {
    const idx = form.enabled_fixture_ids.indexOf(id);
    if (idx === -1) {
        form.enabled_fixture_ids.push(id);
    } else {
        form.enabled_fixture_ids.splice(idx, 1);
    }
}
</script>

<template>
    <AppLayout>
        <div class="space-y-8">
            <div>
                <h1 class="text-2xl font-semibold tracking-normal">Demo 管理</h1>
                <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">管理訪客示範模式與可用範例。</p>
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
                            <input id="demo-enabled" v-model="form.demo_enabled" type="checkbox" class="rounded accent-teal-500" />
                            <Label for="demo-enabled">開放訪客示範（/demo）</Label>
                        </div>

                        <div class="space-y-2">
                            <Label>示範模式</Label>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <label class="flex cursor-pointer items-center gap-2 rounded border border-neutral-200 px-4 py-3 text-sm dark:border-neutral-700">
                                    <input v-model="form.mode" type="radio" value="fake_fixtures" class="accent-teal-500" />
                                    <div>
                                        <p class="font-medium">模擬範例</p>
                                        <p class="text-xs text-neutral-500">使用內建 fixture，不需 API 金鑰</p>
                                    </div>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 rounded border border-neutral-200 px-4 py-3 text-sm dark:border-neutral-700">
                                    <input v-model="form.mode" type="radio" value="shared_local_api" class="accent-teal-500" />
                                    <div>
                                        <p class="font-medium">共享本機 API</p>
                                        <p class="text-xs text-neutral-500">三槽皆指向同一 OpenAI 相容端點（如 Ollama）</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="form.mode === 'shared_local_api'">
                    <CardHeader>
                        <CardTitle>共享 API 設定</CardTitle>
                        <CardDescription>三個共識槽皆使用此端點進行真實推理。</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-1">
                            <Label for="shared-url">API URL</Label>
                            <Input id="shared-url" v-model="form.shared_api_url" type="url" placeholder="http://localhost:11434/v1" />
                            <InputError :message="form.errors.shared_api_url" />
                        </div>
                        <div class="space-y-1">
                            <Label for="shared-key">
                                API 金鑰（留空保留原始值；{{ settings.has_shared_api_key ? '已設定' : '未設定' }}）
                            </Label>
                            <Input id="shared-key" v-model="form.shared_api_key" type="password" autocomplete="off" />
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="form.mode === 'fake_fixtures'">
                    <CardHeader>
                        <CardTitle>範例管理</CardTitle>
                        <CardDescription>選擇訪客可使用的模擬範例。</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-1">
                            <Label for="default-fixture">預設範例</Label>
                            <select id="default-fixture" v-model="form.default_fixture_id" class="w-full rounded border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900">
                                <option v-for="f in allFixtures" :key="f.id" :value="f.id">{{ f.label }}</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label>開放範例</Label>
                            <div class="space-y-2">
                                <label v-for="f in allFixtures" :key="f.id" class="flex cursor-pointer items-start gap-2 rounded border border-neutral-200 p-3 dark:border-neutral-700">
                                    <input
                                        :checked="form.enabled_fixture_ids.includes(f.id)"
                                        type="checkbox"
                                        class="mt-0.5 rounded accent-teal-500"
                                        @change="toggleFixture(f.id)"
                                    />
                                    <div>
                                        <p class="text-sm font-medium">{{ f.label }}</p>
                                        <p class="text-xs text-neutral-500">{{ f.description }}</p>
                                    </div>
                                </label>
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
