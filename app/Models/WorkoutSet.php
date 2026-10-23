<?php

namespace App\Models;

use Database\Factories\WorkoutSetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workout_entry_id
 * @property int $position
 * @property int $reps
 * @property string $weight_kg
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['position', 'reps', 'weight_kg'])]
class WorkoutSet extends Model
{
    /** @use HasFactory<WorkoutSetFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reps' => 'integer',
            'weight_kg' => 'decimal:2',
        ];
    }

    /**
     * Get the exercise entry the set belongs to.
     *
     * @return BelongsTo<WorkoutEntry, $this>
     */
    public function workoutEntry(): BelongsTo
    {
        return $this->belongsTo(WorkoutEntry::class);
    }

    /**
     * Get the estimated one-rep max for this set using the Epley formula: weight × (1 + reps / 30).
     */
    public function estimatedOneRepMax(): float
    {
        return (float) $this->weight_kg * (1 + $this->reps / 30);
    }
}
