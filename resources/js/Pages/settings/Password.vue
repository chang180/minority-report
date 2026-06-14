<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function submit(): void {
    form.put('/user/password', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <AppLayout>
        <div class="grid gap-6 lg:grid-cols-[14rem_1fr]">
            <nav class="flex gap-2 lg:flex-col">
                <Link href="/settings/profile" class="rounded-md px-3 py-2 text-sm font-medium hover:bg-neutral-100 dark:hover:bg-neutral-900">個人資料</Link>
                <Link href="/settings/password" class="rounded-md bg-neutral-200 px-3 py-2 text-sm font-medium dark:bg-neutral-800">密碼</Link>
            </nav>

            <Card>
                <CardHeader>
                    <CardTitle>密碼</CardTitle>
                    <CardDescription>更新帳號登入密碼。</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="grid max-w-xl gap-5" @submit.prevent="submit">
                        <div class="grid gap-2">
                            <Label for="current_password">目前密碼</Label>
                            <Input id="current_password" v-model="form.current_password" type="password" name="current_password" autocomplete="current-password" required />
                            <InputError :message="form.errors.current_password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password">新密碼</Label>
                            <Input id="password" v-model="form.password" type="password" name="password" autocomplete="new-password" required />
                            <InputError :message="form.errors.password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password_confirmation">確認密碼</Label>
                            <Input id="password_confirmation" v-model="form.password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required />
                            <InputError :message="form.errors.password_confirmation" />
                        </div>

                        <div class="flex items-center gap-3">
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? '儲存中...' : '儲存密碼' }}
                            </Button>
                            <p v-if="form.recentlySuccessful" class="text-sm text-teal-700 dark:text-teal-300">已儲存。</p>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
