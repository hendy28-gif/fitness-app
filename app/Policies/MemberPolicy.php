<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MemberPolicy
{
    /**
     * Determine whether the user can view the member and their workouts.
     */
    public function view(User $user, Member $member): Response
    {
        return $this->ownsMember($user, $member);
    }

    /**
     * Determine whether the user can update the member or manage their workouts.
     */
    public function update(User $user, Member $member): Response
    {
        return $this->ownsMember($user, $member);
    }

    /**
     * Determine whether the user can delete the member.
     */
    public function delete(User $user, Member $member): Response
    {
        return $this->ownsMember($user, $member);
    }

    /**
     * Allow only the trainer who owns the member, hiding the member's existence from anyone else.
     */
    private function ownsMember(User $user, Member $member): Response
    {
        return $user->id === $member->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
