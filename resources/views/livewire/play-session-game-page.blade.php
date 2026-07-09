<div class="sports-page">
    <main class="sports-main course-show game-screen">
        <section class="sports-panel play-session-panel game-panel" wire:poll.3s>
            <div class="sports-panel__heading sports-panel__heading--stacked game-header">
                <div>
                    <p class="eyebrow">{{ __('ui.game.eyebrow') }}</p>
                    <h1>{{ __('ui.game.hole_title', ['number' => $currentHoleIndex]) }}</h1>
                    <p class="hero-location">{{ $playSession->course->name }} · {{ __('ui.game.hole_progress', ['current' => $currentHoleIndex, 'total' => max(1, $maxPlayableHoleIndex)]) }}</p>
                </div>

                <div class="course-admin-footer__actions">
                    <a class="button button-secondary" href="{{ route('sessions.show', $playSession) }}">{{ __('ui.game.back_to_session') }}</a>
                </div>
            </div>

            <div class="game-body">
                @if ($playersMissingLayouts->isNotEmpty())
                    <div class="game-notice">
                        <strong>{{ __('ui.game.skipped_players_title') }}</strong>
                        <p>{{ __('ui.game.skipped_players_copy', ['players' => $playersMissingLayouts->join(', ')]) }}</p>
                    </div>
                @endif

                @if ($rows->isEmpty())
                    <div class="sports-empty game-empty">
                        <p class="eyebrow">{{ __('ui.game.empty_eyebrow') }}</p>
                        <h2>{{ __('ui.game.empty_title') }}</h2>
                        <p>{{ __('ui.game.empty_copy') }}</p>
                        <div class="actions">
                            <a class="button button-primary" href="{{ route('sessions.show', $playSession) }}">{{ __('ui.game.back_to_session') }}</a>
                        </div>
                    </div>
                @else
                    <div class="game-score-list">
                        @foreach ($rows as $row)
                            <article class="game-score-card" wire:key="score-row-{{ $row['user_id'] }}-{{ $row['hole_id'] }}">
                                <div class="game-score-card__player">
                                    <strong>{{ $row['player_name'] }}</strong>
                                    <span>{{ $row['layout_name'] }} · {{ __('ui.game.hole_label', ['label' => $row['hole_label']]) }}</span>
                                    <small>{{ __('ui.game.par_label', ['par' => $row['par'] ?? __('ui.course.na')]) }}</small>
                                </div>

                                <div @class([
                                    'game-score-card__points',
                                    'game-score-card__points--positive' => $row['relative_score'] > 0,
                                    'game-score-card__points--negative' => $row['relative_score'] < 0,
                                ])>
                                    {{ __('ui.game.relative_score_summary', ['score' => sprintf('%+d', $row['relative_score'])]) }}
                                </div>

                                <div class="game-score-card__controls">
                                    <button class="score-stepper" type="button" wire:click="decrementScore({{ $row['user_id'] }})" aria-label="{{ __('ui.game.decrement_score', ['name' => $row['player_name']]) }}">
                                        <span aria-hidden="true">-</span>
                                    </button>
                                    <input
                                        class="sports-search__input game-score-card__input"
                                        type="number"
                                        min="1"
                                        max="99"
                                        inputmode="numeric"
                                        wire:model="scoreInputs.{{ $row['user_id'] }}"
                                        wire:change="saveScore({{ $row['user_id'] }}, $event.target.value)"
                                        aria-label="{{ __('ui.game.score_input_label', ['name' => $row['player_name']]) }}"
                                    >
                                    <button class="score-stepper" type="button" wire:click="incrementScore({{ $row['user_id'] }})" aria-label="{{ __('ui.game.increment_score', ['name' => $row['player_name']]) }}">
                                        <span aria-hidden="true">+</span>
                                    </button>
                                </div>

                                @error('scoreInputs.'.$row['user_id'])
                                    <p class="error-text">{{ $message }}</p>
                                @enderror
                            </article>
                        @endforeach
                    </div>
                @endif

                @unless ($canAdvance || $rows->isEmpty())
                    <p class="panel-note">{{ __('ui.game.next_blocked_note') }}</p>
                @endunless
            </div>

            <div class="game-nav">
                <button class="button button-secondary" type="button" wire:click="previousHole" @disabled(! $canGoPrevious) aria-disabled="{{ $canGoPrevious ? 'false' : 'true' }}">
                    {{ __('ui.game.previous_hole') }}
                </button>
                <button class="button button-primary" type="button" wire:click="nextHole" @disabled(! $canGoNext) aria-disabled="{{ $canGoNext ? 'false' : 'true' }}">
                    {{ __('ui.game.next_hole') }}
                </button>
            </div>
        </section>
    </main>
</div>
