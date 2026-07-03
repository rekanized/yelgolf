<?php

namespace App\Http\Middleware;

use App\Services\CurrentPlayerResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $currentPlayer = app(CurrentPlayerResolver::class)->resolve($request);

        if (! $currentPlayer) {
            return redirect()->guest(route('login'));
        }

        if (! $currentPlayer->isAdmin()) {
            abort(403);
        }

        return $next($request);
    }
}