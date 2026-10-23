<?php

use App\Models\Exercise;
use App\Models\Member;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutEntry;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('members.index'))->assertRedirect(route('login'));
});

test('the member list only shows the trainer\'s own members', function () {
    $user = User::factory()->create();
    Member::factory()->for($user)->create(['name' => 'Alice Tan']);
    Member::factory()->create(['name' => 'Someone Else']);

    $this->actingAs($user)
        ->get(route('members.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('members/Index')
            ->has('members.data', 1)
            ->where('members.data.0.name', 'Alice Tan'));
});

test('members can be searched by name, email or phone', function (string $search) {
    $user = User::factory()->create();
    Member::factory()->for($user)->create(['name' => 'Alice Tan', 'email' => 'alice@example.com', 'phone' => '0811-2233']);
    Member::factory()->for($user)->create(['name' => 'Bob Lim', 'email' => 'bob@example.com', 'phone' => '0899-0000']);

    $this->actingAs($user)
        ->get(route('members.index', ['search' => $search]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('members.data', 1)
            ->where('members.data.0.name', 'Alice Tan'));
})->with([
    'partial name' => 'ali',
    'email' => 'alice@',
    'phone' => '2233',
]);

test('a member can be created', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('members.store'), [
        'name' => 'Alice Tan',
        'email' => 'alice@example.com',
        'gender' => 'female',
        'height_cm' => '165',
        'weight_kg' => '60.5',
        'goals' => 'Build strength',
    ]);

    $member = $user->members()->sole();

    $response->assertSessionHasNoErrors()->assertRedirect(route('members.show', $member));
    expect($member->name)->toBe('Alice Tan')
        ->and($member->weight_kg)->toBe('60.5');
});

test('a member requires a name and a valid gender', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('members.store'), ['name' => '', 'gender' => 'robot'])
        ->assertSessionHasErrors([
            'name' => 'The name field is required.',
            'gender' => 'The selected gender is invalid.',
        ]);

    expect(Member::count())->toBe(0);
});

test('the member page shows the last session\'s sets and the best weight for each exercise', function () {
    $user = User::factory()->create();
    $member = Member::factory()->for($user)->create();
    $squat = Exercise::factory()->for($user)->create(['name' => 'Squat']);

    $older = Workout::factory()->for($member)->create(['performed_on' => '2026-10-01']);
    WorkoutEntry::factory()->for($older)->for($squat)->withSets([
        ['reps' => 5, 'weight_kg' => 100],
    ])->create();

    $latest = Workout::factory()->for($member)->create(['performed_on' => '2026-10-10']);
    WorkoutEntry::factory()->for($latest)->for($squat)->withSets([
        ['reps' => 8, 'weight_kg' => 90],
        ['reps' => 6, 'weight_kg' => 92.5],
    ])->create(['position' => 0]);
    WorkoutEntry::factory()->for($latest)->for($squat)->withSets([
        ['reps' => 5, 'weight_kg' => 95],
    ])->create(['position' => 1]);

    $this->actingAs($user)
        ->get(route('members.show', $member))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('members/Show')
            ->has('exerciseSummaries', 1)
            ->where('exerciseSummaries.0.exercise', 'Squat')
            ->where('exerciseSummaries.0.sessions', 2)
            ->where('exerciseSummaries.0.last_performed_on', '2026-10-10')
            ->where('exerciseSummaries.0.last_sets', [
                ['reps' => 8, 'weight_kg' => '90.00'],
                ['reps' => 6, 'weight_kg' => '92.50'],
                ['reps' => 5, 'weight_kg' => '95.00'],
            ])
            ->where('exerciseSummaries.0.best_weight_kg', '100.00')
            ->has('workouts.data', 2)
            ->where('workouts.data.0.id', $latest->id));
});

test('the workout history can be filtered by exercise', function () {
    $user = User::factory()->create();
    $member = Member::factory()->for($user)->create();
    $squat = Exercise::factory()->for($user)->create(['name' => 'Squat']);
    $bench = Exercise::factory()->for($user)->create(['name' => 'Bench press']);

    $squatWorkout = Workout::factory()->for($member)->create();
    WorkoutEntry::factory()->for($squatWorkout)->for($squat)->create();
    $benchWorkout = Workout::factory()->for($member)->create();
    WorkoutEntry::factory()->for($benchWorkout)->for($bench)->create();

    $this->actingAs($user)
        ->get(route('members.show', ['member' => $member, 'exercise' => $bench->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('workouts.data', 1)
            ->where('workouts.data.0.id', $benchWorkout->id));
});

test('a member can be updated', function () {
    $user = User::factory()->create();
    $member = Member::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('members.update', $member), ['name' => 'New Name'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('members.show', $member));

    expect($member->refresh()->name)->toBe('New Name');
});

test('deleting a member deletes their workouts', function () {
    $user = User::factory()->create();
    $member = Member::factory()->for($user)->create();
    $workout = Workout::factory()->for($member)->create();
    WorkoutEntry::factory()->for($workout)->create();

    $this->actingAs($user)
        ->delete(route('members.destroy', $member))
        ->assertRedirect(route('members.index'));

    expect(Member::count())->toBe(0)
        ->and(Workout::count())->toBe(0)
        ->and(WorkoutEntry::count())->toBe(0);
});

test('another trainer\'s member returns 404', function (string $method, string $routeName) {
    $member = Member::factory()->create(['name' => 'Original']);

    $this->actingAs(User::factory()->create())
        ->{$method}(route($routeName, $member), ['name' => 'Hijacked'])
        ->assertNotFound();

    expect($member->fresh())->not->toBeNull()
        ->name->toBe('Original');
})->with([
    'show' => ['get', 'members.show'],
    'edit' => ['get', 'members.edit'],
    'update' => ['put', 'members.update'],
    'destroy' => ['delete', 'members.destroy'],
]);
