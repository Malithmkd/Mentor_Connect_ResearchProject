@extends('layouts.admin')

@section('title', 'Dashboard')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
{{-- Page Header --}}
<div class="adm-page-header">
    <div>
        <h1 class="adm-page-title">Admin Dashboard</h1>
        <p class="adm-page-subtitle">Platform overview, metrics, and management actions.</p>
    </div>
</div>

{{-- Statistics Cards (Top Grid) --}}
<div class="adm-stat-grid">
    {{-- Total Users --}}
    <a href="{{ route('admin.users.mentors') }}" class="adm-stat-card">
        <div class="adm-stat-card__icon adm-stat-card__icon--blue">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
        <div class="adm-stat-card__body">
            <div class="adm-stat-card__value">{{ $stats['users'] }}</div>
            <div class="adm-stat-card__label">Total Users</div>
        </div>
        <span class="adm-stat-card__arrow">→</span>
    </a>

    {{-- Mentors --}}
    <a href="{{ route('admin.users.mentors') }}" class="adm-stat-card">
        <div class="adm-stat-card__icon adm-stat-card__icon--green">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="8" r="5" stroke="currentColor" stroke-width="2"/>
                <path d="M3 21v-2a7 7 0 0114 0v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M16 11l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="adm-stat-card__body">
            <div class="adm-stat-card__value">{{ $stats['mentors'] }}</div>
            <div class="adm-stat-card__label">Mentors</div>
        </div>
        <span class="adm-stat-card__arrow">→</span>
    </a>

    {{-- Freelancers --}}
    <a href="{{ route('admin.users.freelancers') }}" class="adm-stat-card">
        <div class="adm-stat-card__icon adm-stat-card__icon--purple">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <path d="M16 16v1a2 2 0 01-2 2H3a2 2 0 01-2-2V7a2 2 0 012-2h11a2 2 0 012 2v1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M18 8h4a1 1 0 011 1v6a1 1 0 01-1 1h-4l-3 3V5l3 3z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="adm-stat-card__body">
            <div class="adm-stat-card__value">{{ $stats['freelancers'] }}</div>
            <div class="adm-stat-card__label">Freelancers</div>
        </div>
        <span class="adm-stat-card__arrow">→</span>
    </a>

    {{-- Pending Approvals --}}
    <a href="{{ route('admin.approvals.index') }}" class="adm-stat-card {{ $stats['pending_approvals'] > 0 ? 'adm-stat-card--alert' : '' }}">
        <div class="adm-stat-card__icon adm-stat-card__icon--amber">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                <path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
        <div class="adm-stat-card__body">
            <div class="adm-stat-card__value">{{ $stats['pending_approvals'] }}</div>
            <div class="adm-stat-card__label">Pending Approvals</div>
        </div>
        <span class="adm-stat-card__arrow">→</span>
    </a>

    {{-- Total Gigs --}}
    <div class="adm-stat-card">
        <div class="adm-stat-card__icon adm-stat-card__icon--indigo">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <path d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z" stroke="currentColor" stroke-width="2"/>
                <path d="M8 10h8M8 14h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
        <div class="adm-stat-card__body">
            <div class="adm-stat-card__value">{{ $stats['gigs'] }}</div>
            <div class="adm-stat-card__label">Total Gigs</div>
        </div>
    </div>

    {{-- Total Bookings --}}
    <div class="adm-stat-card">
        <div class="adm-stat-card__icon adm-stat-card__icon--blue">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
        <div class="adm-stat-card__body">
            <div class="adm-stat-card__value">{{ $stats['bookings'] }}</div>
            <div class="adm-stat-card__label">Total Bookings</div>
        </div>
    </div>
</div>

{{-- Charts Section --}}
<div class="adm-grid-3" style="margin-bottom: 24px;">
    {{-- User Growth --}}
    <div class="adm-card">
        <div class="adm-card__header">
            <div>
                <h2 class="adm-card__title">User Growth</h2>
                <p class="adm-card__subtitle">New registrations (Last 7 days)</p>
            </div>
        </div>
        <div class="adm-card__body">
            <div class="adm-chart-wrap">
                <canvas id="userGrowthChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Booking Statistics --}}
    <div class="adm-card">
        <div class="adm-card__header">
            <div>
                <h2 class="adm-card__title">Booking Statistics</h2>
                <p class="adm-card__subtitle">Breakdown by current status</p>
            </div>
        </div>
        <div class="adm-card__body">
            <div class="adm-chart-wrap">
                <canvas id="bookingStatsChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Mentor vs Freelancer Distribution --}}
    <div class="adm-card">
        <div class="adm-card__header">
            <div>
                <h2 class="adm-card__title">User Distribution</h2>
                <p class="adm-card__subtitle">Mentors vs Freelancers</p>
            </div>
        </div>
        <div class="adm-card__body">
            <div class="adm-chart-wrap">
                <canvas id="userDistChart"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- Two-Column Grid: Recent Users & Recent Bookings --}}
