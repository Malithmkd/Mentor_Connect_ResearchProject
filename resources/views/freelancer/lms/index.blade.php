@extends('layouts.app')

@section('title', 'My Learning')

@section('content')
<section class="dashboard">
    <div class="dashboard__inner">
        <header class="dashboard__header">
            <div>
                <h1 class="dashboard__title">My Learning</h1>
                <p class="dashboard__subtitle">Your long-term mentorships and enrolled courses in one place.</p>
            </div>
        </header>

        @include('partials.flash')

        {{-- ══════════════════════════════════════════════════════
             OVERALL PROGRESS
             ══════════════════════════════════════════════════════ --}}
        @if($enrollments->count() > 0)
        <a href="{{ route('lms.overall-progress') }}"
           style="display:block;text-decoration:none;margin-bottom:2.5rem;border-radius:var(--radius-lg);transition:transform .15s,box-shadow .15s"
           onmouseenter="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 30px rgba(79,70,229,.35)'"
           onmouseleave="this.style.transform='';this.style.boxShadow=''"
           title="View your full all-time progress">
        <div class="panel" style="background:linear-gradient(135deg,var(--color-primary-dark),var(--color-primary));color:#fff;border:none;margin-bottom:0">
            <div class="panel__body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:2rem">
                <div style="flex:1;min-width:280px">
                    <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:.5rem;color:#fff">All-Time Progress</h2>
                    <p style="color:rgba(255,255,255,0.85);font-size:.9rem;margin-bottom:1.5rem">
                        You have completed <strong>{{ $overallCompletedLessons }}</strong> out of <strong>{{ $overallTotalLessons }}</strong> total lessons across all your enrolled courses. Keep up the great work!
                    </p>

                    <div style="background:rgba(255,255,255,0.25);border-radius:999px;height:12px;width:100%;overflow:hidden">
                        <div style="height:100%;width:{{ $overallProgressPct }}%;background:#fff;border-radius:999px;transition:width 0.5s ease"></div>
                    </div>
                </div>

                <div style="text-align:center;flex-shrink:0">
                    <span style="display:block;font-size:3.5rem;font-weight:800;line-height:1;letter-spacing:-0.03em;color:#fff">{{ $overallProgressPct }}<span style="font-size:2rem">%</span></span>
                    <span style="font-size:.8rem;text-transform:uppercase;letter-spacing:0.1em;color:rgba(255,255,255,0.75);font-weight:700">Completed</span>
                    <div style="margin-top:.75rem;font-size:.78rem;color:rgba(255,255,255,0.7);display:flex;align-items:center;justify-content:center;gap:.3rem">
                        📊 View Details →
                    </div>
                </div>
            </div>
        </div>
        </a>
        @endif

        {{-- ══════════════════════════════════════════════════════
             SECTION 1 — One panel per mentor (all their courses)
             ══════════════════════════════════════════════════════ --}}
        @if($mentorGroups->count() > 0)
        <div style="margin-bottom:2.5rem">
            <h2 style="font-size:1.1rem;font-weight:700;color:var(--color-gray-800);margin-bottom:1rem;display:flex;align-items:center;gap:.5rem">
                <span style="width:10px;height:10px;border-radius:50%;background:#22c55e;display:inline-block"></span>
                Active Mentorships
            </h2>

            @foreach($mentorGroups as $group)
            @php
                $mentor        = $group['mentor'];
                $relationships = $group['relationships'];
                $groupEnrolls  = $group['enrollments'];

                // Overall status badges across all relationships in this group
                $anyExpired  = $relationships->contains(fn($r) => $r->isExpired());
                $minDaysLeft = $relationships->map(fn($r) => $r->daysRemaining())->filter()->min();
            @endphp

            <div class="lms-mentor-panel" style="margin-bottom:2rem">

                {{-- ── Mentor header (avatar + name + course count only) ── --}}
                <div class="lms-mentor-panel__header">
                    <div style="display:flex;align-items:center;gap:.875rem;flex:1;min-width:0;flex-wrap:wrap">
                        <img src="{{ $mentor->avatar_url }}"
                             alt="{{ $mentor->full_name }}"
                             style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid var(--color-primary-light);flex-shrink:0">
                        <div style="min-width:0">
                            <p style="font-weight:700;font-size:1.05rem;color:var(--color-gray-900)">{{ $mentor->full_name }}</p>
                            <p style="font-size:.8rem;color:var(--color-gray-500)">
                                {{ $relationships->count() }} mentorship{{ $relationships->count() > 1 ? 's' : '' }}
                                &middot; {{ $groupEnrolls->count() }} course{{ $groupEnrolls->count() !== 1 ? 's' : '' }} enrolled
                            </p>
                        </div>
                    </div>
                </div>

                {{-- ── Per-relationship rows removed as requested ── --}}



                {{-- ── Courses grid ── --}}
                <div style="padding:1.25rem">
                    @if($groupEnrolls->count() > 0)
                        <div class="lms-course-grid">
                            @foreach($groupEnrolls as $enrollment)
                            @php $pct = $enrollment->progress_percentage; @endphp
                            <a href="{{ route('lms.course', $enrollment) }}"
                               class="lms-course-card lms-course-card--link">
                                <div class="lms-course-card__header">
                                    <span class="badge badge--{{ $enrollment->isCompleted() ? 'success' : 'default' }}">
                                        {{ $enrollment->isCompleted() ? '🎉 Completed' : 'In Progress' }}
                                    </span>
                                    {{-- Show which gig/relationship this course came from (if multiple) --}}
                                    @if($relationships->count() > 1)
                                    <span style="font-size:.7rem;color:var(--color-gray-400);margin-left:auto">
                                        {{ ucfirst($enrollment->course->relationship->payment_type ?? '') }}
                                    </span>
                                    @endif
                                </div>
                                <h3 class="lms-course-card__title">{{ $enrollment->course->title }}</h3>
                                @if($enrollment->course->description)
                                    <p class="lms-course-card__desc">{{ Str::limit($enrollment->course->description, 80) }}</p>
                                @endif
                                <div style="margin-top:auto;padding-top:.75rem">
                                    @include('partials.lms._progress_bar', [
                                        'percent' => $pct,
                                        'label'   => '',
                                        'size'    => 'md'
                                    ])
                                    <p style="text-align:right;font-size:.75rem;color:var(--color-text-muted);margin-top:.2rem">
                                        {{ $pct }}% complete
                                    </p>
                                </div>
                            </a>
                            @endforeach
                        </div>
                    @else
                        {{-- No courses published yet --}}
                        <div style="display:flex;align-items:center;gap:1rem;padding:.5rem 0;color:var(--color-gray-500)">
                            <span style="font-size:2rem">📚</span>
                            <div>
                                <p style="font-weight:600;color:var(--color-gray-700)">No courses published yet.</p>
                                <p style="font-size:.875rem">
                                    <strong>{{ $mentor->first_name }}</strong> has accepted your request and
                                    is building your course. It will appear here once published.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            </div>

            @endforeach
        </div>
        @endif

        {{-- ══════════════════════════════════════════════════════
             SECTION 2 — Pending requests (waiting for mentor)
             ══════════════════════════════════════════════════════ --}}
        @if($pendingRelationships->count() > 0)
        <div style="margin-bottom:2.5rem">
            <h2 style="font-size:1.1rem;font-weight:700;color:var(--color-gray-800);margin-bottom:1rem;display:flex;align-items:center;gap:.5rem">
                <span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;display:inline-block"></span>
                Pending Requests
            </h2>

            @foreach($pendingRelationships as $rel)
            <div class="panel" style="margin-bottom:1rem;border-left:3px solid var(--color-warning)">
                <div class="panel__body" style="display:flex;align-items:center;gap:1rem">
                    <img src="{{ $rel->mentor->avatar_url }}"
                         alt="{{ $rel->mentor->full_name }}"
                         style="width:40px;height:40px;border-radius:50%;object-fit:cover;flex-shrink:0">
                    <div style="flex:1">
                        <p style="font-weight:600;color:var(--color-gray-900)">{{ $rel->mentor->full_name }}</p>
                        <p style="font-size:.8rem;color:var(--color-gray-500)">
                            Request sent {{ $rel->requested_at->diffForHumans() }}
                            @if($rel->payment_amount)
                                &middot; Rs {{ number_format($rel->payment_amount, 2) }} / {{ $rel->payment_type }}
                            @endif
                            @if($rel->duration_months)
                                &middot; {{ $rel->duration_months }} month{{ $rel->duration_months > 1 ? 's' : '' }} requested
                            @endif
                        </p>
                    </div>
                    <span class="badge badge--warning">Awaiting Response</span>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- ══════════════════════════════════════════════════════
             SECTION 3 — Empty state
             ══════════════════════════════════════════════════════ --}}
        @if($mentorGroups->isEmpty() && $pendingRelationships->isEmpty())
        <div class="empty" style="padding:5rem 0;text-align:center">
            <div style="font-size:4rem;margin-bottom:1rem">🎓</div>
            <p class="empty__text" style="font-size:1.1rem">No learning activity yet.</p>
            <p style="color:var(--color-text-muted);font-size:.9rem;margin-top:.5rem;max-width:420px;margin-inline:auto">
                After completing a session with a mentor, go to that booking and send a
                <strong>Long-Term Request</strong>. Once accepted, your courses will appear here.
            </p>
            <a href="{{ route('freelancer.bookings.index') }}" class="btn btn--primary" style="margin-top:1.5rem">
                View My Bookings →
            </a>
        </div>
        @endif

    </div>
