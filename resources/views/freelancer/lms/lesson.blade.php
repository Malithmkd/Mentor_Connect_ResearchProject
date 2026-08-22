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
        @php
            // Append the JS API enable param to the embed URL
            $rawEmbed   = $lesson->getEmbedUrl();
            $isYoutube  = str_contains($rawEmbed, 'youtube.com/embed/');
            $isVimeo    = str_contains($rawEmbed, 'vimeo.com/video/');
            $separator  = str_contains($rawEmbed, '?') ? '&' : '?';
            if ($isYoutube) {
                $embedSrc = $rawEmbed . $separator . 'enablejsapi=1';
            } elseif ($isVimeo) {
                $embedSrc = $rawEmbed . $separator . 'api=1';
            } else {
                $embedSrc = $rawEmbed;
            }
        @endphp
        <div class="panel" style="margin-bottom:1.5rem">
            <div class="panel__body" style="padding:0;overflow:hidden;border-radius:var(--radius-md)">
                <div style="position:relative;padding-top:56.25%">
                    <iframe id="lessonVideoFrame"
                            src="{{ $embedSrc }}"
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

        {{-- ── PDF Notes Download ── --}}
        @if($lesson->hasPdf())
        <div class="panel" style="margin-bottom:1.5rem;border-left:4px solid var(--color-primary)">
            <div class="panel__body" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
                <div style="font-size:2rem;flex-shrink:0">📄</div>
                <div style="flex:1;min-width:0">
                    <p style="font-weight:700;color:var(--color-gray-900);margin:0 0 .15rem">PDF Notes</p>
                    <p style="font-size:.8rem;color:var(--color-gray-500);margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        {{ $lesson->pdfName() }}
                    </p>
                </div>
                <a href="{{ $lesson->pdfUrl() }}"
                   target="_blank"
                   rel="noopener"
                   class="btn btn--primary btn--sm"
                   download>
                    ⬇ Download PDF
                </a>
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
                              action="{{ route('lms.lesson.complete', [$enrollment, $lesson]) }}"
                              id="completeForm">
                            @csrf
                            @if($lesson->getEmbedUrl())
                                {{-- Button locked until the video is fully watched --}}
                                <div style="display:flex;flex-direction:column;align-items:center;gap:.3rem">
                                    <button type="submit"
                                            id="completeBtn"
                                            class="btn btn--success"
                                            disabled
                                            style="opacity:.45;cursor:not-allowed;position:relative"
                                            title="Watch the full video to unlock this button">
                                        Mark as Complete ✓
                                    </button>
                                    <span id="videoHint"
                                          data-progress-url="{{ route('lms.progress', $enrollment) }}"
                                          style="font-size:.75rem;color:var(--color-text-muted);text-align:center">
                                        🎬 Watch the full video to mark as complete
                                    </span>
                                </div>
                            @else
                                <button type="submit" class="btn btn--success">
                                    Mark as Complete ✓
                                </button>
                            @endif
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

@if(!$isCompleted && $lesson->getEmbedUrl())
@push('scripts')
<script>
(function () {
    'use strict';

    var completeBtn = document.getElementById('completeBtn');
    var videoHint   = document.getElementById('videoHint'); 

    if (!completeBtn) return; // already completed — no lock needed

    /** when the video reaches the end */
    function onVideoEnded() {
        completeBtn.disabled = false;
        completeBtn.style.opacity    = '1';
        completeBtn.style.cursor     = 'pointer';
        completeBtn.title            = 'Mark this lesson as complete';
        if (videoHint) {
            var progressUrl = videoHint.getAttribute('data-progress-url');
            videoHint.innerHTML = 'Video watched! You can finish the Module.✅';
            if (progressUrl) {
                videoHint.innerHTML +=
                    ' <a href="' + progressUrl + '"' +
                    ' style="color:#4f46e5;font-weight:600;text-decoration:underline;white-space:nowrap"' +
                    ' title="View your course progress">📊 View Progress →</a>';
            }
            videoHint.style.color = '#16a34a'; 
        }
    }

    // ─── Prevent submit while still disabled (extra safety) ──────────────────
    var form = document.getElementById('completeForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (completeBtn && completeBtn.disabled) {
                e.preventDefault();
            }
        });
    }

    // ─── Detect platform ─────────────────────────────────────────────────────
    var embedSrc = @json($embedSrc ?? '');
    var isYoutube = embedSrc.indexOf('youtube.com/embed/') !== -1;
    var isVimeo   = embedSrc.indexOf('vimeo.com/video/')  !== -1;

    // ─── YouTube IFrame Player API ────────────────────────────────────────────
    if (isYoutube) {
        // Load the YouTube IFrame API script dynamically
        var tag = document.createElement('script');
        tag.src = 'https://www.youtube.com/iframe_api';
        document.head.appendChild(tag);

        // This global callback is invoked by the API when ready
        window.onYouTubeIframeAPIReady = function () {
            new YT.Player('lessonVideoFrame', {
                events: {
                    onStateChange: function (event) {
                        // YT.PlayerState.ENDED === 0
                        if (event.data === YT.PlayerState.ENDED) {
                            onVideoEnded();
                        }
                    }
                }
            });
        };
    }

    // ─── Vimeo Player API (postMessage) ───────────────────────────────────────
    if (isVimeo) {
        var iframe = document.getElementById('lessonVideoFrame');

        // Ask Vimeo player to send us events once it's ready
        iframe.addEventListener('load', function () {
            iframe.contentWindow.postMessage(
                JSON.stringify({ method: 'addEventListener', value: 'finish' }),
                'https://player.vimeo.com'
            );
        });

        window.addEventListener('message', function (e) {
            if (e.origin !== 'https://player.vimeo.com') return;
            try {
                var data = typeof e.data === 'string' ? JSON.parse(e.data) : e.data;
                if (data.event === 'finish') {
                    onVideoEnded();
                }
            } catch (_) {}
        });
    }

    // ─── Fallback for unknown / self-hosted players ───────────────────────────
    // Listen for any postMessage that signals the video ended
    if (!isYoutube && !isVimeo) {
        window.addEventListener('message', function (e) {
            try {
                var data = typeof e.data === 'string' ? JSON.parse(e.data) : e.data;
                if (
                    data.event === 'ended'   ||
                    data.event === 'finish'  ||
                    data.event === 'complete'
                ) {
                    onVideoEnded();
                }
            } catch (_) {}
        });
    }

}());
</script>
@endpush
@endif
