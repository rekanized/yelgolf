<?php

namespace App\Services;

use App\Models\Course;
use App\Models\PlaySession;
use App\Models\User;
use Illuminate\Http\Request;

class PlaySessionStarter
{
    public function start(Course $course, Request $request, ?User $currentPlayer = null): PlaySession
    {
        $session = PlaySession::query()->firstOrCreate(
            [
                'course_id' => $course->id,
                'host_id' => $currentPlayer?->id,
                'host_session_key' => $request->session()->getId(),
                'status' => 'active',
            ],
            [
                'host_name' => $currentPlayer?->name ?? __('ui.session.host_fallback'),
                'started_at' => now(),
            ],
        );

        if ($currentPlayer) {
            $session->players()->syncWithoutDetaching([
                $currentPlayer->id => [
                    'status' => 'joined',
                    'invited_at' => now(),
                    'joined_at' => now(),
                ],
            ]);
        }

        $hostedSessionIds = collect($request->session()->get('hosted_play_session_ids', []))
            ->push($session->id)
            ->unique()
            ->values()
            ->all();

        $request->session()->put('hosted_play_session_ids', $hostedSessionIds);

        return $session;
    }
}