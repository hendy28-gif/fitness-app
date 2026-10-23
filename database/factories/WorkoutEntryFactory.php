<?php

namespace Database\Factories;

use App\Models\Exercise;
use App\Models\Workout;
use App\Models\WorkoutEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkoutEntry>
 */
class WorkoutEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workout_id' => Workout::factory(),
            'exercise_id' => Exercise::factory(),
            'position' => 0,
        ];
    }

    /**
     * Give the entry sets with the given reps and weight, in order.
     *
     * @param  array<int, array{reps: int, weight_kg: float|int}>  $sets
     */
    public function withSets(array $sets): static
    {
        return $this->afterCreating(function (WorkoutEntry $entry) use ($sets) {
            foreach ($sets as $position => $set) {
                $entry->sets()->create([...$set, 'position' => $position]);
            }
        });
    }
}
