<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Component;

class UserManager extends Component
{
    public function render()
    {
        return view('livewire.admin.user-manager', [
            'users' => User::query()
                ->orderByDesc('updated_at')
                ->orderBy('name')
                ->get(),
        ])->layout('layouts.app', ['title' => __('ui.admin.users_page_title')]);
    }
}
