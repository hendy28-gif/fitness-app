<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExerciseRequest;
use App\Models\Exercise;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ExerciseController extends Controller
{
    /**
     * Show the trainer's exercise library, with how often each exercise has been logged.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('exercises/Index', [
            'exercises' => $request->user()->exercises()
                ->withCount('workoutEntries')
                ->orderBy('name')
                ->get(['id', 'name', 'category']),
            'categories' => $request->user()->categories(),
        ]);
    }

    /**
     * Add an exercise to the trainer's library.
     */
    public function store(ExerciseRequest $request): RedirectResponse
    {
        $request->user()->exercises()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Exercise added.')]);

        return to_route('exercises.index');
    }

    /**
     * Rename or recategorise an exercise. Past workouts show the new name, since they refer to the same exercise.
     */
    public function update(ExerciseRequest $request, Exercise $exercise): RedirectResponse
    {
        Gate::authorize('update', $exercise);

        $exercise->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Exercise updated.')]);

        return to_route('exercises.index');
    }

    /**
     * Delete an exercise, unless it has been logged in a workout: deleting it would erase that history.
     */
    public function destroy(Exercise $exercise): RedirectResponse
    {
        Gate::authorize('delete', $exercise);

        if ($exercise->workoutEntries()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This exercise is used in workouts, so it can’t be deleted. Rename it instead.')]);

            return to_route('exercises.index');
        }

        $exercise->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Exercise deleted.')]);

        return to_route('exercises.index');
    }
}
