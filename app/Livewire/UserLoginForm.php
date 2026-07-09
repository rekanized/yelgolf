<?php

namespace App\Livewire;

use Livewire\Component;

class UserLoginForm extends Component
{
    public function render()
    {
        return view('livewire.user-login-form')
            ->layout('layouts.app', ['title' => __('ui.player_auth.page_title')]);
    }
}
