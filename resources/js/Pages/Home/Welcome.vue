<script setup lang="ts">
import GuestLayout from '@/layouts/GuestLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Gauge, Split, TestTubeDiagonal } from '@lucide/vue';
</script>

<template>
    <GuestLayout>
        <section class="mx-auto grid min-h-screen w-full max-w-6xl content-center gap-10 px-5 py-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
            <div class="space-y-7">
                <div class="space-y-4">
                    <p class="text-sm font-semibold uppercase tracking-normal text-teal-700 dark:text-teal-300">
                        Multi-LLM Consensus Engine
                    </p>
                    <h1 class="max-w-3xl text-4xl font-semibold tracking-normal text-neutral-950 dark:text-white sm:text-5xl">
                        關鍵報告
                    </h1>
                    <p class="max-w-2xl text-lg leading-8 text-neutral-700 dark:text-neutral-300">
                        用多個模型的共識、分歧與少數意見，降低單一模型幻覺風險。M7-A 先提供產品入口、訪客 Demo 與帳號基礎。
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <Link href="/demo" class="inline-flex min-h-11 items-center gap-2 rounded-md bg-teal-600 px-5 text-sm font-semibold text-white transition hover:bg-teal-700">
                        <TestTubeDiagonal class="size-4" />
                        Open demo
                    </Link>
                    <Link href="/register" class="inline-flex min-h-11 items-center gap-2 rounded-md border border-neutral-300 bg-white px-5 text-sm font-semibold text-neutral-950 transition hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:hover:bg-neutral-900">
                        Create account
                        <ArrowRight class="size-4" />
                    </Link>
                </div>
            </div>

            <div class="grid gap-3">
                <article class="rounded-lg border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="flex items-start gap-3">
                        <Split class="mt-1 size-5 text-teal-700 dark:text-teal-300" />
                        <div>
                            <h2 class="font-semibold">保留分歧</h2>
                            <p class="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                                Majority、Minority、No Consensus 都會保存並呈現，而不是只輸出單一答案。
                            </p>
                        </div>
                    </div>
                </article>
                <article class="rounded-lg border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="flex items-start gap-3">
                        <Gauge class="mt-1 size-5 text-amber-600 dark:text-amber-300" />
                        <div>
                            <h2 class="font-semibold">信任等級封頂</h2>
                            <p class="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                                Consensus 不等於正確；系統會依 grounding、有效表態與衝突類型限制 Trust Level。
                            </p>
                        </div>
                    </div>
                </article>
            </div>
        </section>
    </GuestLayout>
</template>
