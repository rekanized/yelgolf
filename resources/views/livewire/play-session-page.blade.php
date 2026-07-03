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
        <section class="sports-panel play-session-panel" wire:poll.15s>
            <div class="sports-panel__heading sports-panel__heading--stacked">
                <div>
                    <p class="eyebrow">{{ __('ui.session.eyebrow') }}</p>
                    <h1>{{ $playSession->course->name }}</h1>
                    <p class="hero-location">{{ __('ui.session.hosting_as', ['name' => $playSession->host?->name ?? $playSession->host_name ?? __('ui.session.host_fallback')]) }} {{ __('ui.session.started_at', ['time' => $playSession->started_at?->diffForHumans() ?? __('ui.course.na')]) }}</p>
                </div>

                <div class="course-admin-footer__actions">
                    <a class="button button-secondary" href="{{ route('courses.show', $playSession->course) }}">{{ __('ui.session.back_to_course') }}</a>
                    <div class="badge">{{ trans_choice('ui.session.player_count', $playSession->participantCount(), ['count' => $playSession->participantCount()]) }}</div>
                </div>
            </div>

            @if (! $isParticipant)
                <p class="muted">{{ __('ui.session.not_participant') }}</p>
            @else
                <div class="play-session-card">
                    @if ($isHost)
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
                                        @endphp
                                        <strong>{{ $player->name }}</strong>
                                        <label class="sports-search__label" for="layout-select-{{ $playSession->id }}-{{ $player->key }}">{{ __('ui.session.layout_label') }}</label>
                                        <select
                                            id="layout-select-{{ $playSession->id }}-{{ $player->key }}"
                                            class="sports-search__input"
                                            wire:change="updateParticipantLayout('{{ $player->key }}', $event.target.value)"
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
    </main>
</div>