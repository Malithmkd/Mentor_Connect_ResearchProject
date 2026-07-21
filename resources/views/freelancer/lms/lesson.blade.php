@extends('layouts.app')

@section('title', $lesson->title)

@section('content')
<section class="dashboard">
    <div class="dashboard__inner" style="max-width:860px">

        {{-- Back nav --}}
        <div style="margin-bottom:1.5rem">
            <a href="{{ route('lms.course', $enrollment) }}" class="btn btn--ghost btn--sm">← Back to Course</a>
        </div>

        @include('partials.flash')

        {{-- Lesson Header --}}
        <div class="panel" style="margin-bottom:1.5rem">
            <div class="panel__body">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem">
                    <div>
                        <p style="font-size:.8rem;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.25rem">
                            {{ $lesson->module->title }}
                        </p>
                        <h1 style="font-size:1.6rem;font-weight:700;color:var(--color-text);margin:0">
                            {{ $lesson->title }}
                        </h1>
                    </div>
                    @if($isCompleted)
                        <span class="badge badge--success" style="font-size:.9rem;padding:.4rem 1rem">
                            ✓ Completed
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Video Embed --}}
        @if($lesson->getEmbedUrl())
        <div class="panel" style="margin-bottom:1.5rem">
            <div class="panel__body" style="padding:0;overflow:hidden;border-radius:var(--radius-md)">
                <div style="position:relative;padding-top:56.25%">
                    <iframe src="{{ $lesson->getEmbedUrl() }}"
                            style="position:absolute;top:0;left:0;width:100%;height:100%;border:none"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                            title="{{ $lesson->title }}"></iframe>
                </div>
            </div>
            <div style="padding:.5rem 1rem;background:var(--color-gray-50);font-size:.8rem;color:var(--color-text-muted);text-align:right">
                <a href="{{ $lesson->video_url }}" target="_blank" rel="noopener" style="color:var(--color-primary)">
                    Open video in new tab ↗
                </a>
            </div>
        </div>
        @endif

        {{-- Lesson Content --}}
        @if($lesson->content)
        <div class="panel" style="margin-bottom:1.5rem">
            <div class="panel__body lms-lesson-content">
                {!! nl2br(e($lesson->content)) !!}
            </div>
        </div>
        @endif

        {{-- Complete Button + Navigation --}}
        <div class="panel">
            <div class="panel__body" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem">

                {{-- Previous --}}
                <div>
                    @if($prevLesson)
                        <a href="{{ route('lms.lesson', [$enrollment, $prevLesson]) }}"
                           class="btn btn--ghost btn--sm">← Previous Lesson</a>
                    @endif
                </div>

                {{-- Complete / Next --}}
                <div style="display:flex;gap:.75rem;align-items:center">
                    @if(!$isCompleted)
                        <form method="POST"
                              action="{{ route('lms.lesson.complete', [$enrollment, $lesson]) }}">
                            @csrf
                            <button type="submit" class="btn btn--success">
                                Mark as Complete ✓
                            </button>
                        </form>
                    @else
                        <span style="color:#22c55e;font-weight:600">✓ Lesson complete</span>
                    @endif

                    @if($nextLesson)
                        <a href="{{ route('lms.lesson', [$enrollment, $nextLesson]) }}"
                           class="btn btn--primary btn--sm">Next Lesson →</a>
                    @else
                        <a href="{{ route('lms.course', $enrollment) }}"
                           class="btn btn--primary btn--sm">Back to Course</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
