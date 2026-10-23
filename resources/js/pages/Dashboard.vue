<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CalendarDays, Dumbbell, Plus, Search, Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useInitials } from '@/composables/useInitials';
import { daysAgo, formatDate } from '@/lib/fitness';
import { dashboard } from '@/routes';
import { create, index, show } from '@/routes/members';
import type { Workout } from '@/types';

const props = defineProps<{
    stats: {
        members: number;
        membersTrainedThisWeek: number;
        workoutsThisWeek: number;
        workoutsThisMonth: number;
    };
    recentWorkouts: Workout[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const page = usePage();
const { getInitials } = useInitials();

const greeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 12) {
        return 'Good morning,';
    }

    return hour < 18 ? 'Good afternoon,' : 'Good evening,';
});

const weeklyPercent = computed(() =>
    props.stats.members > 0
        ? Math.round(
              (props.stats.membersTrainedThisWeek / props.stats.members) * 100,
          )
        : 0,
);

const search = ref('');

function searchMembers(): void {
    router.get(index.url({ query: { search: search.value || undefined } }));
}

function exerciseNames(workout: Workout): string {
    return [...new Set(workout.entries.map((entry) => entry.exercise.name))]
        .slice(0, 4)
        .join(' · ');
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div
                    class="flex size-14 shrink-0 items-center justify-center rounded-full bg-primary text-lg font-bold text-primary-foreground"
                >
                    {{ getInitials(page.props.auth.user.name) }}
                </div>
                <div>
                    <h1 class="text-3xl leading-tight font-bold tracking-tight">
                        {{ greeting }}
                    </h1>
                    <p class="text-lg">{{ page.props.auth.user.name }}</p>
                </div>
            </div>
            <Button as-child class="hidden sm:inline-flex">
                <Link :href="create()"><Plus /> New member</Link>
            </Button>
        </div>

        <section class="surface p-6">
            <h2 class="text-xl font-semibold">
                <template v-if="stats.members === 0">
                    Add your first member to get started
                </template>
                <template v-else>
                    You’ve trained {{ stats.membersTrainedThisWeek }} of
                    {{ stats.members }}
                    {{ stats.members === 1 ? 'member' : 'members' }} this week!
                </template>
            </h2>
            <p class="text-muted-foreground">
                {{ weeklyPercent }}% of your members have had a session since
                Monday.
            </p>
            <div
                class="mt-5 h-3 overflow-hidden rounded-full bg-muted"
                role="progressbar"
                :aria-valuenow="weeklyPercent"
                aria-valuemin="0"
                aria-valuemax="100"
                aria-label="Members trained this week"
            >
                <div
                    class="h-full rounded-full bg-primary transition-[width] duration-700"
                    :style="{ width: `${weeklyPercent}%` }"
                />
            </div>
        </section>

        <form class="flex gap-3" @submit.prevent="searchMembers">
            <div class="relative flex-1">
                <Search
                    class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    class="h-13 pl-12 text-base"
                    placeholder="Search members"
                    aria-label="Search members"
                />
            </div>
            <Button
                type="submit"
                variant="secondary"
                class="size-13 rounded-xl"
                aria-label="Search"
            >
                <Search class="size-5" />
            </Button>
        </form>

        <div class="grid grid-cols-3 gap-3 md:gap-4">
            <Link
                :href="index()"
                class="surface p-4 transition-colors hover:bg-accent"
            >
                <Users class="mb-2 size-5 text-link dark:text-primary" />
                <div class="text-3xl font-bold">{{ stats.members }}</div>
                <div class="text-sm text-muted-foreground">Members</div>
            </Link>
            <div class="surface p-4">
                <Dumbbell class="mb-2 size-5 text-link dark:text-primary" />
                <div class="text-3xl font-bold">
                    {{ stats.workoutsThisWeek }}
                </div>
                <div class="text-sm text-muted-foreground">This week</div>
            </div>
            <div class="surface p-4">
                <CalendarDays class="mb-2 size-5 text-link dark:text-primary" />
                <div class="text-3xl font-bold">
                    {{ stats.workoutsThisMonth }}
                </div>
                <div class="text-sm text-muted-foreground">This month</div>
            </div>
        </div>

        <section class="space-y-4">
            <h2 class="text-2xl font-semibold">Recent workouts</h2>

            <div
                v-if="recentWorkouts.length === 0"
                class="flex flex-col items-center gap-3 surface p-10 text-center"
            >
                <Dumbbell class="size-8 text-muted-foreground" />
                <p class="text-muted-foreground">
                    No workouts logged yet. Open a member to log their first
                    session.
                </p>
            </div>

            <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Link
                    v-for="workout in recentWorkouts"
                    :key="workout.id"
                    :href="show(workout.member_id)"
                    class="group flex flex-col gap-3 surface p-5 transition-colors hover:bg-accent"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex size-10 shrink-0 items-center justify-center rounded-full bg-muted text-sm font-semibold group-hover:bg-background"
                            >
                                {{ getInitials(workout.member?.name ?? '') }}
                            </div>
                            <div class="min-w-0">
                                <div class="truncate text-lg font-semibold">
                                    {{ workout.member?.name }}
                                </div>
                                <div class="text-sm text-muted-foreground">
                                    {{ formatDate(workout.performed_on) }}
                                </div>
                            </div>
                        </div>
                        <span
                            class="shrink-0 rounded-full bg-muted px-3 py-1 text-xs font-medium group-hover:bg-background"
                        >
                            {{ daysAgo(workout.performed_on) }}
                        </span>
                    </div>
                    <p class="line-clamp-2 text-sm text-muted-foreground">
                        {{ exerciseNames(workout) }}
                    </p>
                </Link>
            </div>
        </section>
    </div>
</template>
