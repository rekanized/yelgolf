<?php

use App\Livewire\PlaySessionPage;
use App\Livewire\UserLoginForm;
use App\Livewire\Admin\CourseManager;
use App\Models\Course;
use App\Models\PlaySession;
use App\Services\CurrentPlayerResolver;
use App\Services\PlaySessionStarter;
use App\Services\UDiscCourseImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', function (Request $request, CurrentPlayerResolver $resolver) {
    $searchQuery = trim((string) $request->query('q', ''));
    $currentPlayer = $resolver->resolve($request);

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
        'activeSessions' => PlaySession::query()
            ->with([
                'course',
                'host',
                'players' => fn ($query) => $query->orderBy('name'),
            ])
            ->where('status', 'active')
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
            ->orderByDesc('started_at')
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
        'redirect_to' => ['nullable', 'url'],
    ]);

    $request->session()->put('locale', $validated['locale']);
    $request->session()->put('theme', $validated['theme']);

    $redirectTo = (string) ($validated['redirect_to'] ?? '');
    $targetUrl = Str::startsWith($redirectTo, url('/')) ? $redirectTo : url('/');

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
        } catch (\Throwable $exception) {
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

Route::get('/sessions/{playSession}', PlaySessionPage::class)->name('sessions.show');

Route::get('/login', UserLoginForm::class)->name('login');

Route::post('/logout', function () {
    request()->session()->forget('current_player_id');
    request()->session()->forget('admin_authenticated');
    request()->session()->regenerate();

    return redirect()->route('home');
})->name('logout');

Route::prefix('admin')->group(function (): void {
    Route::get('/', CourseManager::class)
        ->middleware('admin.auth')
        ->name('admin.dashboard');
});
