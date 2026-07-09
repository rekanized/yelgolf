<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\PlaySession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class UserAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_uses_google_authentication(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Continue with Google')
            ->assertSee(route('auth.google.redirect'), false);
    }

    public function test_player_can_log_in_with_google(): void
    {
        $this->mockGoogleUser('google-123', 'Test Player', 'test@example.com');

        $this->get(route('auth.google.callback'))
            ->assertRedirect(url('/').'#course-list');

        $user = User::query()->where('email', 'test@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Test Player', $user->name);
        $this->assertSame('google-123', $user->google_id);
        $this->assertSame(User::ROLE_PLAYER, $user->role);
        $this->assertSame($user->id, session('current_player_id'));
    }

    public function test_google_login_updates_existing_player_by_email(): void
    {
        $user = User::query()->create([
            'name' => 'Existing Player',
            'email' => 'test@example.com',
            'password' => 'existing-password',
        ]);

        $this->mockGoogleUser('google-123', 'Test Player', 'test@example.com');

        $this->get(route('auth.google.callback'))
            ->assertRedirect(url('/').'#course-list');

        $this->assertSame(1, User::query()->where('email', 'test@example.com')->count());
        $this->assertSame($user->id, session('current_player_id'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'test@example.com',
            'google_id' => 'google-123',
        ]);
    }

    public function test_logged_in_player_starts_session_as_real_participant(): void
    {
        $user = User::query()->create([
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => 'existing-password',
        ]);

        $course = Course::query()->create([
            'name' => 'Hästhagen',
            'slug' => 'haesthagen-M8Wu',
            'udisc_url' => 'https://udisc.com/courses/haesthagen-M8Wu',
        ]);

        $response = $this->withSession(['current_player_id' => $user->id])
            ->post(route('sessions.store', $course));

        $session = PlaySession::query()->first();

        $response->assertRedirect(route('sessions.show', $session));
        $this->assertDatabaseHas('play_sessions', [
            'id' => $session->id,
            'host_id' => $user->id,
            'host_name' => $user->name,
        ]);
        $this->assertDatabaseHas('play_session_user', [
            'play_session_id' => $session->id,
            'user_id' => $user->id,
            'status' => 'joined',
        ]);
    }

    private function mockGoogleUser(string $id, string $name, string $email): void
    {
        $socialiteUser = (new SocialiteUser)->setRaw([
            'sub' => $id,
            'name' => $name,
            'email' => $email,
            'picture' => 'https://example.com/avatar.jpg',
        ])->map([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

        Socialite::shouldReceive('driver->user')
            ->once()
            ->andReturn($socialiteUser);
    }
}
