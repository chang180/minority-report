<script setup lang="ts">
import { cn } from '@/lib/utils';
import { cva, type VariantProps } from 'class-variance-authority';
import { computed } from 'vue';

const buttonVariants = cva(
    'inline-flex min-h-10 items-center justify-center gap-2 rounded-md px-4 py-2 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 disabled:pointer-events-none disabled:opacity-60',
    {
        variants: {
            variant: {
                default: 'bg-teal-600 text-white hover:bg-teal-700',
                secondary: 'bg-neutral-100 text-neutral-950 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-50 dark:hover:bg-neutral-700',
                outline: 'border border-neutral-300 bg-white text-neutral-950 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-50 dark:hover:bg-neutral-900',
                ghost: 'text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-900',
                danger: 'bg-red-600 text-white hover:bg-red-700',
            },
            size: {
                default: 'h-10',
                sm: 'h-9 px-3',
                lg: 'h-11 px-5',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'default',
        },
    },
);

type ButtonVariants = VariantProps<typeof buttonVariants>;

const props = withDefaults(defineProps<{
    type?: 'button' | 'submit' | 'reset';
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
    class?: string;
    disabled?: boolean;
}>(), {
    type: 'button',
    variant: 'default',
    size: 'default',
    class: '',
    disabled: false,
});

const classes = computed(() => cn(buttonVariants({ variant: props.variant, size: props.size }), props.class));
</script>

<template>
    <button :type="type" :class="classes" :disabled="disabled">
        <slot />
    </button>
</template>
