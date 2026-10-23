<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\WorkoutEntry;
use App\Models\WorkoutSet;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MemberProgressController extends Controller
{
    /**
     * Show a member's session-by-session progress for one exercise.
     */
    public function show(Request $request, Member $member): Response
    {
        Gate::authorize('view', $member);

        $exerciseSummaries = $member->exerciseSummaries();
        $exerciseIds = array_column($exerciseSummaries, 'exercise_id');

        $selectedExerciseId = in_array($request->integer('exercise'), $exerciseIds, true)
            ? $request->integer('exercise')
            : collect($exerciseSummaries)->sortByDesc('sessions')->first()['exercise_id'] ?? null;

        return Inertia::render('members/Progress', [
            'member' => $member->only('id', 'name'),
            'exerciseSummaries' => $exerciseSummaries,
            'selectedExerciseId' => $selectedExerciseId,
            'sessions' => $selectedExerciseId ? $this->sessions($member, $selectedExerciseId) : [],
        ]);
    }

    /**
     * Aggregate the member's entries for an exercise into one data point per workout, oldest first.
     *
     * Estimated one-rep max is the best set by the Epley formula: weight × (1 + reps / 30).
     *
     * @return array<int, array{workout_id: int, performed_on: string, sets: array<int, array{reps: int, weight_kg: string}>, top_weight_kg: float, total_reps: int, volume_kg: float, estimated_one_rep_max_kg: float}>
     */
    private function sessions(Member $member, int $exerciseId): array
    {
        return $member->workoutEntries()
            ->select(['workout_entries.*', 'workouts.performed_on'])
            ->with('sets')
            ->where('workout_entries.exercise_id', $exerciseId)
            ->orderBy('workouts.performed_on')
            ->orderBy('workouts.id')
            ->orderBy('workout_entries.position')
            ->get()
            ->groupBy('workout_id')
            ->map(function (Collection $entries, int $workoutId): array {
                $sets = $entries->flatMap(fn (WorkoutEntry $entry) => $entry->sets);

                return [
                    'workout_id' => $workoutId,
                    'performed_on' => Carbon::parse($entries->first()->performed_on)->toDateString(),
                    'sets' => Member::setSummaries($entries),
                    'top_weight_kg' => (float) $sets->max(fn (WorkoutSet $set): float => (float) $set->weight_kg),
                    'total_reps' => (int) $sets->sum(fn (WorkoutSet $set): int => $set->reps),
                    'volume_kg' => round($sets->sum(fn (WorkoutSet $set): float => $set->reps * (float) $set->weight_kg), 1),
                    'estimated_one_rep_max_kg' => round((float) $sets->max(fn (WorkoutSet $set): float => $set->estimatedOneRepMax()), 1),
                ];
            })
            ->values()
            ->all();
    }
}
