<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { ChartLine, TrendingDown, TrendingUp } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import ProgressChart from '@/components/ProgressChart.vue';
import type { ChartPoint } from '@/components/ProgressChart.vue';
import { describeSets, formatDate, formatNumber } from '@/lib/fitness';
import { index, progress, show } from '@/routes/members';
import type { ExerciseSummary, Member, ProgressSession } from '@/types';

type MetricKey =
    | 'top_weight_kg'
    | 'estimated_one_rep_max_kg'
    | 'volume_kg'
    | 'total_reps';

const props = defineProps<{
    member: Pick<Member, 'id' | 'name'>;
    exerciseSummaries: ExerciseSummary[];
    selectedExerciseId: number | null;
    sessions: ProgressSession[];
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Members', href: index() },
        { title: props.member.name, href: show(props.member.id) },
        { title: 'Progress', href: progress(props.member.id) },
    ],
});

const metrics: {
    key: MetricKey;
    label: string;
    description: string;
    unit: string;
}[] = [
    {
        key: 'top_weight_kg',
        label: 'Top weight',
        description: 'Heaviest weight lifted in the session',
        unit: 'kg',
    },
    {
        key: 'estimated_one_rep_max_kg',
        label: 'Est. 1RM',
        description:
            'Estimated one-rep max (Epley: weight × (1 + reps ÷ 30)) — compares sessions with different rep ranges',
        unit: 'kg',
    },
    {
        key: 'volume_kg',
        label: 'Volume',
        description: 'Total load moved: reps × weight, added up over every set',
        unit: 'kg',
    },
    {
        key: 'total_reps',
        label: 'Total reps',
        description:
            'Reps added up over every set — best for bodyweight exercises',
        unit: 'reps',
    },
];

const isBodyweight = computed(() =>
    props.sessions.every((session) => session.top_weight_kg === 0),
);

const selectedMetricKey = ref<MetricKey>(
    isBodyweight.value ? 'total_reps' : 'top_weight_kg',
);

watch(isBodyweight, (bodyweight) => {
    selectedMetricKey.value = bodyweight ? 'total_reps' : 'top_weight_kg';
});

const availableMetrics = computed(() =>
    isBodyweight.value
        ? metrics.filter((metric) => metric.key === 'total_reps')
        : metrics,
);

const metric = computed(() =>
    metrics.find((item) => item.key === selectedMetricKey.value)!,
);

function formatMetric(value: number): string {
    return `${formatNumber(value)} ${metric.value.unit}`;
}

const points = computed<ChartPoint[]>(() =>
    props.sessions.map((session) => ({
        date: session.performed_on,
        value: session[selectedMetricKey.value],
        detail: describeSets(session.sets).join(', '),
    })),
);

const stats = computed(() => {
    if (props.sessions.length === 0) {
        return null;
    }

    const values = points.value.map((point) => point.value);
    const first = values[0];
    const latest = values[values.length - 1];

    return {
        first,
        latest,
        best: Math.max(...values),
        change: latest - first,
        changePercent: first > 0 ? ((latest - first) / first) * 100 : null,
    };
});

const selectedExercise = ref(props.selectedExerciseId ?? '');

function selectExercise(): void {
    router.get(
        progress.url(props.member.id, {
            query: { exercise: selectedExercise.value || undefined },
        }),
        {},
        { preserveScroll: true },
    );
}

const sessionsNewestFirst = computed(() => [...props.sessions].reverse());
</script>

