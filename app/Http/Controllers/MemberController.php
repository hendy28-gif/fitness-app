<?php

namespace App\Http\Controllers;

use App\Http\Requests\MemberRequest;
use App\Models\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    /**
     * Show the trainer's members, optionally filtered by a search term.
     */
    public function index(Request $request): Response
    {
        $search = trim($request->string('search')->toString());

        $members = $request->user()->members()
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->withCount('workouts')
            ->withMax('workouts as last_workout_on', 'performed_on')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('members/Index', [
            'members' => $members,
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Show the form for creating a member.
     */
    public function create(): Response
    {
        return Inertia::render('members/Create');
    }

    /**
     * Store a new member.
     */
    public function store(MemberRequest $request): RedirectResponse
    {
        $member = $request->user()->members()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member added.')]);

        return to_route('members.show', $member);
    }

    /**
     * Show a member's profile, exercise summary and workout history.
     */
    public function show(Request $request, Member $member): Response
    {
        Gate::authorize('view', $member);

        $exerciseId = $request->integer('exercise') ?: null;

        $workouts = $member->workouts()
            ->when($exerciseId, fn (Builder $query) => $query->whereRelation('entries', 'exercise_id', $exerciseId))
            ->with(['entries.exercise:id,name', 'entries.sets'])
            ->orderByDesc('performed_on')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('members/Show', [
            'member' => $member,
            'exerciseSummaries' => $member->exerciseSummaries(),
            'workouts' => $workouts,
            'filters' => ['exercise' => $exerciseId],
        ]);
    }

    /**
     * Show the form for editing a member.
     */
    public function edit(Member $member): Response
    {
        Gate::authorize('update', $member);

        return Inertia::render('members/Edit', [
            'member' => $member,
        ]);
    }

    /**
     * Update a member's details.
     */
    public function update(MemberRequest $request, Member $member): RedirectResponse
    {
        Gate::authorize('update', $member);

        $member->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member updated.')]);

        return to_route('members.show', $member);
    }

    /**
     * Delete a member together with their workout history.
     */
    public function destroy(Member $member): RedirectResponse
    {
        Gate::authorize('delete', $member);

        $member->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member deleted.')]);

        return to_route('members.index');
    }
}
