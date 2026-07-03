<?php

namespace App\Livewire;

use App\Models\PlaySession;
use App\Models\User;
use App\Services\CurrentPlayerResolver;
use Illuminate\Support\Collection;
use Livewire\Component;

class PlaySessionPage extends Component
{
    public PlaySession $playSession;

    public string $inviteSearch = '';

    public bool $showInvitePicker = false;

    /** @var array<string, string> */
    public array $participantLayouts = [];

    /** @var array<int, string> */
    public array $selectedInviteeIds = [];

    public function mount(PlaySession $playSession, CurrentPlayerResolver $resolver): void
    {
        $this->loadPlaySession($playSession);

        abort_unless($this->isParticipant($resolver), 403);
    }

    public function openInvitePicker(): void
    {
        $this->showInvitePicker = true;
    }

    public function closeInvitePicker(): void
    {
        $this->showInvitePicker = false;
        $this->inviteSearch = '';
        $this->selectedInviteeIds = [];
        $this->resetErrorBag('selectedInviteeIds');
    }

    public function addInvitee(int $userId): void
    {
        $inviteeId = (string) $userId;

        if (in_array($inviteeId, $this->selectedInviteeIds, true)) {
            return;
        }

        $this->selectedInviteeIds[] = $inviteeId;
        $this->inviteSearch = '';
        $this->resetErrorBag('selectedInviteeIds');
    }

    public function removeInvitee(int $userId): void
    {
        $inviteeId = (string) $userId;

        $this->selectedInviteeIds = array_values(array_filter(
            $this->selectedInviteeIds,
            static fn (string $selectedId): bool => $selectedId !== $inviteeId,
        ));
    }

