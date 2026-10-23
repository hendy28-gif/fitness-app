<?php

namespace App\Models;

use Database\Factories\WorkoutEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workout_id
 * @property int $exercise_id
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string|null $performed_on Only present when the query joins and selects workouts.performed_on.
 */
#[Fillable(['exercise_id', 'position'])]
class WorkoutEntry extends Model
{
    /** @use HasFactory<WorkoutEntryFactory> */
    use HasFactory;

    /**
     * Get the workout the entry belongs to.
     *
     * @return BelongsTo<Workout, $this>
     */
    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    /**
     * Get the exercise that was performed.
     *
     * @return BelongsTo<Exercise, $this>
     */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    /**
     * Get the entry's sets, in the order they were performed.
     *
     * @return HasMany<WorkoutSet, $this>
     */
    public function sets(): HasMany
    {
        return $this->hasMany(WorkoutSet::class)->orderBy('position');
    }
}
