<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CurrentPlayerResolver;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request, CurrentPlayerResolver $resolver): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            report($exception);

            return $this->redirectToLoginWithGoogleError();
        }

        $email = Str::lower(trim((string) $googleUser->getEmail()));
        $googleId = Str::limit(trim((string) $googleUser->getId()), 255, '');

        if (! filled($email)) {
            return redirect()
                ->route('login')
                ->withErrors(['google' => __('ui.player_auth.missing_google_email')]);
        }

        if (strlen($email) > 255 || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! filled($googleId) || ! $this->googleEmailIsVerified($googleUser->getRaw())) {
            return $this->redirectToLoginWithGoogleError();
        }

        $user = User::query()
            ->where('google_id', $googleId)
            ->first();

        $user ??= User::query()->firstOrNew(['email' => $email]);

        if ($user->exists && ! hash_equals($user->email, $email)) {
            $emailBelongsToAnotherUser = User::query()
                ->where('email', $email)
                ->whereKeyNot($user->id)
                ->exists();

            if ($emailBelongsToAnotherUser) {
                return $this->redirectToLoginWithGoogleError();
            }
        }

        $isConfiguredAdmin = in_array($email, config('services.google.admin_emails', []), true);

        if (! $user->exists) {
            $user->password = Hash::make(Str::password(40));
            $user->role = $isConfiguredAdmin ? User::ROLE_ADMIN : User::ROLE_PLAYER;
        } elseif ($isConfiguredAdmin && ! $user->isAdmin()) {
            $user->role = User::ROLE_ADMIN;
        }

        try {
            $user->forceFill([
                'name' => Str::limit((string) ($googleUser->getName() ?: $googleUser->getNickname() ?: Str::before($email, '@')), 255, ''),
                'email' => $email,
                'google_id' => $googleId,
                'google_avatar' => $this->validatedAvatarUrl($googleUser->getAvatar()),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        } catch (QueryException $exception) {
            report($exception);

            return $this->redirectToLoginWithGoogleError();
        }

        Auth::login($user);
        $resolver->setCurrentPlayer($user->id, $request);
        $request->session()->regenerate();

        return redirect()->intended(url('/').'#course-list');
    }

    private function googleEmailIsVerified(array $raw): bool
    {
        $verified = $raw['email_verified'] ?? $raw['verified_email'] ?? false;

        return filter_var($verified, FILTER_VALIDATE_BOOLEAN);
    }

    private function validatedAvatarUrl(?string $url): ?string
    {
        if (! filled($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        return Str::limit($url, 255, '');
    }

    private function redirectToLoginWithGoogleError(): RedirectResponse
    {
        return redirect()
            ->route('login')
            ->withErrors(['google' => __('ui.player_auth.invalid_google_account')]);
    }
}
