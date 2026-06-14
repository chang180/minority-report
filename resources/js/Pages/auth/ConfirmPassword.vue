<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

function submit(): void {
    form.post('/user/confirm-password', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <AuthLayout>
        <Card>
            <CardHeader>
                <CardTitle>確認密碼</CardTitle>
                <CardDescription>請輸入密碼以繼續操作。</CardDescription>
            </CardHeader>
            <CardContent>
                <form class="grid gap-5" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="password">密碼</Label>
                        <Input id="password" v-model="form.password" type="password" name="password" autocomplete="current-password" required autofocus />
                        <InputError :message="form.errors.password" />
                    </div>

                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? '確認中...' : '確認' }}
                    </Button>
                </form>
            </CardContent>
        </Card>
    </AuthLayout>
</template>
