<?php

namespace Tests\Feature;

use App\Livewire\Admin\CourseManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCourseImportInteractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_import_dispatches_notification_without_page_refresh(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        Http::fake([
            'udisc.com/*' => Http::response($this->udiscHtml(), 200),
        ]);

        $this->withSession(['current_player_id' => $admin->id]);

        Livewire::test(CourseManager::class)
            ->set('udiscUrl', 'https://udisc.com/courses/haesthagen-M8Wu')
            ->call('importCourse')
            ->assertDispatched('notify');
    }

    private function udiscHtml(): string
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
                <p>The oldest course in Orebro, beautifully situated next to Svartån.</p>
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