<div class="adm-grid-2" style="margin-bottom: 24px;">
    {{-- Recent Users Panel --}}
    <div class="adm-card">
        <div class="adm-card__header">
            <div>
                <h2 class="adm-card__title">Recent Users</h2>
                <p class="adm-card__subtitle">Latest registered accounts</p>
            </div>
            <a href="{{ route('admin.users.mentors') }}" class="adm-btn adm-btn--ghost adm-btn--sm">View All</a>
        </div>
        <div class="adm-table-wrap">
            @if($recentUsers->count() > 0)
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentUsers as $user)
                            <tr>
                                <td>
                                    <div class="adm-user-row">
                                        <div class="adm-user-initials">{{ strtoupper(substr($user->first_name, 0, 1)) }}</div>
                                        <div>
                                            <div class="adm-user-name">{{ $user->full_name }}</div>
                                            <div class="adm-user-email">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="adm-badge adm-badge--{{ $user->role->value === 'admin' ? 'purple' : ($user->role->value === 'mentor' ? 'blue' : 'gray') }}">
                                        {{ $user->role->label() }}
                                    </span>
                                </td>
                                <td>
                                    @if($user->account_status === 'approved')
                                        <span class="adm-badge adm-badge--green">Approved</span>
                                    @elseif($user->account_status === 'pending')
                                        <span class="adm-badge adm-badge--amber">Pending</span>
                                    @else
                                        <span class="adm-badge adm-badge--red">{{ ucfirst($user->account_status) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="adm-empty">
                    <p class="adm-empty__text">No recent users found.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Recent Bookings Panel --}}
    <div class="adm-card">
        <div class="adm-card__header">
            <div>
                <h2 class="adm-card__title">Recent Bookings</h2>
                <p class="adm-card__subtitle">Latest session requests</p>
            </div>
        </div>
        <div class="adm-table-wrap">
            @if($recentBookings->count() > 0)
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Gig / Participants</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentBookings as $booking)
                            <tr>
                                <td>
                                    <div class="adm-user-name">{{ Str::limit($booking->gig->title ?? 'Untitled Gig', 36) }}</div>
                                    <div class="adm-user-email">{{ $booking->freelancer->first_name ?? 'User' }} → {{ $booking->mentor->first_name ?? 'Mentor' }}</div>
                                </td>
                                <td>
                                    <span class="adm-badge adm-badge--{{ $booking->status->colorClass() === 'success' ? 'green' : ($booking->status->colorClass() === 'warning' ? 'amber' : ($booking->status->colorClass() === 'danger' ? 'red' : 'blue')) }}">
                                        {{ $booking->status->label() }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="adm-empty">
                    <p class="adm-empty__text">No recent bookings found.</p>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Quick Action Buttons in a separate card --}}
<div class="adm-card">
    <div class="adm-card__header">
        <div>
            <h2 class="adm-card__title">Quick Actions</h2>
            <p class="adm-card__subtitle">Fast access to key management workflows</p>
        </div>
    </div>
    <div class="adm-quick-actions">
        <a href="{{ route('admin.approvals.index') }}" class="adm-quick-action adm-quick-action--green">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Approve Registrations
            @if($stats['pending_approvals'] > 0)
                <span class="adm-quick-action__badge">{{ $stats['pending_approvals'] }}</span>
            @endif
        </a>
        <a href="{{ route('admin.users.mentors') }}" class="adm-quick-action adm-quick-action--blue">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="8" r="5" stroke="currentColor" stroke-width="2"/>
                <path d="M3 21v-2a7 7 0 0114 0v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            View All Mentors
        </a>
        <a href="{{ route('admin.users.freelancers') }}" class="adm-quick-action adm-quick-action--purple">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M16 16v1a2 2 0 01-2 2H3a2 2 0 01-2-2V7a2 2 0 012-2h11a2 2 0 012 2v1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            View All Freelancers
        </a>
        <a href="{{ route('admin.users.mentors', ['max_rating' => 2, 'sort' => 'rating_asc']) }}" class="adm-quick-action adm-quick-action--red">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Low-Rated Mentors
        </a>
        <a href="{{ route('admin.users.freelancers', ['max_rating' => 2, 'sort' => 'rating_asc']) }}" class="adm-quick-action adm-quick-action--amber">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Low-Rated Freelancers
        </a>
        <a href="{{ route('admin.audit-log') }}" class="adm-quick-action adm-quick-action--indigo">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/>
                <path d="M7 8h10M7 12h10M7 16h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Audit Log
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const primaryColor = '#3b82f6';
    const greenColor = '#10b981';
    const purpleColor = '#8b5cf6';
    const amberColor = '#f59e0b';
    const redColor = '#ef4444';

    // 1. User Growth Chart
    const growthCtx = document.getElementById('userGrowthChart');
    if (growthCtx) {
        new Chart(growthCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($charts['userGrowthDates'] ?? []) !!},
                datasets: [{
                    label: 'New Users',
                    data: {!! json_encode($charts['userGrowthCounts'] ?? []) !!},
                    borderColor: primaryColor,
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // 2. Booking Statistics Chart
    const bookingCtx = document.getElementById('bookingStatsChart');
    if (bookingCtx) {
        const bookingData = {!! json_encode($charts['bookingStats'] ?? []) !!};
        new Chart(bookingCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(bookingData),
                datasets: [{
                    label: 'Bookings',
                    data: Object.values(bookingData),
                    backgroundColor: [amberColor, primaryColor, greenColor, redColor],
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // 3. Mentor vs Freelancer Distribution Chart
    const distCtx = document.getElementById('userDistChart');
    if (distCtx) {
        const distData = {!! json_encode($charts['userDistribution'] ?? []) !!};
        new Chart(distCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(distData),
                datasets: [{
                    data: Object.values(distData),
                    backgroundColor: [greenColor, purpleColor],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 12 } } }
                }
            }
        });
    }
});
</script>
@endpush
