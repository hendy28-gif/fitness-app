<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { History, Plus, Trash2, X } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import ExerciseCombobox from '@/components/workouts/ExerciseCombobox.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { daysAgo, describeSets, formatDate, formatWeight } from '@/lib/fitness';
import type {
    ExerciseOptionGroup,
    ExerciseSummary,
    SetSummary,
    Workout,
    WorkoutFormEntry,
    WorkoutFormSet,
} from '@/types';
import type { RouteDefinition } from '@/wayfinder';

const props = defineProps<{
    action: RouteDefinition<'post' | 'put'>;
    exerciseOptions: ExerciseOptionGroup[];
    exerciseSummaries: ExerciseSummary[];
    initial: {
        performed_on: string;
        notes: string;
        entries: WorkoutFormEntry[];
    };
    lastWorkout?: Workout | null;
    submitLabel: string;
}>();

const blankSet = (): WorkoutFormSet => ({ reps: 10, weight_kg: '' });

const blankEntry = (): WorkoutFormEntry => ({
    exercise: '',
    sets: [blankSet(), blankSet(), blankSet()],
});

const toFormSets = (sets: SetSummary[]): WorkoutFormSet[] =>
    sets.map((set) => ({
        reps: set.reps,
        weight_kg: Number(set.weight_kg) || '',
    }));

const form = useForm({
    performed_on: props.initial.performed_on,
    notes: props.initial.notes,
    entries: props.initial.entries.length
        ? props.initial.entries
        : [blankEntry()],
});

const summariesByName = computed(
    () =>
        new Map(
            props.exerciseSummaries.map((summary) => [
                summary.exercise.toLowerCase(),
                summary,
            ]),
        ),
);

function summaryFor(entry: WorkoutFormEntry): ExerciseSummary | undefined {
    return summariesByName.value.get(
        entry.exercise.trim().replace(/\s+/g, ' ').toLowerCase(),
    );
}

function addEntry(): void {
    form.entries.push(blankEntry());
}

function removeEntry(index: number): void {
    form.entries.splice(index, 1);
}

/**
 * New sets start as a copy of the previous one, since most sets repeat it or change only slightly.
 */
function addSet(entry: WorkoutFormEntry): void {
    const previous = entry.sets[entry.sets.length - 1];

    entry.sets.push(previous ? { ...previous } : blankSet());
}

function removeSet(entry: WorkoutFormEntry, setIndex: number): void {
    entry.sets.splice(setIndex, 1);
}

function useLastSets(entry: WorkoutFormEntry): void {
    const summary = summaryFor(entry);

    if (summary) {
        entry.sets = toFormSets(summary.last_sets);
    }
}

function error(key: string): string | undefined {
    return (form.errors as Record<string, string | undefined>)[key];
}

function copyLastWorkout(): void {
    if (!props.lastWorkout) {
        return;
    }

    form.entries = props.lastWorkout.entries.map((entry) => ({
        exercise: entry.exercise.name,
        sets: toFormSets(entry.sets),
    }));
}

