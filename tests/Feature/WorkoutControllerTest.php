<?php

use App\Models\Exercise;
use App\Models\Member;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutEntry;
use App\Models\WorkoutSet;
use Inertia\Testing\AssertableInertia as Assert;

test('the log workout page offers the member\'s last workout to copy', function () {
    $user = User::factory()->create();
    $member = Member::factory()->for($user)->create();
    Workout::factory()->for($member)->create(['performed_on' => '2026-09-01']);
    $latest = Workout::factory()->for($member)->create(['performed_on' => '2026-10-01']);

    $this->actingAs($user)
        ->get(route('members.workouts.create', $member))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workouts/Create')
            ->where('lastWorkout.id', $latest->id));
});

test('the log workout page suggests the trainer\'s exercise library by category, uncategorised last', function () {
    $user = User::factory()->create();
    $member = Member::factory()->for($user)->create();
    Exercise::factory()->for($user)->create(['name' => 'Squat', 'category' => 'Legs']);
    Exercise::factory()->for($user)->create(['name' => 'Hyrox', 'category' => null]);
    Exercise::factory()->for($user)->create(['name' => 'Bench Press', 'category' => 'Chest']);
    Exercise::factory()->for($user)->create(['name' => 'Box Squat', 'category' => 'Legs']);
    Exercise::factory()->create(['name' => 'Another Trainer Move', 'category' => 'Legs']);

    $this->actingAs($user)
        ->get(route('members.workouts.create', $member))
        ->assertInertia(fn (Assert $page) => $page
            ->where('exerciseOptions', [
                ['category' => 'Legs', 'names' => ['Box Squat', 'Squat']],
                ['category' => 'Chest', 'names' => ['Bench Press']],
                ['category' => '', 'names' => ['Hyrox']],
            ]));
});

test('a workout is logged with each set\'s own reps and weight, in order', function () {
    $user = User::factory()->create();
    $member = Member::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('members.workouts.store', $member), [
            'performed_on' => '2026-10-20',
            'notes' => 'Felt strong',
            'entries' => [
                ['exercise' => 'Squat', 'sets' => [
                    ['reps' => 10, 'weight_kg' => 60],
                    ['reps' => 8, 'weight_kg' => 70],
                    ['reps' => 6, 'weight_kg' => 80],
                ]],
                ['exercise' => 'Push-up', 'sets' => [
                    ['reps' => 15, 'weight_kg' => null],
                ]],
            ],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('members.show', $member));

    $workout = $member->workouts()->sole();
    $entries = $workout->entries()->with(['exercise', 'sets'])->get();

    expect($workout->performed_on->toDateString())->toBe('2026-10-20')
        ->and($workout->notes)->toBe('Felt strong')
        ->and($entries->pluck('exercise.name')->all())->toBe(['Squat', 'Push-up'])
        ->and($entries[0]->sets->map->only('position', 'reps', 'weight_kg')->all())->toBe([
            ['position' => 0, 'reps' => 10, 'weight_kg' => '60.00'],
            ['position' => 1, 'reps' => 8, 'weight_kg' => '70.00'],
            ['position' => 2, 'reps' => 6, 'weight_kg' => '80.00'],
        ])
        ->and($entries[1]->sets->sole()->weight_kg)->toBe('0.00');
});

test('exercise names are matched to the trainer\'s library ignoring case and spacing', function () {
    $user = User::factory()->create();
    $member = Member::factory()->for($user)->create();
    $benchPress = Exercise::factory()->for($user)->create(['name' => 'Bench press']);
    $otherTrainersSquat = Exercise::factory()->create(['name' => 'Squat']);

    $this->actingAs($user)->post(route('members.workouts.store', $member), [
        'performed_on' => '2026-10-20',
        'entries' => [
            ['exercise' => '  bench   PRESS ', 'sets' => [['reps' => 8, 'weight_kg' => 60]]],
            ['exercise' => 'Squat', 'sets' => [['reps' => 5, 'weight_kg' => 100]]],
            ['exercise' => 'squat', 'sets' => [['reps' => 3, 'weight_kg' => 110]]],
        ],
    ])->assertSessionHasNoErrors();

    $entries = $member->workouts()->sole()->entries;
    $squat = $user->exercises()->where('name', 'Squat')->sole();

    expect($user->exercises()->count())->toBe(2)
        ->and($entries->pluck('exercise_id')->all())->toBe([$benchPress->id, $squat->id, $squat->id])
        ->and($squat->id)->not->toBe($otherTrainersSquat->id);
});

