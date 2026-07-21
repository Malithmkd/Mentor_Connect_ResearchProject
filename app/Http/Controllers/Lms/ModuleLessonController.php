<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * ModuleLessonController (Mentor only)
 * Handles CRUD for modules and lessons within a course builder.
 * All routes redirect back to the course edit view so the mentor
 * stays in the builder after every action.
 */
class ModuleLessonController extends Controller
{
    /* ════════════════════════════════════
     *  MODULES
     * ════════════════════════════════════ */

    public function storeModule(Request $request, Course $course): RedirectResponse
    {
        abort_if(auth()->id() !== $course->mentor_id, 403);

        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        // Append module at the end
        $maxOrder = $course->modules()->max('sort_order') ?? -1;

        $course->modules()->create([
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? null,
            'sort_order'  => $maxOrder + 1,
        ]);

        return redirect()
            ->route('mentor.lms.courses.edit', $course)
            ->with('success', 'Module added.');
    }

    public function updateModule(Request $request, CourseModule $module): RedirectResponse
    {
        abort_if(auth()->id() !== $module->course->mentor_id, 403);

        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $module->update($validated);

        return redirect()
            ->route('mentor.lms.courses.edit', $module->course)
            ->with('success', 'Module updated.');
    }

    public function destroyModule(CourseModule $module): RedirectResponse
    {
        abort_if(auth()->id() !== $module->course->mentor_id, 403);

        $course = $module->course;
        $module->delete(); // cascades to lessons

        return redirect()
            ->route('mentor.lms.courses.edit', $course)
            ->with('success', 'Module deleted.');
    }

    /* ════════════════════════════════════
     *  LESSONS
     * ════════════════════════════════════ */

    public function storeLesson(Request $request, CourseModule $module): RedirectResponse
    {
        abort_if(auth()->id() !== $module->course->mentor_id, 403);

        $validated = $request->validate([
            'title'     => ['required', 'string', 'max:255'],
            'content'   => ['nullable', 'string'],
            'video_url' => ['nullable', 'url', 'max:500'],
        ]);

        $maxOrder = $module->lessons()->max('sort_order') ?? -1;

        $module->lessons()->create([
            'title'      => $validated['title'],
            'content'    => $validated['content'] ?? null,
            'video_url'  => $validated['video_url'] ?? null,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()
            ->route('mentor.lms.courses.edit', $module->course)
            ->with('success', 'Lesson added.');
    }

    public function updateLesson(Request $request, Lesson $lesson): RedirectResponse
    {
        abort_if(auth()->id() !== $lesson->module->course->mentor_id, 403);

        $validated = $request->validate([
            'title'     => ['required', 'string', 'max:255'],
            'content'   => ['nullable', 'string'],
            'video_url' => ['nullable', 'url', 'max:500'],
        ]);

        $lesson->update($validated);

        return redirect()
            ->route('mentor.lms.courses.edit', $lesson->module->course)
            ->with('success', 'Lesson updated.');
    }

    public function destroyLesson(Lesson $lesson): RedirectResponse
    {
        abort_if(auth()->id() !== $lesson->module->course->mentor_id, 403);

        $course = $lesson->module->course;
        $lesson->delete();

        return redirect()
            ->route('mentor.lms.courses.edit', $course)
            ->with('success', 'Lesson deleted.');
    }
}
