<?php

namespace Tests\Feature;

use App\Livewire\UserLoginForm;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
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

        Course::query()->create([
            'name' => 'Hästhagen',
            'slug' => 'haesthagen-M8Wu',
            'udisc_url' => 'https://udisc.com/courses/haesthagen-M8Wu',
        ]);

        $response = $this->withSession(['current_player_id' => $admin->id])->get('/admin');

        $response
            ->assertOk()
            ->assertSee('Admin panel')
            ->assertSee('Log out')
            ->assertDontSee('User panel');
    }

    public function test_admin_can_log_in_from_shared_login_form(): void
    {
        $admin = User::query()->create([
            'name' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('test'),
            'role' => User::ROLE_ADMIN,
        ]);

        Livewire::test(UserLoginForm::class)
            ->set('login', 'admin@example.com')
            ->set('password', 'test')
            ->call('authenticate')
            ->assertRedirect(url('/').'#course-list');

        $this->assertSame($admin->id, session('current_player_id'));
    }

    public function test_invalid_admin_credentials_are_rejected(): void
    {
        User::query()->create([
            'name' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('test'),
            'role' => User::ROLE_ADMIN,
        ]);

        Livewire::test(UserLoginForm::class)
            ->set('login', 'admin')
            ->set('password', 'credentials')
            ->call('authenticate')
            ->assertHasErrors(['login']);

        $this->assertNull(session('current_player_id'));
    }
}