<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\CurrentPlayerResolver;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class UserLoginForm extends Component
{
    public string $login = '';

    public string $password = '';

    public function authenticate(CurrentPlayerResolver $resolver)
    {
        $validated = $this->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('name', $validated['login'])
            ->orWhere('email', $validated['login'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            $this->addError('login', __('ui.player_auth.invalid_credentials'));

            return null;
        }

        $resolver->setCurrentPlayer($user->id, request());
        session()->regenerate();

        return redirect()->to(url('/').'#course-list');
    }

    public function render()
    {
        return view('livewire.user-login-form')
            ->layout('layouts.app', ['title' => __('ui.player_auth.page_title')]);
    }
}