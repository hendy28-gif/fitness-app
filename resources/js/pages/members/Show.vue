<script setup lang="ts">
import { Form, Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import {
    ChartLine,
    Dumbbell,
    History,
    Pencil,
    Plus,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    daysAgo,
    describeSets,
    formatDate,
    formatWeight,
    parseDate,
} from '@/lib/fitness';
import { edit, index, progress, show } from '@/routes/members';
import {
    create as createWorkout,
    destroy as destroyWorkout,
    edit as editWorkout,
} from '@/routes/members/workouts';
import type { ExerciseSummary, Member, Paginated, Workout } from '@/types';

const props = defineProps<{
    member: Member;
    exerciseSummaries: ExerciseSummary[];
    workouts: Paginated<Workout>;
    filters: { exercise: number | null };
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Members', href: index() },
        { title: props.member.name, href: show(props.member.id) },
    ],
});

const age = computed(() => {
    if (!props.member.date_of_birth) {
        return null;
    }

    const birth = parseDate(props.member.date_of_birth);
    const today = new Date();
    const hadBirthdayThisYear =
        today.getMonth() > birth.getMonth() ||
        (today.getMonth() === birth.getMonth() &&
            today.getDate() >= birth.getDate());

    return (
        today.getFullYear() -
        birth.getFullYear() -
        (hadBirthdayThisYear ? 0 : 1)
    );
});

const profileFacts = computed(() =>
    [
        { label: 'Age', value: age.value !== null ? `${age.value}` : null },
        {
            label: 'Gender',
            value: props.member.gender
                ? props.member.gender.charAt(0).toUpperCase() +
                  props.member.gender.slice(1)
                : null,
        },
        {
            label: 'Height',
            value: props.member.height_cm
                ? `${Number(props.member.height_cm)} cm`
                : null,
        },
        {
            label: 'Body weight',
            value: props.member.weight_kg
                ? formatWeight(props.member.weight_kg)
                : null,
        },
        { label: 'Email', value: props.member.email },
        { label: 'Phone', value: props.member.phone },
    ].filter((fact) => fact.value),
);

const selectedExercise = ref(props.filters.exercise ?? '');

function filterHistory(): void {
    router.get(
        show.url(props.member.id, {
            query: { exercise: selectedExercise.value || undefined },
        }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            only: ['workouts', 'filters'],
        },
    );
}