test('a workout needs at least one valid exercise', function () {
    $user = User::factory()->create();
    $member = Member::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('members.workouts.store', $member), ['performed_on' => '2026-10-20', 'entries' => []])
        ->assertSessionHasErrors(['entries' => 'Add at least one exercise.']);

    $this->actingAs($user)
        ->post(route('members.workouts.store', $member), [
            'performed_on' => '2026-10-20',
            'entries' => [
                ['exercise' => '', 'sets' => [['reps' => 'ten', 'weight_kg' => -5]]],
                ['exercise' => 'Squat', 'sets' => []],
            ],
        ])
        ->assertSessionHasErrors([
            'entries.0.exercise' => 'The exercise field is required.',
            'entries.0.sets.0.reps' => 'The reps field must be an integer.',
            'entries.0.sets.0.weight_kg' => 'The weight field must be between 0 and 1000.',
            'entries.1.sets' => 'Add at least one set.',
        ]);

    expect(Workout::count())->toBe(0);
});

test('updating a workout replaces its exercises and sets', function () {
    $user = User::factory()->create();
    $member = Member::factory()->for($user)->create();
    $workout = Workout::factory()->for($member)->create();
    WorkoutEntry::factory()->count(2)->for($workout)->withSets([['reps' => 10, 'weight_kg' => 50]])->create();

    $this->actingAs($user)
        ->put(route('members.workouts.update', [$member, $workout]), [
            'performed_on' => '2026-10-18',
            'entries' => [['exercise' => 'Deadlift', 'sets' => [
                ['reps' => 5, 'weight_kg' => 140],
                ['reps' => 3, 'weight_kg' => 150],
            ]]],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('members.show', $member));

    $entries = $workout->refresh()->entries()->with(['exercise', 'sets'])->get();

    expect($workout->performed_on->toDateString())->toBe('2026-10-18')
        ->and($entries)->toHaveCount(1)
        ->and($entries[0]->exercise->name)->toBe('Deadlift')
        ->and($entries[0]->sets->pluck('weight_kg')->all())->toBe(['140.00', '150.00'])
        ->and(WorkoutSet::count())->toBe(2);
});

test('a workout can be deleted', function () {
    $user = User::factory()->create();
    $member = Member::factory()->for($user)->create();
    $workout = Workout::factory()->for($member)->create();

    $this->actingAs($user)
        ->delete(route('members.workouts.destroy', [$member, $workout]))
        ->assertRedirect(route('members.show', $member));

    expect($workout->fresh())->toBeNull();
});

test('workouts cannot be logged for another trainer\'s member', function () {
    $member = Member::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('members.workouts.store', $member), [
            'performed_on' => '2026-10-20',
            'entries' => [['exercise' => 'Squat', 'sets' => [['reps' => 5]]]],
        ])
        ->assertNotFound();

    expect(Workout::count())->toBe(0);
});

test('a workout cannot be changed through a member it does not belong to', function () {
    $user = User::factory()->create();
    $ownMember = Member::factory()->for($user)->create();
    $otherWorkout = Workout::factory()->create();

    $this->actingAs($user)
        ->delete(route('members.workouts.destroy', [$ownMember, $otherWorkout]))
        ->assertNotFound();

    expect($otherWorkout->fresh())->not->toBeNull();
});
