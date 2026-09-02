<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

/**
 * Seeds the four fixed grade bands as reference data — courses aren't
 * ad-hoc-creatable via Filament (see CourseResource::canCreate()), this
 * seeder is the only source of Course rows.
 */
class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gradeBands = ['Grade 5 - 6', 'Grade 7 - 8', 'Grade 9 - 10', 'Grade 11 - 12'];

        foreach ($gradeBands as $index => $gradeBand) {
            Course::updateOrCreate(
                ['grade_band' => $gradeBand],
                ['sort_order' => $index + 1],
            );
        }
    }
}
