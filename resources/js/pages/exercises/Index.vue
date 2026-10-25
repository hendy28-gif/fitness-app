<script setup lang="ts">
import { Form, Head, useForm } from '@inertiajs/vue3';
import { Dumbbell, Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { destroy, index, store, update } from '@/routes/exercises';
import type { LibraryExercise } from '@/types';

const props = defineProps<{
    exercises: LibraryExercise[];
    categories: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Exercises', href: index() }],
    },
});

const NO_CATEGORY = '';

const search = ref('');
const categoryFilter = ref<string | null>(null);

/**
 * Exercises grouped by category in the library's order, with uncategorised ones last.
 * Search matches every typed word against the name, so "inc db" finds "Incline Dumbbell Press".
 */
const groups = computed(() => {
    const words = search.value.toLowerCase().split(/\s+/).filter(Boolean);

    const visible = props.exercises.filter((exercise) => {
        const name = exercise.name.toLowerCase();
        const category = exercise.category ?? NO_CATEGORY;

        return (
            words.every((word) => name.includes(word)) &&
            (categoryFilter.value === null || categoryFilter.value === category)
        );
    });

    return [...props.categories, NO_CATEGORY]
        .map((category) => ({
            category,
            exercises: visible.filter(
                (exercise) => (exercise.category ?? NO_CATEGORY) === category,
            ),
        }))
        .filter((group) => group.exercises.length > 0);
});

const visibleCount = computed(() =>
    groups.value.reduce((total, group) => total + group.exercises.length, 0),
);

const isEditorOpen = ref(false);
const editing = ref<LibraryExercise | null>(null);
const form = useForm({ name: '', category: '' });

function openAdd(category: string = categoryFilter.value ?? ''): void {
    editing.value = null;
    form.defaults({ name: '', category });
    form.reset();
    form.clearErrors();
    isEditorOpen.value = true;
}

function openEdit(exercise: LibraryExercise): void {
    editing.value = exercise;
    form.defaults({ name: exercise.name, category: exercise.category ?? '' });
    form.reset();
    form.clearErrors();
    isEditorOpen.value = true;
}

function save(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            isEditorOpen.value = false;
        },
    };

    if (editing.value) {
        form.put(update.url(editing.value.id), options);
    } else {
        form.post(store.url(), options);
    }
}

const exerciseToDelete = ref<LibraryExercise | null>(null);
</script>

<template>
    <Head title="Exercises" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Exercises"
                description="Your exercise library. These are suggested when you log a workout."
            />
            <Button @click="openAdd()"><Plus /> Add exercise</Button>
        </div>

        <div class="flex flex-wrap gap-3">
            <div class="relative min-w-60 flex-1 sm:max-w-md">
                <Search
                    class="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    class="pl-10"
                    placeholder="Search exercises…"
                    aria-label="Search exercises"
                />
            </div>
            <select
                v-model="categoryFilter"
                class="h-11 field"
                aria-label="Filter by category"
            >
                <option :value="null">All categories</option>
                <option
                    v-for="category in categories"
                    :key="category"
                    :value="category"
                >
                    {{ category }}
                </option>
                <option :value="NO_CATEGORY">No category</option>
            </select>
        </div>

        <p class="-mt-2 text-sm text-muted-foreground">
            {{ visibleCount }} of {{ exercises.length }} exercises
        </p>

        <div
            v-if="groups.length === 0"
            class="flex flex-col items-center gap-3 surface p-10 text-center"
        >
            <Dumbbell class="size-8 text-muted-foreground" />
            <p class="text-muted-foreground">
                <template v-if="search">
                    No exercises match “{{ search }}”.
                </template>
                <template v-else>No exercises in this category yet.</template>
            </p>
            <Button size="sm" @click="openAdd()"><Plus /> Add exercise</Button>
        </div>

        <section
            v-for="group in groups"
            :key="group.category"
            class="overflow-hidden surface"
        >
            <header
                class="flex items-center justify-between gap-2 border-b px-4 py-3"
            >
                <h2 class="font-semibold">
                    {{ group.category || 'No category' }}
                    <span
                        class="ml-1 text-sm font-normal text-muted-foreground"
                        >{{ group.exercises.length }}</span
                    >
                </h2>
                <Button
                    variant="ghost"
                    size="sm"
                    class="text-link dark:text-primary"
                    @click="openAdd(group.category)"
                >
                    <Plus /> Add
                </Button>
            </header>
            <ul class="divide-y">
                <li
                    v-for="exercise in group.exercises"
                    :key="exercise.id"
                    class="flex items-center justify-between gap-3 px-4 py-2"
                >
                    <div class="min-w-0">
                        <div class="truncate">{{ exercise.name }}</div>
                        <div
                            v-if="exercise.workout_entries_count > 0"
                            class="text-xs text-muted-foreground"
                        >
                            Logged
                            {{ exercise.workout_entries_count }}
                            {{
                                exercise.workout_entries_count === 1
                                    ? 'time'
                                    : 'times'
                            }}
                        </div>
                    </div>
                    <div class="flex shrink-0 gap-1">
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            :title="`Edit ${exercise.name}`"
                            @click="openEdit(exercise)"
                        >
                            <Pencil />
                            <span class="sr-only"
                                >Edit {{ exercise.name }}</span
                            >
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            :disabled="exercise.workout_entries_count > 0"
                            :title="
                                exercise.workout_entries_count > 0
                                    ? 'Used in workouts, so it can’t be deleted. Rename it instead.'
                                    : `Delete ${exercise.name}`
                            "
                            @click="exerciseToDelete = exercise"
                        >
                            <Trash2 />
                            <span class="sr-only"
                                >Delete {{ exercise.name }}</span
                            >
                        </Button>
                    </div>
                </li>
            </ul>
        </section>

        <Dialog v-model:open="isEditorOpen">
            <DialogContent>
                <form class="space-y-6" @submit.prevent="save">
                    <DialogHeader>
                        <DialogTitle>
                            {{ editing ? 'Edit exercise' : 'Add exercise' }}
                        </DialogTitle>
                        <DialogDescription v-if="editing">
                            Renaming also updates past workouts that used this
                            exercise.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label for="exercise-name">Name</Label>
                        <Input
                            id="exercise-name"
                            v-model="form.name"
                            required
                            autofocus
                            placeholder="e.g. Incline Dumbbell Press"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="exercise-category">Category</Label>
                        <Input
                            id="exercise-category"
                            v-model="form.category"
                            list="exercise-categories"
                            autocomplete="off"
                            placeholder="Pick one, type a new one, or leave empty"
                        />
                        <datalist id="exercise-categories">
                            <option
                                v-for="category in categories"
                                :key="category"
                                :value="category"
                            />
                        </datalist>
                        <InputError :message="form.errors.category" />
                    </div>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button type="button" variant="secondary"
                                >Cancel</Button
                            >
                        </DialogClose>
                        <Button type="submit" :disabled="form.processing">
                            {{ editing ? 'Save changes' : 'Add exercise' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="exerciseToDelete !== null"
            @update:open="(open) => !open && (exerciseToDelete = null)"
        >
            <DialogContent v-if="exerciseToDelete">
                <DialogHeader>
                    <DialogTitle>
                        Delete “{{ exerciseToDelete.name }}”?
                    </DialogTitle>
                    <DialogDescription>
                        It will no longer be suggested when logging workouts.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Form
                        v-bind="destroy.form(exerciseToDelete.id)"
                        :options="{ preserveScroll: true }"
                        @success="exerciseToDelete = null"
                        v-slot="{ processing }"
                    >
                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="processing"
                        >
                            Delete exercise
                        </Button>
                    </Form>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
