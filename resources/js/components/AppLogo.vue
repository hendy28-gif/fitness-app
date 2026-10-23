<script setup lang="ts">
import { Dumbbell } from '@lucide/vue';
import BrandWordmark from '@/components/BrandWordmark.vue';
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground"
    >
        <Dumbbell class="size-5" />
    </div>
    <div class="ml-1 grid flex-1 text-left">
        <BrandWordmark class="truncate text-xl leading-tight" />
    </div>
</template>
