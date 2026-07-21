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
            <div class="panel">
                <div class="panel__header">
                    <h2 class="panel__title">Session Details</h2>
                </div>
                <div class="panel__body">
                    <dl class="detail-list">
                        <div class="detail-list__row">
                            <dt>Gig</dt>
                            <dd><a href="{{ route('gigs.show', $booking->gig->slug) }}">{{ $booking->gig->title }}</a></dd>
                        </div>
                        <div class="detail-list__row">
                            <dt>Mentor</dt>
                            <dd style="display:flex; align-items:center; gap:var(--space-3)">
                                {{ $booking->mentor->full_name }}
                                <a href="{{ route('users.profile', $booking->mentor) }}"
                                   class="btn btn--ghost btn--xs" style="padding:2px 8px; font-size:var(--text-xs)">
                                    View Profile
                                </a>
                            </dd>
                        </div>
                        <div class="detail-list__row">
                            <dt>Duration</dt>
                            <dd>{{ $booking->gig->duration_minutes }} minutes</dd>
                        </div>
                        <div class="detail-list__row">
                            <dt>Price</dt>
                            <dd>{{ $booking->formatted_price }}</dd>
                        </div>
                        <div class="detail-list__row">
                            <dt>Status</dt>
                            <dd><span class="badge badge--{{ $booking->status->colorClass() }}">{{ $booking->status->label() }}</span></dd>
                        </div>
                        @if ($booking->freelancer_note)
                            <div class="detail-list__row">
                                <dt>Your Note</dt>
                                <dd>{{ $booking->freelancer_note }}</dd>
                            </div>
                        @endif
                        @if ($booking->proposed_date)
                            <div class="detail-list__row">
                                <dt>Proposed Date</dt>
                                <dd>{{ $booking->proposed_date->format('D, M d Y') }}</dd>
                            </div>
                        @endif
                        @if ($booking->meeting_link)
                            <div class="detail-list__row">
                                <dt>Meeting Link</dt>
                                <dd><a href="{{ $booking->meeting_link }}" target="_blank" rel="noopener">Join Session &rarr;</a></dd>
                            </div>
                        @endif
                        @if ($booking->scheduled_at)
                            <div class="detail-list__row">
                                <dt>Scheduled</dt>
                                <dd>{{ $booking->scheduled_at->format('D, M d Y \a\t g:i A') }}</dd>
                            </div>
                        @endif
                    </dl>
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
                    <div class="panel" style="margin-top:1.5rem">
                        <div class="panel__header"><h2 class="panel__title">Leave a Review for Mentor</h2></div>
                        <div class="panel__body">
                            <form method="POST" action="{{ route('bookings.review', $booking) }}">
                                @csrf
                                <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                                <div class="form-group">
                                    <label class="form-label" for="rating">Rating</label>
                                    <select name="rating" id="rating" class="form-select" required>
                                        <option value="">Select rating</option>
                                        @for ($i = 5; $i >= 1; $i--)
                                            <option value="{{ $i }}">{{ $i }} Star{{ $i > 1 ? 's' : '' }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="comment">Comment (optional)</label>
                                    <textarea name="comment" id="comment" class="form-textarea" rows="4"
                                              placeholder="Share your experience with the mentor...">{{ old('comment') }}</textarea>
                                </div>
                                <label class="form-check" style="margin-bottom:1rem">
                                    <input type="checkbox" name="is_public" value="1" checked> Make review public
                                </label>
                                <button type="submit" class="btn btn--primary btn--sm" style="width:100%">Submit Review</button>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- Show freelancer's submitted review --}}
                @if ($booking->freelancerReview)
                    <div class="panel" style="margin-top:1.5rem">
                        <div class="panel__header"><h2 class="panel__title">Your Review of Mentor</h2></div>
                        <div class="panel__body">
                            <div class="review-card__stars" style="margin-bottom:.5rem">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg width="14" height="14" viewBox="0 0 16 16" fill="{{ $i <= $booking->freelancerReview->rating ? 'currentColor' : 'none' }}"
                                         class="review__star {{ $i <= $booking->freelancerReview->rating ? 'review__star--filled' : '' }}">
                                        <path d="M8 1l2.12 4.3 4.74.7-3.43 3.34.81 4.72L8 11.77l-4.24 2.29.81-4.72L1.14 6l4.74-.7z"/>
                                    </svg>
                                @endfor
                            </div>
                            @if ($booking->freelancerReview->comment)
                                <p style="color:var(--text-secondary)">"{{ $booking->freelancerReview->comment }}"</p>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Show mentor's review of freelancer --}}
                @if ($booking->mentorReview)
                    <div class="panel" style="margin-top:1.5rem">
                        <div class="panel__header"><h2 class="panel__title">Mentor's Review of You</h2></div>
                        <div class="panel__body">
                            <div class="review-card__stars" style="margin-bottom:.5rem">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg width="14" height="14" viewBox="0 0 16 16" fill="{{ $i <= $booking->mentorReview->rating ? 'currentColor' : 'none' }}"
                                         class="review__star {{ $i <= $booking->mentorReview->rating ? 'review__star--filled' : '' }}">
                                        <path d="M8 1l2.12 4.3 4.74.7-3.43 3.34.81 4.72L8 11.77l-4.24 2.29.81-4.72L1.14 6l4.74-.7z"/>
                                    </svg>
                                @endfor
                            </div>
                            @if ($booking->mentorReview->comment)
                                <p style="color:var(--text-secondary)">"{{ $booking->mentorReview->comment }}"</p>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- ─── Long-Term Mentorship Invite ─── --}}
                @if (in_array($booking->status->value, ['completed', 'reviewed']))
                    @if ($booking->mentorshipRelationship)
                        {{-- Relationship already exists — show status --}}
                        <div class="panel" style="margin-top:1.5rem;border-left:3px solid var(--color-primary)">
                            <div class="panel__header"><h2 class="panel__title">Long-Term Mentorship</h2></div>
                            <div class="panel__body">
                                <div style="display:flex;align-items:center;gap:.75rem">
                                    <span class="badge badge--{{ $booking->mentorshipRelationship->status->colorClass() }}">
                                        {{ $booking->mentorshipRelationship->status->label() }}
                                    </span>
                                    <span style="color:var(--color-text-muted);font-size:.9rem">
                                        Request sent {{ $booking->mentorshipRelationship->requested_at->diffForHumans() }}
                                    </span>
                                </div>
                                @if($booking->mentorshipRelationship->isAccepted())
                                    <a href="{{ route('lms.index') }}"
                                       class="btn btn--primary btn--sm" style="margin-top:1rem">
                                        → Go to My Learning
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
