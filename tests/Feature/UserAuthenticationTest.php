<?php

namespace Tests\Feature;

use App\Livewire\UserLoginForm;
use App\Models\Course;
use App\Models\PlaySession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_can_log_in_with_test_credentials(): void
    {
        $user = User::query()->create([
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => Hash::make('test'),
        ]);

        Livewire::test(UserLoginForm::class)
            ->set('login', 'test')
            ->set('password', 'test')
            ->call('authenticate')
            ->assertRedirect(url('/').'#course-list');

        $this->assertSame($user->id, session('current_player_id'));
    }

    public function test_invalid_player_credentials_are_rejected(): void
    {
        User::query()->create([
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => Hash::make('test'),
        ]);

        Livewire::test(UserLoginForm::class)
            ->set('login', 'test')
            ->set('password', 'wrong')
            ->call('authenticate')
            ->assertHasErrors(['login']);

        $this->assertNull(session('current_player_id'));
    }

    public function test_logged_in_player_starts_session_as_real_participant(): void
    {
        $user = User::query()->create([
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => Hash::make('test'),
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
}