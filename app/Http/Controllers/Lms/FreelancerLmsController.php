<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * FreelancerLmsController
 * Provides the freelancer's view of their enrolled courses, lesson viewer,
 * and lesson completion tracking.
 */
class FreelancerLmsController extends Controller
{
    /**
     * Dashboard: one panel per mentor, containing all their courses.
     * Multiple relationships with the same mentor are merged into one section.
     */
    public function index(): View
    {
        $freelancerId = auth()->id();

        // All accepted relationships (may be multiple per mentor from different gigs)
        $acceptedRelationships = \App\Models\MentorshipRelationship::with(['mentor', 'booking', 'courses'])
            ->forFreelancer($freelancerId)
            ->accepted()
            ->latest()
            ->get();

        // Pending relationships (still waiting for mentor to accept)
        $pendingRelationships = \App\Models\MentorshipRelationship::with(['mentor', 'booking'])
            ->forFreelancer($freelancerId)
            ->pending()
            ->latest()
            ->get();

        // All enrollments (courses that have been published and enrolled)
        $enrollments = Enrollment::with([
                'course.modules.lessons',
                'course.relationship.mentor',
                'lessonProgress',
            ])
            ->forFreelancer($freelancerId)
            ->latest()
            ->get();

        // ── Group accepted relationships by mentor ──────────────────────────
        // Each group = [ 'mentor' => User, 'relationships' => Collection, 'enrollments' => Collection ]
        $mentorGroups = $acceptedRelationships
            ->groupBy('mentor_id')
            ->map(function ($rels) use ($enrollments) {
                $mentor          = $rels->first()->mentor;
                $relationshipIds = $rels->pluck('id');

                // All enrollments whose course belongs to any of this mentor's relationships
                $mentorEnrollments = $enrollments->filter(
                    fn($e) => $relationshipIds->contains($e->course->relationship_id)
                )->values();

                return [
                    'mentor'        => $mentor,
                    'relationships' => $rels,          // for per-relationship actions (renew, badge)
                    'enrollments'   => $mentorEnrollments,
                ];
            })
            ->values(); // re-index to 0, 1, 2...

        // ── Calculate overall progress ─────────────────────────────────────
        $overallTotalLessons = 0;
        $overallCompletedLessons = 0;

        foreach ($enrollments as $enrollment) {
            $overallTotalLessons += $enrollment->course->lessons()->count();
            $overallCompletedLessons += $enrollment->lessonProgress()->whereNotNull('completed_at')->count();
        }

        $overallProgressPct = $overallTotalLessons > 0
            ? (int) round(($overallCompletedLessons / $overallTotalLessons) * 100)
            : 0;

        return view('freelancer.lms.index', compact(
            'mentorGroups',
            'pendingRelationships',
            'enrollments',
            'overallTotalLessons',
            'overallCompletedLessons',
            'overallProgressPct'
        ));
    }

    /**
     * Course overview: modules accordion + lesson list with per-lesson completion state.
     */
    public function showCourse(Enrollment $enrollment): View
    {
        abort_if(auth()->id() !== $enrollment->freelancer_id, 403);

        $enrollment->load([
            'course.modules.lessons',
            'course.relationship.mentor',
            'lessonProgress',
        ]);

        // Build a Set of completed lesson IDs for quick lookup in the view
        $completedLessonIds = $enrollment->lessonProgress
            ->whereNotNull('completed_at')
            ->pluck('lesson_id')
            ->toArray();

        return view('freelancer.lms.course', compact('enrollment', 'completedLessonIds'));
    }

    /**
     * Lesson viewer: title, content body, optional video embed.
     */
    public function showLesson(Enrollment $enrollment, Lesson $lesson): View
    {
        abort_if(auth()->id() !== $enrollment->freelancer_id, 403);

        // Ensure lesson belongs to this enrollment's course
        abort_unless(
            $lesson->module->course_id === $enrollment->course_id,
            404
        );

        $enrollment->load(['course.relationship.mentor', 'lessonProgress']);

        $completedLessonIds = $enrollment->lessonProgress
            ->whereNotNull('completed_at')
            ->pluck('lesson_id')
            ->toArray();

        $isCompleted = in_array($lesson->id, $completedLessonIds);

        // Previous / Next lesson for navigation
        $allLessons = $enrollment->course
            ->lessons()
            ->orderBy('course_modules.sort_order')
            ->orderBy('lessons.sort_order')
            ->get();

        $currentIndex = $allLessons->search(fn($l) => $l->id === $lesson->id);
        $prevLesson   = $currentIndex > 0 ? $allLessons[$currentIndex - 1] : null;
        $nextLesson   = $currentIndex < $allLessons->count() - 1 ? $allLessons[$currentIndex + 1] : null;

        return view('freelancer.lms.lesson', compact(
            'enrollment', 'lesson', 'isCompleted', 'prevLesson', 'nextLesson'
        ));
    }

    /**
     * Mark a lesson as complete, and mark the enrollment complete if it's the last lesson.
     */
    public function completeLesson(Enrollment $enrollment, Lesson $lesson): RedirectResponse
    {
        abort_if(auth()->id() !== $enrollment->freelancer_id, 403);

        abort_unless(
            $lesson->module->course_id === $enrollment->course_id,
            404
        );

        // Upsert the progress record
        LessonProgress::updateOrCreate(
            [
                'enrollment_id' => $enrollment->id,
                'lesson_id'     => $lesson->id,
            ],
            [
                'freelancer_id' => auth()->id(),
                'completed_at'  => now(),
            ]
        );

        // Check if all lessons are now complete → mark enrollment done
        $totalLessons     = $enrollment->course->lessons()->count();
        $completedLessons = $enrollment->lessonProgress()->whereNotNull('completed_at')->count();

        if ($completedLessons >= $totalLessons && !$enrollment->isCompleted()) {
            $enrollment->update(['completed_at' => now()]);
        }

        return back()->with('success', 'Lesson marked as complete! 🎉');
    }

