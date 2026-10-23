<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import WorkoutForm from '@/components/workouts/WorkoutForm.vue';
import { todayDate } from '@/lib/fitness';
import { index, show } from '@/routes/members';
import { create, store } from '@/routes/members/workouts';
import type {
    ExerciseOptionGroup,
    ExerciseSummary,
    Member,
    Workout,
} from '@/types';

const props = defineProps<{
    member: Pick<Member, 'id' | 'name'>;
    exerciseOptions: ExerciseOptionGroup[];
    exerciseSummaries: ExerciseSummary[];
    lastWorkout: Workout | null;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Members', href: index() },
        { title: props.member.name, href: show(props.member.id) },
        { title: 'Log workout', href: create(props.member.id) },
    ],
});
</script>

<template>
    <Head :title="`Log workout · ${member.name}`" />

    <div class="mx-auto w-full max-w-3xl p-4 md:p-6">
        <Heading
            title="Log workout"
            :description="`Record today's session for ${member.name}`"
        />

        <WorkoutForm
            :action="store(member.id)"
            :exercise-options="exerciseOptions"
            :exercise-summaries="exerciseSummaries"
            :last-workout="lastWorkout"
            :initial="{ performed_on: todayDate(), notes: '', entries: [] }"
            submit-label="Save workout"
        />
    </div>
</template>
