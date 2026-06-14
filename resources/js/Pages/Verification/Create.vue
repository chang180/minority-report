<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    question: '',
});

function submit() {
    form.post('/verifications', { preserveScroll: false });
}
</script>

<template>
    <AppLayout>
        <div class="mx-auto max-w-2xl space-y-6">
            <div>
                <h1 class="text-2xl font-semibold tracking-normal">新建驗證</h1>
                <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                    輸入問題，系統將使用您設定的供應端執行共識驗證。
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>驗證問題</CardTitle>
                    <CardDescription>請輸入 8 到 2000 個字元的問題。</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="space-y-4" @submit.prevent="submit">
                        <div class="space-y-2">
                            <label for="question" class="text-sm font-medium">問題</label>
                            <textarea
                                id="question"
                                v-model="form.question"
                                rows="7"
                                class="w-full resize-y rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm leading-6 text-neutral-900 outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                                placeholder="例如：產品於 2024 年 Q3 是否通過所有品質檢測？"
                            />
                            <p v-if="form.errors.question" class="text-sm text-rose-600 dark:text-rose-400">
                                {{ form.errors.question }}
                            </p>
                        </div>

                        <div class="rounded-md bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-950 dark:text-amber-200">
                            系統將使用您在「供應端設定」中配置的三個共識槽執行真實 AI 推理。請確保供應端已正確設定。
                        </div>

                        <div class="flex items-center gap-3">
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? '驗證中...' : '執行驗證' }}
                            </Button>
                            <Button variant="outline" as="a" href="/settings/providers">
                                設定供應端
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
