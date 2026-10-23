<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search, Users } from '@lucide/vue';
import { useDebounceFn } from '@vueuse/core';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { daysAgo, formatDate } from '@/lib/fitness';
import { create, index, show } from '@/routes/members';
import type { MemberListItem, Paginated } from '@/types';

const props = defineProps<{
    members: Paginated<MemberListItem>;
    filters: { search: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Members', href: index() }],
    },
});

const search = ref(props.filters.search);

const applySearch = useDebounceFn(() => {
    router.get(
        index.url({ query: { search: search.value || undefined } }),
        {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 300);
</script>

<template>
    <Head title="Members" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Members"
                description="Everyone you're training. Search by name, email or phone."
            />
            <Button as-child>
                <Link :href="create()"><Plus /> New member</Link>
            </Button>
        </div>

        <div class="relative max-w-md">
            <Search
                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                v-model="search"
                type="search"
                class="pl-9"
                placeholder="Search members…"
                aria-label="Search members"
                @update:model-value="applySearch"
            />
        </div>

        <div
            v-if="members.data.length === 0"
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed p-12 text-center"
        >
            <Users class="size-8 text-muted-foreground" />
            <p v-if="filters.search" class="text-sm text-muted-foreground">
                No members match “{{ filters.search }}”.
            </p>
            <template v-else>
                <p class="text-sm text-muted-foreground">
                    You haven't added any members yet.
                </p>
                <Button as-child size="sm">
                    <Link :href="create()"><Plus /> Add your first member</Link>
                </Button>
            </template>
        </div>

        <div v-else class="overflow-hidden surface">
            <table class="w-full text-sm">
                <thead class="text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="hidden px-4 py-3 font-medium md:table-cell">
                            Contact
                        </th>
                        <th class="px-4 py-3 text-right font-medium">
                            Workouts
                        </th>
                        <th
                            class="hidden px-4 py-3 text-right font-medium sm:table-cell"
                        >
                            Last workout
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="member in members.data"
                        :key="member.id"
                        class="transition-colors hover:bg-accent/60"
                    >
                        <td class="px-4 py-3">
                            <Link
                                :href="show(member.id)"
                                class="font-medium hover:underline"
                            >
                                {{ member.name }}
                            </Link>
                            <p
                                v-if="member.goals"
                                class="line-clamp-1 text-xs text-muted-foreground"
                            >
                                {{ member.goals }}
                            </p>
                        </td>
                        <td
                            class="hidden px-4 py-3 text-muted-foreground md:table-cell"
                        >
                            <div>{{ member.email ?? '—' }}</div>
                            <div v-if="member.phone" class="text-xs">
                                {{ member.phone }}
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ member.workouts_count }}
                        </td>
                        <td
                            class="hidden px-4 py-3 text-right text-muted-foreground sm:table-cell"
                        >
                            <template v-if="member.last_workout_on">
                                <div>
                                    {{ formatDate(member.last_workout_on) }}
                                </div>
                                <div class="text-xs">
                                    {{ daysAgo(member.last_workout_on) }}
                                </div>
                            </template>
                            <span v-else>Never</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination :links="members.links" />
    </div>
</template>
