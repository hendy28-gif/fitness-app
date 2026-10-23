<?php

namespace Database\Factories;

use App\Models\WorkoutEntry;
use App\Models\WorkoutSet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkoutSet>
 */
class WorkoutSetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workout_entry_id' => WorkoutEntry::factory(),
            'position' => 0,
            'reps' => fake()->numberBetween(5, 15),
            'weight_kg' => fake()->numberBetween(4, 40) * 2.5,
        ];
    }
}
