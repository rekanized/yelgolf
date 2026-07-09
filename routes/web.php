<?php

use App\Http\Controllers\GoogleAuthController;
use App\Livewire\Admin\CourseManager;
use App\Livewire\Admin\UserManager;
use App\Livewire\PlaySessionGamePage;
use App\Livewire\PlaySessionPage;
use App\Livewire\UserLoginForm;
use App\Models\Course;
use App\Models\PlaySession;
use App\Services\CurrentPlayerResolver;
use App\Services\PlaySessionStarter;
use App\Services\UDiscCourseImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    $validated = $request->validate([
        'q' => ['nullable', 'string', 'max:100'],
    ]);
    $searchQuery = trim((string) ($validated['q'] ?? ''));

    return view('welcome', [
        'courses' => Course::query()
            ->when($searchQuery !== '', function ($query) use ($searchQuery) {
                $query->where(function ($courseQuery) use ($searchQuery) {
                    $courseQuery
                        ->where('name', 'like', '%'.$searchQuery.'%')
                        ->orWhere('location_name', 'like', '%'.$searchQuery.'%')
                        ->orWhere('description', 'like', '%'.$searchQuery.'%');
                });
            })
            ->orderBy('name')
            ->get(),
        'searchQuery' => $searchQuery,
    ]);
})->name('home');

Route::get('/settings', function () {
    return view('settings.edit');
})->middleware('admin.auth')->name('settings.edit');

Route::post('/preferences', function (Request $request) {
    $availableLocales = array_keys(config('yelgolf.locales', []));
    $availableThemes = array_keys(config('yelgolf.themes', []));

    $validated = $request->validate([
        'locale' => ['required', 'string', 'in:'.implode(',', $availableLocales)],
        'theme' => ['required', 'string', 'in:'.implode(',', $availableThemes)],
        'redirect_to' => ['nullable', 'url', 'max:2048'],
    ]);

    $request->session()->put('locale', $validated['locale']);
    $request->session()->put('theme', $validated['theme']);

    $redirectTo = (string) ($validated['redirect_to'] ?? '');
    $targetUrl = yelgolf_same_origin_url($redirectTo) ? $redirectTo : url('/');

    return redirect()->to($targetUrl)
        ->withCookie(cookie()->forever(config('yelgolf.locale_cookie', 'yelgolf_locale'), $validated['locale']))
        ->withCookie(cookie()->forever(config('yelgolf.theme_cookie', 'yelgolf_theme'), $validated['theme']));
})->middleware('admin.auth')->name('preferences.update');

Route::get('/courses/{course:slug}', function (Course $course, UDiscCourseImporter $importer) {
    if (
        empty($course->photos)
        || $course->target_type === null
        || $course->holes()->doesntExist()
    ) {
        try {
            $course = $importer->import($course->udisc_url)->fresh();
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    $course->load('holes');

    return view('courses.show', [
        'course' => $course,
    ]);
})->name('courses.show');

Route::post('/courses/{course:slug}/sessions', function (Course $course, PlaySessionStarter $starter, CurrentPlayerResolver $resolver) {
    $currentPlayer = $resolver->resolve(request());

    abort_unless($currentPlayer, 403);

    $session = $starter->start($course, request(), $currentPlayer);

    return redirect()->route('sessions.show', $session);
})->name('sessions.store');

Route::get('/sessions', function (Request $request, CurrentPlayerResolver $resolver) {
    $currentPlayer = $resolver->resolve($request);

    return view('sessions.index', [
        'sessions' => PlaySession::query()
            ->with([
                'course',
                'host',
                'players' => fn ($query) => $query->orderBy('name'),
            ])
            ->when(
                $currentPlayer,
                fn ($query) => $query->where(function ($sessionQuery) use ($currentPlayer) {
                    $sessionQuery
                        ->where('host_id', $currentPlayer->id)
                        ->orWhereHas('players', function ($playerQuery) use ($currentPlayer) {
                            $playerQuery
                                ->where('users.id', $currentPlayer->id)
                                ->where('play_session_user.status', 'joined');
                        });
                }),
                fn ($query) => $query->whereRaw('0 = 1'),
            )
            ->orderByRaw("case when status = 'active' then 0 else 1 end")
            ->orderByDesc('started_at')
            ->get(),
    ]);
})->name('sessions.index');

Route::get('/sessions/{playSession}/game', PlaySessionGamePage::class)->name('sessions.game');

Route::get('/sessions/{playSession}', PlaySessionPage::class)->name('sessions.show');

Route::get('/login', UserLoginForm::class)->name('login');
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('home');
})->name('logout');

Route::prefix('admin')->group(function (): void {
    Route::get('/', CourseManager::class)
        ->middleware('admin.auth')
        ->name('admin.dashboard');

    Route::get('/users', UserManager::class)
        ->middleware('admin.auth')
        ->name('admin.users');
});

if (! function_exists('yelgolf_same_origin_url')) {
    function yelgolf_same_origin_url(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        $applicationUrl = parse_url(url('/'));
        $candidateUrl = parse_url($url);

        if (! is_array($applicationUrl) || ! is_array($candidateUrl)) {
            return false;
        }

        $applicationScheme = strtolower((string) ($applicationUrl['scheme'] ?? ''));
        $candidateScheme = strtolower((string) ($candidateUrl['scheme'] ?? ''));
        $applicationHost = strtolower((string) ($applicationUrl['host'] ?? ''));
        $candidateHost = strtolower((string) ($candidateUrl['host'] ?? ''));
        $applicationPort = (int) ($applicationUrl['port'] ?? ($applicationScheme === 'https' ? 443 : 80));
        $candidatePort = (int) ($candidateUrl['port'] ?? ($candidateScheme === 'https' ? 443 : 80));

        return $applicationScheme === $candidateScheme
            && $applicationHost !== ''
            && $applicationHost === $candidateHost
            && $applicationPort === $candidatePort;
    }
}
