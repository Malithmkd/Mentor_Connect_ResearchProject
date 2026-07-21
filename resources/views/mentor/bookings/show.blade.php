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
            <div class="panel">
                <div class="panel__header">
                    <h2 class="panel__title">Session Details</h2>
                </div>
                <div class="panel__body">
                    <dl class="detail-list">
                        <div class="detail-list__row">
                            <dt>Freelancer</dt>
                            <dd style="display:flex; align-items:center; gap:var(--space-3)">
                                {{ $booking->freelancer->full_name }}
                                <a href="{{ route('users.profile', $booking->freelancer) }}"
                                   class="btn btn--ghost btn--xs" style="padding:2px 8px; font-size:var(--text-xs)">
                                    View Profile
                                </a>
                            </dd>
                        </div>
                        <div class="detail-list__row">
                            <dt>Gig</dt>
                            <dd>{{ $booking->gig->title }}</dd>
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
                                <dt>Freelancer Note</dt>
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
                {{-- Accept / Decline --}}
                @if ($booking->status->value === 'requested')
                    <div class="panel" style="margin-bottom:1.5rem">
                        <div class="panel__header"><h2 class="panel__title">Respond to Request</h2></div>
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
                    </div>
                @endif

                {{-- Schedule session --}}
                @if ($booking->status->value === 'accepted')
                    <div class="panel" style="margin-bottom:1.5rem">
                        <div class="panel__header"><h2 class="panel__title">Schedule Session</h2></div>
                        <div class="panel__body">
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
                    <div class="panel" style="margin-bottom:1.5rem">
                        <div class="panel__header"><h2 class="panel__title">Session Complete?</h2></div>
                        <div class="panel__body">
                            <form method="POST" action="{{ route('bookings.status', $booking) }}"
                                  onsubmit="return confirm('Mark this session as completed?')">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="completed">
                                <button type="submit" class="btn btn--success btn--sm" style="width:100%">Mark as Completed</button>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- Cancel --}}
                @if (in_array($booking->status->value, ['requested', 'accepted', 'scheduled']))
                    <div class="panel">
                        <div class="panel__header"><h2 class="panel__title">Cancel</h2></div>
                        <div class="panel__body">
                            <form method="POST" action="{{ route('bookings.cancel', $booking) }}"
                                  onsubmit="return confirm('Cancel this session?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn--error btn--sm" style="width:100%">Cancel Session</button>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- Leave review for freelancer --}}
                @if ($booking->canBeReviewedBy(auth()->user()))
                    <div class="panel" style="margin-top:1.5rem">
                        <div class="panel__header"><h2 class="panel__title">Leave a Review for Freelancer</h2></div>
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
                                              placeholder="Share your experience with this freelancer...">{{ old('comment') }}</textarea>
                                </div>
                                <label class="form-check" style="margin-bottom:1rem">
                                    <input type="checkbox" name="is_public" value="1" checked> Make review public
                                </label>
                                <button type="submit" class="btn btn--primary btn--sm" style="width:100%">Submit Review</button>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- Show mentor's submitted review --}}
                @if ($booking->mentorReview)
                    <div class="panel" style="margin-top:1.5rem">
                        <div class="panel__header"><h2 class="panel__title">Your Review of Freelancer</h2></div>
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

                {{-- Show freelancer's review of mentor --}}
                @if ($booking->freelancerReview)
                    <div class="panel" style="margin-top:1.5rem">
                        <div class="panel__header"><h2 class="panel__title">Freelancer's Review of You</h2></div>
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

                {{-- ─── Long-Term Mentorship Request ─── --}}
                @if (in_array($booking->status->value, ['completed', 'reviewed']))
                    @php $rel = $booking->mentorshipRelationship; @endphp
                    @if ($rel)
                        <div class="panel" style="margin-top:1.5rem;border-left:3px solid var(--color-primary)">
                            <div class="panel__header">
                                <h2 class="panel__title">Long-Term Mentorship Request</h2>
                                <span class="badge badge--{{ $rel->status->colorClass() }}">
                                    {{ $rel->status->label() }}
                                </span>
                            </div>
                            <div class="panel__body">
                                <dl class="detail-list" style="margin-bottom:1rem">
                                    <div class="detail-list__row">
                                        <dt>From</dt>
                                        <dd><strong>{{ $booking->freelancer->full_name }}</strong></dd>
                                    </div>
                                    <div class="detail-list__row">
                                        <dt>Payment</dt>
                                        <dd>
                                            {{ ucfirst($rel->payment_type ?? 'Not specified') }}
                                            @if($rel->payment_amount)
                                                — <strong>${{ number_format($rel->payment_amount, 2) }}</strong>
                                            @endif
                                        </dd>
                                    </div>
                                    @if($rel->payment_notes)
                                    <div class="detail-list__row">
                                        <dt>Notes</dt>
                                        <dd>{{ $rel->payment_notes }}</dd>
                                    </div>
                                    @endif
                                    <div class="detail-list__row">
                                        <dt>Sent</dt>
                                        <dd>{{ $rel->requested_at->diffForHumans() }}</dd>
                                    </div>
                                </dl>

                                @if($rel->isPending())
                                    <div style="display:flex;gap:.75rem">
                                        <form method="POST"
                                              action="{{ route('mentor.lms.relationships.accept', $rel) }}"
                                              style="flex:1">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn--success btn--sm" style="width:100%">
                                                Accept & Build Course
                                            </button>
                                        </form>
                                        <form method="POST"
                                              action="{{ route('mentor.lms.relationships.decline', $rel) }}"
                                              style="flex:1"
                                              onsubmit="return confirm('Decline this long-term request?')">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn--error btn--sm" style="width:100%">
                                                Decline
                                            </button>
                                        </form>
                                    </div>
                                @elseif($rel->isAccepted())
                                    <a href="{{ route('mentor.lms.courses.index', $rel) }}"
                                       class="btn btn--primary btn--sm" style="width:100%">
                                        → Manage Courses for {{ $booking->freelancer->first_name }}
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
