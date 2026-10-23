<script setup lang="ts">
import { Search } from '@lucide/vue';
import { computed, nextTick, ref, useId, watch } from 'vue';
import { Input } from '@/components/ui/input';
import type { ExerciseOptionGroup } from '@/types';

const props = defineProps<{
    id: string;
    options: ExerciseOptionGroup[];
    placeholder?: string;
    required?: boolean;
}>();

const model = defineModel<string>({ required: true });

const listboxId = useId();
const isOpen = ref(false);
const activeIndex = ref(-1);
const listbox = ref<HTMLElement | null>(null);

const normalizedQuery = computed(() =>
    model.value.trim().replace(/\s+/g, ' ').toLowerCase(),
);

/**
 * Filter by every typed word (so "inc db" finds "Incline Dumbbell Press"), listing names that start with the query first.
 */
const filteredGroups = computed(() => {
    const words = normalizedQuery.value.split(' ').filter(Boolean);

    return props.options
        .map((group) => {
            const names = group.names.filter((name) => {
                const lower = name.toLowerCase();

                return words.every((word) => lower.includes(word));
            });

            const query = normalizedQuery.value;

            return {
                category: group.category,
                names: query
                    ? [
                          ...names.filter((name) =>
                              name.toLowerCase().startsWith(query),
                          ),
                          ...names.filter(
                              (name) => !name.toLowerCase().startsWith(query),
                          ),
                      ]
                    : names,
            };
        })
        .filter((group) => group.names.length > 0);
});

const flatOptions = computed(() =>
    filteredGroups.value.flatMap((group) => group.names),
);

const hasExactMatch = computed(() =>
    flatOptions.value.some(
        (name) => name.toLowerCase() === normalizedQuery.value,
    ),
);

const optionId = (index: number): string => `${listboxId}-option-${index}`;

watch(normalizedQuery, () => {
    activeIndex.value = -1;
});

function open(): void {
    isOpen.value = true;
}

function close(): void {
    isOpen.value = false;
    activeIndex.value = -1;
}

function select(name: string): void {
    model.value = name;
    close();
}

async function moveActive(step: number): Promise<void> {
    if (!isOpen.value) {
        open();
    }

    const count = flatOptions.value.length;

    if (count === 0) {
        return;
    }

    activeIndex.value = (activeIndex.value + step + count) % count;

    await nextTick();
    listbox.value
        ?.querySelector(`#${CSS.escape(optionId(activeIndex.value))}`)
        ?.scrollIntoView({ block: 'nearest' });
}

function onEnter(event: KeyboardEvent): void {
    if (isOpen.value && activeIndex.value >= 0) {
        event.preventDefault();
        select(flatOptions.value[activeIndex.value]);
    } else if (isOpen.value) {
        event.preventDefault();
        close();
    }
}

const optionIndexes = computed(
    () => new Map(flatOptions.value.map((name, index) => [name, index])),
);

function indexOf(name: string): number {
    return optionIndexes.value.get(name) ?? -1;
}

/**
 * Split a name around the first typed word so the match can be emphasised.
 */
function highlight(name: string): [string, string, string] {
    const word = normalizedQuery.value.split(' ').find(Boolean);
    const start = word ? name.toLowerCase().indexOf(word) : -1;

    if (!word || start === -1) {
        return [name, '', ''];
    }

    return [
        name.slice(0, start),
        name.slice(start, start + word.length),
        name.slice(start + word.length),
    ];
}
</script>

<template>
    <div class="relative">
        <Input
            :id="id"
            v-model="model"
            role="combobox"
            autocomplete="off"
            aria-autocomplete="list"
            :aria-expanded="isOpen"
            :aria-controls="listboxId"
            :aria-activedescendant="
                activeIndex >= 0 ? optionId(activeIndex) : undefined
            "
            :required="required"
            :placeholder="placeholder"
            @focus="open"
            @click="open"
            @input="open"
            @blur="close"
            @keydown.down.prevent="moveActive(1)"
            @keydown.up.prevent="moveActive(-1)"
            @keydown.enter="onEnter"
            @keydown.esc="close"
            @keydown.tab="close"
        />

        <div
            v-if="isOpen"
            :id="listboxId"
            ref="listbox"
            role="listbox"
            class="absolute inset-x-0 top-full z-50 mt-2 max-h-72 overflow-y-auto rounded-xl border bg-popover p-1 text-popover-foreground shadow-xl"
            @mousedown.prevent
        >
            <div
                v-if="flatOptions.length === 0"
                class="flex items-center gap-2 px-3 py-3 text-sm text-muted-foreground"
            >
                <Search class="size-4 shrink-0" />
                <span v-if="normalizedQuery">
                    No match — “{{ model.trim() }}” will be saved as a new
                    exercise.
                </span>
                <span v-else>Start typing to search exercises.</span>
            </div>

            <template v-for="group in filteredGroups" :key="group.category">
                <div
                    v-if="group.category"
                    role="presentation"
                    class="sticky top-0 z-10 bg-popover px-3 pt-2.5 pb-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    {{ group.category }}
                </div>
                <div v-else role="separator" class="mx-2 my-1.5 border-t" />
                <div
                    v-for="name in group.names"
                    :id="optionId(indexOf(name))"
                    :key="`${group.category}-${name}`"
                    role="option"
                    :aria-selected="indexOf(name) === activeIndex"
                    class="cursor-pointer rounded-lg px-3 py-2 text-sm"
                    :class="
                        indexOf(name) === activeIndex
                            ? 'bg-primary text-primary-foreground'
                            : 'hover:bg-accent'
                    "
                    @mousemove="activeIndex = indexOf(name)"
                    @click="select(name)"
                >
                    <template v-for="(part, i) in highlight(name)" :key="i">
                        <strong v-if="i === 1" class="font-bold">{{
                            part
                        }}</strong>
                        <template v-else>{{ part }}</template>
                    </template>
                </div>
            </template>

            <div
                v-if="
                    flatOptions.length > 0 && normalizedQuery && !hasExactMatch
                "
                class="mt-1 border-t px-3 pt-2 pb-1.5 text-xs text-muted-foreground"
            >
                Press Enter to keep “{{ model.trim() }}” as a new exercise.
            </div>
        </div>
    </div>
</template>
