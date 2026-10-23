<?php

namespace App\Models;

use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property Carbon|null $date_of_birth
 * @property string|null $gender
 * @property string|null $height_cm
 * @property string|null $weight_kg
 * @property string|null $goals
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'phone', 'date_of_birth', 'gender', 'height_cm', 'weight_kg', 'goals', 'notes'])]
class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date:Y-m-d',
            'height_cm' => 'decimal:1',
            'weight_kg' => 'decimal:1',
        ];
    }

    /**
     * Get the trainer who owns the member.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the member's workouts.
     *
     * @return HasMany<Workout, $this>
     */
    public function workouts(): HasMany
    {
        return $this->hasMany(Workout::class);
    }

    /**
     * Get every exercise entry the member has logged.
     *
     * @return HasManyThrough<WorkoutEntry, Workout, $this>
     */
    public function workoutEntries(): HasManyThrough
    {
        return $this->hasManyThrough(WorkoutEntry::class, Workout::class);
    }

    /**
     * Summarise every exercise the member has performed: the sets they did last time and their best weight.
     *
     * @return array<int, array{exercise_id: int, exercise: string, sessions: int, last_performed_on: string, last_sets: array<int, array{reps: int, weight_kg: string}>, best_weight_kg: string}>
     */
    public function exerciseSummaries(): array
    {
        $entries = $this->workoutEntries()
            ->select(['workout_entries.*', 'workouts.performed_on'])
            ->with(['exercise:id,name', 'sets'])
            ->orderByDesc('workouts.performed_on')
            ->orderByDesc('workouts.id')
            ->orderBy('workout_entries.position')
            ->get();

        return $entries
            ->groupBy('exercise_id')
            ->map(function (Collection $exerciseEntries): array {
                $latestWorkoutId = $exerciseEntries->first()->workout_id;
                $allSets = $exerciseEntries->flatMap(fn (WorkoutEntry $entry) => $entry->sets);

                return [
                    'exercise_id' => $exerciseEntries->first()->exercise_id,
                    'exercise' => $exerciseEntries->first()->exercise->name,
                    'sessions' => $exerciseEntries->unique('workout_id')->count(),
                    'last_performed_on' => Carbon::parse($exerciseEntries->first()->performed_on)->toDateString(),
                    'last_sets' => self::setSummaries($exerciseEntries->where('workout_id', $latestWorkoutId)),
                    'best_weight_kg' => $allSets->sortByDesc(fn (WorkoutSet $set): float => (float) $set->weight_kg)->first()->weight_kg ?? '0.00',
                ];
            })
            ->sortBy('exercise', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * Flatten the sets of the given entries, in order, into the shape sent to the frontend.
     *
     * @param  Collection<int, WorkoutEntry>  $entries
     * @return array<int, array{reps: int, weight_kg: string}>
     */
    public static function setSummaries(Collection $entries): array
    {
        return $entries
            ->flatMap(fn (WorkoutEntry $entry) => $entry->sets)
            ->map(fn (WorkoutSet $set): array => [
                'reps' => $set->reps,
                'weight_kg' => $set->weight_kg,
            ])
            ->values()
            ->all();
    }
}
