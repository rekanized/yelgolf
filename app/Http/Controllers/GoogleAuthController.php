<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CurrentPlayerResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request, CurrentPlayerResolver $resolver): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();
        $email = $googleUser->getEmail();

        if (! filled($email)) {
            return redirect()
                ->route('login')
                ->withErrors(['google' => __('ui.player_auth.missing_google_email')]);
        }

        $user = User::query()->firstOrNew(['email' => $email]);
        $isConfiguredAdmin = in_array(strtolower($email), config('services.google.admin_emails', []), true);

        if (! $user->exists) {
            $user->password = Hash::make(Str::password(40));
            $user->role = $isConfiguredAdmin ? User::ROLE_ADMIN : User::ROLE_PLAYER;
        } elseif ($isConfiguredAdmin && ! $user->isAdmin()) {
            $user->role = User::ROLE_ADMIN;
        }

        $user->forceFill([
            'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: Str::before($email, '@'),
            'google_id' => $googleUser->getId(),
            'google_avatar' => $googleUser->getAvatar(),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        Auth::login($user);
        $resolver->setCurrentPlayer($user->id, $request);
        $request->session()->regenerate();
        $request->session()->forget('admin_authenticated');

        return redirect()->intended(url('/').'#course-list');
    }
}
