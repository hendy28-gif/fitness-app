<?php

use App\Models\Member;
use App\Models\User;
use App\Models\Workout;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard only counts the trainer\'s own members and workouts', function () {
    $user = User::factory()->create();
    $member = Member::factory()->for($user)->create();
    Member::factory()->for($user)->create();
    Workout::factory()->count(2)->for($member)->create(['performed_on' => now()->toDateString()]);
    Workout::factory()->create(['performed_on' => now()->toDateString()]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.members', 2)
            ->where('stats.membersTrainedThisWeek', 1)
            ->where('stats.workoutsThisWeek', 2)
            ->has('recentWorkouts', 2)
            ->where('recentWorkouts.0.member.name', $member->name));
});
