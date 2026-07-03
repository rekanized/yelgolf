<?php

namespace Tests\Feature;

use App\Livewire\Admin\LoginForm;
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

    public function test_admin_can_log_in_with_configured_credentials(): void
    {
        Livewire::test(LoginForm::class)
            ->set('username', 'test')
            ->set('password', 'test')
            ->call('authenticate')
            ->assertRedirect(route('admin.dashboard'));

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