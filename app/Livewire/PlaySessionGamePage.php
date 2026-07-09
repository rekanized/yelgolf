<?php

namespace App\Livewire;

use App\Models\PlaySession;
use App\Models\User;
use App\Services\CurrentPlayerResolver;
use Illuminate\Support\Collection;
use Livewire\Component;

class PlaySessionGamePage extends Component
{
    public PlaySession $playSession;

    /** @var array<int, string> */
    public array $scoreInputs = [];

    public function mount(PlaySession $playSession, CurrentPlayerResolver $resolver): void
    {
        $this->loadPlaySession($playSession);

        abort_unless($this->canUseGame($resolver), 403);

        $this->refreshScoreInputs();
    }

    public function decrementScore(int $userId, CurrentPlayerResolver $resolver): void
    {
        $this->adjustScore($userId, -1, $resolver);
    }

    public function incrementScore(int $userId, CurrentPlayerResolver $resolver): void
    {
        $this->adjustScore($userId, 1, $resolver);
    }

    public function saveScore(int $userId, mixed $value, CurrentPlayerResolver $resolver): void
    {
        abort_unless($this->canUseGame($resolver), 403);

        $row = $this->scoreRowForUser($userId);

        abort_unless($row, 422);

        $value = trim((string) $value);

        if ($value === '') {
            $this->playSession->scores()
                ->where('user_id', $userId)
                ->where('hole_index', $this->currentHoleIndex())
                ->delete();

            $this->scoreInputs[$userId] = '';
            $this->loadPlaySession($this->playSession->fresh());
            $this->refreshScoreInputs();

            return;
        }

        if (! ctype_digit($value) || (int) $value < 1 || (int) $value > 99) {
            $this->addError('scoreInputs.'.$userId, __('ui.game.score_invalid'));

            return;
        }

        $strokes = (int) $value;

        $this->playSession->scores()->updateOrCreate(
            [
                'user_id' => $userId,
                'hole_index' => $this->currentHoleIndex(),
            ],
            [
                'hole_id' => $row['hole_id'],
                'strokes' => $strokes,
            ],
        );

        $this->scoreInputs[$userId] = (string) $strokes;
        $this->resetErrorBag('scoreInputs.'.$userId);
        $this->loadPlaySession($this->playSession->fresh());
        $this->refreshScoreInputs();
    }

    public function previousHole(CurrentPlayerResolver $resolver): void
    {
        abort_unless($this->canUseGame($resolver), 403);

        if ($this->currentHoleIndex() <= 1) {
            return;
        }

        $this->playSession->forceFill([
            'current_hole_index' => $this->currentHoleIndex() - 1,
        ])->save();

        $this->loadPlaySession($this->playSession->fresh());
        $this->refreshScoreInputs();
    }

    public function nextHole(CurrentPlayerResolver $resolver): void
    {
        abort_unless($this->canUseGame($resolver), 403);

        $rows = $this->scoreRows();

        if (! $this->canAdvance($rows)) {
            $this->dispatch('notify', message: __('ui.game.complete_scores_first'), type: 'error');

            return;
        }

        if ($this->currentHoleIndex() >= $this->maxPlayableHoleIndex()) {
            return;
        }

        $this->savePendingScoreInputs($rows);

        $this->playSession->forceFill([
            'current_hole_index' => $this->currentHoleIndex() + 1,
        ])->save();

        $this->loadPlaySession($this->playSession->fresh());
        $this->refreshScoreInputs();
    }

    public function render(CurrentPlayerResolver $resolver)
    {
        abort_unless($this->canUseGame($resolver), 403);

        $this->loadPlaySession($this->playSession->fresh());
        $rows = $this->scoreRows();
        $this->refreshScoreInputs($rows);

        return view('livewire.play-session-game-page', [
            'rows' => $rows,
            'playersMissingLayouts' => $this->playersMissingLayouts(),
            'currentHoleIndex' => $this->currentHoleIndex(),
            'maxPlayableHoleIndex' => $this->maxPlayableHoleIndex(),
            'canAdvance' => $this->canAdvance($rows),
            'canGoNext' => $rows->isNotEmpty() && $this->canAdvance($rows) && $this->currentHoleIndex() < $this->maxPlayableHoleIndex(),
            'canGoPrevious' => $this->currentHoleIndex() > 1,
        ])->layout('layouts.app', [
            'title' => __('ui.game.page_title', ['course' => $this->playSession->course->name]),
        ]);
    }

    protected function adjustScore(int $userId, int $delta, CurrentPlayerResolver $resolver): void
    {
        abort_unless($this->canUseGame($resolver), 403);

        $row = $this->scoreRowForUser($userId);

        abort_unless($row, 422);

        $currentValue = $this->scoreInputs[$userId] ?? null;
        $base = ctype_digit((string) $currentValue)
            ? (int) $currentValue
            : (int) ($row['strokes'] ?? $row['par'] ?? 0);

        $this->saveScore($userId, (string) min(99, max(1, $base + $delta)), $resolver);
    }

    protected function canUseGame(CurrentPlayerResolver $resolver): bool
    {
        $freshPlaySession = $this->playSession->fresh();

        if (! $freshPlaySession) {
            return false;
        }

        $this->loadPlaySession($freshPlaySession);

        $currentPlayer = $resolver->resolve(request());

        return $this->playSession->status === 'active'
            && $this->playSession->players->contains(function (User $player) use ($currentPlayer): bool {
                return $player->id === $currentPlayer?->id
                    && $player->pivot->status === 'joined';
            });
    }

    protected function scoreRowForUser(int $userId): ?array
    {
        return $this->scoreRows()
            ->first(fn (array $row): bool => $row['user_id'] === $userId);
    }

