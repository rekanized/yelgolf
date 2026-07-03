<?php

use App\Livewire\Admin\CourseManager;
use App\Livewire\Admin\LoginForm;
use App\Models\Course;
use App\Services\UDiscCourseImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    $searchQuery = trim((string) $request->query('q', ''));

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
});

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

Route::redirect('/login', '/admin/login');

Route::prefix('admin')->group(function (): void {
    Route::get('/login', LoginForm::class)->name('admin.login');

    Route::get('/', CourseManager::class)
        ->middleware('admin.auth')
        ->name('admin.dashboard');

    Route::post('/logout', function () {
        request()->session()->forget('admin_authenticated');
        request()->session()->regenerateToken();

        return redirect()->route('admin.login');
    })->name('admin.logout');
});
