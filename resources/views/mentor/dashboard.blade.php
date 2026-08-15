@extends('layouts.app')

@section('title', 'Mentor Dashboard')

@section('content')
<section class="dashboard">
    <div class="dashboard__inner">
        <header class="dashboard__header">
            <div>
                <h1 class="dashboard__title">Mentor Dashboard</h1>
                <p class="dashboard__subtitle">Manage your gigs, sessions, and earnings.</p>
            </div>
            <a href="{{ route('mentor.gigs.create') }}" class="btn btn--primary btn--sm">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                New Gig
            </a>
        </header>

        <div class="stat-cards">
            <div class="stat-card">
                <div class="stat-card__icon stat-card__icon--green">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 2v16M2 10h16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </div>
                <div class="stat-card__info">
                    <span class="stat-card__value">Rs {{ number_format($stats['earnings'], 2) }}</span>
                    <span class="stat-card__label">Total Earnings</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon stat-card__icon--blue">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.5"/><path d="M7 10l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div class="stat-card__info">
                    <span class="stat-card__value">{{ $stats['total_sessions'] }}</span>
                    <span class="stat-card__label">Sessions Done</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon stat-card__icon--amber">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 2l2.5 5.08L18 7.9l-4 3.9.94 5.5L10 14.77l-4.94 2.6L6 11.8l-4-3.9 5.5-.82L10 2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div class="stat-card__info">
                    <span class="stat-card__value">{{ number_format($stats['average_rating'], 1) }}</span>
                    <span class="stat-card__label">Avg Rating</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon stat-card__icon--purple">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 2a6 6 0 00-6 6v2H2v8h16v-8h-2V8a6 6 0 00-6-6z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M10 14v.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </div>
                <div class="stat-card__info">
                    <span class="stat-card__value">{{ $stats['pending_requests'] }}</span>
                    <span class="stat-card__label">Pending Requests</span>
                </div>
            </div>
        </div>

        <div class="dashboard__grid">
            <div class="panel">
                <div class="panel__header">
                    <h2 class="panel__title">Pending Requests</h2>
                    <a href="{{ route('mentor.bookings.index') }}" class="panel__link">All Bookings</a>
                </div>
                <div class="panel__body">
                    @if ($recentRequests->count() > 0)
                        <div class="booking-list">
                            @foreach ($recentRequests as $booking)
                                <div class="booking-item">
                                    <div class="booking-item__avatar">{{ strtoupper(substr($booking->freelancer->first_name, 0, 1)) }}</div>
                                    <div class="booking-item__info">
                                        <p class="booking-item__title">{{ $booking->gig->title }}</p>
                                        <p class="booking-item__subtitle">{{ $booking->freelancer->full_name }}</p>
                                    </div>
                                    <form method="POST" action="{{ route('bookings.status', $booking) }}" class="booking-item__actions">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="accepted">
                                        <button type="submit" class="btn btn--success btn--xs">Accept</button>
                                    </form>
                                    <form method="POST" action="{{ route('bookings.status', $booking) }}" class="booking-item__actions">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="btn btn--error btn--xs">Decline</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty">
                            <p class="empty__text">No pending requests. Your gigs are getting views!</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="panel">
                <div class="panel__header">
                    <h2 class="panel__title">Upcoming Sessions</h2>
                    <a href="{{ route('mentor.bookings.index') }}" class="panel__link">View All</a>
                </div>
                <div class="panel__body">
                    @if ($upcomingSessions->count() > 0)
                        <div class="booking-list">
                            @foreach ($upcomingSessions as $session)
                                <a href="{{ route('mentor.bookings.show', $session) }}" class="booking-item">
                                    <div class="booking-item__avatar">{{ strtoupper(substr($session->freelancer->first_name, 0, 1)) }}</div>
                                    <div class="booking-item__info">
                                        <p class="booking-item__title">{{ $session->gig->title }}</p>
                                        <p class="booking-item__subtitle">{{ $session->freelancer->full_name }}</p>
                                    </div>
                                    <span class="booking-item__status badge badge--info">{{ $session->scheduled_at?->format('M d') }}</span>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="empty">
                            <p class="empty__text">No upcoming sessions scheduled.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel__header">
                <h2 class="panel__title">Recent Reviews</h2>
            </div>
            <div class="panel__body">
                @if ($recentReviews->count() > 0)
                    <div class="reviews-grid">
                        @foreach ($recentReviews as $review)
                            <div class="review-card">
                                <div class="review-card__header">
                                    <div class="review-card__stars">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <svg width="12" height="12" viewBox="0 0 16 16" fill="{{ $i <= $review->rating ? 'currentColor' : 'none' }}" class="review__star {{ $i <= $review->rating ? 'review__star--filled' : '' }}"><path d="M8 1l2.12 4.3 4.74.7-3.43 3.34.81 4.72L8 11.77l-4.24 2.29.81-4.72L1.14 6l4.74-.7z"/></svg>
                                        @endfor
                                    </div>
                                    <span class="review-card__time">{{ $review->created_at->diffForHumans() }}</span>
                                </div>
                                @if ($review->comment)
                                    <p class="review-card__text">"{{ Str::limit($review->comment, 150) }}"</p>
                                @endif
                                <p class="review-card__meta">on {{ $review->gig->title }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty">
                        <p class="empty__text">No reviews yet. Complete sessions to receive feedback!</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
