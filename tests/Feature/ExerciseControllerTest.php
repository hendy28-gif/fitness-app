<?php

use App\Models\Exercise;
use App\Models\User;
use App\Models\WorkoutEntry;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('exercises.index'))->assertRedirect(route('login'));
});

test('the exercise list only shows the trainer\'s own exercises, with how often each was logged', function () {
    $user = User::factory()->create();
    $squat = Exercise::factory()->for($user)->create(['name' => 'Squat', 'category' => 'Legs']);
    WorkoutEntry::factory()->count(2)->for($squat)->create();
    Exercise::factory()->create(['name' => 'Someone Else\'s Exercise']);

    $this->actingAs($user)
        ->get(route('exercises.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('exercises/Index')
            ->has('exercises', 1)
            ->where('exercises.0.name', 'Squat')
            ->where('exercises.0.workout_entries_count', 2)
            ->where('categories', ['Legs']));
});

test('an exercise can be added with a category', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('exercises.store'), ['name' => '  Sandbag   Carry ', 'category' => 'Functional'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('exercises.index'));

    $exercise = $user->exercises()->sole();

    expect($exercise->name)->toBe('Sandbag Carry')
        ->and($exercise->category)->toBe('Functional');
});

test('an exercise can be added without a category', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('exercises.store'), ['name' => 'Hyrox', 'category' => ''])
        ->assertSessionHasNoErrors();

    expect($user->exercises()->sole()->category)->toBeNull();
});

test('an exercise needs a name that the trainer does not already use, ignoring case', function () {
    $user = User::factory()->create();
    Exercise::factory()->for($user)->create(['name' => 'Bench Press']);
    Exercise::factory()->create(['name' => 'Deadlift']);

    $this->actingAs($user)
        ->post(route('exercises.store'), ['name' => ''])
        ->assertSessionHasErrors(['name' => 'The name field is required.']);

    $this->actingAs($user)
        ->post(route('exercises.store'), ['name' => 'bench press'])
        ->assertSessionHasErrors(['name' => 'You already have an exercise with this name.']);

    $this->actingAs($user)
        ->post(route('exercises.store'), ['name' => 'Deadlift'])
        ->assertSessionHasNoErrors();

    expect($user->exercises()->count())->toBe(2);
});

test('renaming an exercise renames it in past workouts too', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->for($user)->create(['name' => 'Bench Pres', 'category' => null]);
    $entry = WorkoutEntry::factory()->for($exercise)->create();

    $this->actingAs($user)
        ->put(route('exercises.update', $exercise), ['name' => 'Bench Press', 'category' => 'Chest'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('exercises.index'));

    expect($exercise->refresh()->name)->toBe('Bench Press')
        ->and($exercise->category)->toBe('Chest')
        ->and($entry->refresh()->exercise->name)->toBe('Bench Press');
});

test('an exercise can keep its own name when edited', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->for($user)->create(['name' => 'Squat']);

    $this->actingAs($user)
        ->put(route('exercises.update', $exercise), ['name' => 'squat', 'category' => 'Legs'])
        ->assertSessionHasNoErrors();

    expect($exercise->refresh()->name)->toBe('squat');
});

test('an unused exercise can be deleted', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('exercises.destroy', $exercise))
        ->assertRedirect(route('exercises.index'));

    expect($exercise->fresh())->toBeNull();
});

test('an exercise used in a workout cannot be deleted, so the workout history is kept', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->for($user)->create();
    $entry = WorkoutEntry::factory()->for($exercise)->create();

    $this->actingAs($user)
        ->delete(route('exercises.destroy', $exercise))
        ->assertRedirect(route('exercises.index'))
        ->assertInertiaFlash('toast.type', 'error');

    expect($exercise->fresh())->not->toBeNull()
        ->and($entry->fresh())->not->toBeNull();
});

test('another trainer\'s exercise returns 404', function (string $method, string $routeName) {
    $exercise = Exercise::factory()->create(['name' => 'Original']);

    $this->actingAs(User::factory()->create())
        ->{$method}(route($routeName, $exercise), ['name' => 'Hijacked'])
        ->assertNotFound();

    expect($exercise->fresh())->not->toBeNull()
        ->name->toBe('Original');
})->with([
    'update' => ['put', 'exercises.update'],
    'destroy' => ['delete', 'exercises.destroy'],
]);
