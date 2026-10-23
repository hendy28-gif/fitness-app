<?php

namespace App\Http\Controllers;

use App\Models\Workout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the trainer's overview: totals and the most recent workouts.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $trainerWorkouts = fn (): Builder => Workout::query()->whereRelation('member', 'user_id', $user->id);
        $startOfWeek = now()->startOfWeek()->toDateString();

        return Inertia::render('Dashboard', [
            'stats' => [
                'members' => $user->members()->count(),
                'membersTrainedThisWeek' => $user->members()
                    ->whereHas('workouts', fn (Builder $query) => $query->where('performed_on', '>=', $startOfWeek))
                    ->count(),
                'workoutsThisWeek' => $trainerWorkouts()->where('performed_on', '>=', $startOfWeek)->count(),
                'workoutsThisMonth' => $trainerWorkouts()->where('performed_on', '>=', now()->startOfMonth()->toDateString())->count(),
            ],
            'recentWorkouts' => $trainerWorkouts()
                ->with(['member:id,name', 'entries.exercise:id,name'])
                ->orderByDesc('performed_on')
                ->orderByDesc('id')
                ->limit(8)
                ->get(),
        ]);
    }
}
