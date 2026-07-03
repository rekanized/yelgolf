<?php

namespace App\Services;

use App\Models\Course;
use App\Models\PlaySession;
use App\Models\User;
use Illuminate\Http\Request;

class PlaySessionStarter
{
    public function start(Course $course, Request $request, User $currentPlayer): PlaySession
    {
        $session = PlaySession::query()->firstOrCreate(
            [
                'course_id' => $course->id,
                'host_id' => $currentPlayer->id,
                'host_session_key' => $request->session()->getId(),
                'status' => 'active',
            ],
            [
                'host_name' => $currentPlayer->name,
                'started_at' => now(),
            ],
        );

        $session->players()->syncWithoutDetaching([
            $currentPlayer->id => [
                'status' => 'joined',
                'invited_at' => now(),
                'joined_at' => now(),
            ],
        ]);

        return $session;
    }
}