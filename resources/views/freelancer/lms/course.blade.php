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

        {{-- Renew Notification Block (Only shows if <=3 days left or expired) --}}
        @php
            $rel = $enrollment->course->relationship;
            $dLeft = $rel->daysRemaining();
            $isExp = $rel->isExpired();
            $showRenew = $isExp || ($dLeft !== null && $dLeft <= 3);
        @endphp

        @if($showRenew)
        <div class="panel" style="margin-bottom:1.5rem;border-left:4px solid var(--color-{{ $isExp ? 'error' : 'warning' }})">
            <div class="panel__body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
                <div>
                    <h3 style="font-weight:700;margin-bottom:.25rem;color:var(--color-{{ $isExp ? 'error' : 'warning' }}-700, #b45309)">
                        @if($isExp)
                            ⚠️ Your mentorship plan has expired
                        @else
                            ⏳ Your mentorship plan expires in {{ $dLeft }} day{{ $dLeft === 1 ? '' : 's' }}
                        @endif
                    </h3>
                    <p style="font-size:.85rem;color:var(--color-gray-600)">
                        Renew your plan with {{ $rel->mentor->full_name }} to keep your access to this course and continue learning.
                    </p>
                </div>
                <button class="btn btn--primary" onclick="openRenewModal({{ $rel->id }}, '{{ addslashes($rel->mentor->full_name) }}')">
                    🔄 Renew Plan
                </button>
            </div>
        </div>

        {{-- Hidden renewal form --}}
        <form method="POST" action="{{ route('lms.relationships.renew', $rel) }}"
              id="renewForm-{{ $rel->id }}" style="display:none">
            @csrf
            <input type="hidden" name="duration_months" id="renewDuration-{{ $rel->id }}" value="">
        </form>
        @endif

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

@if($showRenew)
{{-- ══════════════════════════════════════════════════════
     Renew Modal
     ══════════════════════════════════════════════════════ --}}
<div id="renewModal" class="lms-modal-overlay" style="display:none" onclick="closeRenewModal(event)">
    <div class="lms-modal" onclick="event.stopPropagation()">
        <button class="lms-modal__close" onclick="closeRenewModal()">&times;</button>
        <h2 class="lms-modal__title">🔄 Renew Mentorship</h2>
        <p class="lms-modal__subtitle" id="renewModalSubtitle">Choose a new duration to extend your mentorship.</p>

        <div class="lms-duration-grid" id="renewDurationOptions">
            <button class="lms-duration-btn" data-months="1" onclick="selectDuration(1)">
                <span class="lms-duration-btn__months">1</span>
                <span class="lms-duration-btn__label">Month</span>
                <span class="lms-duration-btn__note">Short-term</span>
            </button>
            <button class="lms-duration-btn" data-months="3" onclick="selectDuration(3)">
                <span class="lms-duration-btn__months">3</span>
                <span class="lms-duration-btn__label">Months</span>
                <span class="lms-duration-btn__note">Most popular</span>
            </button>
            <button class="lms-duration-btn" data-months="6" onclick="selectDuration(6)">
                <span class="lms-duration-btn__months">6</span>
                <span class="lms-duration-btn__label">Months</span>
                <span class="lms-duration-btn__note">Deep dive</span>
            </button>
            <button class="lms-duration-btn" data-months="12" onclick="selectDuration(12)">
                <span class="lms-duration-btn__months">1</span>
                <span class="lms-duration-btn__label">Year</span>
                <span class="lms-duration-btn__note">Best value</span>
            </button>
        </div>

        <button id="renewSubmitBtn" class="btn btn--primary btn--block" onclick="submitRenew()" disabled>
            Confirm Renewal
        </button>
    </div>
</div>
@endif
@endsection

@if($showRenew)
@push('styles')
<style>
/* ── Renew Modal ── */
.lms-modal-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,.5);
    display: flex; align-items: center; justify-content: center;
    z-index: 9999;
    animation: fadeIn .2s ease;
}
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

.lms-modal {
    background: #fff;
    border-radius: 16px;
    padding: 2rem;
    max-width: 480px;
    width: 90%;
    position: relative;
    animation: slideUp .25s ease;
    box-shadow: 0 25px 60px rgba(0,0,0,.2);
}
@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.lms-modal__close {
    position: absolute; top: 1rem; right: 1.25rem;
    background: none; border: none; font-size: 1.5rem;
    cursor: pointer; color: #6b7280; line-height: 1;
}
.lms-modal__close:hover { color: #111; }
.lms-modal__title    { font-size: 1.25rem; font-weight: 700; margin-bottom: .25rem; color: #111827; }
.lms-modal__subtitle { font-size: .875rem; color: #6b7280; margin-bottom: 1.5rem; }

/* ── Duration picker grid ── */
.lms-duration-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: .75rem;
    margin-bottom: 1.5rem;
}
.lms-duration-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem .5rem;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    background: #f9fafb;
    cursor: pointer;
    transition: border-color .15s, background .15s, transform .15s;
    gap: .15rem;
}
.lms-duration-btn:hover        { border-color: #4f46e5; background: #eef2ff; transform: translateY(-2px); }
.lms-duration-btn.is-selected  { border-color: #4f46e5; background: #eef2ff; box-shadow: 0 0 0 3px rgba(79,70,229,.15); }
.lms-duration-btn__months { font-size: 1.5rem; font-weight: 800; color: #4f46e5; }
.lms-duration-btn__label  { font-size: .8rem;  font-weight: 600; color: #374151; }
.lms-duration-btn__note   { font-size: .7rem;  color: #9ca3af; }

@media (max-width: 640px) {
    .lms-duration-grid { grid-template-columns: repeat(2, 1fr); }
}

/* Dark mode */
[data-theme="dark"] .lms-modal { background: #1e293b; border-color: #334155; }
[data-theme="dark"] .lms-modal__title { color: #f1f5f9; }
[data-theme="dark"] .lms-modal__subtitle { color: #94a3b8; }
[data-theme="dark"] .lms-duration-btn { background: #0f172a; border-color: #334155; color: #e2e8f0; }
[data-theme="dark"] .lms-duration-btn:hover,
[data-theme="dark"] .lms-duration-btn.is-selected { border-color: #818cf8; background: #1e1b4b; }
</style>
@endpush

@push('scripts')
<script>
var _renewRelId = null;
var _renewDuration = null;

function openRenewModal(relId, mentorName) {
    _renewRelId = relId;
    _renewDuration = null;
    document.getElementById('renewModalSubtitle').textContent =
        'Extend your mentorship with ' + mentorName + ' by choosing a new duration.';
    document.querySelectorAll('.lms-duration-btn').forEach(function(b) {
        b.classList.remove('is-selected');
    });
    document.getElementById('renewSubmitBtn').disabled = true;
    document.getElementById('renewModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeRenewModal(e) {
    if (e && e.target !== document.getElementById('renewModal')) return;
    document.getElementById('renewModal').style.display = 'none';
    document.body.style.overflow = '';
}

function selectDuration(months) {
    _renewDuration = months;
    document.querySelectorAll('.lms-duration-btn').forEach(function(b) {
        b.classList.toggle('is-selected', parseInt(b.dataset.months) === months);
    });
    document.getElementById('renewSubmitBtn').disabled = false;
}

function submitRenew() {
    if (!_renewRelId || !_renewDuration) return;
    var form = document.getElementById('renewForm-' + _renewRelId);
    document.getElementById('renewDuration-' + _renewRelId).value = _renewDuration;
    form.submit();
}
</script>
@endpush
@endif
