@extends('layouts.app')

@section('title', 'Booking Details')

@section('content')
<section class="dashboard">
    <div class="dashboard__inner">
        <header class="dashboard__header">
            <div>
                <a href="{{ route('freelancer.bookings.index') }}" class="btn btn--ghost btn--sm" style="margin-bottom:.5rem">
                    &larr; Back to Bookings
                </a>
                <h1 class="dashboard__title">{{ $booking->gig->title }}</h1>
                <p class="dashboard__subtitle">
                    with <strong>{{ $booking->mentor->full_name }}</strong>
                    &middot; Booked {{ $booking->requested_at->format('M d, Y') }}
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
                        @if($booking->mentor->avatar_url)
                            <img src="{{ $booking->mentor->avatar_url }}" alt="{{ $booking->mentor->full_name }}" class="session-hero__avatar">
                        @else
                            <div class="session-hero__avatar">
                                {{ strtoupper(substr($booking->mentor->first_name, 0, 1) . substr($booking->mentor->last_name, 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <h3 class="session-hero__name">{{ $booking->mentor->full_name }}</h3>
                            <div class="session-hero__role">
                                <span>🎓 Mentor</span>
                                &middot;
                                <a href="{{ route('users.profile', $booking->mentor) }}" style="color: #ffffff; text-decoration: underline; font-weight: 600;">View Profile →</a>
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
                        <span class="session-metric-pill__label">💰 Total Fee</span>
                        <span class="session-metric-pill__value">{{ $booking->formatted_price }}</span>
                    </div>
                    <div class="session-metric-pill">
                        <span class="session-metric-pill__label">📅 Date</span>
                        <span class="session-metric-pill__value">
                            {{ $booking->scheduled_at ? $booking->scheduled_at->format('M d, Y') : ($booking->proposed_date ? $booking->proposed_date->format('M d, Y') : 'Pending') }}
                        </span>
                    </div>
                </div>

                {{-- Details Info Grid --}}
                <div class="session-info-grid">
                    <div class="session-info-item">
                        <span class="session-info-item__label">Mentorship Session</span>
                        <span class="session-info-item__value">
                            <a href="{{ route('gigs.show', $booking->gig->slug) }}" style="color:var(--color-primary); font-weight:700;">
                                {{ $booking->gig->title }} →
                            </a>
                        </span>
                    </div>

                    @if ($booking->scheduled_at)
                        <div class="session-info-item">
                            <span class="session-info-item__label">Confirmed Schedule</span>
                            <span class="session-info-item__value">
                                🕒 {{ $booking->scheduled_at->format('D, M d Y \a\t g:i A') }}
                            </span>
                        </div>
                    @elseif ($booking->proposed_date)
                        <div class="session-info-item">
                            <span class="session-info-item__label">Proposed Date</span>
                            <span class="session-info-item__value">
                                📅 {{ $booking->proposed_date->format('D, M d Y') }}
                            </span>
                        </div>
                    @endif

                    @if ($booking->meeting_link)
                        <div class="session-meeting-box">
                            <div class="session-meeting-box__info">
                                <div class="session-meeting-box__icon">🎥</div>
                                <div>
                                    <h4 class="session-meeting-box__title">Online Video Call Ready</h4>
                                    <span class="session-meeting-box__sub">Your mentor has shared the meeting link</span>
                                </div>
                            </div>
                            <a href="{{ $booking->meeting_link }}" target="_blank" rel="noopener" class="btn btn--primary" style="background:#10b981; border:none; border-radius:10px; font-weight:700; gap:0.5rem; display:inline-flex; align-items:center;">
                                <span>Join Video Session</span>
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </a>
                        </div>
                    @endif

                    @if ($booking->freelancer_note)
                        <div class="session-note-box">
                            <div class="session-note-box__label">Your Note / Learning Goals</div>
                            <p class="session-note-box__text">"{{ $booking->freelancer_note }}"</p>
                        </div>
                    @endif
                </div>
            </div>

            <div>
                {{-- Cancel action --}}
                @if (in_array($booking->status->value, ['requested', 'accepted', 'scheduled']))
                    <div class="panel" style="margin-bottom:1.5rem">
                        <div class="panel__header"><h2 class="panel__title">Actions</h2></div>
                        <div class="panel__body">
                            <form method="POST" action="{{ route('bookings.cancel', $booking) }}"
                                  onsubmit="return confirm('Cancel this session?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn--error btn--sm" style="width:100%">Cancel Session</button>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- Leave review --}}
                @if ($booking->canBeReviewedBy(auth()->user()))
                    <div class="review-panel">
                        <div class="review-panel__header">
                            <h2 class="review-panel__title">
                                <span>⭐</span> Leave a Review for Mentor
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
                                              placeholder="Share details about your mentorship session, feedback, and key takeaways...">{{ old('comment') }}</textarea>
                                </div>

                                <label class="review-toggle-label">
                                    <input type="checkbox" name="is_public" value="1" class="review-toggle-input" checked>
                                    <span class="review-toggle-switch"></span>
                                    <span>Make review public on mentor's profile</span>
                                </label>

                                <button type="submit" class="btn btn--primary" style="width:100%; border-radius:12px; padding:0.8rem; font-weight:600; display:flex; align-items:center; justify-content:center; gap:0.5rem;">
                                    <span>Submit Review</span>
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- Show freelancer's submitted review --}}
                @if ($booking->freelancerReview)
                    <div class="review-panel">
                        <div class="review-panel__header">
                            <h2 class="review-panel__title">✨ Your Review of Mentor</h2>
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

                {{-- Show mentor's review of freelancer --}}
                @if ($booking->mentorReview)
                    <div class="review-panel">
                        <div class="review-panel__header">
                            <h2 class="review-panel__title">🎓 Mentor's Review of You</h2>
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

                {{-- ─── Long-Term Mentorship Invite ─── --}}
                @if (in_array($booking->status->value, ['completed', 'reviewed']))
                    @if ($booking->mentorshipRelationship)
                        {{-- Relationship already exists — show status --}}
                        <div class="lt-card">
                            <div class="lt-card__header">
                                <h2 class="lt-card__title">
                                    <span>🚀</span> Long-Term Mentorship
                                </h2>
                                <span class="lt-card__status-badge">
                                    <span style="width:7px;height:7px;border-radius:50%;background:#ffffff;display:inline-block"></span>
                                    {{ $booking->mentorshipRelationship->status->label() }}
                                </span>
                            </div>
                            <div class="lt-card__body">
                                <div class="lt-card__applicant">
                                    @if($booking->mentor->avatar_url)
                                        <img src="{{ $booking->mentor->avatar_url }}" alt="{{ $booking->mentor->full_name }}" class="lt-card__avatar">
                                    @else
                                        <div class="lt-card__avatar" style="background:#e0e7ff; color:#4f46e5; display:flex; align-items:center; justify-content:center; font-weight:700;">
                                            {{ strtoupper(substr($booking->mentor->first_name, 0, 1) . substr($booking->mentor->last_name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <h3 class="lt-card__applicant-name">{{ $booking->mentor->full_name }}</h3>
                                        <div class="lt-card__applicant-sub">
                                            Sent {{ $booking->mentorshipRelationship->requested_at->diffForHumans() }} &middot; Mentor
                                        </div>
                                    </div>
                                </div>

                                <div class="lt-card__info-grid">
                                    <div class="lt-card__info-item">
                                        <span class="lt-card__info-label">💳 Payment Model</span>
                                        <span class="lt-card__info-val">{{ ucfirst($booking->mentorshipRelationship->payment_type ?? 'Not specified') }}</span>
                                    </div>
                                    <div class="lt-card__info-item">
                                        <span class="lt-card__info-label">💰 Rate / Amount</span>
                                        <span class="lt-card__info-val">
                                            {{ $booking->mentorshipRelationship->payment_amount ? '$' . number_format($booking->mentorshipRelationship->payment_amount, 2) : 'Custom' }}
                                        </span>
                                    </div>
                                    @if($booking->mentorshipRelationship->duration_months)
                                    <div class="lt-card__info-item">
                                        <span class="lt-card__info-label">⏱️ Duration</span>
                                        <span class="lt-card__info-val">{{ $booking->mentorshipRelationship->duration_months }} Month{{ $booking->mentorshipRelationship->duration_months > 1 ? 's' : '' }}</span>
                                    </div>
                                    @endif
                                </div>

                                @if($booking->mentorshipRelationship->isAccepted())
                                    <a href="{{ route('lms.index') }}" class="lt-card__btn-primary">
                                        <span>→ Go to My Learning Portal</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @else
                        {{-- No relationship yet — show invite form --}}
                        <div class="panel" style="margin-top:1.5rem;border-left:3px solid var(--color-primary)">
                            <div class="panel__header">
                                <h2 class="panel__title">Continue Long-Term?</h2>
                            </div>
                            <div class="panel__body">
                                <p style="color:var(--color-text-muted);font-size:.9rem;margin-bottom:1rem">
                                    Enjoyed this session? Invite <strong>{{ $booking->mentor->full_name }}</strong>
                                    to be your long-term mentor. Agree on terms below and they'll be notified.
                                </p>
                                <form method="POST" action="{{ route('lms.relationships.request') }}" class="form">
                                    @csrf
                                    <input type="hidden" name="booking_id" value="{{ $booking->id }}">

                                    <div class="form__group">
                                        <label class="form__label" for="payment_type">Payment Arrangement</label>
                                        <select name="payment_type" id="payment_type" class="form__input" required>
                                            <option value="">Select type…</option>
                                            <option value="hourly">Hourly</option>
                                            <option value="monthly">Monthly Retainer</option>
                                            <option value="per_module">Per Module</option>
                                            <option value="custom">Custom / Discuss Later</option>
                                        </select>
                                    </div>

                                    <div class="form__group">
                                        <label class="form__label" for="payment_amount">Agreed Amount (USD, optional)</label>
                                        <input type="number" name="payment_amount" id="payment_amount"
                                               class="form__input" min="0" step="0.01" placeholder="e.g. 50.00">
                                    </div>

                                    <div class="form__group">
                                        <label class="form__label" for="payment_notes">Notes / Terms (optional)</label>
                                        <textarea name="payment_notes" id="payment_notes" class="form__input" rows="3"
                                                  placeholder="Any details you'd like to share with the mentor..."></textarea>
                                    </div>

                                    <button type="submit" class="btn btn--primary btn--sm" style="width:100%">
                                        Send Long-Term Request →
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
