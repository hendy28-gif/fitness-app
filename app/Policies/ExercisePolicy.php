<?php

namespace App\Policies;

use App\Models\Exercise;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ExercisePolicy
{
    /**
     * Determine whether the user can rename or recategorise the exercise.
     */
    public function update(User $user, Exercise $exercise): Response
    {
        return $this->ownsExercise($user, $exercise);
    }

    /**
     * Determine whether the user can delete the exercise.
     */
    public function delete(User $user, Exercise $exercise): Response
    {
        return $this->ownsExercise($user, $exercise);
    }

    /**
     * Allow only the trainer who owns the exercise, hiding its existence from anyone else.
     */
    private function ownsExercise(User $user, Exercise $exercise): Response
    {
        return $user->id === $exercise->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
