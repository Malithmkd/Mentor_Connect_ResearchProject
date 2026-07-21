@extends('layouts.app')

@section('title', 'My Bookings')

@section('content')
<section class="dashboard">
    <div class="dashboard__inner">
        <header class="dashboard__header">
            <div>
                <h1 class="dashboard__title">My Bookings</h1>
                <p class="dashboard__subtitle">Track all your mentoring sessions.</p>
            </div>
            <a href="{{ route('gigs.index') }}" class="btn btn--primary btn--sm">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                Book a Session
            </a>
        </header>

        @if ($pendingReview > 0)
            <div class="alert alert--warning">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="1.5"/><path d="M8 5v3.5M8 10.5v.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                You have <strong>{{ $pendingReview }}</strong> session{{ $pendingReview > 1 ? 's' : '' }} awaiting your review.
            </div>
        @endif

        @include('partials.flash')

        <div class="panel">
            <div class="panel__header">
                <h2 class="panel__title">All Bookings ({{ $bookings->total() }})</h2>
            </div>
            <div class="panel__body">
                @if ($bookings->count() > 0)
                    <div class="booking-list">
                        @foreach ($bookings as $booking)
                            <a href="{{ route('freelancer.bookings.show', $booking) }}" class="booking-item">
                                <div class="booking-item__avatar">
                                    {{ strtoupper(substr($booking->mentor->first_name, 0, 1) . substr($booking->mentor->last_name, 0, 1)) }}
                                </div>
                                <div class="booking-item__info">
                                    <p class="booking-item__title">{{ $booking->gig->title }}</p>
                                    <p class="booking-item__subtitle">
                                        with {{ $booking->mentor->full_name }}
                                        &middot; {{ $booking->requested_at->format('M d, Y') }}
                                    </p>
                                </div>
                                <span class="badge badge--{{ $booking->status->colorClass() }}">
                                    {{ $booking->status->label() }}
                                </span>
                                <span class="booking-item__price">{{ $booking->formatted_price }}</span>
                            </a>
                        @endforeach
                    </div>
                    <div class="pagination-wrapper">
                        {{ $bookings->links() }}
                    </div>
                @else
                    <div class="empty">
                        <p class="empty__text">No bookings yet. <a href="{{ route('gigs.index') }}">Browse mentors</a> to get started.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
