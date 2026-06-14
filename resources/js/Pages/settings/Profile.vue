<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import type { Auth } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => (page.props.auth as Auth).user);

const form = useForm({
    name: user.value?.name ?? '',
    email: user.value?.email ?? '',
});

function submit(): void {
    form.put('/user/profile-information', {
        preserveScroll: true,
    });
}
</script>

<template>
    <AppLayout>
        <div class="grid gap-6 lg:grid-cols-[14rem_1fr]">
            <nav class="flex gap-2 lg:flex-col">
                <Link href="/settings/profile" class="rounded-md bg-neutral-200 px-3 py-2 text-sm font-medium dark:bg-neutral-800">個人資料</Link>
                <Link href="/settings/password" class="rounded-md px-3 py-2 text-sm font-medium hover:bg-neutral-100 dark:hover:bg-neutral-900">密碼</Link>
            </nav>

            <Card>
                <CardHeader>
                    <CardTitle>個人資料</CardTitle>
                    <CardDescription>更新帳號姓名與電子郵件。</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="grid max-w-xl gap-5" @submit.prevent="submit">
                        <div class="grid gap-2">
                            <Label for="name">姓名</Label>
                            <Input id="name" v-model="form.name" name="name" autocomplete="name" required />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">電子郵件</Label>
                            <Input id="email" v-model="form.email" type="email" name="email" autocomplete="email" required />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="flex items-center gap-3">
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? '儲存中...' : '儲存' }}
                            </Button>
                            <p v-if="form.recentlySuccessful" class="text-sm text-teal-700 dark:text-teal-300">已儲存。</p>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
