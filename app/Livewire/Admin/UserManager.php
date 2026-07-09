<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\EnsuresAdminAccess;
use App\Models\User;
use Livewire\Component;

class UserManager extends Component
{
    use EnsuresAdminAccess;

    public function mount(): void
    {
        $this->ensureAdminAccess();
    }

    public function render()
    {
        $this->ensureAdminAccess();

        return view('livewire.admin.user-manager', [
            'users' => User::query()
                ->orderByDesc('updated_at')
                ->orderBy('name')
                ->get(),
        ])->layout('layouts.app', ['title' => __('ui.admin.users_page_title')]);
    }
}
