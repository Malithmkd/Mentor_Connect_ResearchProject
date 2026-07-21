@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<section class="dashboard">
    <div class="dashboard__inner">
        <header class="dashboard__header">
            <div>
                <h1 class="dashboard__title">Welcome, {{ auth()->user()->first_name }}!</h1>
                <p class="dashboard__subtitle">
                    Manage your mentoring sessions and bookings.
                    @if ($stats['total_reviews'] > 0)
                        &middot; <span style="color: var(--color-warning); font-weight: 600;">&#9733; {{ number_format($stats['average_rating'], 1) }}</span>
                        ({{ $stats['total_reviews'] }} review{{ $stats['total_reviews'] > 1 ? 's' : '' }})
                    @endif
                </p>
            </div>
            <a href="{{ route('gigs.index') }}" class="btn btn--primary btn--sm">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                Book a Session
            </a>
        </header>

        <div class="stat-cards">
            <div class="stat-card">
                <div class="stat-card__icon stat-card__icon--blue">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 2l2.5 5.08L18 7.9l-4 3.9.94 5.5L10 14.77l-4.94 2.6L6 11.8l-4-3.9 5.5-.82L10 2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div class="stat-card__info">
                    <span class="stat-card__value">{{ $stats['total'] }}</span>
                    <span class="stat-card__label">Total Bookings</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon stat-card__icon--green">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.5"/><path d="M7 10l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div class="stat-card__info">
                    <span class="stat-card__value">{{ $stats['completed'] }}</span>
                    <span class="stat-card__label">Completed</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon stat-card__icon--amber">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 2l2.5 5.08L18 7.9l-4 3.9.94 5.5L10 14.77l-4.94 2.6L6 11.8l-4-3.9 5.5-.82L10 2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div class="stat-card__info">
                    <span class="stat-card__value">
                        @if ($stats['total_reviews'] > 0)
                            {{ number_format($stats['average_rating'], 1) }}
                        @else
                            —
                        @endif
                    </span>
                    <span class="stat-card__label">Avg Rating</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon stat-card__icon--purple">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 2a6 6 0 00-6 6v2H2v8h16v-8h-2V8a6 6 0 00-6-6z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M10 14v.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </div>
                <div class="stat-card__info">
                    <span class="stat-card__value">{{ $stats['pending_reviews'] }}</span>
                    <span class="stat-card__label">Pending Reviews</span>
                </div>
            </div>
        </div>

        <div class="dashboard__grid">
            <div class="panel">
                <div class="panel__header">
                    <h2 class="panel__title">Recent Bookings</h2>
                    <a href="{{ route('freelancer.bookings.index') }}" class="panel__link">View All</a>
                </div>
                <div class="panel__body">
                    @if ($recentBookings->count() > 0)
                        <div class="booking-list">
                            @foreach ($recentBookings as $booking)
                                <a href="{{ route('freelancer.bookings.show', $booking) }}" class="booking-item">
                                    <div class="booking-item__avatar">{{ strtoupper(substr($booking->mentor->first_name, 0, 1) . substr($booking->mentor->last_name, 0, 1)) }}</div>
                                    <div class="booking-item__info">
                                        <p class="booking-item__title">{{ $booking->gig->title }}</p>
                                        <p class="booking-item__subtitle">with {{ $booking->mentor->full_name }}</p>
                                    </div>
                                    <span class="booking-item__status badge badge--{{ $booking->status->colorClass() }}">{{ $booking->status->label() }}</span>
                                    <span class="booking-item__price">{{ $booking->formatted_price }}</span>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="empty">
                            <p class="empty__text">No bookings yet. <a href="{{ route('gigs.index') }}">Browse mentors</a> to get started.</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="panel">
                <div class="panel__header">
                    <h2 class="panel__title">Recommended for You</h2>
                    <a href="{{ route('gigs.index') }}" class="panel__link">Explore</a>
                </div>
                <div class="panel__body">
                    @if ($recommendedGigs->count() > 0)
                        <div class="gig-list">
                            @foreach ($recommendedGigs as $gig)
                                <a href="{{ route('gigs.show', $gig->slug) }}" class="gig-list__item">
                                    <div class="gig-list__avatar">{{ strtoupper(substr($gig->mentor->first_name, 0, 1)) }}</div>
                                    <div class="gig-list__info">
                                        <p class="gig-list__title">{{ Str::limit($gig->title, 50) }}</p>
                                        <p class="gig-list__subtitle">{{ $gig->mentor->full_name }}</p>
                                    </div>
                                    <span class="gig-list__price">{{ $gig->formatted_price }}</span>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="empty">
                            <p class="empty__text">No recommendations yet. Browse <a href="{{ route('gigs.index') }}">all mentors</a>.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Recent Reviews received by this freelancer --}}
        <div class="panel" style="margin-top: 1.5rem">
            <div class="panel__header">
                <h2 class="panel__title">Reviews I've Received</h2>
            </div>
            <div class="panel__body">
                @if ($recentReviews->count() > 0)
                    <div class="reviews-grid">
                        @foreach ($recentReviews as $review)
                            <div class="review-card">
                                <div class="review-card__header">
                                    <div class="review-card__stars">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <svg width="12" height="12" viewBox="0 0 16 16"
                                                 fill="{{ $i <= $review->rating ? 'currentColor' : 'none' }}"
                                                 class="review__star {{ $i <= $review->rating ? 'review__star--filled' : '' }}">
                                                <path d="M8 1l2.12 4.3 4.74.7-3.43 3.34.81 4.72L8 11.77l-4.24 2.29.81-4.72L1.14 6l4.74-.7z"/>
                                            </svg>
                                        @endfor
                                    </div>
                                    <span class="review-card__time">{{ $review->created_at->diffForHumans() }}</span>
                                </div>
                                @if ($review->comment)
                                    <p class="review-card__text">"{{ Str::limit($review->comment, 150) }}"</p>
                                @endif
                                <p class="review-card__meta">
                                    by {{ $review->reviewer->full_name }}
                                    &middot; on {{ $review->gig->title }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty">
                        <p class="empty__text">No reviews yet. Complete sessions to receive feedback from mentors!</p>
                    </div>
                @endif
            </div>
        </div>

    </div>
</section>
@endsection

