<?php

use App\Models\Exercise;
use App\Models\Member;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutEntry;
use Inertia\Testing\AssertableInertia as Assert;

test('progress is aggregated over every set of each session, oldest first', function () {
    $user = User::factory()->create();
    $member = Member::factory()->for($user)->create();
    $squat = Exercise::factory()->for($user)->create(['name' => 'Squat']);

    $second = Workout::factory()->for($member)->create(['performed_on' => '2026-10-08']);
    WorkoutEntry::factory()->for($second)->for($squat)->withSets([
        ['reps' => 5, 'weight_kg' => 100],
        ['reps' => 5, 'weight_kg' => 100],
        ['reps' => 3, 'weight_kg' => 105],
    ])->create(['position' => 0]);
    WorkoutEntry::factory()->for($second)->for($squat)->withSets([
        ['reps' => 1, 'weight_kg' => 110],
    ])->create(['position' => 1]);

    $first = Workout::factory()->for($member)->create(['performed_on' => '2026-10-01']);
    WorkoutEntry::factory()->for($first)->for($squat)->withSets([
        ['reps' => 10, 'weight_kg' => 60],
        ['reps' => 10, 'weight_kg' => 60],
        ['reps' => 8, 'weight_kg' => 65],
    ])->create();

    $this->actingAs($user)
        ->get(route('members.progress', ['member' => $member, 'exercise' => $squat->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('members/Progress')
            ->where('selectedExerciseId', $squat->id)
            ->has('sessions', 2)
            ->where('sessions.0.performed_on', '2026-10-01')
            ->where('sessions.0.top_weight_kg', 65)
            ->where('sessions.0.total_reps', 28)
            ->where('sessions.0.volume_kg', 1720)
            ->where('sessions.0.estimated_one_rep_max_kg', 82.3)
            ->where('sessions.1.performed_on', '2026-10-08')
            ->where('sessions.1.top_weight_kg', 110)
            ->where('sessions.1.total_reps', 14)
            ->where('sessions.1.volume_kg', 1425)
            ->where('sessions.1.estimated_one_rep_max_kg', 116.7)
            ->where('sessions.1.sets', [
                ['reps' => 5, 'weight_kg' => '100.00'],
                ['reps' => 5, 'weight_kg' => '100.00'],
                ['reps' => 3, 'weight_kg' => '105.00'],
                ['reps' => 1, 'weight_kg' => '110.00'],
            ]));
});

test('progress defaults to the most frequently performed exercise', function () {
    $user = User::factory()->create();
    $member = Member::factory()->for($user)->create();
    $squat = Exercise::factory()->for($user)->create(['name' => 'Squat']);
    $bench = Exercise::factory()->for($user)->create(['name' => 'Bench press']);

    WorkoutEntry::factory()->for(Workout::factory()->for($member))->for($bench)->create();
    WorkoutEntry::factory()->count(2)->for($squat)->sequence(
        fn () => ['workout_id' => Workout::factory()->for($member)],
    )->create();

    $this->actingAs($user)
        ->get(route('members.progress', $member))
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedExerciseId', $squat->id)
            ->has('sessions', 2));
});

test('progress ignores an exercise the member has never performed', function () {
    $user = User::factory()->create();
    $member = Member::factory()->for($user)->create();
    $squat = Exercise::factory()->for($user)->create();
    WorkoutEntry::factory()->for(Workout::factory()->for($member))->for($squat)->create();

    $otherMembersEntry = WorkoutEntry::factory()->create();

    $this->actingAs($user)
        ->get(route('members.progress', ['member' => $member, 'exercise' => $otherMembersEntry->exercise_id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedExerciseId', $squat->id)
            ->has('sessions', 1));
});

test('another trainer\'s member progress returns 404', function () {
    $member = Member::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('members.progress', $member))
        ->assertNotFound();
});
