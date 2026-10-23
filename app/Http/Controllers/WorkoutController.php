<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkoutRequest;
use App\Models\Exercise;
use App\Models\Member;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WorkoutController extends Controller
{
    /**
     * Show the form for logging a workout for a member.
     */
    public function create(Request $request, Member $member): Response
    {
        Gate::authorize('update', $member);

        $lastWorkout = $member->workouts()
            ->with(['entries.exercise:id,name', 'entries.sets'])
            ->orderByDesc('performed_on')
            ->orderByDesc('id')
            ->first();

        return Inertia::render('workouts/Create', [
            'member' => $member->only('id', 'name'),
            'exerciseOptions' => $this->exerciseOptions($request->user()),
            'exerciseSummaries' => $member->exerciseSummaries(),
            'lastWorkout' => $lastWorkout,
        ]);
    }

    /**
     * Store a new workout for a member.
     */
    public function store(WorkoutRequest $request, Member $member): RedirectResponse
    {
        Gate::authorize('update', $member);

        DB::transaction(function () use ($request, $member) {
            $workout = $member->workouts()->create($request->safe()->only('performed_on', 'notes'));

            $this->saveEntries($request->user(), $workout, $request->validated('entries'));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workout saved.')]);

        return to_route('members.show', $member);
    }

    /**
     * Show the form for editing a member's workout.
     */
    public function edit(Request $request, Member $member, Workout $workout): Response
    {
        Gate::authorize('update', $member);

        return Inertia::render('workouts/Edit', [
            'member' => $member->only('id', 'name'),
            'workout' => $workout->load(['entries.exercise:id,name', 'entries.sets']),
            'exerciseOptions' => $this->exerciseOptions($request->user()),
            'exerciseSummaries' => $member->exerciseSummaries(),
        ]);
    }

    /**
     * Update a member's workout, replacing its exercise entries.
     */
    public function update(WorkoutRequest $request, Member $member, Workout $workout): RedirectResponse
    {
        Gate::authorize('update', $member);

        DB::transaction(function () use ($request, $workout) {
            $workout->update($request->safe()->only('performed_on', 'notes'));

            $workout->entries()->delete();

            $this->saveEntries($request->user(), $workout, $request->validated('entries'));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workout updated.')]);

        return to_route('members.show', $member);
    }

    /**
     * Delete a member's workout.
     */
    public function destroy(Member $member, Workout $workout): RedirectResponse
    {
        Gate::authorize('update', $member);

        $workout->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workout deleted.')]);

        return to_route('members.show', $member);
    }

    /**
     * Get the autocomplete suggestions: the trainer's own exercises that aren't in the built-in library, then the library by category.
     *
     * @return array<int, array{category: string, names: array<int, string>}>
     */
    private function exerciseOptions(User $user): array
    {
        /** @var array<string, array<int, string>> $library */
        $library = config('exercises');

        $libraryNames = collect($library)->flatten()->map(fn (string $name): string => mb_strtolower($name))->flip();

        $customNames = $user->exercises()
            ->orderBy('name')
            ->get(['name'])
            ->map(fn (Exercise $exercise): string => $exercise->name)
            ->reject(fn (string $name): bool => $libraryNames->has(mb_strtolower($name)))
            ->values()
            ->all();

        return collect($library)
            ->map(fn (array $names, string $category): array => ['category' => $category, 'names' => $names])
            ->values()
            ->when($customNames !== [], fn ($options) => $options->prepend(['category' => 'Your exercises', 'names' => $customNames]))
            ->all();
    }

    /**
     * Create the workout's entries, matching each exercise name to the trainer's library case-insensitively.
     *
     * @param  list<array{exercise: string, sets: list<array{reps: int, weight_kg?: float|string|null}>}>  $entries
     */
    private function saveEntries(User $user, Workout $workout, array $entries): void
    {
        $exerciseIds = [];

        foreach ($entries as $position => $entry) {
            $name = preg_replace('/\s+/', ' ', trim($entry['exercise']));
            $key = mb_strtolower($name);

            $exerciseIds[$key] ??= $user->exercises()->whereRaw('lower(name) = ?', [$key])->value('id')
                ?? $user->exercises()->create(['name' => $name])->id;

            $workoutEntry = $workout->entries()->create([
                'exercise_id' => $exerciseIds[$key],
                'position' => $position,
            ]);

            $workoutEntry->sets()->createMany(array_map(fn (array $set, int $setPosition): array => [
                'position' => $setPosition,
                'reps' => $set['reps'],
                'weight_kg' => $set['weight_kg'] ?? 0,
            ], $entry['sets'], array_keys($entry['sets'])));
        }
    }
}
