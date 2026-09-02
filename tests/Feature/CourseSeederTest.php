<?php

namespace Tests\Feature;

use App\Models\Course;
use Database\Seeders\CourseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_seeds_the_four_canonical_grade_bands_in_order(): void
    {
        $this->seed(CourseSeeder::class);

        $this->assertSame(
            ['Grade 5 - 6', 'Grade 7 - 8', 'Grade 9 - 10', 'Grade 11 - 12'],
            Course::query()->orderBy('sort_order')->pluck('grade_band')->all(),
        );
    }

    public function test_seeder_is_idempotent_and_preserves_an_existing_thumbnail(): void
    {
        $this->seed(CourseSeeder::class);

        Course::where('grade_band', 'Grade 5 - 6')->update(['thumbnail_path' => 'course-thumbnails/grade-5-6.png']);

        $this->seed(CourseSeeder::class);

        $this->assertSame(4, Course::count());
        $this->assertSame(
            'course-thumbnails/grade-5-6.png',
            Course::where('grade_band', 'Grade 5 - 6')->value('thumbnail_path'),
        );
    }
}