</section>
@endsection

@push('styles')
<style>
/* ── Mentor Panel ── */
.lms-mentor-panel {
    background: var(--color-white);
    border: 1px solid var(--color-gray-200);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.lms-mentor-panel__header {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, #eef2ff, #f5f3ff);
    border-bottom: 1px solid var(--color-gray-100);
    flex-wrap: wrap;
}

/* ── Course grid ── */
.lms-course-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 1rem;
}

/* ── LMS Duration Badges ── */
.lms-duration-badge {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    padding: .3rem .7rem;
    border-radius: 20px;
    font-size: .78rem;
    font-weight: 700;
    white-space: nowrap;
    flex-shrink: 0;
}
.lms-duration-badge--ok      { background: #dcfce7; color: #166534; }
.lms-duration-badge--soon    { background: #fef9c3; color: #854d0e; }
.lms-duration-badge--warning { background: #fee2e2; color: #991b1b; }
.lms-duration-badge--expired { background: #f3f4f6; color: #6b7280; }

@media (max-width: 640px) {
    .lms-course-grid { grid-template-columns: 1fr; }
}
@media (max-width: 480px) {
    .lms-mentor-panel__header { flex-direction: column; align-items: flex-start; }
}

/* Dark mode */
[data-theme="dark"] .lms-mentor-panel { background: #1e293b; border-color: #334155; }
[data-theme="dark"] .lms-mentor-panel__header { background: linear-gradient(135deg,#1e1b4b,#1a1a3e); border-color: #3730a3; }
[data-theme="dark"] .lms-duration-badge--ok      { background: #052e16; color: #86efac; }
[data-theme="dark"] .lms-duration-badge--soon    { background: #1c1300; color: #fde68a; }
[data-theme="dark"] .lms-duration-badge--warning { background: #450a0a; color: #fca5a5; }
</style>
@endpush
