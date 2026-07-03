<div @class(['player-console', 'player-console--empty' => $pendingInvites->isEmpty()]) @if($pendingInvites->isEmpty()) hidden @endif wire:poll.15s>
    @if ($pendingInvites->isNotEmpty())
        <div class="player-console__invites">
            <div class="player-console__invites-head">
                <p class="eyebrow eyebrow-light">{{ __('ui.session.pending_invites') }}</p>
                <div class="badge">{{ trans_choice('ui.session.player_count', $pendingInvites->count(), ['count' => $pendingInvites->count()]) }}</div>
            </div>

            <div class="pending-invite-list">
                @foreach ($pendingInvites as $invitation)
                    <article class="pending-invite-card" wire:key="invite-{{ $invitation['session_id'] }}-{{ $invitation['invitee_id'] }}">
                        <div>
                            <h3>{{ $invitation['course_name'] }}</h3>
                            <p class="muted">{{ __('ui.session.invited_for', ['name' => $invitation['invitee_name']]) }}</p>
                            <p class="muted">{{ __('ui.session.invited_by', ['name' => $invitation['host_name']]) }}</p>
                        </div>

                        <button class="button button-primary" type="button" wire:click="joinSession({{ $invitation['session_id'] }})">
                            {{ __('ui.session.join') }}
                        </button>
                    </article>
                @endforeach
            </div>
        </div>
    @endif
</div>