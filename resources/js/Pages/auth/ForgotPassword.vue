<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

function submit(): void {
    form.post('/forgot-password');
}
</script>

<template>
    <AuthLayout>
        <Card>
            <CardHeader>
                <CardTitle>Reset password</CardTitle>
                <CardDescription>Enter your email and Fortify will send a password reset link.</CardDescription>
            </CardHeader>
            <CardContent>
                <p v-if="status" class="mb-4 rounded-md bg-teal-50 px-3 py-2 text-sm text-teal-800 dark:bg-teal-950 dark:text-teal-200">
                    {{ status }}
                </p>

                <form class="grid gap-5" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input id="email" v-model="form.email" type="email" name="email" autocomplete="email" required autofocus />
                        <InputError :message="form.errors.email" />
                    </div>

                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Sending...' : 'Send reset link' }}
                    </Button>
                </form>

                <p class="mt-6 text-center text-sm">
                    <TextLink href="/login">Back to login</TextLink>
                </p>
            </CardContent>
        </Card>
    </AuthLayout>
</template>
