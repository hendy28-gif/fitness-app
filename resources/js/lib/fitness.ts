import type { SetSummary } from '@/types';

const dateFormatter = new Intl.DateTimeFormat(undefined, {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

const shortDateFormatter = new Intl.DateTimeFormat(undefined, {
    day: 'numeric',
    month: 'short',
});

/**
 * Parse a `YYYY-MM-DD` string as a local calendar date (not UTC midnight).
 */
export function parseDate(value: string): Date {
    const [year, month, day] = value.slice(0, 10).split('-').map(Number);

    return new Date(year, month - 1, day);
}

export function formatDate(value: string | null): string {
    return value ? dateFormatter.format(parseDate(value)) : '—';
}

export function formatShortDate(value: string): string {
    return shortDateFormatter.format(parseDate(value));
}

export function daysAgo(value: string): string {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const days = Math.round(
        (today.getTime() - parseDate(value).getTime()) / 86_400_000,
    );

    if (days <= 0) {
        return 'today';
    }

    return days === 1 ? 'yesterday' : `${days} days ago`;
}

export function formatWeight(value: string | number): string {
    const weight = Number(value);

    return `${Number.isInteger(weight) ? weight : weight.toFixed(1)} kg`;
}

export function formatNumber(value: number): string {
    return new Intl.NumberFormat(undefined, {
        maximumFractionDigits: 1,
    }).format(value);
}

/**
 * Collapse consecutive identical sets and describe each run the way trainers write it,
 * e.g. ["3 × 10 @ 60 kg", "1 × 6 @ 70 kg"].
 */
export function describeSets(sets: SetSummary[]): string[] {
    const runs: { count: number; reps: number; weight: number }[] = [];

    for (const set of sets) {
        const weight = Number(set.weight_kg);
        const last = runs[runs.length - 1];

        if (last && last.reps === set.reps && last.weight === weight) {
            last.count++;
        } else {
            runs.push({ count: 1, reps: set.reps, weight });
        }
    }

    return runs.map(({ count, reps, weight }) =>
        weight > 0
            ? `${count} × ${reps} @ ${formatWeight(weight)}`
            : `${count} × ${reps} (bodyweight)`,
    );
}

export function todayDate(): string {
    const today = new Date();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');

    return `${today.getFullYear()}-${month}-${day}`;
}
