<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Give every set its own reps and weight: an entry logged as "3 × 10 @ 60 kg" becomes three set rows.
     */
    public function up(): void
    {
        Schema::create('workout_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_entry_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->unsignedSmallInteger('reps');
            $table->decimal('weight_kg', 6, 2)->default(0);
            $table->timestamps();
        });

        DB::table('workout_entries')->orderBy('id')->chunkById(500, function ($entries) {
            $sets = [];

            foreach ($entries as $entry) {
                for ($position = 0; $position < $entry->sets; $position++) {
                    $sets[] = [
                        'workout_entry_id' => $entry->id,
                        'position' => $position,
                        'reps' => $entry->reps,
                        'weight_kg' => $entry->weight_kg,
                        'created_at' => $entry->created_at,
                        'updated_at' => $entry->updated_at,
                    ];
                }
            }

            foreach (array_chunk($sets, 500) as $chunk) {
                DB::table('workout_sets')->insert($chunk);
            }
        });

        Schema::table('workout_entries', function (Blueprint $table) {
            $table->dropColumn(['sets', 'reps', 'weight_kg']);
        });
    }

    /**
     * Collapse each entry's sets back into one row. This is lossy when sets differ:
     * the entry keeps its set count, its first set's reps and its heaviest weight.
     */
    public function down(): void
    {
        Schema::table('workout_entries', function (Blueprint $table) {
            $table->unsignedSmallInteger('sets')->default(1);
            $table->unsignedSmallInteger('reps')->default(1);
            $table->decimal('weight_kg', 6, 2)->default(0);
        });

        DB::table('workout_entries')->orderBy('id')->chunkById(500, function ($entries) {
            foreach ($entries as $entry) {
                $sets = DB::table('workout_sets')->where('workout_entry_id', $entry->id)->orderBy('position')->get();

                if ($sets->isEmpty()) {
                    continue;
                }

                DB::table('workout_entries')->where('id', $entry->id)->update([
                    'sets' => $sets->count(),
                    'reps' => $sets->first()->reps,
                    'weight_kg' => $sets->max('weight_kg'),
                ]);
            }
        });

        Schema::dropIfExists('workout_sets');
    }
};