    /**
     * Progress analytics page for a single enrollment.
     * Provides pre-computed chart data (JSON) for Chart.js.
     */
    public function showProgress(Enrollment $enrollment): View
    {
        abort_if(auth()->id() !== $enrollment->freelancer_id, 403);

        $enrollment->load([
            'course.modules.lessons',
            'course.relationship.mentor',
            'lessonProgress',
        ]);

        $completedIds = $enrollment->lessonProgress
            ->whereNotNull('completed_at')
            ->pluck('lesson_id')
            ->toArray();

        // ── Per-module bar chart data ─────────────────────────────────────
        $moduleLabels    = [];
        $moduleTotals    = [];
        $moduleCompleted = [];

        foreach ($enrollment->course->modules as $module) {
            $moduleLabels[]    = $module->title;
            $total             = $module->lessons->count();
            $done              = $module->lessons->whereIn('id', $completedIds)->count();
            $moduleTotals[]    = $total;
            $moduleCompleted[] = $done;
        }

        // ── Overall doughnut ─────────────────────────────────────────────
        $totalLessons     = array_sum($moduleTotals);
        $completedLessons = array_sum($moduleCompleted);
        $remaining        = max(0, $totalLessons - $completedLessons);

        // ── 30-day activity line chart ────────────────────────────────────
        $days         = collect(range(29, 0))->map(fn($d) => now()->subDays($d)->format('M d'));
        $activityData = collect(range(29, 0))->map(function ($d) use ($enrollment) {
            $date = now()->subDays($d)->toDateString();
            return $enrollment->lessonProgress
                ->whereNotNull('completed_at')
                ->filter(fn($p) => $p->completed_at->toDateString() === $date)
                ->count();
        });

        // Running cumulative for the line chart
        $cumulative = [];
        $running    = 0;
        foreach ($activityData as $count) {
            $running     += $count;
            $cumulative[] = $running;
        }

        return view('freelancer.lms.progress', compact(
            'enrollment',
            'totalLessons',
            'completedLessons',
            'remaining',
            'moduleLabels',
            'moduleTotals',
            'moduleCompleted',
            'days',
            'cumulative'
        ));
    }

    /**
     * All-time overall progress page: aggregated across ALL enrollments for this freelancer.
     */
    public function showOverallProgress(): \Illuminate\View\View
    {
        $freelancerId = auth()->id();

        $enrollments = Enrollment::with([
                'course.modules.lessons',
                'course.relationship.mentor',
                'lessonProgress',
            ])
            ->forFreelancer($freelancerId)
            ->latest()
            ->get();

        // ── Totals ──────────────────────────────────────────────────────────
        $overallTotalLessons     = 0;
        $overallCompletedLessons = 0;
        $completedCourses        = 0;

        // ── Per-course breakdown (bar chart) ─────────────────────────────────
        $courseLabels    = [];
        $courseTotals    = [];
        $courseCompleted = [];

        foreach ($enrollments as $enrollment) {
            $total = $enrollment->course->lessons()->count();
            $done  = $enrollment->lessonProgress()->whereNotNull('completed_at')->count();

            $overallTotalLessons     += $total;
            $overallCompletedLessons += $done;

            if ($enrollment->isCompleted()) {
                $completedCourses++;
            }

            $courseLabels[]    = \Str::limit($enrollment->course->title, 22);
            $courseTotals[]    = $total;
            $courseCompleted[] = $done;
        }

        $overallProgressPct = $overallTotalLessons > 0
            ? (int) round(($overallCompletedLessons / $overallTotalLessons) * 100)
            : 0;

        $remainingLessons = max(0, $overallTotalLessons - $overallCompletedLessons);

        // ── 30-day cumulative activity (all enrollments combined) ─────────────
        $days = collect(range(29, 0))->map(fn($d) => now()->subDays($d)->format('M d'));

        $activityData = collect(range(29, 0))->map(function ($d) use ($enrollments) {
            $date  = now()->subDays($d)->toDateString();
            $count = 0;
            foreach ($enrollments as $enrollment) {
                $count += $enrollment->lessonProgress
                    ->whereNotNull('completed_at')
                    ->filter(fn($p) => $p->completed_at->toDateString() === $date)
                    ->count();
            }
            return $count;
        });

        $cumulative = [];
        $running    = 0;
        foreach ($activityData as $count) {
            $running     += $count;
            $cumulative[] = $running;
        }

        // ── Per-mentor summary ────────────────────────────────────────────────
        $mentorSummaries = $enrollments
            ->groupBy(fn($e) => optional($e->course->relationship)->mentor_id ?? 0)
            ->map(function ($group) {
                $mentor = optional($group->first()->course->relationship)->mentor;
                $total  = $group->sum(fn($e) => $e->course->lessons()->count());
                $done   = $group->sum(fn($e) => $e->lessonProgress()->whereNotNull('completed_at')->count());
                $pct    = $total > 0 ? (int) round($done / $total * 100) : 0;
                return ['mentor' => $mentor, 'total' => $total, 'done' => $done, 'pct' => $pct, 'count' => $group->count()];
            })
            ->values();

        return view('freelancer.lms.overall_progress', compact(
            'enrollments',
            'overallTotalLessons',
            'overallCompletedLessons',
            'overallProgressPct',
            'remainingLessons',
            'completedCourses',
            'courseLabels',
            'courseTotals',
            'courseCompleted',
            'days',
            'cumulative',
            'mentorSummaries'
        ));
    }
}

