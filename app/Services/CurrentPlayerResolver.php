<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class CurrentPlayerResolver
{
    public function availablePlayers(): Collection
    {
        if (! Schema::hasTable('users')) {
            return collect();
        }

        return User::query()
            ->orderBy('name')
            ->get();
    }

    public function resolve(?Request $request = null): ?User
    {
        if (! Schema::hasTable('users')) {
            return null;
        }

        $request ??= request();

        $sessionPlayerId = $request->hasSession()
            ? $request->session()->get('current_player_id')
            : null;

        if (! filled($sessionPlayerId) && app()->bound('session')) {
            $sessionPlayerId = session()->get('current_player_id');
        }

        $currentPlayer = filled($sessionPlayerId)
            ? User::query()->find($sessionPlayerId)
            : null;

        return $currentPlayer;
    }

    public function setCurrentPlayer(int|string|null $playerId, ?Request $request = null): ?User
    {
        $request ??= request();

        if (! Schema::hasTable('users')) {
            return null;
        }

        $validatedId = filter_var($playerId, FILTER_VALIDATE_INT);

        if ($validatedId === false) {
            if ($request->hasSession()) {
                $request->session()->forget('current_player_id');
            }

            if (app()->bound('session')) {
                session()->forget('current_player_id');
            }

            return null;
        }

        $player = User::query()->find($validatedId);

        if (! $player) {
            if ($request->hasSession()) {
                $request->session()->forget('current_player_id');
            }

            if (app()->bound('session')) {
                session()->forget('current_player_id');
            }

            return null;
        }

        if ($request->hasSession()) {
            $request->session()->put('current_player_id', $player->id);
        }

        if (app()->bound('session')) {
            session()->put('current_player_id', $player->id);
        }

        return $player;
    }
}
