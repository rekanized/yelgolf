<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaySessionScore extends Model
{
    protected $fillable = [
        'play_session_id',
        'user_id',
        'hole_id',
        'hole_index',
        'strokes',
    ];

    protected function casts(): array
    {
        return [
            'hole_index' => 'integer',
            'strokes' => 'integer',
        ];
    }

    public function playSession(): BelongsTo
    {
        return $this->belongsTo(PlaySession::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function hole(): BelongsTo
    {
        return $this->belongsTo(Hole::class);
    }
}
