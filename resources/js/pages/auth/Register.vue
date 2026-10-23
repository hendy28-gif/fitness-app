<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Lock, Mail, User } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Create an account',
    },
});

const iconClass =
    'pointer-events-none absolute top-1/2 left-4 z-10 size-5 -translate-y-1/2 text-muted-foreground';
const fieldClass = 'h-13 pl-12 text-base';
</script>

<template>
    <Head title="Register" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-8"
    >
        <div class="grid gap-6">
            <div class="grid gap-2.5">
                <Label for="name" class="text-base font-normal"
                    >Your name *</Label
                >
                <div class="relative">
                    <User :class="iconClass" />
                    <Input
                        id="name"
                        type="text"
                        required
                        v-focus
                        :tabindex="1"
                        autocomplete="name"
                        name="name"
                        placeholder="Full name"
                        :class="fieldClass"
                    />
                </div>
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2.5">
                <Label for="email" class="text-base font-normal"
                    >Enter your email *</Label
                >
                <div class="relative">
                    <Mail :class="iconClass" />
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        name="email"
                        placeholder="i.e. trainer@befit.com"
                        :class="fieldClass"
                    />
                </div>
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2.5">
                <Label for="password" class="text-base font-normal"
                    >Create password *</Label
                >
                <div class="relative">
                    <Lock :class="iconClass" />
                    <PasswordInput
                        id="password"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        name="password"
                        placeholder="••••••••"
                        :passwordrules="passwordRules"
                        :class="fieldClass"
                    />
                </div>
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2.5">
                <Label for="password_confirmation" class="text-base font-normal"
                    >Repeat password *</Label
                >
                <div class="relative">
                    <Lock :class="iconClass" />
                    <PasswordInput
                        id="password_confirmation"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        name="password_confirmation"
                        placeholder="••••••••"
                        :passwordrules="passwordRules"
                        :class="fieldClass"
                    />
                </div>
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                size="lg"
                class="mt-2 w-full text-lg"
                tabindex="5"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Sign up
            </Button>
        </div>

        <div class="text-center text-muted-foreground">
            Already have an account?
            <TextLink :href="login()" :tabindex="6">Log in</TextLink>
        </div>
    </Form>
</template>
