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
                $relEnrollments = $enrollments->filter(
                    fn($e) => $e->course->relationship_id === $rel->id
                );
                $daysLeft = $rel->daysRemaining();
                $isExpired = $rel->isExpired();
            @endphp
            <div class="panel lms-rel-section" style="margin-bottom:1.5rem">
                {{-- Relationship header --}}
                <div class="panel__header" style="background:linear-gradient(135deg,#eef2ff,#f5f3ff);border-radius:var(--radius-lg) var(--radius-lg) 0 0">
                    <div style="display:flex;align-items:center;gap:.75rem;flex:1;flex-wrap:wrap">
                        <img src="{{ $rel->mentor->avatar_url }}"
                             alt="{{ $rel->mentor->full_name }}"
                             style="width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid var(--color-primary-light)">
                        <div style="flex:1;min-width:0">
                            <p style="font-weight:700;color:var(--color-gray-900)">{{ $rel->mentor->full_name }}</p>
                            <p style="font-size:.8rem;color:var(--color-gray-500)">
                                {{ ucfirst($rel->payment_type ?? 'custom') }} plan
                                @if($rel->payment_amount)
                                    &middot; ${{ number_format($rel->payment_amount, 2) }}
                                @endif
                                &middot; Accepted {{ $rel->accepted_at->diffForHumans() }}
                            </p>
                        </div>

                        {{-- Duration badge --}}
                        @if($daysLeft !== null)
                            @if($isExpired)
                                <span class="lms-duration-badge lms-duration-badge--expired">
                                    ⚠️ Expired
                                </span>
                            @elseif($daysLeft <= 7)
                                <span class="lms-duration-badge lms-duration-badge--warning">
                                    🔴 {{ $daysLeft }} day{{ $daysLeft === 1 ? '' : 's' }} left
                                </span>
                            @elseif($daysLeft <= 30)
                                <span class="lms-duration-badge lms-duration-badge--soon">
                                    🟡 {{ $daysLeft }} days left
                                </span>
                            @else
                                <span class="lms-duration-badge lms-duration-badge--ok">
                                    🟢 {{ $daysLeft }} days left
                                </span>
                            @endif
                        @else
                            <span class="lms-duration-badge lms-duration-badge--ok">🟢 Active</span>
                        @endif

                        {{-- Renew button --}}
                        <button class="btn btn--primary btn--sm"
                                onclick="openRenewModal({{ $rel->id }}, '{{ addslashes($rel->mentor->full_name) }}')"
                                title="Renew mentorship duration">
                            🔄 Renew
                        </button>

                        <span class="badge badge--success">Active</span>
                    </div>
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

                {{-- Renewal form (hidden, submitted via modal) --}}
                <form method="POST" action="{{ route('lms.relationships.renew', $rel) }}"
                      id="renewForm-{{ $rel->id }}" style="display:none">
                    @csrf
                    <input type="hidden" name="duration_months" id="renewDuration-{{ $rel->id }}" value="">
                </form>
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
@endsection

@push('styles')
<style>
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
.lms-duration-badge--ok { background: #dcfce7; color: #166534; }
.lms-duration-badge--soon { background: #fef9c3; color: #854d0e; }
.lms-duration-badge--warning { background: #fee2e2; color: #991b1b; }
.lms-duration-badge--expired { background: #f3f4f6; color: #6b7280; }

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
.lms-modal__title { font-size: 1.25rem; font-weight: 700; margin-bottom: .25rem; color: #111827; }
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
.lms-duration-btn:hover {
    border-color: #4f46e5;
    background: #eef2ff;
    transform: translateY(-2px);
}
.lms-duration-btn.is-selected {
    border-color: #4f46e5;
    background: #eef2ff;
    box-shadow: 0 0 0 3px rgba(79,70,229,.15);
}
.lms-duration-btn__months { font-size: 1.5rem; font-weight: 800; color: #4f46e5; }
.lms-duration-btn__label  { font-size: .8rem; font-weight: 600; color: #374151; }
.lms-duration-btn__note   { font-size: .7rem; color: #9ca3af; }

@media (max-width: 480px) {
    .lms-duration-grid { grid-template-columns: repeat(2, 1fr); }
    .panel__header { flex-wrap: wrap; gap: .5rem; }
}

/* Dark mode */
[data-theme="dark"] .lms-modal {
    background: #1e293b;
    border-color: #334155;
}
[data-theme="dark"] .lms-modal__title { color: #f1f5f9; }
[data-theme="dark"] .lms-modal__subtitle { color: #94a3b8; }
[data-theme="dark"] .lms-duration-btn {
    background: #0f172a;
    border-color: #334155;
    color: #e2e8f0;
}
[data-theme="dark"] .lms-duration-btn:hover,
[data-theme="dark"] .lms-duration-btn.is-selected {
    border-color: #818cf8;
    background: #1e1b4b;
}
[data-theme="dark"] .lms-duration-badge--ok { background: #052e16; color: #86efac; }
[data-theme="dark"] .lms-duration-badge--soon { background: #1c1300; color: #fde68a; }
[data-theme="dark"] .lms-duration-badge--warning { background: #450a0a; color: #fca5a5; }
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
