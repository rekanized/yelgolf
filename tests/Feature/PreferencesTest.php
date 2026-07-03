<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_preferences_route_sets_locale_and_theme_cookies(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this
            ->withSession(['current_player_id' => $admin->id])
            ->post(route('preferences.update'), [
                'locale' => 'sv',
                'theme' => 'light',
                'redirect_to' => url('/'),
            ]);

        $response->assertRedirect(url('/'));
        $response->assertCookie(config('yelgolf.locale_cookie'), 'sv');
        $response->assertCookie(config('yelgolf.theme_cookie'), 'light');
    }

    public function test_guests_use_default_english_and_light_theme_even_when_preference_state_exists(): void
    {
        $response = $this
            ->withSession([
                'locale' => 'sv',
                'theme' => 'dark',
            ])
            ->get('/');

        $response->assertOk();
        $response->assertSee('data-theme="light"', false);
        $response->assertSee('Find a course');
        $response->assertDontSee('Settings');
    }

    public function test_authenticated_admins_can_see_their_saved_preferences(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->withSession([
            'current_player_id' => $admin->id,
            'locale' => 'sv',
            'theme' => 'dark',
        ])->get(route('settings.edit'));

        $response->assertOk();
        $response->assertSee('data-theme="dark"', false);
        $response->assertSee('Visningsinställningar');
        $response->assertSee('href="'.route('settings.edit').'"', false);
        $response->assertSee('sports-nav__link--active', false);
        $response->assertSee('aria-current="page"', false);
    }

    public function test_settings_page_requires_authentication(): void
    {
        $response = $this->get(route('settings.edit'));

        $response->assertRedirect(route('login'));
    }

    public function test_settings_page_shows_admin_nav_for_authenticated_admins(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->withSession([
            'current_player_id' => $admin->id,
        ])->get(route('settings.edit'));

        $response->assertOk();
        $response->assertSee('href="'.route('admin.dashboard').'"', false);
        $response->assertSee('>Admin<', false);
    }
}