function showHistoryFor(exerciseId: number): void {
    selectedExercise.value = exerciseId;
    filterHistory();
    document
        .getElementById('history')
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

const workoutToDelete = ref<Workout | null>(null);
</script>

<template>
    <Head :title="member.name" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ member.name }}
                </h1>
                <p
                    v-if="member.goals"
                    class="max-w-2xl text-sm whitespace-pre-line text-muted-foreground"
                >
                    {{ member.goals }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link :href="edit(member.id)"><Pencil /> Edit</Link>
                </Button>
                <Button
                    v-if="exerciseSummaries.length"
                    variant="outline"
                    as-child
                >
                    <Link :href="progress(member.id)"
                        ><ChartLine /> Progress</Link
                    >
                </Button>
                <Button as-child>
                    <Link :href="createWorkout(member.id)"
                        ><Plus /> Log workout</Link
                    >
                </Button>
            </div>
        </div>

        <div
            v-if="profileFacts.length || member.notes"
            class="grid gap-4 surface p-4 text-sm sm:grid-cols-3 lg:grid-cols-6"
        >
            <div v-for="fact in profileFacts" :key="fact.label">
                <div class="text-xs text-muted-foreground">
                    {{ fact.label }}
                </div>
                <div class="truncate font-medium">{{ fact.value }}</div>
            </div>
            <div v-if="member.notes" class="sm:col-span-3 lg:col-span-6">
                <div class="text-xs text-muted-foreground">Notes</div>
                <p class="whitespace-pre-line">{{ member.notes }}</p>
            </div>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Exercise summary</CardTitle>
                <CardDescription>
                    What {{ member.name }} did last time for each exercise — use
                    it to plan the next session.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="exerciseSummaries.length === 0"
                    class="flex flex-col items-center gap-3 py-8 text-center"
                >
                    <Dumbbell class="size-8 text-muted-foreground" />
                    <p class="text-sm text-muted-foreground">
                        No workouts logged yet.
                    </p>
                    <Button as-child size="sm">
                        <Link :href="createWorkout(member.id)"
                            ><Plus /> Log the first workout</Link
                        >
                    </Button>
                </div>
                <div v-else class="-mx-6 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-muted-foreground">
                            <tr class="border-b">
                                <th class="px-6 py-2 font-medium">Exercise</th>
                                <th class="px-3 py-2 font-medium">Last done</th>
                                <th class="px-3 py-2 font-medium">
                                    Last session
                                </th>
                                <th class="px-3 py-2 text-right font-medium">
                                    Best
                                </th>
                                <th class="px-3 py-2 text-right font-medium">
                                    Sessions
                                </th>
                                <th class="px-6 py-2">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="summary in exerciseSummaries"
                                :key="summary.exercise_id"
                            >
                                <td class="px-6 py-3 font-medium">
                                    {{ summary.exercise }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <div>
                                        {{
                                            formatDate(
                                                summary.last_performed_on,
                                            )
                                        }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ daysAgo(summary.last_performed_on) }}
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    <div
                                        v-for="(line, i) in describeSets(
                                            summary.last_sets,
                                        )"
                                        :key="i"
                                        class="whitespace-nowrap tabular-nums"
                                    >
                                        {{ line }}
                                    </div>
                                </td>
                                <td
                                    class="px-3 py-3 text-right whitespace-nowrap tabular-nums"
                                >
                                    {{
                                        Number(summary.best_weight_kg) > 0
                                            ? formatWeight(
                                                  summary.best_weight_kg,
                                              )
                                            : '—'
                                    }}
                                </td>
                                <td class="px-3 py-3 text-right tabular-nums">
                                    {{ summary.sessions }}
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex justify-end gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon-sm"
                                            :title="`Progress for ${summary.exercise}`"
                                            as-child
                                        >
                                            <Link
                                                :href="
                                                    progress(member.id, {
                                                        query: {
                                                            exercise:
                                                                summary.exercise_id,
                                                        },
                                                    })
                                                "
                                            >
                                                <ChartLine />
                                                <span class="sr-only"
                                                    >Progress</span
                                                >
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon-sm"
                                            :title="`History for ${summary.exercise}`"
                                            @click="
                                                showHistoryFor(
                                                    summary.exercise_id,
                                                )
                                            "
                                        >
                                            <History />
                                            <span class="sr-only">History</span>
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <section
            v-if="exerciseSummaries.length"
            id="history"
            class="scroll-mt-4 space-y-4"
        >
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold">Workout history</h2>
                <label class="flex items-center gap-2 text-sm">
                    <span class="text-muted-foreground">Exercise</span>
                    <select
                        v-model="selectedExercise"
                        class="h-10 field"
                        @change="filterHistory"
                    >
                        <option value="">All exercises</option>
                        <option
                            v-for="summary in exerciseSummaries"
                            :key="summary.exercise_id"
                            :value="summary.exercise_id"
                        >
                            {{ summary.exercise }}
                        </option>
                    </select>
                </label>
            </div>

            <p
                v-if="workouts.data.length === 0"
                class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground"
            >
                No workouts match this filter.
            </p>

            <article
                v-for="workout in workouts.data"
                :key="workout.id"
                class="surface"
            >
                <header
                    class="flex items-center justify-between gap-2 border-b px-4 py-2"
                >
                    <div>
                        <span class="font-medium">{{
                            formatDate(workout.performed_on)
                        }}</span>
                        <span class="ml-2 text-xs text-muted-foreground">{{
                            daysAgo(workout.performed_on)
                        }}</span>
                    </div>
                    <div class="flex gap-1">
                        <Button variant="ghost" size="icon-sm" as-child>
                            <Link
                                :href="editWorkout([member.id, workout.id])"
                                title="Edit workout"
                            >
                                <Pencil />
                                <span class="sr-only">Edit workout</span>
                            </Link>
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            title="Delete workout"
                            @click="workoutToDelete = workout"
                        >
                            <Trash2 />
                            <span class="sr-only">Delete workout</span>
                        </Button>
                    </div>
                </header>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-xs text-muted-foreground">
                            <tr>
                                <th class="px-4 pt-2 font-medium">Exercise</th>
                                <th class="px-4 pt-2 font-medium">
                                    Sets × reps @ weight
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="entry in workout.entries"
                                :key="entry.id"
                                :class="
                                    filters.exercise &&
                                    entry.exercise_id !== filters.exercise
                                        ? 'text-muted-foreground'
                                        : ''
                                "
                            >
                                <td class="px-4 py-1.5 align-top">
                                    {{ entry.exercise.name }}
                                </td>
                                <td class="px-4 py-1.5 tabular-nums">
                                    <div
                                        v-for="(line, i) in describeSets(
                                            entry.sets,
                                        )"
                                        :key="i"
                                        class="whitespace-nowrap"
                                    >
                                        {{ line }}
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p
                    v-if="workout.notes"
                    class="border-t px-4 py-2 text-sm whitespace-pre-line text-muted-foreground"
                >
                    {{ workout.notes }}
                </p>
                <div v-else class="h-2" />
            </article>

            <Pagination :links="workouts.links" />
        </section>

        <Dialog
            :open="workoutToDelete !== null"
            @update:open="(open) => !open && (workoutToDelete = null)"
        >
            <DialogContent v-if="workoutToDelete">
                <DialogHeader>
                    <DialogTitle>Delete this workout?</DialogTitle>
                    <DialogDescription>
                        The workout from
                        {{ formatDate(workoutToDelete.performed_on) }} will be
                        permanently removed.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Form
                        v-bind="
                            destroyWorkout.form([member.id, workoutToDelete.id])
                        "
                        :options="{ preserveScroll: true }"
                        @success="workoutToDelete = null"
                        v-slot="{ processing }"
                    >
                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="processing"
                        >
                            Delete workout
                        </Button>
                    </Form>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
