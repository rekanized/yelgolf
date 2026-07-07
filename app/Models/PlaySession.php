<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlaySession extends Model
{
    protected $fillable = [
        'course_id',
        'host_id',
        'host_session_key',
        'host_name',
        'host_layout_id',
        'status',
        'started_at',
        'ended_at',
        'current_hole_index',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'current_hole_index' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['status', 'invited_at', 'joined_at', 'selected_layout_id'])
            ->withTimestamps();
    }

    public function scores(): HasMany
    {
        return $this->hasMany(PlaySessionScore::class);
    }

    public function hasAnonymousHostParticipant(): bool
    {
        return $this->host_id === null && filled($this->host_session_key);
    }

    public function participantCount(): int
    {
        return $this->players->count() + ($this->hasAnonymousHostParticipant() ? 1 : 0);
    }

    public function participantRoster(): Collection
    {
        $roster = collect();

        if ($this->hasAnonymousHostParticipant()) {
            $roster->push((object) [
                'key' => 'host',
                'name' => $this->host_name ?: __('ui.session.host_fallback'),
                'status' => 'joined',
                'selected_layout_id' => $this->host_layout_id,
                'is_anonymous_host' => true,
            ]);
        }

        return $roster->merge(
            $this->players->map(static fn (User $player): object => (object) [
                'key' => 'user-'.$player->id,
                'name' => $player->name,
                'status' => $player->pivot->status,
                'selected_layout_id' => $player->pivot->selected_layout_id,
                'is_anonymous_host' => false,
            ])
        );
    }
}
