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

    public bool $showEndSessionModal = false;

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
        abort_unless($this->playSession->status === 'active', 403);

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
        if ($this->playSession->status !== 'active') {
            $this->dispatch('notify', message: __('ui.session.ended_action_denied'), type: 'error');

            return;
        }

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
        abort_unless($this->playSession->status === 'active', 403);
        abort_unless($this->canUpdateParticipantLayout($key, app(CurrentPlayerResolver::class)), 403);

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

    public function openEndSessionModal(CurrentPlayerResolver $resolver): void
    {
        abort_unless($this->playSession->status === 'active' && $this->viewerIsHost($resolver), 403);

        $this->showEndSessionModal = true;
    }

    public function closeEndSessionModal(): void
    {
        $this->showEndSessionModal = false;
    }

    public function endSession(CurrentPlayerResolver $resolver): void
    {
        abort_unless($this->playSession->status === 'active' && $this->viewerIsHost($resolver), 403);

        $this->playSession->forceFill([
            'status' => 'ended',
            'ended_at' => now(),
        ])->save();

        $this->showEndSessionModal = false;

        $this->dispatch('notify', message: __('ui.session.ended_successfully'), type: 'success');

        $this->redirectRoute('courses.show', ['course' => $this->playSession->course->slug], navigate: true);
    }

    public function render(CurrentPlayerResolver $resolver)
    {
        $selectedInvitees = $this->selectedInvitees();
        $inviteOptions = $this->inviteOptions($selectedInvitees);
        $isHost = $this->viewerIsHost($resolver);
        $isParticipant = $this->isParticipant($resolver);
        $editableParticipantKeys = $this->editableParticipantKeys($resolver);
        $layoutOptions = $this->layoutOptions();
        $layoutNames = $layoutOptions
            ->mapWithKeys(fn (array $layout): array => [$layout['id'] => $layout['name']])
            ->all();

        return view('livewire.play-session-page', [
            'selectedInvitees' => $selectedInvitees,
            'inviteOptions' => $inviteOptions,
            'isHost' => $isHost,
            'isParticipant' => $isParticipant,
            'isActive' => $this->playSession->status === 'active',
            'editableParticipantKeys' => $editableParticipantKeys,
            'layoutOptions' => $layoutOptions,
            'layoutNames' => $layoutNames,
            'scoreCharts' => $this->scoreCharts(),
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

    protected function scoreCharts(): Collection
    {
        $holesByLayout = $this->holesByLayout();
        $maxPlayableHoleIndex = $this->maxPlayableHoleIndex();

        return $this->joinedPlayers()
            ->filter(fn (User $player): bool => filled($player->pivot->selected_layout_id))
            ->map(function (User $player) use ($holesByLayout, $maxPlayableHoleIndex): ?array {
                $layoutHoles = $holesByLayout->get((int) $player->pivot->selected_layout_id) ?? collect();

                if ($layoutHoles->isEmpty() || $maxPlayableHoleIndex === 0) {
                    return null;
                }

                $scoresByHoleIndex = $this->playSession->scores
                    ->where('user_id', $player->id)
                    ->keyBy('hole_index');
                $runningScore = 0;
                $labels = [];
                $values = [];

                for ($holeIndex = 1; $holeIndex <= $maxPlayableHoleIndex; $holeIndex++) {
                    $hole = $layoutHoles->get($holeIndex - 1);
                    $score = $scoresByHoleIndex->get($holeIndex);
                    $labels[] = __('ui.game.hole_label', ['label' => $hole?->hole_label ?: (string) ($hole?->number ?: $holeIndex)]);

                    if (! $hole || $hole->par === null || ! $score) {
                        $values[] = null;

                        continue;
                    }

                    $runningScore += (int) $score->strokes - (int) $hole->par;
                    $values[] = $runningScore;
                }

                return [
                    'id' => $player->id,
                    'player_name' => $player->name,
                    'layout_name' => $layoutHoles->first()?->layout_name ?: __('ui.course.layout_fallback'),
                    'labels' => $labels,
                    'values' => $values,
                ];
            })
            ->filter()
            ->values();
    }

    protected function holesByLayout(): Collection
    {
        return $this->playSession->course->holes
            ->groupBy(fn ($hole): int => (int) ($hole->layout_id ?: $hole->layout_order))
            ->map(fn (Collection $holes): Collection => $holes->values());
    }

    protected function joinedPlayers(): Collection
    {
        return $this->playSession->players
            ->filter(fn (User $player): bool => $player->pivot->status === 'joined')
            ->values();
    }

    protected function maxPlayableHoleIndex(): int
    {
        $holesByLayout = $this->holesByLayout();
        $counts = $this->joinedPlayers()
            ->filter(fn (User $player): bool => filled($player->pivot->selected_layout_id))
            ->map(fn (User $player): int => ($holesByLayout->get((int) $player->pivot->selected_layout_id) ?? collect())->count())
            ->filter(fn (int $count): bool => $count > 0);

        return $counts->isEmpty() ? 0 : $counts->min();
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

    protected function canUpdateParticipantLayout(string $key, CurrentPlayerResolver $resolver): bool
    {
        return $this->editableParticipantKeys($resolver)->contains($key);
    }

    protected function editableParticipantKeys(CurrentPlayerResolver $resolver): Collection
    {
        $currentPlayer = $resolver->resolve(request());

        if (! $currentPlayer) {
            return collect();
        }

        if ($this->playSession->status !== 'active') {
            return collect();
        }

        return $this->playSession->players
            ->filter(fn (User $player): bool => $player->id === $currentPlayer->id && $player->pivot->status === 'joined')
            ->map(fn (User $player): string => 'user-'.$player->id)
            ->values();
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
            'scores',
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
