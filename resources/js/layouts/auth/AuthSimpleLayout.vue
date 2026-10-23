<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import BrandWordmark from '@/components/BrandWordmark.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative isolate flex min-h-svh flex-col items-center justify-center overflow-hidden bg-background px-6 py-12 md:p-10"
    >
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(60rem_40rem_at_50%_-10%,color-mix(in_oklab,var(--primary)_18%,transparent),transparent_70%),radial-gradient(40rem_30rem_at_100%_110%,color-mix(in_oklab,var(--primary)_10%,transparent),transparent_70%)]"
        />

        <div class="w-full max-w-md">
            <div class="flex flex-col gap-10">
                <div class="flex flex-col items-center gap-3 text-center">
                    <Link :href="home()" class="text-6xl leading-none">
                        <BrandWordmark />
                    </Link>
                    <h1 class="text-3xl font-semibold tracking-tight">
                        {{ title }}
                    </h1>
                    <p v-if="description" class="text-muted-foreground">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
