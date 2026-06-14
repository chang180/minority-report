<script setup lang="ts">
defineProps<{
    for?: string;
}>();
</script>

<template>
    <label :for="for" class="text-sm font-medium leading-none text-neutral-800 dark:text-neutral-200">
        <slot />
    </label>
</template>
