<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Give exercises a category and copy the default library (config/exercises.php) into every
     * existing trainer's own exercise list, so each trainer can edit theirs.
     */
    public function up(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->string('category')->nullable()->after('name');
        });

        /** @var array<string, array<int, string>> $library */
        $library = config('exercises');
        $now = now();

        foreach (DB::table('users')->pluck('id') as $userId) {
            $existing = DB::table('exercises')
                ->where('user_id', $userId)
                ->get(['id', 'name', 'category'])
                ->keyBy(fn (object $exercise): string => mb_strtolower($exercise->name));

            $rows = [];

            foreach ($library as $category => $names) {
                foreach ($names as $name) {
                    $exercise = $existing->get(mb_strtolower($name));

                    if ($exercise === null) {
                        $rows[] = [
                            'user_id' => $userId,
                            'name' => $name,
                            'category' => $category ?: null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    } elseif ($exercise->category === null && $category !== '') {
                        DB::table('exercises')->where('id', $exercise->id)->update(['category' => $category]);
                    }
                }
            }

            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('exercises')->insert($chunk);
            }
        }
    }

    /**
     * Remove the category column. Library exercises copied in by up() are kept, since some may
     * have been used in workouts since.
     */
    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
