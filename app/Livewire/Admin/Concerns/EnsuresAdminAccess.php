<?php

namespace App\Livewire\Admin\Concerns;

use App\Services\CurrentPlayerResolver;

trait EnsuresAdminAccess
{
    protected function ensureAdminAccess(): void
    {
        $currentPlayer = app(CurrentPlayerResolver::class)->resolve(request());

        abort_unless($currentPlayer?->isAdmin(), 403);
    }
}
