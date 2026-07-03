<?php

namespace App\Livewire;

use App\Models\PlaySession;
use App\Services\CurrentPlayerResolver;
use Illuminate\Support\Collection;
use Livewire\Component;

class PlayerConsole extends Component
{
    public function joinSession(int $sessionId, int $userId, CurrentPlayerResolver $resolver): void
    {
        $session = PlaySession::query()
            ->with('course')
            ->whereKey($sessionId)
            ->where('status', 'active')
            ->first();

        if (! $session) {
            $this->dispatch('notify', message: __('ui.session.join_failed'), type: 'error');

            return;
        }

        $membership = $session->players()
            ->where('users.id', $userId)
            ->first();

        if (! $membership || $membership->pivot->status !== 'invited') {
            $this->dispatch('notify', message: __('ui.session.join_failed'), type: 'error');

            return;
        }

        $session->players()->updateExistingPivot($userId, [
            'status' => 'joined',
            'joined_at' => now(),
            'updated_at' => now(),
        ]);

        $resolver->setCurrentPlayer($userId, request());

        $this->dispatch('notify', message: __('ui.session.joined_successfully', ['course' => $session->course->name]), type: 'success');

        $this->redirectRoute('sessions.show', ['playSession' => $session->id], navigate: true);
    }

    public function render(CurrentPlayerResolver $resolver)
    {
        $pendingInvites = $this->pendingInvites();

        return view('livewire.player-console', [
            'pendingInvites' => $pendingInvites,
        ]);
    }

    protected function pendingInvites(): Collection
    {
        return PlaySession::query()
            ->with([
                'course',
                'host',
                'players' => fn ($query) => $query->orderBy('name'),
            ])
            ->where('status', 'active')
            ->orderByDesc('started_at')
            ->get()
            ->flatMap(function (PlaySession $session): Collection {
                return $session->players
                    ->filter(fn ($player): bool => $player->pivot->status === 'invited')
                    ->map(function ($player) use ($session): array {
                    return [
                        'session_id' => $session->id,
                        'course_name' => $session->course->name,
                        'host_name' => $session->host?->name ?? $session->host_name ?? __('ui.session.host_fallback'),
                        'invitee_id' => $player->id,
                        'invitee_name' => $player->name,
                    ];
                    });
            })
            ->values();
    }
}