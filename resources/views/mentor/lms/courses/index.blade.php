@extends('layouts.app')

@section('title', 'Courses — ' . $relationship->freelancer->full_name)

@section('content')
<section class="dashboard">
    <div class="dashboard__inner">
        <header class="dashboard__header">
            <div>
                <h1 class="dashboard__title">Courses for {{ $relationship->freelancer->full_name }}</h1>
                <p class="dashboard__subtitle">
                    Long-term mentorship &middot; {{ ucfirst($relationship->payment_type ?? 'custom') }} plan
                    @if($relationship->payment_amount)
                        &middot; ${{ number_format($relationship->payment_amount, 2) }}
                    @endif
                </p>
            </div>
            @if($relationship->isAccepted())
                <a href="{{ route('mentor.lms.courses.create', $relationship) }}"
                   class="btn btn--primary">+ New Course</a>
            @endif
        </header>

        @include('partials.flash')

        <div class="panel">
            <div class="panel__header">
                <h2 class="panel__title">All Courses ({{ $courses->count() }})</h2>
            </div>
            <div class="panel__body">
                @if($courses->count() > 0)
                    <div class="lms-course-grid">
                        @foreach($courses as $course)
                        <div class="lms-course-card">
                            <div class="lms-course-card__header">
                                <span class="badge badge--{{ $course->status->colorClass() }}">
                                    {{ $course->status->label() }}
                                </span>
                            </div>
                            <h3 class="lms-course-card__title">{{ $course->title }}</h3>
                            @if($course->description)
                                <p class="lms-course-card__desc">{{ Str::limit($course->description, 100) }}</p>
                            @endif
                            <div class="lms-course-card__footer">
                                <a href="{{ route('mentor.lms.courses.edit', $course) }}"
                                   class="btn btn--ghost btn--sm">Edit / Build</a>
                                @if($course->isDraft())
                                    <form method="POST" action="{{ route('mentor.lms.courses.publish', $course) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn--success btn--sm">Publish</button>
                                    </form>
                                    <form method="POST" action="{{ route('mentor.lms.courses.destroy', $course) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn--error btn--sm"
                                            onclick="return confirm('Delete this course?')">Delete</button>
                                    </form>
                                @elseif($course->isPublished())
                                    <a href="{{ route('mentor.lms.courses.show', $course) }}"
                                       class="btn btn--secondary btn--sm">View Progress</a>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty">
                        <p class="empty__text">No courses yet. Create one to start teaching!</p>
                        <a href="{{ route('mentor.lms.courses.create', $relationship) }}"
                           class="btn btn--primary" style="margin-top:1rem">Create First Course</a>
                    </div>
                @endif
            </div>
        </div>

        <div style="margin-top:1rem">
            <a href="{{ route('mentor.lms.relationships.index') }}" class="btn btn--ghost btn--sm">
                ← Back to Relationships
            </a>
        </div>
    </div>
</section>
@endsection
