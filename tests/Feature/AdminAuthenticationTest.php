<?php

namespace Tests\Feature;

use App\Livewire\Admin\LoginForm;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_requires_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_authenticated_admin_dashboard_uses_admin_panel_copy_and_nav_logout(): void
    {
        Course::query()->create([
            'name' => 'Hästhagen',
            'slug' => 'haesthagen-M8Wu',
            'udisc_url' => 'https://udisc.com/courses/haesthagen-M8Wu',
        ]);

        $response = $this->withSession(['admin_authenticated' => true])->get('/admin');

        $response
            ->assertOk()
            ->assertSee('Admin panel')
            ->assertSee('Log out')
            ->assertDontSee('User panel');
    }

    public function test_admin_can_log_in_with_configured_credentials(): void
    {
        Livewire::test(LoginForm::class)
            ->set('username', 'test')
            ->set('password', 'test')
            ->call('authenticate')
            ->assertRedirect(url('/').'#course-list');

        $this->assertTrue((bool) session('admin_authenticated'));
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        Livewire::test(LoginForm::class)
            ->set('username', 'wrong')
            ->set('password', 'credentials')
            ->call('authenticate')
            ->assertHasErrors(['username']);

        $this->assertFalse((bool) session('admin_authenticated'));
    }
}