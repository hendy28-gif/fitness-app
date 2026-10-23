<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { House, User, Users } from '@lucide/vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard } from '@/routes';
import { index } from '@/routes/members';
import { edit } from '@/routes/profile';
import type { NavItem } from '@/types';

const { isCurrentOrParentUrl } = useCurrentUrl();

const items: NavItem[] = [
    { title: 'Home', href: dashboard(), icon: House },
    { title: 'Members', href: index(), icon: Users },
    { title: 'Profile', href: edit(), icon: User },
];
</script>

<template>
    <nav
        class="fixed inset-x-0 bottom-0 z-40 border-t bg-background/95 pb-[env(safe-area-inset-bottom)] backdrop-blur md:hidden"
        aria-label="Primary"
    >
        <ul class="grid grid-cols-3">
            <li v-for="item in items" :key="item.title">
                <Link
                    :href="item.href"
                    class="flex flex-col items-center gap-1 py-3 text-xs font-medium transition-colors"
                    :class="
                        isCurrentOrParentUrl(item.href)
                            ? 'text-link dark:text-primary'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    :aria-current="
                        isCurrentOrParentUrl(item.href) ? 'page' : undefined
                    "
                >
                    <component :is="item.icon" class="size-6" />
                    {{ item.title }}
                </Link>
            </li>
        </ul>
    </nav>
</template>
