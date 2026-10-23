<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    /**
     * Seed demo members for every trainer, each with ten weeks of progressively heavier workouts.
     */
    public function run(): void
    {
        $program = [
            ['exercise' => 'Squat', 'sets' => 4, 'reps' => 6, 'start' => 60, 'increment' => 2.5],
            ['exercise' => 'Bench Press', 'sets' => 4, 'reps' => 8, 'start' => 40, 'increment' => 1.25],
            ['exercise' => 'Barbell Row', 'sets' => 3, 'reps' => 10, 'start' => 35, 'increment' => 1.25],
            ['exercise' => 'Push-Up', 'sets' => 3, 'reps' => 10, 'start' => 0, 'increment' => 0],
        ];

        User::all()->each(function (User $user) use ($program) {
            $exerciseIds = collect($program)->mapWithKeys(fn (array $step): array => [
                $step['exercise'] => $user->exercises()->firstOrCreate(['name' => $step['exercise']])->id,
            ]);

            Member::factory()->count(3)->for($user)->create()->each(function (Member $member) use ($program, $exerciseIds) {
                foreach (range(9, 0) as $weeksAgo) {
                    $week = 9 - $weeksAgo;

                    $workout = $member->workouts()->create([
                        'performed_on' => now()->subWeeks($weeksAgo)->startOfWeek()->addDays(fake()->numberBetween(0, 4))->toDateString(),
                        'notes' => fake()->optional(0.3)->randomElement(['Felt strong today', 'Low energy, kept it light', 'Great form on squats']),
                    ]);

                    foreach ($program as $position => $step) {
                        $entry = $workout->entries()->create([
                            'exercise_id' => $exerciseIds[$step['exercise']],
                            'position' => $position,
                        ]);

                        foreach (range(0, $step['sets'] - 1) as $setPosition) {
                            $isTopSet = $step['start'] > 0 && $setPosition === $step['sets'] - 1;

                            $entry->sets()->create([
                                'position' => $setPosition,
                                'reps' => $step['start'] > 0 ? $step['reps'] - ($isTopSet ? 2 : 0) : $step['reps'] + $week,
                                'weight_kg' => $step['start'] + $step['increment'] * $week + ($isTopSet ? 5 : 0),
                            ]);
                        }
                    }
                }
            });
        });
    }
}
