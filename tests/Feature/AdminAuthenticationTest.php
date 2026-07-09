<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_requires_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_non_admin_user_cannot_access_admin_dashboard(): void
    {
        $user = User::query()->create([
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => Hash::make('test'),
            'role' => User::ROLE_PLAYER,
        ]);

        $this->withSession(['current_player_id' => $user->id])
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_authenticated_admin_dashboard_uses_admin_panel_copy_and_nav_logout(): void
    {
        $admin = User::query()->create([
            'name' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('test'),
            'role' => User::ROLE_ADMIN,
        ]);

        $course = Course::query()->create([
            'name' => 'Hästhagen',
            'slug' => 'haesthagen-M8Wu',
            'udisc_url' => 'https://udisc.com/courses/haesthagen-M8Wu',
        ]);

        $response = $this->withSession(['current_player_id' => $admin->id])->get('/admin');

        $response
            ->assertOk()
            ->assertSee('Admin panel')
            ->assertSee('Log out')
            ->assertSee(route('admin.users'), false)
            ->assertSee('Users')
            ->assertSee(route('courses.show', $course), false)
            ->assertDontSee('User panel');
    }

    public function test_admin_users_page_requires_login(): void
    {
        $this->get('/admin/users')->assertRedirect('/login');
    }

    public function test_non_admin_user_cannot_access_admin_users_page(): void
    {
        $user = User::query()->create([
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => Hash::make('test'),
            'role' => User::ROLE_PLAYER,
        ]);

        $this->withSession(['current_player_id' => $user->id])
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_authenticated_admin_can_view_signed_in_users(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('test'),
            'role' => User::ROLE_ADMIN,
            'google_id' => 'google-admin',
        ]);

        User::query()->create([
            'name' => 'Player One',
            'email' => 'player@example.com',
            'password' => Hash::make('test'),
            'role' => User::ROLE_PLAYER,
            'google_id' => 'google-player',
        ]);

        $response = $this->withSession(['current_player_id' => $admin->id])->get('/admin/users');

        $response
            ->assertOk()
            ->assertSee('Signed-in users')
            ->assertSee('Admin User')
            ->assertSee('admin@example.com')
            ->assertSee('Player One')
            ->assertSee('player@example.com')
            ->assertSee('Google connected')
            ->assertSee(route('admin.dashboard'), false);
    }

    public function test_admin_can_log_in_with_google_from_shared_login_flow(): void
    {
        $admin = User::query()->create([
            'name' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('test'),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->mockGoogleUser('google-admin', 'Admin', 'admin@example.com');

        $this->get(route('auth.google.callback'))
            ->assertRedirect(url('/').'#course-list');

        $this->assertSame($admin->id, session('current_player_id'));
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => User::ROLE_ADMIN,
            'google_id' => 'google-admin',
        ]);
    }

    public function test_configured_google_admin_email_is_promoted_on_login(): void
    {
        config(['services.google.admin_emails' => ['admin@example.com']]);
        $this->mockGoogleUser('google-admin', 'Admin', 'admin@example.com');

        $this->get(route('auth.google.callback'))
            ->assertRedirect(url('/').'#course-list');

        $admin = User::query()->where('email', 'admin@example.com')->first();

        $this->assertNotNull($admin);
        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertSame($admin->id, session('current_player_id'));
    }

    public function test_google_login_without_email_is_rejected(): void
    {
        $socialiteUser = (new SocialiteUser)->setRaw(['sub' => 'google-admin'])
            ->map(['id' => 'google-admin', 'name' => 'Admin']);

        Socialite::shouldReceive('driver->user')
            ->once()
            ->andReturn($socialiteUser);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'));

        $this->assertNull(session('current_player_id'));
    }

    private function mockGoogleUser(string $id, string $name, string $email): void
    {
        $socialiteUser = (new SocialiteUser)->setRaw([
            'sub' => $id,
            'name' => $name,
            'email' => $email,
        ])->map([
            'id' => $id,
            'name' => $name,
            'email' => $email,
        ]);

        Socialite::shouldReceive('driver->user')
            ->once()
            ->andReturn($socialiteUser);
    }
}
