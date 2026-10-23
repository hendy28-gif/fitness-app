<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { PaginationLink } from '@/types';

defineProps<{
    links: PaginationLink[];
}>();

function label(link: PaginationLink): string {
    return link.label
        .replace('&laquo;', '‹')
        .replace('&raquo;', '›')
        .replace(/&amp;/g, '&');
}
</script>

<template>
    <nav
        v-if="links.length > 3"
        class="flex flex-wrap items-center justify-center gap-1"
        aria-label="Pagination"
    >
        <template v-for="(link, index) in links" :key="index">
            <Link
                v-if="link.url"
                :href="link.url"
                preserve-scroll
                class="min-w-9 rounded-md border px-3 py-1.5 text-center text-sm transition-colors hover:bg-accent"
                :class="
                    link.active
                        ? 'border-primary bg-primary text-primary-foreground hover:bg-primary/90'
                        : ''
                "
                :aria-current="link.active ? 'page' : undefined"
            >
                {{ label(link) }}
            </Link>
            <span
                v-else
                class="min-w-9 px-3 py-1.5 text-center text-sm text-muted-foreground"
            >
                {{ label(link) }}
            </span>
        </template>
    </nav>
</template>
