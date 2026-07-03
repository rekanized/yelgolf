<?php

namespace Tests\Feature;

use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_can_filter_courses_by_search_query(): void
    {
        Course::query()->create([
            'name' => 'Hästhagen',
            'slug' => 'haesthagen-M8Wu',
            'udisc_url' => 'https://udisc.com/courses/haesthagen-M8Wu',
            'location_name' => 'Örebro, Sweden',
        ]);

        Course::query()->create([
            'name' => 'Tallbackens DGB',
            'slug' => 'tallbackens-dgb-kc3i',
            'udisc_url' => 'https://udisc.com/courses/tallbackens-dgb-kc3i',
            'location_name' => 'Brevens Bruk, Sweden',
        ]);

        $response = $this->get('/?q=Hästhagen');

        $response->assertOk();
        $response->assertSee('Hästhagen');
        $response->assertDontSee('Tallbackens DGB');
        $response->assertSee('Showing 1 course for "Hästhagen"', false);
    }

    public function test_homepage_shows_empty_search_state_when_no_courses_match(): void
    {
        Course::query()->create([
            'name' => 'Hästhagen',
            'slug' => 'haesthagen-M8Wu',
            'udisc_url' => 'https://udisc.com/courses/haesthagen-M8Wu',
            'location_name' => 'Örebro, Sweden',
        ]);

        $response = $this->get('/?q=NoMatch');

        $response->assertOk();
        $response->assertSee('No matching courses');
        $response->assertSee('remove the current search text to browse all courses again.');
    }
}