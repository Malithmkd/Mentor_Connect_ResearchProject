@extends('layouts.app')

@section('title', 'Booking Details')

@section('content')
<section class="dashboard">
    <div class="dashboard__inner">
        <header class="dashboard__header">
            <div>
                <a href="{{ route('mentor.bookings.index') }}" class="btn btn--ghost btn--sm" style="margin-bottom:.5rem">
                    &larr; Back to Bookings
                </a>
                <h1 class="dashboard__title">{{ $booking->gig->title }}</h1>
                <p class="dashboard__subtitle">
                    Requested by <strong>{{ $booking->freelancer->full_name }}</strong>
                    &middot; {{ $booking->requested_at->format('M d, Y') }}
                </p>
            </div>
            <span class="badge badge--{{ $booking->status->colorClass() }}" style="font-size:1rem;padding:.5rem 1.25rem">
                {{ $booking->status->label() }}
            </span>
        </header>

        @include('partials.flash')

        <div class="dashboard__grid">
            {{-- Stylish Executive Session Details Card --}}
            <div class="session-card">
                <div class="session-hero">
                    <div class="session-hero__user">
                        @if($booking->freelancer->avatar_url)
                            <img src="{{ $booking->freelancer->avatar_url }}" alt="{{ $booking->freelancer->full_name }}" class="session-hero__avatar">
                        @else
                            <div class="session-hero__avatar">
                                {{ strtoupper(substr($booking->freelancer->first_name, 0, 1) . substr($booking->freelancer->last_name, 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <h3 class="session-hero__name">{{ $booking->freelancer->full_name }}</h3>
                            <div class="session-hero__role">
                                <span>💻 Freelancer / Learner</span>
                                &middot;
                                <a href="{{ route('users.profile', $booking->freelancer) }}" style="color: #ffffff; text-decoration: underline; font-weight: 600;">View Profile →</a>
                            </div>
                        </div>
                    </div>
                    <div class="session-hero__status">
                        <span style="width:8px; height:8px; border-radius:50%; background:#818cf8; display:inline-block"></span>
                        {{ $booking->status->label() }}
                    </div>
                </div>

                {{-- Metric Pills Row --}}
                <div class="session-metrics">
                    <div class="session-metric-pill">
                        <span class="session-metric-pill__label">⏱️ Duration</span>
                        <span class="session-metric-pill__value">{{ $booking->gig->duration_minutes }} Mins</span>
                    </div>
                    <div class="session-metric-pill">
                        <span class="session-metric-pill__label">💰 Session Fee</span>
                        <span class="session-metric-pill__value">{{ $booking->formatted_price }}</span>
                    </div>
                    <div class="session-metric-pill">
                        <span class="session-metric-pill__label">📅 Date</span>
                        <span class="session-metric-pill__value">
                            {{ $booking->proposed_date ? $booking->proposed_date->format('M d, Y') : 'Pending' }}
                        </span>
                    </div>
                    @if ($booking->proposed_time)
                        <div class="session-metric-pill">
                            <span class="session-metric-pill__label">⏰ Time</span>
                            <span class="session-metric-pill__value">
                                {{ \Carbon\Carbon::parse($booking->proposed_time)->format('g:i A') }}
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Details Info Grid --}}
                <div class="session-info-grid">
                    <div class="session-info-item">
                        <span class="session-info-item__label">Mentorship Gig</span>
                        <span class="session-info-item__value">
                            <a href="{{ route('gigs.show', $booking->gig->slug) }}" style="color:var(--color-primary); font-weight:700;">
                                {{ $booking->gig->title }} →
                            </a>
                        </span>
                    </div>

                    {{-- Always show the freelancer's requested date & time — mentor cannot change this --}}
                    @if ($booking->proposed_date)
                        <div class="session-info-item">
                            <span class="session-info-item__label">
                                {{ in_array($booking->status->value, ['scheduled','completed','reviewed']) ? 'Session Date & Time' : 'Requested Date & Time' }}
                            </span>
                            <span class="session-info-item__value">
                                📅 {{ $booking->proposed_date->format('D, M d Y') }}
                                @if ($booking->proposed_time)
                                    &nbsp;⏰ {{ \Carbon\Carbon::parse($booking->proposed_time)->format('g:i A') }}
                                @endif
                            </span>
                        </div>
                    @elseif ($booking->scheduled_at)
                        {{-- Fallback for legacy bookings without proposed_date --}}
                        <div class="session-info-item">
                            <span class="session-info-item__label">Confirmed Schedule</span>
                            <span class="session-info-item__value">
                                🕒 {{ $booking->scheduled_at->format('D, M d Y \a\t g:i A') }}
                            </span>
                        </div>
                    @endif

                    @if ($booking->meeting_link)
                        <div class="session-meeting-box">
                            <div class="session-meeting-box__info">
                                <div class="session-meeting-box__icon">🎥</div>
                                <div>
                                    <h4 class="session-meeting-box__title">Video Meeting Link Set</h4>
                                    <span class="session-meeting-box__sub">You have attached the meeting link for this session</span>
                                </div>
                            </div>
                            <a href="{{ $booking->meeting_link }}" target="_blank" rel="noopener" class="btn btn--primary" style="background:#10b981; border:none; border-radius:10px; font-weight:700; gap:0.5rem; display:inline-flex; align-items:center;">
                                <span>Join Video Session</span>
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </a>
                        </div>
                    @endif

                    {{-- Note Conversation Thread --}}
                    <div class="session-notes-thread">
                        <div class="session-notes-thread__header">
                            <span class="session-notes-thread__icon">&#x1F4AC;</span>
                            <span class="session-notes-thread__title">Session Notes</span>
                        </div>

                        @forelse ($booking->notes as $note)
                            <div class="session-note-bubble {{ $note->user_id === $booking->freelancer_id ? 'session-note-bubble--freelancer' : 'session-note-bubble--mentor' }}">
                                <div class="session-note-bubble__meta">
                                    <span class="session-note-bubble__avatar {{ $note->user_id === $booking->mentor_id ? 'session-note-bubble__avatar--mentor' : '' }}">
                                        {{ strtoupper(substr($note->user->first_name, 0, 1)) }}
                                    </span>
                                    <span class="session-note-bubble__name">{{ $note->user->full_name }}</span>
                                    <span class="session-note-bubble__role">
                                        @if ($note->user_id === $booking->freelancer_id)
                                            Freelancer
                                        @else
                                            Mentor (You)
                                        @endif
                                    </span>
                                    <span style="font-size: 0.7rem; color: #a1a1aa; margin-left: auto;">{{ $note->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="session-note-bubble__text">{{ $note->note }}</p>
                            </div>
                        @empty
                            <p style="font-size: .85rem; color: var(--color-text-muted); padding: 1rem;">No notes yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div>
                {{-- Accept / Decline --}}
                @if ($booking->status->value === 'requested')
                    @php
                        $booking->loadMissing('gig');
                        $acceptanceExpired = $booking->isAcceptanceExpired();
                    @endphp
                    <div class="panel" style="margin-bottom:1.5rem">
                        <div class="panel__header"><h2 class="panel__title">Respond to Request</h2></div>
                        @if ($acceptanceExpired)
                            <div class="panel__body">
                                <div style="display:flex;align-items:center;gap:.6rem;padding:.75rem 1rem;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:8px;">
                                    <span style="font-size:1.2rem">⚠️</span>
                                    <div>
                                        <p style="font-weight:600;color:#ef4444;margin:0 0 .2rem">Session Time Expired</p>
                                        <p style="font-size:.82rem;color:var(--color-text-muted);margin:0">
                                            The requested session time ({{ $booking->proposed_date->format('M d, Y') }}
                                            @if($booking->proposed_time)
                                                at {{ \Carbon\Carbon::parse($booking->proposed_time)->format('g:i A') }}
                                            @endif
                                            ) has already passed. This request can no longer be accepted.
                                        </p>
                                    </div>
                                </div>
                                {{-- Only allow decline --}}
                                <form method="POST" action="{{ route('bookings.status', $booking) }}" style="margin-top:.75rem"
                                      onsubmit="return confirm('Decline this request?')">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit" class="btn btn--error btn--sm" style="width:100%">Decline</button>
                                </form>
                            </div>
                        @else
                            <div class="panel__body" style="display:flex;gap:.75rem">
                                <form method="POST" action="{{ route('bookings.status', $booking) }}" style="flex:1">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="accepted">
                                    <button type="submit" class="btn btn--success btn--sm" style="width:100%">Accept</button>
                                </form>
                                <form method="POST" action="{{ route('bookings.status', $booking) }}" style="flex:1"
                                      onsubmit="return confirm('Decline this request?')">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit" class="btn btn--error btn--sm" style="width:100%">Decline</button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Schedule session --}}
                @if ($booking->status->value === 'accepted')
                    <div class="panel" style="margin-bottom:1.5rem">
                        <div class="panel__header"><h2 class="panel__title">Confirm &amp; Schedule Session</h2></div>
                        <div class="panel__body">
                            {{-- Show the locked session date/time from the freelancer --}}
                            @if ($booking->proposed_date)
                                <div style="display:flex;align-items:center;gap:.5rem;padding:.6rem .85rem;background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.2);border-radius:8px;margin-bottom:1rem;font-size:.85rem;">
                                    <span>📅</span>
                                    <span>
                                        <strong>Session time:</strong>
                                        {{ $booking->proposed_date->format('D, M d Y') }}
                                        @if ($booking->proposed_time)
                                            at {{ \Carbon\Carbon::parse($booking->proposed_time)->format('g:i A') }}
                                        @endif
                                        &nbsp;<span style="color:var(--color-text-muted);">(set by freelancer — cannot be changed)</span>
                                    </span>
                                </div>
                            @endif
                            <form method="POST" action="{{ route('bookings.status', $booking) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="scheduled">
                                <div class="form-group">
                                    <label class="form-label" for="meeting_link">Meeting Link</label>
                                    <input type="url" name="meeting_link" id="meeting_link" class="form-input"
                                           placeholder="https://zoom.us/j/..." value="{{ old('meeting_link') }}">
                                </div>
                                <button type="submit" class="btn btn--primary btn--sm" style="width:100%">Confirm Schedule</button>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- Mark as completed --}}
                @if ($booking->status->value === 'scheduled')
                    @php
                        $booking->loadMissing('gig');
                        $sessionEnd = $booking->sessionEndDateTime();
                        $canComplete = $booking->canBeMarkedComplete();
                    @endphp
                    <div class="panel" style="margin-bottom:1.5rem">
                        <div class="panel__header"><h2 class="panel__title">Session Complete?</h2></div>
                        <div class="panel__body">
                            @if ($canComplete)
                                <form method="POST" action="{{ route('bookings.status', $booking) }}"
                                      onsubmit="return confirm('Mark this session as completed?')">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="btn btn--success btn--sm" style="width:100%">Mark as Completed</button>
                                </form>
                            @else
                                <div style="display:flex;align-items:center;gap:.6rem;padding:.75rem 1rem;background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.25);border-radius:8px;">
                                    <span style="font-size:1.2rem">⏳</span>
                                    <div>
                                        <p style="font-weight:600;color:var(--color-primary);margin:0 0 .2rem">Session Not Finished Yet</p>
                                        <p style="font-size:.82rem;color:var(--color-text-muted);margin:0">
                                            You can mark this session as completed after
                                            <strong>{{ $sessionEnd->format('D, M d Y \a\t g:i A') }}</strong>.
                                        </p>
                                    </div>
                                </div>
                                <button type="button" class="btn btn--success btn--sm" style="width:100%;margin-top:.75rem;opacity:.45;cursor:not-allowed" disabled>Mark as Completed</button>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Add Note to Thread --}}
                <div class="panel" style="margin-bottom:1.5rem; border-left: 3px solid var(--color-primary)">
                    <div class="panel__header">
                        <h2 class="panel__title">&#x1F4AC; Add a Note</h2>
                    </div>
                    <div class="panel__body">
                        <p style="font-size:.85rem; color:var(--color-text-muted); margin-bottom:.75rem">
                            Send a message to the freelancer to discuss session details.
                        </p>
                        <form method="POST" action="{{ route('bookings.storeNote', $booking) }}">
                            @csrf
                            <div class="form-group" style="margin-bottom:.75rem">
                                <textarea name="note" id="note" class="form-input"
                                          rows="3"
                                          placeholder="Type your message here..."
                                          style="width:100%; resize:vertical" required></textarea>
                                @error('note')
                                    <p style="color:#ef4444; font-size:.8rem; margin-top:.35rem">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn--primary btn--sm" style="width:100%">
                                Send Message &#x2192;
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Leave review for freelancer --}}
                @if ($booking->canBeReviewedBy(auth()->user()))
                    <div class="review-panel">
                        <div class="review-panel__header">
                            <h2 class="review-panel__title">
                                <span>⭐</span> Leave a Review for Freelancer
                            </h2>
                        </div>
                        <div class="review-panel__body">
                            <form method="POST" action="{{ route('bookings.review', $booking) }}">
                                @csrf
                                <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                                
                                <div style="margin-bottom: 1.25rem;">
                                    <label class="review-comment-box__label">Overall Rating</label>
                                    @include('partials._star_rating', ['inputName' => 'rating', 'currentRating' => 0])
                                </div>

                                <div class="review-comment-box">
                                    <div class="review-comment-box__header">
                                        <label class="review-comment-box__label" for="comment">
                                            <span>💬</span> Your Feedback & Comments <span style="font-weight:400;color:var(--color-gray-400)">(optional)</span>
                                        </label>
                                    </div>
                                    <textarea name="comment" id="comment" class="review-comment-box__textarea" rows="4"
                                              placeholder="Share details about this freelancer's progress, work ethic, and recommendations...">{{ old('comment') }}</textarea>
                                </div>

                                <label class="review-toggle-label">
                                    <input type="checkbox" name="is_public" value="1" class="review-toggle-input" checked>
                                    <span class="review-toggle-switch"></span>
                                    <span>Make review public on freelancer's profile</span>
                                </label>

                                <button type="submit" class="btn btn--primary" style="width:100%; border-radius:12px; padding:0.8rem; font-weight:600; display:flex; align-items:center; justify-content:center; gap:0.5rem;">
                                    <span>Submit Review</span>
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- Show mentor's submitted review --}}
                @if ($booking->mentorReview)
                    <div class="review-panel">
                        <div class="review-panel__header">
                            <h2 class="review-panel__title">✨ Your Review of Freelancer</h2>
                        </div>
                        <div class="review-panel__body">
                            <div class="review-display-card">
                                <div class="review-display-card__quote">“</div>
                                <div class="review-card__stars" style="margin-bottom:0.75rem; display:flex; gap:0.25rem;">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <svg width="18" height="18" viewBox="0 0 16 16" fill="{{ $i <= $booking->mentorReview->rating ? '#f59e0b' : 'none' }}"
                                             stroke="{{ $i <= $booking->mentorReview->rating ? 'none' : '#d1d5db' }}">
                                            <path d="M8 1l2.12 4.3 4.74.7-3.43 3.34.81 4.72L8 11.77l-4.24 2.29.81-4.72L1.14 6l4.74-.7z"/>
                                        </svg>
                                    @endfor
                                    <span style="font-weight:700; font-size:0.875rem; color:#f59e0b; margin-left:0.35rem;">{{ $booking->mentorReview->rating }}.0/5</span>
                                </div>
                                @if ($booking->mentorReview->comment)
                                    <p class="review-display-card__comment">"{{ $booking->mentorReview->comment }}"</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Show freelancer's review of mentor --}}
                @if ($booking->freelancerReview)
                    <div class="review-panel">
                        <div class="review-panel__header">
                            <h2 class="review-panel__title">🎓 Freelancer's Review of You</h2>
                        </div>
                        <div class="review-panel__body">
                            <div class="review-display-card">
                                <div class="review-display-card__quote">“</div>
                                <div class="review-card__stars" style="margin-bottom:0.75rem; display:flex; gap:0.25rem;">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <svg width="18" height="18" viewBox="0 0 16 16" fill="{{ $i <= $booking->freelancerReview->rating ? '#f59e0b' : 'none' }}"
                                             stroke="{{ $i <= $booking->freelancerReview->rating ? 'none' : '#d1d5db' }}">
                                            <path d="M8 1l2.12 4.3 4.74.7-3.43 3.34.81 4.72L8 11.77l-4.24 2.29.81-4.72L1.14 6l4.74-.7z"/>
                                        </svg>
                                    @endfor
                                    <span style="font-weight:700; font-size:0.875rem; color:#f59e0b; margin-left:0.35rem;">{{ $booking->freelancerReview->rating }}.0/5</span>
                                </div>
                                @if ($booking->freelancerReview->comment)
                                    <p class="review-display-card__comment">"{{ $booking->freelancerReview->comment }}"</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ─── Long-Term Mentorship Request ─── --}}
                @if (in_array($booking->status->value, ['completed', 'reviewed']))
                    @php $rel = $booking->mentorshipRelationship; @endphp
                    @if ($rel)
                        <div class="lt-card">
                            <div class="lt-card__header">
                                <h2 class="lt-card__title">
                                    <span>🚀</span> Long-Term Mentorship Request
                                </h2>
                                <span class="lt-card__status-badge">
                                    <span style="width:7px;height:7px;border-radius:50%;background:#ffffff;display:inline-block"></span>
                                    {{ $rel->status->label() }}
                                </span>
                            </div>
                            <div class="lt-card__body">
                                {{-- Applicant Header --}}
                                <div class="lt-card__applicant">
                                    @if($booking->freelancer->avatar_url)
                                        <img src="{{ $booking->freelancer->avatar_url }}" alt="{{ $booking->freelancer->full_name }}" class="lt-card__avatar">
                                    @else
                                        <div class="lt-card__avatar" style="background:#e0e7ff; color:#4f46e5; display:flex; align-items:center; justify-content:center; font-weight:700;">
                                            {{ strtoupper(substr($booking->freelancer->first_name, 0, 1) . substr($booking->freelancer->last_name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <h3 class="lt-card__applicant-name">{{ $booking->freelancer->full_name }}</h3>
                                        <div class="lt-card__applicant-sub">
                                            Sent {{ $rel->requested_at->diffForHumans() }} &middot; Learner
                                        </div>
                                    </div>
                                </div>

                                {{-- Info Grid --}}
                                <div class="lt-card__info-grid">
                                    <div class="lt-card__info-item">
                                        <span class="lt-card__info-label">💳 Payment Model</span>
                                        <span class="lt-card__info-val">{{ ucfirst($rel->payment_type ?? 'Not specified') }}</span>
                                    </div>
                                    <div class="lt-card__info-item">
                                        <span class="lt-card__info-label">💰 Rate / Amount</span>
                                        <span class="lt-card__info-val">
                                            {{ $rel->payment_amount ? 'Rs ' . number_format($rel->payment_amount, 2) : 'To discuss' }}
                                        </span>
                                    </div>
                                    @if($rel->duration_months)
                                    <div class="lt-card__info-item">
                                        <span class="lt-card__info-label">⏱️ Duration</span>
                                        <span class="lt-card__info-val">{{ $rel->duration_months }} Month{{ $rel->duration_months > 1 ? 's' : '' }}</span>
                                    </div>
                                    @endif
                                </div>

                                @if($rel->payment_notes)
                                    <div class="lt-card__notes">
                                        <div class="lt-card__notes-label">📝 Applicant Notes & Terms</div>
                                        <p class="lt-card__notes-text">"{{ $rel->payment_notes }}"</p>
                                    </div>
                                @endif

                                @if($rel->isPending())
                                    <div class="lt-card__actions">
                                        <form method="POST" action="{{ route('mentor.lms.relationships.accept', $rel) }}" style="flex:1">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn--success" style="width:100%; border-radius:12px; padding:0.8rem; font-weight:700; display:flex; align-items:center; justify-content:center; gap:0.4rem;">
                                                <span>✓ Accept & Build Course</span>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('mentor.lms.relationships.decline', $rel) }}" style="flex:1" onsubmit="return confirm('Decline this long-term request?')">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn--error" style="width:100%; border-radius:12px; padding:0.8rem; font-weight:700; display:flex; align-items:center; justify-content:center; gap:0.4rem;">
                                                <span>✕ Decline</span>
                                            </button>
                                        </form>
                                    </div>
                                @elseif($rel->isAccepted())
                                    <a href="{{ route('mentor.lms.courses.index', $rel) }}" class="lt-card__btn-primary">
                                        <span>→ Manage Courses for {{ $booking->freelancer->first_name }}</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @else
                        {{-- No request yet — nudge mentor to the relationships page --}}
                        <div class="panel" style="margin-top:1.5rem;border-left:3px solid var(--color-gray-300)">
                            <div class="panel__body" style="color:var(--color-text-muted);font-size:.875rem">
                                The freelancer hasn't sent a long-term request yet.
                                <a href="{{ route('mentor.lms.relationships.index') }}"
                                   style="margin-left:.25rem">View all relationships →</a>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
