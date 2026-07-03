<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class LoginForm extends Component
{
    public string $username = '';

    public string $password = '';

    public function authenticate()
    {
        $validated = $this->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (
            $validated['username'] !== config('admin.username')
            || $validated['password'] !== config('admin.password')
        ) {
            $this->addError('username', 'Invalid admin credentials.');

            return null;
        }

        session()->put('admin_authenticated', true);
        session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function render()
    {
        return view('livewire.admin.login-form')
            ->layout('layouts.app', ['title' => 'Admin login']);
    }
}