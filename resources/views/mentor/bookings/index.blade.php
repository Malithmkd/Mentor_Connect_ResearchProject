@extends('layouts.app')

@section('title', 'Session Requests')

@section('content')
<section class="dashboard">
    <div class="dashboard__inner">
        <header class="dashboard__header">
            <div>
                <h1 class="dashboard__title">Session Requests</h1>
                <p class="dashboard__subtitle">Manage your incoming and active mentoring sessions.</p>
            </div>
            @if ($pendingResponse > 0)
                <span class="badge badge--warning" style="font-size:.9rem;padding:.4rem 1rem">
                    {{ $pendingResponse }} pending
                </span>
            @endif
        </header>

        @include('partials.flash')

        <div class="panel">
            <div class="panel__header">
                <h2 class="panel__title">All Bookings ({{ $bookings->total() }})</h2>
            </div>
            <div class="panel__body">
                @if ($bookings->count() > 0)
                    <div class="booking-list">
                        @foreach ($bookings as $booking)
                            <div class="booking-item" style="display:flex;align-items:center;gap:1rem">
                                <div class="booking-item__avatar">
                                    {{ strtoupper(substr($booking->freelancer->first_name, 0, 1) . substr($booking->freelancer->last_name, 0, 1)) }}
                                </div>
                                <div class="booking-item__info" style="flex:1">
                                    <p class="booking-item__title">{{ $booking->gig->title }}</p>
                                    <p class="booking-item__subtitle" style="display:flex; align-items:center; gap:var(--space-2)">
                                        {{ $booking->freelancer->full_name }}
                                        <a href="{{ route('users.profile', $booking->freelancer) }}"
                                           style="font-size:var(--text-xs); color:var(--color-primary); font-weight:500; white-space:nowrap">
                                            View Profile
                                        </a>
                                        &middot; {{ $booking->requested_at->format('M d, Y') }}
                                    </p>
                                </div>
                                <span class="badge badge--{{ $booking->status->colorClass() }}">
                                    {{ $booking->status->label() }}
                                </span>
                                <span class="booking-item__price">{{ $booking->formatted_price }}</span>

                                {{-- Quick actions for pending requests --}}
                                @if ($booking->status->value === 'requested')
                                    <form method="POST" action="{{ route('bookings.status', $booking) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="accepted">
                                        <button type="submit" class="btn btn--success btn--xs">Accept</button>
                                    </form>
                                    <form method="POST" action="{{ route('bookings.status', $booking) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="btn btn--error btn--xs">Decline</button>
                                    </form>
                                @endif

                                <a href="{{ route('mentor.bookings.show', $booking) }}" class="btn btn--ghost btn--xs">View</a>
                            </div>
                        @endforeach
                    </div>
                    <div class="pagination-wrapper">
                        {{ $bookings->links() }}
                    </div>
                @else
                    <div class="empty">
                        <p class="empty__text">No bookings yet. Once freelancers book your gigs, they'll appear here.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
