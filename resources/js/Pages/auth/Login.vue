<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';

defineProps<{
    canResetPassword: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit(): void {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <AuthLayout>
        <Card>
            <CardHeader>
                <CardTitle>登入</CardTitle>
                <CardDescription>登入後可進入儀表板與帳號設定。</CardDescription>
            </CardHeader>
            <CardContent>
                <p v-if="status" class="mb-4 rounded-md bg-teal-50 px-3 py-2 text-sm text-teal-800 dark:bg-teal-950 dark:text-teal-200">
                    {{ status }}
                </p>

                <form class="grid gap-5" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="email">電子郵件</Label>
                        <Input id="email" v-model="form.email" type="email" name="email" autocomplete="email" required autofocus />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <Label for="password">密碼</Label>
                            <TextLink v-if="canResetPassword" href="/forgot-password">忘記密碼？</TextLink>
                        </div>
                        <Input id="password" v-model="form.password" type="password" name="password" autocomplete="current-password" required />
                        <InputError :message="form.errors.password" />
                    </div>

                    <label class="flex items-center gap-2 text-sm text-neutral-700 dark:text-neutral-300">
                        <input v-model="form.remember" type="checkbox" class="size-4 rounded border-neutral-300 text-teal-600 focus:ring-teal-500" />
                        記住我
                    </label>

                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? '登入中...' : '登入' }}
                    </Button>
                </form>

                <p class="mt-6 text-center text-sm text-neutral-600 dark:text-neutral-400">
                    還沒有帳號？
                    <TextLink href="/register">建立帳號</TextLink>
                </p>
            </CardContent>
        </Card>
    </AuthLayout>
</template>
