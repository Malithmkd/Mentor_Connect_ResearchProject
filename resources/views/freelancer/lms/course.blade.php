@extends('layouts.app')

@section('title', $enrollment->course->title)

@section('content')
<section class="dashboard">
    <div class="dashboard__inner">
        <header class="dashboard__header">
            <div>
                <h1 class="dashboard__title">{{ $enrollment->course->title }}</h1>
                <p class="dashboard__subtitle">
                    Mentor: <strong>{{ $enrollment->course->relationship->mentor->full_name }}</strong>
                </p>
            </div>
            <div style="display:flex;gap:.75rem;align-items:center">
                <a href="{{ route('lms.progress', $enrollment) }}" class="btn btn--secondary btn--sm">
                    📊 View Progress
                </a>
                <a href="{{ route('lms.index') }}" class="btn btn--ghost btn--sm">← My Learning</a>
            </div>
        </header>

        @include('partials.flash')

        {{-- Overall Progress --}}
        <div class="panel" style="margin-bottom:1.5rem">
            <div class="panel__body">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
                    <span style="font-weight:600">Your Progress</span>
                    <span class="badge badge--{{ $enrollment->isCompleted() ? 'success' : 'default' }}">
                        {{ $enrollment->isCompleted() ? '🎉 Course Complete!' : $enrollment->progress_percentage . '% done' }}
                    </span>
                </div>
                @include('partials.lms._progress_bar', [
                    'percent' => $enrollment->progress_percentage,
                    'label'   => '',
                    'size'    => 'lg'
                ])
            </div>
        </div>

        {{-- Modules Accordion --}}
        @foreach($enrollment->course->modules as $module)
        <div class="panel lms-module" style="margin-bottom:1rem">
            <div class="panel__header lms-module__header" style="cursor:pointer"
                 onclick="this.nextElementSibling.classList.toggle('hidden')">
                <div>
                    <span class="lms-module__order">Module {{ $loop->iteration }}</span>
                    <span style="font-weight:600;margin-left:.5rem">{{ $module->title }}</span>
                </div>
                <span style="font-size:1.2rem">▾</span>
            </div>
            <div class="panel__body">
                @if($module->description)
                    <p style="color:var(--color-text-muted);font-size:.9rem;margin-bottom:1rem">{{ $module->description }}</p>
                @endif
                @forelse($module->lessons as $lesson)
                @php $done = in_array($lesson->id, $completedLessonIds); @endphp
                <a href="{{ route('lms.lesson', [$enrollment, $lesson]) }}"
                   class="lms-lesson-row lms-lesson-row--link {{ $done ? 'lms-lesson-row--done' : '' }}">
                    <span class="lms-lesson-row__num">
                        @if($done)
                            <span style="color:#22c55e;font-size:1.1rem">✓</span>
                        @else
                            {{ $loop->iteration }}
                        @endif
                    </span>
                    <div class="lms-lesson-row__info">
                        {{ $lesson->title }}
                        @if($lesson->video_url)
                            <span class="badge badge--default" style="margin-left:.5rem;font-size:.7rem">🎬</span>
                        @endif
                    </div>
                    <span class="badge badge--{{ $done ? 'success' : 'default' }} badge--sm">
                        {{ $done ? 'Done' : 'Start' }}
                    </span>
                </a>
                @empty
                    <p class="empty__text">No lessons in this module yet.</p>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>
</section>
@endsection
