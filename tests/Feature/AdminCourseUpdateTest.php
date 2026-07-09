<?php

namespace Tests\Feature;

use App\Livewire\Admin\CourseManager;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCourseUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_existing_course_from_stored_udisc_url(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $course = Course::query()->create([
            'name' => 'Hästhagen',
            'slug' => 'haesthagen-M8Wu',
            'udisc_url' => 'https://udisc.com/courses/haesthagen-M8Wu',
            'location_name' => 'Örebro, Sweden',
            'description' => 'Old summary',
            'holes_count' => 9,
        ]);

        Http::fake([
            'udisc.com/*' => Http::response($this->updatedUdiscHtml(), 200),
        ]);

        $this->withSession(['current_player_id' => $admin->id]);

        Livewire::test(CourseManager::class)
            ->call('updateCourse', $course->id)
            ->assertDispatched('notify');

        $course->refresh();

        $this->assertSame(18, $course->holes_count);
        $this->assertSame(3.8, $course->rating);
        $this->assertSame(2789, $course->ratings_count);
        $this->assertSame('Updated summary from UDisc.', $course->description);
    }

    private function updatedUdiscHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Hästhagen - Örebro, Sweden | UDisc Disc Golf Course Directory</title>
    </head>
    <body>
        <main>
            <a href="https://www.google.com/maps/place/59.2677595105919,15.161870509788173">Get directions</a>
            <div>3.8 (2789 ratings)</div>
            <img src="https://udisc-parse.s3.amazonaws.com/hash_m_IMG_1001.jpg" alt="course photo">
            <section>
                <h2>About the course</h2>
                <p>Updated summary from UDisc.</p>
            </section>
            <div>Show more</div>
            <div>18 holes</div>
            <div>Established in 1982</div>
        </main>
    </body>
</html>
HTML;
    }
}
