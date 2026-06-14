<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <main class="flex min-h-screen items-center justify-center bg-neutral-950 text-neutral-100">
        <div class="mx-auto max-w-md space-y-4 px-6 text-center">
            <p class="text-sm font-medium text-teal-300">關鍵報告 · Minority Report</p>
            <h1 class="text-3xl font-semibold">訪客示範暫停服務</h1>
            <p class="text-neutral-400">系統管理員已暫時停用訪客示範功能，請稍後再試。</p>
            <Link href="/" class="mt-6 inline-flex min-h-10 items-center rounded border border-white/15 bg-white/10 px-4 font-medium text-white transition hover:bg-white/15">
                返回首頁
            </Link>
        </div>
    </main>
</template>
