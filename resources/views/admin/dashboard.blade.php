@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<section class="dashboard">
    <div class="dashboard__inner">
        <header class="dashboard__header">
            <div>
                <h1 class="dashboard__title">Admin Dashboard</h1>
                <p class="dashboard__subtitle">Platform overview and management.</p>
            </div>
        </header>

        <div class="stat-cards" style="grid-template-columns: repeat(2, 1fr); gap: var(--space-3);">
            <div class="stat-card">
                <div class="stat-card__icon stat-card__icon--blue"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 2a6 6 0 00-6 6v2H2v8h16v-8h-2V8a6 6 0 00-5-5.916V2h-1z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M10 14v.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></div>
                <div class="stat-card__info"><span class="stat-card__value">{{ $stats['users'] }}</span><span class="stat-card__label">Total Users</span></div>
            </div>
            <a href="{{ route('admin.users.mentors') }}" class="stat-card stat-card--link">
                <div class="stat-card__icon stat-card__icon--green"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="7" r="5" stroke="currentColor" stroke-width="1.5"/><path d="M2 18c0-4.418 3.582-8 8-8s8 3.582 8 8" stroke="currentColor" stroke-width="1.5"/></svg></div>
                <div class="stat-card__info"><span class="stat-card__value">{{ $stats['mentors'] }}</span><span class="stat-card__label">Mentors <span class="stat-card__cta">→ Manage</span></span></div>
            </a>
            <a href="{{ route('admin.users.freelancers') }}" class="stat-card stat-card--link">
                <div class="stat-card__icon stat-card__icon--purple"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 2l2.5 5.08L18 7.9l-4 3.9.94 5.5L10 14.77l-4.94 2.6L6 11.8l-4-3.9 5.5-.82L10 2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
                <div class="stat-card__info"><span class="stat-card__value">{{ $stats['freelancers'] }}</span><span class="stat-card__label">Freelancers <span class="stat-card__cta">→ Manage</span></span></div>
            </a>
            <a href="{{ route('admin.approvals.index') }}" class="stat-card stat-card--link {{ $stats['pending_approvals'] > 0 ? 'stat-card--alert' : '' }}">
                <div class="stat-card__icon stat-card__icon--amber"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M10 6v5l3 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></div>
                <div class="stat-card__info">
                    <span class="stat-card__value">{{ $stats['pending_approvals'] }}</span>
                    <span class="stat-card__label">Pending Approvals <span class="stat-card__cta">→ Review</span></span>
                </div>
            </a>
            <div class="stat-card">
                <div class="stat-card__icon stat-card__icon--blue"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M2 4h16v12H2z" stroke="currentColor" stroke-width="1.5"/><path d="M6 8h8M6 12h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></div>
                <div class="stat-card__info"><span class="stat-card__value">{{ $stats['gigs'] }}</span><span class="stat-card__label">Total Gigs</span></div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon stat-card__icon--green"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M5 10l3 3 7-7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
                <div class="stat-card__info"><span class="stat-card__value">{{ $stats['bookings'] }}</span><span class="stat-card__label">Bookings</span></div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon stat-card__icon--purple"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 2v16M2 10h16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></div>
                <div class="stat-card__info"><span class="stat-card__value">${{ number_format($stats['revenue'], 2) }}</span><span class="stat-card__label">Revenue</span></div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon stat-card__icon--amber"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 2l2.5 5.08L18 7.9l-4 3.9.94 5.5L10 14.77l-4.94 2.6L6 11.8l-4-3.9 5.5-.82L10 2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
                <div class="stat-card__info"><span class="stat-card__value">{{ $stats['average_rating'] }}</span><span class="stat-card__label">Avg Rating</span></div>
            </div>
        </div>

        <div class="dashboard__grid">
            <div class="panel">
                <div class="panel__header"><h2 class="panel__title">Recent Users</h2></div>
                <div class="panel__body">
                    @if ($recentUsers->count() > 0)
                        <div class="booking-list">
                            @foreach ($recentUsers as $user)
                                <div class="booking-item">
                                    <div class="booking-item__avatar">{{ strtoupper(substr($user->first_name, 0, 1)) }}</div>
                                    <div class="booking-item__info">
                                        <p class="booking-item__title">{{ $user->full_name }}</p>
                                        <p class="booking-item__subtitle">{{ $user->email }}</p>
                                    </div>
                                    <span class="badge badge--{{ $user->role->value === 'admin' ? 'purple' : ($user->role->value === 'mentor' ? 'info' : 'neutral') }}">{{ $user->role->label() }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty"><p class="empty__text">No users yet.</p></div>
                    @endif
                </div>
            </div>
            <div class="panel">
                <div class="panel__header"><h2 class="panel__title">Recent Bookings</h2></div>
                <div class="panel__body">
                    @if ($recentBookings->count() > 0)
                        <div class="booking-list">
                            @foreach ($recentBookings as $booking)
                                <div class="booking-item">
                                    <div class="booking-item__info">
                                        <p class="booking-item__title">{{ Str::limit($booking->gig->title, 30) }}</p>
                                        <p class="booking-item__subtitle">{{ $booking->freelancer->full_name }} &rarr; {{ $booking->mentor->full_name }}</p>
                                    </div>
                                    <span class="badge badge--{{ $booking->status->colorClass() }}">{{ $booking->status->label() }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty"><p class="empty__text">No bookings yet.</p></div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Quick Action buttons --}}
        <div class="quick-actions">
            <a href="{{ route('admin.approvals.index') }}" class="quick-action-btn quick-action-btn--green"
               style="position:relative;">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M5 10l3 3 7-7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Approve Registrations
                @if ($stats['pending_approvals'] > 0)
                    <span style="background:#ef4444;color:#fff;border-radius:99px;font-size:0.7rem;
                                 padding:1px 6px;position:absolute;top:-6px;right:-6px;font-weight:700;">
                        {{ $stats['pending_approvals'] }}
                    </span>
                @endif
            </a>
            <a href="{{ route('admin.users.mentors') }}" class="quick-action-btn quick-action-btn--blue">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="7" r="5" stroke="currentColor" stroke-width="1.5"/><path d="M2 18c0-4.418 3.582-8 8-8s8 3.582 8 8" stroke="currentColor" stroke-width="1.5"/></svg>
                View All Mentors
            </a>
            <a href="{{ route('admin.users.freelancers') }}" class="quick-action-btn quick-action-btn--purple">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 2l2.5 5.08L18 7.9l-4 3.9.94 5.5L10 14.77l-4.94 2.6L6 11.8l-4-3.9 5.5-.82L10 2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                View All Freelancers
            </a>
            <a href="{{ route('admin.users.mentors', ['max_rating' => 2, 'sort' => 'rating_asc']) }}" class="quick-action-btn quick-action-btn--danger">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 2l7.66 13.5H2.34L10 2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M10 8v4M10 14v.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                Low-Rated Mentors
            </a>
            <a href="{{ route('admin.users.freelancers', ['max_rating' => 2, 'sort' => 'rating_asc']) }}" class="quick-action-btn quick-action-btn--amber">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 2l7.66 13.5H2.34L10 2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M10 8v4M10 14v.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                Low-Rated Freelancers
            </a>
        </div>

    </div>
</section>

<style>
/* ── Stat-card link variant ── */
.stat-card--link {
    text-decoration: none;
    color: inherit;
    cursor: pointer;
    transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
}
.stat-card--link:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,.12);
    border-color: var(--primary, #4f46e5);
}
.stat-card__cta {
    font-size: 0.7rem;
    color: var(--primary, #4f46e5);
    font-weight: 600;
    margin-left: 4px;
    opacity: 0;
    transition: opacity .15s;
}
.stat-card--link:hover .stat-card__cta { opacity: 1; }

/* ── Quick actions row ── */
.quick-actions {
    display: flex;
    gap: var(--space-3);
    flex-wrap: wrap;
    margin-top: var(--space-5);
}
.quick-action-btn {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: opacity .15s, transform .15s;
    color: #fff;
}
.quick-action-btn:hover { opacity: .9; transform: translateY(-1px); }
.quick-action-btn--blue   { background: #3b82f6; }
.quick-action-btn--purple { background: #8b5cf6; }
.quick-action-btn--danger { background: #ef4444; }
.quick-action-btn--amber  { background: #f59e0b; }
.quick-action-btn--green  { background: #10b981; }

/* ── Stat-card alert pulse (pending approvals) ── */
.stat-card--alert {
    border-color: rgba(239,68,68,.4) !important;
    animation: card-pulse 2s ease-in-out infinite;
}
@keyframes card-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,.2); }
    50%       { box-shadow: 0 0 0 6px rgba(239,68,68,0); }
}
</style>
@endsection