    public function invitePlayers(CurrentPlayerResolver $resolver): void
    {
        if (! $this->viewerIsHost($resolver)) {
            $this->dispatch('notify', message: __('ui.session.invite_denied'), type: 'error');

            return;
        }

        $currentPlayer = $resolver->resolve(request());

        $inviteeIds = collect($this->selectedInviteeIds)
            ->map(static fn (string $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($inviteeIds->isEmpty()) {
            $this->addError('selectedInviteeIds', __('ui.session.select_players'));

            return;
        }

        $eligibleInviteeIds = User::query()
            ->whereIn('id', $inviteeIds)
            ->when($currentPlayer, fn ($query) => $query->where('id', '!=', $currentPlayer->id))
            ->pluck('id')
            ->all();

        $existingMemberships = $this->playSession->players()
            ->whereIn('users.id', $eligibleInviteeIds)
            ->get()
            ->keyBy('id');

        $payload = [];

        foreach ($eligibleInviteeIds as $inviteeId) {
            $membership = $existingMemberships->get($inviteeId);

            if ($membership && $membership->pivot->status === 'joined') {
                continue;
            }

            $payload[$inviteeId] = [
                'status' => 'invited',
                'invited_at' => now(),
                'joined_at' => null,
            ];
        }

        if ($payload === []) {
            $this->dispatch('notify', message: __('ui.session.no_more_players'), type: 'error');

            return;
        }

        $this->playSession->players()->syncWithoutDetaching($payload);

        $this->loadPlaySession($this->playSession->fresh());

        $this->selectedInviteeIds = [];
        $this->inviteSearch = '';
        $this->showInvitePicker = false;
        $this->resetErrorBag('selectedInviteeIds');

        $this->dispatch(
            'notify',
            message: trans_choice('ui.session.invited_successfully', count($payload), ['count' => count($payload)]),
            type: 'success',
        );
    }

    public function updateParticipantLayout(string $key, string $value): void
    {
        $layoutId = $this->validatedLayoutId($value);

        $this->participantLayouts[$key] = $value;

        if ($key === 'host') {
            $this->playSession->forceFill([
                'host_layout_id' => $layoutId,
            ])->save();
        } else {
            $userId = (int) str($key)->after('user-')->value();

            $this->playSession->players()->updateExistingPivot($userId, [
                'selected_layout_id' => $layoutId,
                'updated_at' => now(),
            ]);
        }

        $this->loadPlaySession($this->playSession->fresh());

        $this->dispatch('notify', message: __('ui.session.layout_updated'), type: 'success');
    }

    public function render(CurrentPlayerResolver $resolver)
    {
        $selectedInvitees = $this->selectedInvitees();
        $inviteOptions = $this->inviteOptions($selectedInvitees);
        $isHost = $this->viewerIsHost($resolver);
        $isParticipant = $this->isParticipant($resolver);
        $layoutOptions = $this->layoutOptions();
        $layoutNames = $layoutOptions
            ->mapWithKeys(fn (array $layout): array => [$layout['id'] => $layout['name']])
            ->all();

        return view('livewire.play-session-page', [
            'selectedInvitees' => $selectedInvitees,
            'inviteOptions' => $inviteOptions,
            'isHost' => $isHost,
            'isParticipant' => $isParticipant,
            'layoutOptions' => $layoutOptions,
            'layoutNames' => $layoutNames,
        ])->layout('layouts.app', [
            'title' => __('ui.session.page_title', ['course' => $this->playSession->course->name]),
        ]);
    }

    protected function selectedInvitees(): Collection
    {
        if ($this->selectedInviteeIds === []) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $this->selectedInviteeIds)
            ->orderBy('name')
            ->get();
    }

    protected function inviteOptions(Collection $selectedInvitees): Collection
    {
        $excludedIds = $this->playSession->players
            ->pluck('id')
            ->merge($selectedInvitees->pluck('id'))
            ->unique()
            ->values();

        if ($this->playSession->host_id) {
            $excludedIds = $excludedIds
                ->push($this->playSession->host_id)
                ->unique()
                ->values();
        }

        return User::query()
            ->whereNotIn('id', $excludedIds)
            ->when($this->inviteSearch !== '', function ($query) {
                $search = '%'.$this->inviteSearch.'%';

                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search);
                });
            })
            ->orderBy('name')
            ->limit(8)
            ->get();
    }

    protected function layoutOptions(): Collection
    {
        return $this->playSession->course->holes
            ->groupBy(fn ($hole) => $hole->layout_id ?: $hole->layout_order)
            ->map(function (Collection $holes): array {
                $layout = $holes->first();

                return [
                    'id' => (int) ($layout->layout_id ?: $layout->layout_order),
                    'name' => $layout->layout_name ?: __('ui.course.layout_fallback'),
                ];
            })
            ->values();
    }

    protected function isParticipant(CurrentPlayerResolver $resolver): bool
    {
        $currentPlayer = $resolver->resolve(request());

        return $this->viewerIsHost($resolver)
            || $this->playSession->players->contains(function (User $player) use ($currentPlayer): bool {
                return $player->id === $currentPlayer?->id
                    && $player->pivot->status === 'joined';
            });
    }

    protected function validatedLayoutId(string $value): ?int
    {
        $allowedLayoutIds = $this->layoutOptions()
            ->pluck('id')
            ->all();

        if ($value === '') {
            return null;
        }

        $layoutId = (int) $value;

        if (! in_array($layoutId, $allowedLayoutIds, true)) {
            abort(422, __('ui.session.layout_denied'));
        }

        return $layoutId;
    }

    protected function loadPlaySession(PlaySession $playSession): void
    {
        $this->playSession = $playSession->load([
            'course.holes',
            'host',
            'players' => fn ($query) => $query->orderBy('name'),
        ]);

        $this->participantLayouts = $this->playSession->participantRoster()
            ->mapWithKeys(fn (object $player): array => [
                $player->key => $player->selected_layout_id ? (string) $player->selected_layout_id : '',
            ])
            ->all();
    }

    protected function viewerIsHost(CurrentPlayerResolver $resolver): bool
    {
        $currentPlayer = $resolver->resolve(request());

        return $currentPlayer && $this->playSession->host_id === $currentPlayer->id;
    }
}