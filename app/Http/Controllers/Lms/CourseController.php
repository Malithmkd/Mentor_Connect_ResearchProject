<?php

namespace App\Http\Controllers\Lms;

use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\MentorshipRelationship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CourseController (Mentor only)
 * Full CRUD for courses within an accepted mentorship relationship.
 * Publishing a course automatically enrolls the paired freelancer.
 */
class CourseController extends Controller
{
    /**
     * List all courses for a given relationship.
     */
    public function index(MentorshipRelationship $relationship): View
    {
        abort_if(auth()->id() !== $relationship->mentor_id, 403);

        $courses = $relationship->courses()->with('enrollments')->latest()->get();

        return view('mentor.lms.courses.index', compact('relationship', 'courses'));
    }

    /**
     * Show the create course form.
     */
    public function create(MentorshipRelationship $relationship): View
    {
        abort_if(auth()->id() !== $relationship->mentor_id, 403);
        abort_if(!$relationship->isAccepted(), 422);

        return view('mentor.lms.courses.create', compact('relationship'));
    }

    /**
     * Store a new course (starts as draft).
     */
    public function store(Request $request, MentorshipRelationship $relationship): RedirectResponse
    {
        abort_if(auth()->id() !== $relationship->mentor_id, 403);
        abort_if(!$relationship->isAccepted(), 422);

        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $course = $relationship->courses()->create([
            'mentor_id'   => auth()->id(),
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status'      => CourseStatus::DRAFT,
        ]);

        return redirect()
            ->route('mentor.lms.courses.edit', $course)
            ->with('success', 'Course created! Add modules and lessons below.');
    }

    /**
     * Show the course builder (edit form with modules + lessons).
     */
    public function edit(Course $course): View
    {
        abort_if(auth()->id() !== $course->mentor_id, 403);

        $course->load(['modules.lessons', 'relationship.freelancer']);

        return view('mentor.lms.courses.edit', compact('course'));
    }

    /**
     * Update course title / description.
     */
    public function update(Request $request, Course $course): RedirectResponse
    {
        abort_if(auth()->id() !== $course->mentor_id, 403);

        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $course->update($validated);

        return back()->with('success', 'Course updated.');
    }

    /**
     * Publish the course and auto-enroll the freelancer.
     */
    public function publish(Course $course): RedirectResponse
    {
        abort_if(auth()->id() !== $course->mentor_id, 403);
        abort_if($course->isPublished(), 422, 'Course is already published.');

        // Must have at least one lesson
        if ($course->lessons()->count() === 0) {
            return back()->with('error', 'Add at least one lesson before publishing.');
        }

        $course->update(['status' => CourseStatus::PUBLISHED]);

        // Auto-enroll the freelancer from the relationship
        $relationship = $course->relationship;

        Enrollment::firstOrCreate(
            [
                'course_id'      => $course->id,
                'freelancer_id'  => $relationship->freelancer_id,
            ],
            [
                'relationship_id' => $relationship->id,
                'enrolled_at'     => now(),
            ]
        );

        return back()->with('success', 'Course published! The freelancer has been enrolled and can now access it.');
    }

    /**
     * Delete a draft course.
     */
    public function destroy(Course $course): RedirectResponse
    {
        abort_if(auth()->id() !== $course->mentor_id, 403);

        $relationship = $course->relationship;
        $course->delete();

        return redirect()
            ->route('mentor.lms.courses.index', $relationship)
            ->with('success', 'Course deleted.');
    }

    /**
     * View a published course with the freelancer's progress (mentor read-only).
     */
    public function show(Course $course): View
    {
        abort_if(auth()->id() !== $course->mentor_id, 403);

        $course->load(['modules.lessons', 'relationship.freelancer', 'enrollments.lessonProgress']);

        $enrollment = $course->enrollments()->first();

        return view('mentor.lms.courses.show', compact('course', 'enrollment'));
    }
}
