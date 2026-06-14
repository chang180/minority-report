<script setup lang="ts">
import GuestLayout from '@/layouts/GuestLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const form = useForm({});

function resend(): void {
    form.post('/email/verification-notification');
}
</script>

<template>
    <GuestLayout>
        <div class="space-y-5">
            <div>
                <h1 class="text-xl font-semibold">驗證您的電子郵件</h1>
                <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                    感謝您的註冊！在使用驗證功能之前，請先確認您的電子郵件地址。
                    我們已向您的信箱傳送一封驗證信，請點擊信中的連結完成驗證。
                </p>
            </div>

            <div v-if="status === 'verification-link-sent'" class="rounded-md bg-teal-50 p-3 text-sm text-teal-700 dark:bg-teal-950 dark:text-teal-300">
                驗證連結已重新發送至您的信箱，請查收。
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <button
                    type="button"
                    :disabled="form.processing"
                    class="inline-flex min-h-10 items-center justify-center rounded-md bg-teal-600 px-4 text-sm font-semibold text-white transition hover:bg-teal-700 disabled:opacity-50"
                    @click="resend"
                >
                    重新發送驗證信
                </button>

                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="text-sm text-neutral-600 underline hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-neutral-100"
                >
                    登出
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>
