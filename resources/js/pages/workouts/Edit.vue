<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import WorkoutForm from '@/components/workouts/WorkoutForm.vue';
import { formatDate } from '@/lib/fitness';
import { index, show } from '@/routes/members';
import { edit, update } from '@/routes/members/workouts';
import type {
    ExerciseOptionGroup,
    ExerciseSummary,
    Member,
    Workout,
} from '@/types';

const props = defineProps<{
    member: Pick<Member, 'id' | 'name'>;
    workout: Workout;
    exerciseOptions: ExerciseOptionGroup[];
    exerciseSummaries: ExerciseSummary[];
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Members', href: index() },
        { title: props.member.name, href: show(props.member.id) },
        {
            title: 'Edit workout',
            href: edit([props.member.id, props.workout.id]),
        },
    ],
});
</script>

<template>
    <Head :title="`Edit workout · ${member.name}`" />

    <div class="mx-auto w-full max-w-3xl p-4 md:p-6">
        <Heading
            title="Edit workout"
            :description="`${member.name} · ${formatDate(workout.performed_on)}`"
        />

        <WorkoutForm
            :action="update([member.id, workout.id])"
            :exercise-options="exerciseOptions"
            :exercise-summaries="exerciseSummaries"
            :initial="{
                performed_on: workout.performed_on,
                notes: workout.notes ?? '',
                entries: workout.entries.map((entry) => ({
                    exercise: entry.exercise.name,
                    sets: entry.sets.map((set) => ({
                        reps: set.reps,
                        weight_kg: Number(set.weight_kg) || '',
                    })),
                })),
            }"
            submit-label="Save changes"
        />
    </div>
</template>
