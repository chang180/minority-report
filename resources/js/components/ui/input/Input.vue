<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed } from 'vue';

const props = withDefaults(defineProps<{
    modelValue?: string | boolean;
    type?: string;
    id?: string;
    name?: string;
    autocomplete?: string;
    required?: boolean;
    autofocus?: boolean;
    placeholder?: string;
    class?: string;
}>(), {
    modelValue: '',
    type: 'text',
    class: '',
});

const emit = defineEmits<{
    'update:modelValue': [value: string | boolean];
}>();

const classes = computed(() => cn(
    'flex h-10 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-950 outline-none transition placeholder:text-neutral-500 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-50 dark:placeholder:text-neutral-500',
    props.class,
));
</script>

<template>
    <input
        :id="id"
        :name="name"
        :type="type"
        :autocomplete="autocomplete"
        :required="required"
        :autofocus="autofocus"
        :placeholder="placeholder"
        :class="classes"
        :checked="type === 'checkbox' ? Boolean(modelValue) : undefined"
        :value="type === 'checkbox' ? '1' : modelValue"
        @input="emit('update:modelValue', type === 'checkbox' ? ($event.target as HTMLInputElement).checked : ($event.target as HTMLInputElement).value)"
    />
</template>
