<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    href: string;
}>();
</script>

<template>
    <Link :href="href" class="font-medium text-teal-700 underline-offset-4 hover:underline dark:text-teal-300">
        <slot />
    </Link>
</template>
