<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Lock, Mail } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import PasskeyVerify from '@/components/PasskeyVerify.vue';

defineOptions({
    layout: {
        title: 'Welcome back',
        description: 'Log in to track your members’ progress',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Log in" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600 dark:text-green-500"
    >
        {{ status }}
    </div>

    <PasskeyVerify />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-8"
    >
        <div class="grid gap-6">
            <div class="grid gap-2.5">
                <Label for="email" class="text-base font-normal"
                    >Enter your email *</Label
                >
                <div class="relative">
                    <Mail
                        class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        v-focus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="i.e. trainer@befit.com"
                        class="h-13 pl-12 text-base"
                    />
                </div>
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2.5">
                <div class="flex items-center justify-between">
                    <Label for="password" class="text-base font-normal"
                        >Password *</Label
                    >
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm"
                        :tabindex="5"
                    >
                        Forgot password?
                    </TextLink>
                </div>
                <div class="relative">
                    <Lock
                        class="pointer-events-none absolute top-1/2 left-4 z-10 size-5 -translate-y-1/2 text-muted-foreground"
                    />
                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="h-13 pl-12 text-base"
                    />
                </div>
                <InputError :message="errors.password" />
            </div>

            <Label for="remember" class="flex items-center gap-3 font-normal">
                <Checkbox id="remember" name="remember" :tabindex="3" />
                <span>Remember me</span>
            </Label>

            <Button
                type="submit"
                size="lg"
                class="mt-2 w-full text-lg"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                Log in
            </Button>
        </div>

        <div class="text-center text-muted-foreground">
            Don't have an account?
            <TextLink :href="register()" :tabindex="5">Sign up</TextLink>
        </div>
    </Form>
</template>
