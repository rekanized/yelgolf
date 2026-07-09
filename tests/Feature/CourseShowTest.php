<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Hole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_lists_only_basic_course_stats(): void
    {
        $course = Course::query()->create([
            'name' => 'Hästhagen',
            'slug' => 'haesthagen-M8Wu',
            'udisc_url' => 'https://udisc.com/courses/haesthagen-M8Wu',
            'description' => 'Long description that should not appear on the index.',
            'holes_count' => 18,
            'rating' => 3.8,
            'ratings_count' => 2789,
            'difficulty_levels' => ['challenging'],
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(route('courses.show', $course), false);
        $response->assertSee('Hästhagen');
        $response->assertSee('2,789');
        $response->assertSee('Difficulty');
        $response->assertSee('Challenging');
        $response->assertSee('material-symbols-outlined', false);
        $response->assertDontSee('Long description that should not appear on the index.');
    }

    public function test_course_show_page_displays_details_and_photos(): void
    {
        $course = Course::query()->create([
            'name' => 'Hästhagen',
            'slug' => 'haesthagen-M8Wu',
            'udisc_url' => 'https://udisc.com/courses/haesthagen-M8Wu',
            'location_name' => 'Örebro, Sweden',
            'description' => 'Detailed course summary.',
            'holes_count' => 18,
            'rating' => 3.8,
            'ratings_count' => 2789,
            'latitude' => 59.2677595105919,
            'longitude' => 15.161870509788173,
            'target_type' => 'DISCatcher Pro (original)',
            'tee_types' => ['Konstgräs'],
            'land_types' => ['publicPark'],
            'property_type' => 'mixedUse',
            'difficulty_levels' => ['intermediate', 'challenging'],
            'has_bathroom' => true,
            'has_drinking_water' => true,
            'is_cart_friendly' => true,
            'is_dog_friendly' => true,
            'is_stroller_friendly' => true,
            'accessibility' => 'limited',
            'accessibility_description' => 'Alla hål ligger på jämn mark, vagn och rullstol bör fungera.',
            'photos' => [
                'https://udisc-parse.s3.amazonaws.com/photo-1.jpg',
                'https://udisc-parse.s3.amazonaws.com/photo-2.jpg',
            ],
        ]);

        Hole::query()->create([
            'course_id' => $course->id,
            'layout_id' => 37576,
            'layout_name' => 'Hästhagen Främre',
            'layout_caddie_book_url' => 'https://udisc.com/courses/haesthagen-M8Wu/layouts/37576/caddie-book',
            'layout_difficulty' => 'intermediate',
            'layout_order' => 1,
            'sort_order' => 1,
            'number' => 1,
            'hole_label' => '1',
            'par' => 3,
            'distance_meters' => 58,
            'distance_feet' => 190.29,
        ]);

        Hole::query()->create([
            'course_id' => $course->id,
            'layout_id' => 37576,
            'layout_name' => 'Hästhagen Främre',
            'layout_caddie_book_url' => 'https://udisc.com/courses/haesthagen-M8Wu/layouts/37576/caddie-book',
            'layout_difficulty' => 'intermediate',
            'layout_order' => 1,
            'sort_order' => 2,
            'number' => 2,
            'hole_label' => '2',
            'par' => 4,
            'distance_meters' => 92,
            'distance_feet' => 301.84,
        ]);

        $response = $this->get(route('courses.show', $course));

        $response->assertOk();
        $response->assertSee('Detailed course summary.');
        $response->assertSee('DISCatcher Pro (original)');
        $response->assertSee('Konstgräs');
        $response->assertSee('Public Park');
        $response->assertSee('Mixed Use');
        $response->assertSee('material-symbols-outlined', false);
        $response->assertSee('course-difficulty-token--challenging', false);
        $response->assertSee('Location');
        $response->assertSee('Restroom available');
        $response->assertSee('Dogs allowed');
        $response->assertSee('Hästhagen Främre');
        $response->assertSee('Layout map');
        $response->assertSee('https://udisc.com/courses/haesthagen-M8Wu/layouts/37576/caddie-book', false);
        $response->assertSee('58 m');
        $response->assertSee('92 m');
        $response->assertSee('https://udisc-parse.s3.amazonaws.com/photo-1.jpg', false);
        $response->assertSee('https://udisc-parse.s3.amazonaws.com/photo-2.jpg', false);
    }
}