<template>
    <Head :title="`Progress · ${member.name}`" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            title="Progress"
            :description="`How ${member.name} is progressing, session by session`"
        />

        <div
            v-if="exerciseSummaries.length === 0"
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed p-12 text-center"
        >
            <ChartLine class="size-8 text-muted-foreground" />
            <p class="text-sm text-muted-foreground">
                Log a workout to start tracking progress.
            </p>
        </div>

        <template v-else>
            <div class="flex flex-wrap items-center gap-3">
                <label class="flex items-center gap-2 text-sm">
                    <span class="text-muted-foreground">Exercise</span>
                    <select
                        v-model="selectedExercise"
                        class="h-10 field"
                        @change="selectExercise"
                    >
                        <option
                            v-for="summary in exerciseSummaries"
                            :key="summary.exercise_id"
                            :value="summary.exercise_id"
                        >
                            {{ summary.exercise }} ({{ summary.sessions }})
                        </option>
                    </select>
                </label>

                <div
                    class="inline-flex rounded-lg bg-muted p-1"
                    role="tablist"
                    aria-label="Metric"
                >
                    <button
                        v-for="item in availableMetrics"
                        :key="item.key"
                        type="button"
                        role="tab"
                        :aria-selected="selectedMetricKey === item.key"
                        class="rounded-md px-3 py-1 text-sm transition-colors"
                        :class="
                            selectedMetricKey === item.key
                                ? 'bg-background font-medium shadow-xs'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        :title="item.description"
                        @click="selectedMetricKey = item.key"
                    >
                        {{ item.label }}
                    </button>
                </div>
            </div>

            <div v-if="stats" class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div class="surface p-4">
                    <div class="text-sm text-muted-foreground">Latest</div>
                    <div class="text-2xl font-semibold">
                        {{ formatMetric(stats.latest) }}
                    </div>
                </div>
                <div class="surface p-4">
                    <div class="text-sm text-muted-foreground">
                        Change since first session
                    </div>
                    <div
                        class="flex items-center gap-1.5 text-2xl font-semibold"
                    >
                        <TrendingUp
                            v-if="stats.change > 0"
                            class="size-5 text-green-700 dark:text-green-500"
                            aria-label="Up"
                        />
                        <TrendingDown
                            v-else-if="stats.change < 0"
                            class="size-5 text-red-600 dark:text-red-400"
                            aria-label="Down"
                        />
                        {{ stats.change > 0 ? '+' : ''
                        }}{{ formatMetric(stats.change) }}
                    </div>
                    <div
                        v-if="stats.changePercent !== null"
                        class="text-xs text-muted-foreground"
                    >
                        {{ stats.changePercent > 0 ? '+' : ''
                        }}{{ formatNumber(stats.changePercent) }}% from
                        {{ formatMetric(stats.first) }}
                    </div>
                </div>
                <div class="surface p-4">
                    <div class="text-sm text-muted-foreground">Best</div>
                    <div class="text-2xl font-semibold">
                        {{ formatMetric(stats.best) }}
                    </div>
                </div>
                <div class="surface p-4">
                    <div class="text-sm text-muted-foreground">Sessions</div>
                    <div class="text-2xl font-semibold">
                        {{ sessions.length }}
                    </div>
                </div>
            </div>

            <section class="surface p-4">
                <h2 class="font-medium">{{ metric.label }} per session</h2>
                <p class="mb-4 text-xs text-muted-foreground">
                    {{ metric.description }}
                </p>
                <ProgressChart
                    :points="points"
                    :label="metric.label"
                    :format-value="formatMetric"
                />
            </section>

            <section class="overflow-hidden surface">
                <h2 class="border-b px-4 py-3 font-medium">Session log</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-muted-foreground">
                            <tr class="border-b">
                                <th class="px-4 py-2 font-medium">Date</th>
                                <th class="px-3 py-2 font-medium">Sets</th>
                                <th
                                    v-if="!isBodyweight"
                                    class="px-3 py-2 text-right font-medium"
                                >
                                    Top weight
                                </th>
                                <th
                                    v-if="!isBodyweight"
                                    class="px-3 py-2 text-right font-medium"
                                >
                                    Est. 1RM
                                </th>
                                <th
                                    v-if="!isBodyweight"
                                    class="px-3 py-2 text-right font-medium"
                                >
                                    Volume
                                </th>
                                <th class="px-4 py-2 text-right font-medium">
                                    Total reps
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="session in sessionsNewestFirst"
                                :key="session.workout_id"
                            >
                                <td class="px-4 py-2 whitespace-nowrap">
                                    {{ formatDate(session.performed_on) }}
                                </td>
                                <td class="px-3 py-2 tabular-nums">
                                    <div
                                        v-for="(line, i) in describeSets(
                                            session.sets,
                                        )"
                                        :key="i"
                                        class="whitespace-nowrap"
                                    >
                                        {{ line }}
                                    </div>
                                </td>
                                <td
                                    v-if="!isBodyweight"
                                    class="px-3 py-2 text-right tabular-nums"
                                >
                                    {{ formatNumber(session.top_weight_kg) }} kg
                                </td>
                                <td
                                    v-if="!isBodyweight"
                                    class="px-3 py-2 text-right tabular-nums"
                                >
                                    {{
                                        formatNumber(
                                            session.estimated_one_rep_max_kg,
                                        )
                                    }}
                                    kg
                                </td>
                                <td
                                    v-if="!isBodyweight"
                                    class="px-3 py-2 text-right tabular-nums"
                                >
                                    {{ formatNumber(session.volume_kg) }} kg
                                </td>
                                <td class="px-4 py-2 text-right tabular-nums">
                                    {{ session.total_reps }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </template>
    </div>
</template>
