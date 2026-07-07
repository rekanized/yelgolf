<div class="sports-page">
    <header class="sports-header">
        <div class="sports-topbar">
            <a class="sports-brand" href="{{ url('/') }}">
                <span class="sports-brand__crest">YG</span>
                <span>
                    <strong>Yelgolf</strong>
                    <span class="sports-brand__sub">{{ __('ui.brand.subtitle') }}</span>
                </span>
            </a>

            @livewire('player-console')
        </div>

        @include('partials.sports-nav')
    </header>

    <main class="sports-main course-show">
        <section class="sports-panel play-session-panel" @if($isActive) wire:poll.15s @endif>
            <div class="sports-panel__heading sports-panel__heading--stacked">
                <div>
                    <p class="eyebrow">{{ __('ui.session.eyebrow') }}</p>
                    <h1>{{ $playSession->course->name }}</h1>
                    <p class="hero-location">{{ __('ui.session.hosting_as', ['name' => $playSession->host?->name ?? $playSession->host_name ?? __('ui.session.host_fallback')]) }} {{ __('ui.session.started_at', ['time' => $playSession->started_at?->diffForHumans() ?? __('ui.course.na')]) }}</p>
                </div>

                <div class="course-admin-footer__actions">
                    <a class="button button-secondary" href="{{ route('courses.show', $playSession->course) }}">{{ __('ui.session.back_to_course') }}</a>
                    @if ($isParticipant && $isActive)
                        <a class="button button-primary" href="{{ route('sessions.game', $playSession) }}">{{ __('ui.game.open_button') }}</a>
                    @endif
                    @if ($isHost && $isActive)
                        <button class="button button-danger" type="button" wire:click="openEndSessionModal">
                            {{ __('ui.session.end_button') }}
                        </button>
                    @endif
                    @unless ($isActive)
                        <div class="badge">{{ __('ui.session.statuses.ended') }}</div>
                    @endunless
                    <div class="badge">{{ trans_choice('ui.session.player_count', $playSession->participantCount(), ['count' => $playSession->participantCount()]) }}</div>
                </div>
            </div>

            @if (! $isParticipant)
                <p class="muted">{{ __('ui.session.not_participant') }}</p>
            @else
                <div class="play-session-card">
                    @if ($isHost && $isActive)
                        <div class="play-session-form">
                            <div class="play-session-form__header">
                                <div>
                                    <p class="eyebrow eyebrow-light">{{ __('ui.session.invite_eyebrow') }}</p>
                                    <h2>{{ __('ui.session.invite_title') }}</h2>
                                </div>

                                @if (! $showInvitePicker)
                                    <button class="button button-primary" type="button" wire:click="openInvitePicker">{{ __('ui.session.invite_button') }}</button>
                                @endif
                            </div>

                            @if ($showInvitePicker)
                                <div class="play-session-form__picker">
                                    <label class="sports-search__label" for="invite-search-{{ $playSession->id }}">{{ __('ui.session.invite_label') }}</label>
                                    <input
                                        id="invite-search-{{ $playSession->id }}"
                                        class="sports-search__input"
                                        type="search"
                                        wire:model.live.debounce.250ms="inviteSearch"
                                        placeholder="{{ __('ui.session.invite_placeholder') }}"
                                        autocomplete="off"
                                    >

                                    @if ($selectedInvitees->isNotEmpty())
                                        <div class="invite-chip-list">
                                            @foreach ($selectedInvitees as $invitee)
                                                <button class="invite-chip" type="button" wire:click="removeInvitee({{ $invitee->id }})">
                                                    <span>{{ $invitee->name }}</span>
                                                    <span class="material-symbols-outlined" aria-hidden="true">close</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif

                                    @error('selectedInviteeIds') <p class="error-text">{{ $message }}</p> @enderror

                                    <div class="invite-search-list invite-search-list--dropdown">
                                        @if ($inviteOptions->isNotEmpty())
                                            @foreach ($inviteOptions as $option)
                                                <button class="invite-search-option" type="button" wire:click="addInvitee({{ $option->id }})">
                                                    <span>{{ $option->name }}</span>
                                                    <small>{{ $option->email }}</small>
                                                </button>
                                            @endforeach
                                        @else
                                            <p class="muted">
                                                {{ $inviteSearch !== '' ? __('ui.session.no_players_match') : __('ui.session.no_more_players') }}
                                            </p>
                                        @endif
                                    </div>

                                    <div class="actions">
                                        <button class="button button-secondary" type="button" wire:click="closeInvitePicker">{{ __('ui.session.cancel_invite') }}</button>
                                        <button class="button button-primary button-with-spinner" type="button" wire:click="invitePlayers" wire:loading.attr="disabled" wire:target="invitePlayers">
                                            <span wire:loading.remove wire:target="invitePlayers">{{ __('ui.session.invite_action') }}</span>
                                            <span class="button-spinner-wrap" wire:loading wire:target="invitePlayers">
                                                <span class="button-spinner" aria-hidden="true"></span>
                                                {{ __('ui.session.inviting') }}
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="play-session-members">
                        <h2>{{ __('ui.session.players_title') }}</h2>

                        <ul class="play-session-members__list">
                            @foreach ($playSession->participantRoster() as $player)
                                <li class="play-session-members__item">
                                    <div class="play-session-members__identity">
                                        @php
                                            $selectedLayout = $participantLayouts[$player->key] ?? '';
                                            $canEditLayout = $editableParticipantKeys->contains($player->key);
                                        @endphp
                                        <strong>{{ $player->name }}</strong>
                                        <label class="sports-search__label" for="layout-select-{{ $playSession->id }}-{{ $player->key }}">{{ __('ui.session.layout_label') }}</label>
                                        <select
                                            id="layout-select-{{ $playSession->id }}-{{ $player->key }}"
                                            class="sports-search__input"
                                            wire:change="updateParticipantLayout('{{ $player->key }}', $event.target.value)"
                                            @disabled(! $canEditLayout)
                                            aria-disabled="{{ $canEditLayout ? 'false' : 'true' }}"
                                        >
                                            <option value="" @selected($selectedLayout === '')>{{ __('ui.session.layout_none') }}</option>
                                            @foreach ($layoutOptions as $layoutOption)
                                                <option value="{{ $layoutOption['id'] }}" @selected($selectedLayout === (string) $layoutOption['id'])>{{ $layoutOption['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <span class="badge badge--subtle">{{ __('ui.session.statuses.'.$player->status) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </section>

        @if ($isParticipant && $scoreCharts->isNotEmpty())
            <section class="sports-panel play-session-panel game-chart-section">
                <div>
                    <p class="eyebrow eyebrow-light">{{ __('ui.game.chart_eyebrow') }}</p>
                    <h2>{{ __('ui.game.chart_title') }}</h2>
                    <p class="panel-note">{{ __('ui.game.chart_copy') }}</p>
                </div>

                <div class="game-chart-list">
                    @foreach ($scoreCharts as $chart)
                        <article class="game-chart-card" wire:key="session-score-chart-{{ $chart['id'] }}">
                            <div class="game-chart-card__header">
                                <strong>{{ $chart['player_name'] }}</strong>
                                <span>{{ $chart['layout_name'] }}</span>
                            </div>
                            <div class="game-chart-card__canvas">
                                <canvas
                                    data-score-chart
                                    data-chart-label="{{ $chart['player_name'] }}"
                                    data-chart-labels='@json($chart['labels'])'
                                    data-chart-values='@json($chart['values'])'
                                ></canvas>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </main>

    @if ($showEndSessionModal)
        <div class="modal-backdrop" role="presentation">
            <section
                class="modal-dialog modal-dialog--danger"
                role="dialog"
                aria-modal="true"
                aria-labelledby="end-session-title"
                aria-describedby="end-session-description"
            >
                <div class="modal-dialog__header">
                    <p class="eyebrow eyebrow-light">{{ __('ui.session.end_modal_eyebrow') }}</p>
                    <h2 id="end-session-title">{{ __('ui.session.end_modal_title') }}</h2>
                </div>

                <p id="end-session-description" class="modal-dialog__copy">
                    {{ __('ui.session.end_modal_copy', ['course' => $playSession->course->name]) }}
                </p>

                <div class="modal-dialog__actions">
                    <button class="button button-secondary" type="button" wire:click="closeEndSessionModal">
                        {{ __('ui.session.end_modal_cancel') }}
                    </button>
                    <button class="button button-danger button-with-spinner" type="button" wire:click="endSession" wire:loading.attr="disabled" wire:target="endSession">
                        <span wire:loading.remove wire:target="endSession">{{ __('ui.session.end_modal_confirm') }}</span>
                        <span class="button-spinner-wrap" wire:loading wire:target="endSession">
                            <span class="button-spinner" aria-hidden="true"></span>
                            {{ __('ui.session.ending') }}
                        </span>
                    </button>
                </div>
            </section>
        </div>
    @endif

    @if ($isParticipant)
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
        <script>
            (() => {
                if (window.yelgolfScoreChartsReady) {
                    window.yelgolfRenderScoreCharts?.();
                    return;
                }

                window.yelgolfScoreChartsReady = true;
                window.yelgolfScoreChartInstances = new Map();

                const parseJson = (value, fallback) => {
                    try {
                        return JSON.parse(value || '');
                    } catch (error) {
                        return fallback;
                    }
                };

                const relativeTick = (value) => {
                    if (value === 0) {
                        return 'E';
                    }

                    return value > 0 ? `+${value}` : `${value}`;
                };

                window.yelgolfDestroyScoreChart = (canvas) => {
                    const tracked = window.yelgolfScoreChartInstances.get(canvas);
                    const chart = tracked?.chart || window.Chart?.getChart?.(canvas);

                    chart?.destroy();
                    window.yelgolfScoreChartInstances.delete(canvas);
                };

                window.yelgolfDestroyScoreChartsInside = (root) => {
                    if (!root?.querySelectorAll) {
                        return;
                    }

                    if (root.matches?.('[data-score-chart]')) {
                        window.yelgolfDestroyScoreChart(root);
                    }

                    root.querySelectorAll('[data-score-chart]').forEach(window.yelgolfDestroyScoreChart);
                };

                window.yelgolfRenderScoreCharts = () => {
                    if (!window.Chart) {
                        return;
                    }

                    const styles = getComputedStyle(document.documentElement);
                    const accent = styles.getPropertyValue('--accent').trim() || '#79c2e8';
                    const textColor = styles.getPropertyValue('--ink-soft').trim() || '#97a0b7';
                    const gridColor = styles.getPropertyValue('--line').trim() || 'rgba(255, 255, 255, 0.1)';

                    window.yelgolfScoreChartInstances.forEach((tracked, canvas) => {
                        if (!canvas.isConnected) {
                            tracked.chart?.destroy();
                            window.yelgolfScoreChartInstances.delete(canvas);
                        }
                    });

                    document.querySelectorAll('[data-score-chart]').forEach((canvas) => {
                        const labels = parseJson(canvas.dataset.chartLabels, []);
                        const values = parseJson(canvas.dataset.chartValues, []);
                        const label = canvas.dataset.chartLabel || '';
                        const signature = JSON.stringify({ labels, values, label });
                        const existing = window.yelgolfScoreChartInstances.get(canvas);

                        if (existing?.signature === signature) {
                            return;
                        }

                        existing?.chart.destroy();
                        window.Chart.getChart?.(canvas)?.destroy();

                        const chart = new Chart(canvas, {
                            type: 'line',
                            data: {
                                labels,
                                datasets: [{
                                    label,
                                    data: values,
                                    borderColor: accent,
                                    backgroundColor: 'rgba(121, 194, 232, 0.14)',
                                    borderWidth: 2,
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                    tension: 0.25,
                                    spanGaps: false,
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false,
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: (context) => `${label}: ${relativeTick(context.parsed.y)}`,
                                        },
                                    },
                                },
                                scales: {
                                    x: {
                                        ticks: {
                                            color: textColor,
                                        },
                                        grid: {
                                            color: gridColor,
                                        },
                                    },
                                    y: {
                                        ticks: {
                                            color: textColor,
                                            callback: relativeTick,
                                        },
                                        grid: {
                                            color: gridColor,
                                        },
                                        title: {
                                            display: true,
                                            text: @js(__('ui.game.relative_score_axis')),
                                            color: textColor,
                                        },
                                    },
                                },
                            },
                        });

                        window.yelgolfScoreChartInstances.set(canvas, { chart, signature });
                    });
                };

                let renderFrame = null;
                const scheduleRender = () => {
                    window.cancelAnimationFrame(renderFrame);
                    renderFrame = window.requestAnimationFrame(window.yelgolfRenderScoreCharts);
                };

                document.addEventListener('DOMContentLoaded', scheduleRender);
                document.addEventListener('livewire:navigated', scheduleRender);

                const registerLivewireHooks = () => {
                    if (!window.Livewire || window.yelgolfScoreChartsLivewireHooksReady) {
                        return;
                    }

                    window.yelgolfScoreChartsLivewireHooksReady = true;

                    window.Livewire.hook('morph.updating', ({ el }) => {
                        window.yelgolfDestroyScoreChartsInside(el);
                    });

                    window.Livewire.hook('morph.removing', ({ el }) => {
                        window.yelgolfDestroyScoreChartsInside(el);
                    });

                    window.Livewire.hook('morphed', scheduleRender);
                    window.Livewire.hook('morph.added', scheduleRender);
                };

                registerLivewireHooks();
                document.addEventListener('livewire:init', registerLivewireHooks);

                if (document.body) {
                    new MutationObserver(scheduleRender).observe(document.body, {
                        childList: true,
                        subtree: true,
                        attributes: true,
                        attributeFilter: ['data-chart-labels', 'data-chart-values'],
                    });
                }

                scheduleRender();
            })();
        </script>
    @endif
</div>
