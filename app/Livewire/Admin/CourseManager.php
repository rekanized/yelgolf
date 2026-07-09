<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\EnsuresAdminAccess;
use App\Models\Course;
use App\Services\UDiscCourseImporter;
use InvalidArgumentException;
use Livewire\Component;
use Throwable;

class CourseManager extends Component
{
    use EnsuresAdminAccess;

    public string $udiscUrl = '';

    public function mount(): void
    {
        $this->ensureAdminAccess();
    }

    public function importCourse(UDiscCourseImporter $importer): void
    {
        $this->ensureAdminAccess();

        $validated = $this->validate([
            'udiscUrl' => ['required', 'url', 'max:2048'],
        ]);

        try {
            $course = $importer->import($validated['udiscUrl']);
        } catch (InvalidArgumentException $exception) {
            $this->addError('udiscUrl', $exception->getMessage());
            $this->dispatch('notify', message: $exception->getMessage(), type: 'error');

            return;
        } catch (Throwable $exception) {
            report($exception);

            $this->addError('udiscUrl', __('ui.admin.import_failed'));
            $this->dispatch('notify', message: __('ui.admin.import_failed'), type: 'error');

            return;
        }

        $this->reset('udiscUrl');
        $this->dispatch('notify', message: __('ui.admin.imported_successfully', ['name' => $course->name]), type: 'success');
    }

    public function updateCourse(int $courseId, UDiscCourseImporter $importer): void
    {
        $this->ensureAdminAccess();

        $course = Course::query()->findOrFail($courseId);

        try {
            $updatedCourse = $importer->import($course->udisc_url);
        } catch (Throwable $exception) {
            report($exception);

            $this->dispatch('notify', message: __('ui.admin.update_failed', ['name' => $course->name]), type: 'error');

            return;
        }

        $this->dispatch('notify', message: __('ui.admin.updated_successfully', ['name' => $updatedCourse->name]), type: 'success');
    }

    public function render()
    {
        $this->ensureAdminAccess();

        return view('livewire.admin.course-manager', [
            'courses' => Course::query()->with('holes')->orderBy('name')->get(),
        ])->layout('layouts.app', ['title' => __('ui.admin.page_title')]);
    }
}
