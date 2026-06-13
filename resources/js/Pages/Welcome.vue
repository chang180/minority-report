<script setup lang="ts">
defineProps<{
    laravelVersion: string;
    phpVersion: string;
}>();
</script>

<template>
    <div class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] flex flex-col items-center justify-center p-6">
        <main class="max-w-xl text-center space-y-4">
            <h1 class="text-3xl font-semibold tracking-tight">
                關鍵報告 · Minority Report
            </h1>
            <p class="text-[#706f6c] leading-relaxed">
                Multi-LLM Consensus Engine — Vue + Inertia 前端骨架已就緒。
            </p>
            <p class="text-sm text-[#706f6c]">
                Laravel {{ laravelVersion }} · PHP {{ phpVersion }}
            </p>
        </main>
    </div>
</template>
