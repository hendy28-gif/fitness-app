<script setup lang="ts">
import { useElementSize } from '@vueuse/core';
import { computed, ref, useTemplateRef } from 'vue';
import { formatShortDate, parseDate } from '@/lib/fitness';

export type ChartPoint = {
    date: string;
    value: number;
    detail?: string;
};

const props = defineProps<{
    points: ChartPoint[];
    label: string;
    formatValue: (value: number) => string;
}>();

const container = useTemplateRef<HTMLDivElement>('container');
const { width } = useElementSize(container);

const height = 280;
const margin = { top: 16, right: 20, bottom: 32, left: 56 };

const plotWidth = computed(() =>
    Math.max(width.value - margin.left - margin.right, 0),
);
const plotHeight = height - margin.top - margin.bottom;

function niceStep(range: number, targetTicks: number): number {
    const rough = range / targetTicks;
    const magnitude = 10 ** Math.floor(Math.log10(rough));
    const residual = rough / magnitude;
    const nice = residual >= 5 ? 10 : residual >= 2 ? 5 : residual >= 1 ? 2 : 1;

    return nice * magnitude;
}

const yScale = computed(() => {
    const values = props.points.map((point) => point.value);
    let min = Math.min(...values);
    let max = Math.max(...values);

    if (min === max) {
        min = Math.max(0, min - Math.max(1, min * 0.1));
        max = max + Math.max(1, max * 0.1);
    }

    const step = niceStep(max - min, 4);
    const lower = Math.max(0, Math.floor(min / step) * step);
    const upper = Math.ceil(max / step) * step;
    const ticks: number[] = [];

    for (let tick = lower; tick <= upper + step / 2; tick += step) {
        ticks.push(Number(tick.toFixed(6)));
    }

    return {
        ticks,
        y: (value: number) =>
            margin.top +
            plotHeight -
            ((value - lower) / (upper - lower || 1)) * plotHeight,
    };
});

const xScale = computed(() => {
    const times = props.points.map((point) => parseDate(point.date).getTime());
    const first = Math.min(...times);
    const last = Math.max(...times);

    return (date: string) =>
        last === first
            ? margin.left + plotWidth.value / 2
            : margin.left +
              ((parseDate(date).getTime() - first) / (last - first)) *
                  plotWidth.value;
});

const positioned = computed(() =>
    props.points.map((point) => ({
        ...point,
        x: xScale.value(point.date),
        y: yScale.value.y(point.value),
    })),
);

const linePath = computed(() =>
    positioned.value
        .map((point, i) => `${i === 0 ? 'M' : 'L'}${point.x},${point.y}`)
        .join(' '),
);

const areaPath = computed(() => {
    if (positioned.value.length < 2) {
        return '';
    }

    const baseline = margin.top + plotHeight;
    const first = positioned.value[0];
    const last = positioned.value[positioned.value.length - 1];

    return `${linePath.value} L${last.x},${baseline} L${first.x},${baseline} Z`;
});

/**
 * Show at most ~6 date labels so they never collide; always include the first and last session.
 */
const xLabels = computed(() => {
    const count = positioned.value.length;
    const maxLabels = Math.max(2, Math.floor(plotWidth.value / 90));
    const every = Math.max(1, Math.ceil(count / maxLabels));

    return positioned.value.filter(
        (_, i) =>
            i === 0 ||
            i === count - 1 ||
            (i % every === 0 && count - 1 - i >= every / 2),
    );
});

const hoverIndex = ref<number | null>(null);
const hovered = computed(() =>
    hoverIndex.value === null ? null : positioned.value[hoverIndex.value],
);

function onPointerMove(event: PointerEvent): void {
    const bounds = (event.currentTarget as SVGElement).getBoundingClientRect();
    const x = event.clientX - bounds.left;
    let nearest = 0;

    positioned.value.forEach((point, i) => {
        if (Math.abs(point.x - x) < Math.abs(positioned.value[nearest].x - x)) {
            nearest = i;
        }
    });

    hoverIndex.value = nearest;
}

