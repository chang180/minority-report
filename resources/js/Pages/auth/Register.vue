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
    passwordRules?: string;
}>();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit(): void {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <AuthLayout>
        <Card>
            <CardHeader>
                <CardTitle>建立帳號</CardTitle>
                <CardDescription>M7-A 階段開放註冊。</CardDescription>
            </CardHeader>
            <CardContent>
                <form class="grid gap-5" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="name">姓名</Label>
                        <Input id="name" v-model="form.name" name="name" autocomplete="name" required autofocus />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">電子郵件</Label>
                        <Input id="email" v-model="form.email" type="email" name="email" autocomplete="email" required />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">密碼</Label>
                        <Input id="password" v-model="form.password" type="password" name="password" autocomplete="new-password" required />
                        <p v-if="passwordRules" class="text-xs text-neutral-500">{{ passwordRules }}</p>
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation">確認密碼</Label>
                        <Input id="password_confirmation" v-model="form.password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? '建立中...' : '建立帳號' }}
                    </Button>
                </form>

                <p class="mt-6 text-center text-sm text-neutral-600 dark:text-neutral-400">
                    已經有帳號？
                    <TextLink href="/login">登入</TextLink>
                </p>
            </CardContent>
        </Card>
    </AuthLayout>
</template>