function submit(): void {
    form.submit(props.action);
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div class="grid gap-2 sm:max-w-xs">
            <Label for="performed_on">Date</Label>
            <Input
                id="performed_on"
                v-model="form.performed_on"
                type="date"
                required
            />
            <InputError :message="form.errors.performed_on" />
        </div>

        <div class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-medium">Exercises</h2>
                <Button
                    v-if="lastWorkout"
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="copyLastWorkout"
                >
                    <History />
                    Copy last workout ({{
                        formatDate(lastWorkout.performed_on)
                    }})
                </Button>
            </div>

            <InputError :message="form.errors.entries" />

            <div
                v-for="(entry, index) in form.entries"
                :key="index"
                class="space-y-3 surface p-4"
            >
                <div class="flex items-end gap-2">
                    <div class="grid flex-1 gap-1.5">
                        <Label :for="`exercise-${index}`" class="text-xs"
                            >Exercise {{ index + 1 }}</Label
                        >
                        <ExerciseCombobox
                            :id="`exercise-${index}`"
                            v-model="entry.exercise"
                            :options="exerciseOptions"
                            required
                            placeholder="Search exercises…"
                        />
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="size-11"
                        title="Remove exercise"
                        :disabled="form.entries.length === 1"
                        @click="removeEntry(index)"
                    >
                        <Trash2 />
                        <span class="sr-only">Remove exercise</span>
                    </Button>
                </div>
                <InputError :message="error(`entries.${index}.exercise`)" />

                <div
                    v-if="summaryFor(entry)"
                    class="flex flex-wrap items-center gap-x-2 gap-y-1 rounded-lg bg-muted/60 px-3 py-2 text-xs text-muted-foreground"
                >
                    <span>
                        Last time ({{
                            daysAgo(summaryFor(entry)!.last_performed_on)
                        }}):
                        <span class="text-foreground tabular-nums">{{
                            describeSets(summaryFor(entry)!.last_sets).join(
                                ', ',
                            )
                        }}</span>
                        <template
                            v-if="Number(summaryFor(entry)!.best_weight_kg) > 0"
                        >
                            · Best
                            {{
                                formatWeight(summaryFor(entry)!.best_weight_kg)
                            }}
                        </template>
                    </span>
                    <button
                        type="button"
                        class="font-medium text-link hover:underline dark:text-primary"
                        @click="useLastSets(entry)"
                    >
                        Use these sets
                    </button>
                </div>

                <div class="space-y-2">
                    <div
                        class="grid grid-cols-[2.5rem_minmax(0,1fr)_minmax(0,1fr)_2.25rem] items-center gap-2 px-1 text-xs text-muted-foreground"
                        aria-hidden="true"
                    >
                        <span>Set</span>
                        <span>Reps</span>
                        <span>Weight (kg)</span>
                        <span />
                    </div>

                    <div
                        v-for="(set, setIndex) in entry.sets"
                        :key="setIndex"
                        class="space-y-1"
                    >
                        <div
                            class="grid grid-cols-[2.5rem_minmax(0,1fr)_minmax(0,1fr)_2.25rem] items-center gap-2"
                        >
                            <span
                                class="flex size-9 items-center justify-center rounded-full bg-muted text-sm font-semibold tabular-nums"
                            >
                                {{ setIndex + 1 }}
                            </span>
                            <Input
                                v-model="set.reps"
                                type="number"
                                min="1"
                                inputmode="numeric"
                                required
                                :aria-label="`Set ${setIndex + 1} reps`"
                            />
                            <Input
                                v-model="set.weight_kg"
                                type="number"
                                min="0"
                                step="0.25"
                                inputmode="decimal"
                                placeholder="Weight"
                                :aria-label="`Set ${setIndex + 1} weight in kg`"
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                title="Remove set"
                                :disabled="entry.sets.length === 1"
                                @click="removeSet(entry, setIndex)"
                            >
                                <X />
                                <span class="sr-only"
                                    >Remove set {{ setIndex + 1 }}</span
                                >
                            </Button>
                        </div>
                        <InputError
                            class="pl-12"
                            :message="
                                error(
                                    `entries.${index}.sets.${setIndex}.reps`,
                                ) ??
                                error(
                                    `entries.${index}.sets.${setIndex}.weight_kg`,
                                )
                            "
                        />
                    </div>

                    <InputError :message="error(`entries.${index}.sets`)" />

                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="text-link dark:text-primary"
                        @click="addSet(entry)"
                    >
                        <Plus /> Add set
                    </Button>
                </div>
            </div>

            <Button type="button" variant="outline" @click="addEntry">
                <Plus /> Add exercise
            </Button>
        </div>

        <div class="grid gap-2">
            <Label for="notes">Notes</Label>
            <textarea
                id="notes"
                v-model="form.notes"
                rows="3"
                class="w-full field py-2.5"
                placeholder="How did it go? Energy, form cues, pain…"
            />
            <InputError :message="form.errors.notes" />
        </div>

        <Button type="submit" :disabled="form.processing">{{
            submitLabel
        }}</Button>
    </form>
</template>
