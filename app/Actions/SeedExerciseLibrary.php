<?php

namespace App\Actions;

use App\Models\Exercise;
use App\Models\User;

class SeedExerciseLibrary
{
    /**
     * Give a trainer their own copy of the default exercise library from config/exercises.php.
     *
     * Exercises the trainer already has (matched case-insensitively) are kept, and get the
     * library's category when they don't have one yet.
     */
    public function handle(User $user): void
    {
        /** @var array<string, array<int, string>> $library */
        $library = config('exercises');

        $existing = $user->exercises()->get()->keyBy(fn (Exercise $exercise): string => mb_strtolower($exercise->name));

        foreach ($library as $category => $names) {
            foreach ($names as $name) {
                $exercise = $existing->get(mb_strtolower($name));

                if ($exercise === null) {
                    $user->exercises()->create(['name' => $name, 'category' => $category ?: null]);
                } elseif ($exercise->category === null && $category !== '') {
                    $exercise->update(['category' => $category]);
                }
            }
        }
    }
}
