<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberProgressController;
use App\Http\Controllers\WorkoutController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('members', MemberController::class);
    Route::get('members/{member}/progress', [MemberProgressController::class, 'show'])->name('members.progress');
    Route::resource('members.workouts', WorkoutController::class)
        ->except(['index', 'show'])
        ->scoped();

    Route::resource('exercises', ExerciseController::class)->only(['index', 'store', 'update', 'destroy']);
});

require __DIR__.'/settings.php';