const tooltipStyle = computed(() => {
    if (!hovered.value) {
        return {};
    }

    const flip = hovered.value.x > width.value / 2;

    return {
        top: `${Math.max(hovered.value.y - 12, 0)}px`,
        left: flip ? 'auto' : `${hovered.value.x + 14}px`,
        right: flip ? `${width.value - hovered.value.x + 14}px` : 'auto',
    };
});
</script>

<template>
    <div ref="container" class="progress-chart relative w-full">
        <svg
            v-if="width > 0 && points.length"
            :width="width"
            :height="height"
            role="img"
            :aria-label="`${label} per session`"
            class="block touch-none select-none"
            @pointermove="onPointerMove"
            @pointerleave="hoverIndex = null"
        >
            <g>
                <template v-for="tick in yScale.ticks" :key="tick">
                    <line
                        :x1="margin.left"
                        :x2="margin.left + plotWidth"
                        :y1="yScale.y(tick)"
                        :y2="yScale.y(tick)"
                        class="grid-line"
                    />
                    <text
                        :x="margin.left - 8"
                        :y="yScale.y(tick)"
                        text-anchor="end"
                        dominant-baseline="middle"
                        class="axis-text"
                    >
                        {{ formatValue(tick) }}
                    </text>
                </template>
            </g>

            <line
                :x1="margin.left"
                :x2="margin.left + plotWidth"
                :y1="margin.top + plotHeight"
                :y2="margin.top + plotHeight"
                class="baseline"
            />

            <text
                v-for="point in xLabels"
                :key="point.date"
                :x="point.x"
                :y="height - 10"
                text-anchor="middle"
                class="axis-text"
            >
                {{ formatShortDate(point.date) }}
            </text>

            <path v-if="areaPath" :d="areaPath" class="area" />
            <path :d="linePath" class="line" />

            <line
                v-if="hovered"
                :x1="hovered.x"
                :x2="hovered.x"
                :y1="margin.top"
                :y2="margin.top + plotHeight"
                class="crosshair"
            />

            <circle
                v-for="(point, i) in positioned"
                :key="point.date + i"
                :cx="point.x"
                :cy="point.y"
                :r="hoverIndex === i ? 6 : 4"
                class="marker"
            />

            <rect
                :x="margin.left - 12"
                :y="0"
                :width="plotWidth + 24"
                :height="height"
                fill="transparent"
            />
        </svg>

        <div
            v-if="hovered"
            class="tooltip pointer-events-none absolute z-10 rounded-md border bg-popover px-3 py-2 text-sm shadow-md"
            :style="tooltipStyle"
        >
            <div class="text-xs text-muted-foreground">
                {{ formatShortDate(hovered.date) }}
                {{ parseDate(hovered.date).getFullYear() }}
            </div>
            <div class="flex items-center gap-2 font-medium">
                <span class="swatch" aria-hidden="true" />
                {{ label }}: {{ formatValue(hovered.value) }}
            </div>
            <div
                v-if="hovered.detail"
                class="text-xs whitespace-nowrap text-muted-foreground"
            >
                {{ hovered.detail }}
            </div>
        </div>
    </div>
</template>

<style scoped>
.progress-chart {
    --series: #2a78d6;
    --grid: #e1e0d9;
    --axis: #c3c2b7;
    --muted-ink: #898781;
    --surface: var(--card);
}

.dark .progress-chart {
    --series: #3987e5;
    --grid: #2c2c2a;
    --axis: #383835;
}

.grid-line {
    stroke: var(--grid);
    stroke-width: 1;
}

.baseline {
    stroke: var(--axis);
    stroke-width: 1;
}

.axis-text {
    fill: var(--muted-ink);
    font-size: 11px;
    font-variant-numeric: tabular-nums;
}

.line {
    fill: none;
    stroke: var(--series);
    stroke-width: 2;
    stroke-linejoin: round;
    stroke-linecap: round;
}

.area {
    fill: var(--series);
    opacity: 0.1;
}

.marker {
    fill: var(--series);
    stroke: var(--surface);
    stroke-width: 2;
    transition: r 120ms ease;
}

.crosshair {
    stroke: var(--axis);
    stroke-width: 1;
}

.swatch {
    display: inline-block;
    width: 10px;
    height: 2px;
    border-radius: 1px;
    background: var(--series);
}
</style>
