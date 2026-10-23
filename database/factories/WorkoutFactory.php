<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workout>
 */
class WorkoutFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'performed_on' => fake()->dateTimeBetween('-3 months')->format('Y-m-d'),
            'notes' => null,
        ];
    }
}