    protected function scoreRows(): Collection
    {
        $currentHoleIndex = $this->currentHoleIndex();
        $holesByLayout = $this->holesByLayout();
        $relativeScoresByUser = $this->relativeScoresByUser();
        $scoresByUserAndHoleIndex = $this->playSession->scores
            ->keyBy(fn ($score): string => $score->user_id.':'.$score->hole_index);

        return $this->joinedPlayers()
            ->filter(fn (User $player): bool => filled($player->pivot->selected_layout_id))
            ->map(function (User $player) use ($currentHoleIndex, $holesByLayout, $relativeScoresByUser, $scoresByUserAndHoleIndex): ?array {
                $layoutId = (int) $player->pivot->selected_layout_id;
                $hole = ($holesByLayout->get($layoutId) ?? collect())->get($currentHoleIndex - 1);

                if (! $hole) {
                    return null;
                }

                $score = $scoresByUserAndHoleIndex->get($player->id.':'.$currentHoleIndex);

                return [
                    'user_id' => $player->id,
                    'player_name' => $player->name,
                    'layout_name' => $hole->layout_name ?: __('ui.course.layout_fallback'),
                    'hole_id' => $hole->id,
                    'hole_label' => $hole->hole_label ?: (string) ($hole->number ?: $currentHoleIndex),
                    'par' => $hole->par,
                    'strokes' => $score?->strokes,
                    'relative_score' => $relativeScoresByUser->get($player->id, 0),
                ];
            })
            ->filter()
            ->values();
    }

    protected function relativeScoresByUser(): Collection
    {
        $holesByLayout = $this->holesByLayout();

        return $this->joinedPlayers()
            ->filter(fn (User $player): bool => filled($player->pivot->selected_layout_id))
            ->mapWithKeys(function (User $player) use ($holesByLayout): array {
                $layoutHoles = $holesByLayout->get((int) $player->pivot->selected_layout_id) ?? collect();
                $scores = $this->playSession->scores
                    ->where('user_id', $player->id);

                $relativeScore = $scores->sum(function ($score) use ($layoutHoles): int {
                    $hole = $layoutHoles->get($score->hole_index - 1);

                    if (! $hole || $hole->par === null) {
                        return 0;
                    }

                    return (int) $score->strokes - (int) $hole->par;
                });

                return [$player->id => $relativeScore];
            });
    }

    protected function joinedPlayers(): Collection
    {
        return $this->playSession->players
            ->filter(fn (User $player): bool => $player->pivot->status === 'joined')
            ->values();
    }

    protected function playersMissingLayouts(): Collection
    {
        return $this->joinedPlayers()
            ->filter(fn (User $player): bool => blank($player->pivot->selected_layout_id))
            ->pluck('name')
            ->values();
    }

    protected function holesByLayout(): Collection
    {
        return $this->playSession->course->holes
            ->groupBy(fn ($hole): int => (int) ($hole->layout_id ?: $hole->layout_order))
            ->map(fn (Collection $holes): Collection => $holes->values());
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

    protected function currentHoleIndex(): int
    {
        $currentHoleIndex = max(1, (int) ($this->playSession->current_hole_index ?: 1));
        $maxPlayableHoleIndex = $this->maxPlayableHoleIndex();

        return $maxPlayableHoleIndex > 0
            ? min($currentHoleIndex, $maxPlayableHoleIndex)
            : $currentHoleIndex;
    }

    protected function canAdvance(?Collection $rows = null): bool
    {
        $rows ??= $this->scoreRows();

        return $rows->isNotEmpty()
            && $rows->every(fn (array $row): bool => $this->scoreValueForRow($row) !== null);
    }

    protected function refreshScoreInputs(?Collection $rows = null): void
    {
        $rows ??= $this->scoreRows();

        $this->scoreInputs = $rows
            ->mapWithKeys(function (array $row): array {
                $scoreValue = $this->prefilledScoreValueForRow($row);

                return [$row['user_id'] => $scoreValue !== null ? (string) $scoreValue : ''];
            })
            ->all();
    }

    protected function savePendingScoreInputs(Collection $rows): void
    {
        $rows
            ->each(function (array $row): void {
                $scoreValue = $this->scoreValueForRow($row);

                if ($scoreValue === null) {
                    return;
                }

                $this->playSession->scores()->updateOrCreate(
                    [
                        'user_id' => $row['user_id'],
                        'hole_index' => $this->currentHoleIndex(),
                    ],
                    [
                        'hole_id' => $row['hole_id'],
                        'strokes' => $scoreValue,
                    ],
                );

                $this->scoreInputs[$row['user_id']] = (string) $scoreValue;
                $this->resetErrorBag('scoreInputs.'.$row['user_id']);
            });
    }

    protected function scoreValueForRow(array $row): ?int
    {
        if (array_key_exists($row['user_id'], $this->scoreInputs)) {
            $inputValue = trim((string) $this->scoreInputs[$row['user_id']]);

            if ($inputValue !== '') {
                return $this->validScoreValue($inputValue);
            }
        }

        return $this->prefilledScoreValueForRow($row);
    }

    protected function prefilledScoreValueForRow(array $row): ?int
    {
        if ($row['strokes'] !== null) {
            return $this->validScoreValue($row['strokes']);
        }

        return $this->validScoreValue($row['par']);
    }

    protected function validScoreValue(mixed $value): ?int
    {
        $value = trim((string) $value);

        if ($value === '' || ! ctype_digit($value)) {
            return null;
        }

        $scoreValue = (int) $value;

        return $scoreValue >= 1 && $scoreValue <= 99 ? $scoreValue : null;
    }

    protected function loadPlaySession(PlaySession $playSession): void
    {
        $this->playSession = $playSession->load([
            'course.holes',
            'players' => fn ($query) => $query->orderBy('name'),
            'scores.hole',
        ]);
    }
}
