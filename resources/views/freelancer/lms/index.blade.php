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
             SECTION 1 — Accepted relationships (courses available)
             ══════════════════════════════════════════════════════ --}}
        @if($acceptedRelationships->count() > 0)
        <div style="margin-bottom:2.5rem">
            <h2 style="font-size:1.1rem;font-weight:700;color:var(--color-gray-800);margin-bottom:1rem;display:flex;align-items:center;gap:.5rem">
                <span style="width:10px;height:10px;border-radius:50%;background:#22c55e;display:inline-block"></span>
                Active Mentorships
            </h2>

            @foreach($acceptedRelationships as $rel)
            @php
                // Enrollments for this relationship
                $relEnrollments = $enrollments->filter(
                    fn($e) => $e->course->relationship_id === $rel->id
                );
            @endphp
            <div class="panel lms-rel-section" style="margin-bottom:1.5rem">
                {{-- Relationship header --}}
                <div class="panel__header" style="background:linear-gradient(135deg,#eef2ff,#f5f3ff);border-radius:var(--radius-lg) var(--radius-lg) 0 0">
                    <div style="display:flex;align-items:center;gap:.75rem">
                        <img src="{{ $rel->mentor->avatar_url }}"
                             alt="{{ $rel->mentor->full_name }}"
                             style="width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid var(--color-primary-light)">
                        <div>
                            <p style="font-weight:700;color:var(--color-gray-900)">{{ $rel->mentor->full_name }}</p>
                            <p style="font-size:.8rem;color:var(--color-gray-500)">
                                {{ ucfirst($rel->payment_type ?? 'custom') }} plan
                                @if($rel->payment_amount)
                                    &middot; ${{ number_format($rel->payment_amount, 2) }}
                                @endif
                                &middot; Accepted {{ $rel->accepted_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                    <span class="badge badge--success">Active</span>
                </div>

                <div class="panel__body">
                    @if($relEnrollments->count() > 0)
                        {{-- Show enrolled courses --}}
                        <div class="lms-course-grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr))">
                            @foreach($relEnrollments as $enrollment)
                            @php $pct = $enrollment->progress_percentage; @endphp
                            <a href="{{ route('lms.course', $enrollment) }}"
                               class="lms-course-card lms-course-card--link">
                                <div class="lms-course-card__header">
                                    <span class="badge badge--{{ $enrollment->isCompleted() ? 'success' : 'default' }}">
                                        {{ $enrollment->isCompleted() ? '🎉 Completed' : 'In Progress' }}
                                    </span>
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
                        {{-- Accepted but no courses published yet --}}
                        <div style="display:flex;align-items:center;gap:1rem;padding:.5rem 0;color:var(--color-gray-500)">
                            <span style="font-size:2rem">📚</span>
                            <div>
                                <p style="font-weight:600;color:var(--color-gray-700)">Your mentor hasn't published a course yet.</p>
                                <p style="font-size:.875rem">
                                    <strong>{{ $rel->mentor->first_name }}</strong> has accepted your request and
                                    is building your course. It will appear here once published.
                                </p>
                            </div>
                        </div>
                    @endif
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
                                &middot; ${{ number_format($rel->payment_amount, 2) }} / {{ $rel->payment_type }}
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
             SECTION 3 — Empty state (nothing at all)
             ══════════════════════════════════════════════════════ --}}
        @if($acceptedRelationships->isEmpty() && $pendingRelationships->isEmpty() && $enrollments->isEmpty())
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
