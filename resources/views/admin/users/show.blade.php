@extends('layouts.admin')

@section('title', 'User Profile — ' . $user->full_name)

@section('content')
<div class="adm-page-header">
    <div>
        <h1 class="adm-page-title">{{ $user->full_name }}</h1>
        <p class="adm-page-subtitle">{{ $user->isMentor() ? 'Mentor' : 'Freelancer' }} account details and management</p>
    </div>
    <div class="adm-page-header__actions">
        <a href="{{ $user->isMentor() ? route('admin.users.mentors') : route('admin.users.freelancers') }}"
           class="adm-btn adm-btn--ghost adm-btn--sm">
            ← Back to {{ $user->isMentor() ? 'Mentors' : 'Freelancers' }}
        </a>
    </div>
</div>

<div>

        {{-- Flash --}}
        @if (session('success'))
            <div class="adm-alert adm-alert--success">
                <svg width="16" height="16" viewBox="0 0 20 20" fill="none" style="flex-shrink:0"><path d="M5 10l3 3 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- Profile hero --}}
        <div class="ushow-hero">
            <div class="ushow-hero__left">
                <img src="{{ $user->avatar_url }}" alt="{{ $user->full_name }}" class="ushow-hero__avatar">
                <div class="ushow-hero__meta">
                    <h1 class="ushow-hero__name">{{ $user->full_name }}</h1>
                    <p class="ushow-hero__email">{{ $user->email }}</p>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:8px;">
                        <span class="adm-badge {{ $user->isMentor() ? 'adm-badge--blue' : 'adm-badge--purple' }}">
                            {{ $user->role->label() }}
                        </span>
                        <span class="adm-badge {{ $user->is_active ? 'adm-badge--green' : 'adm-badge--red' }}">
                            {{ $user->is_active ? 'Active' : 'Disabled' }}
                        </span>
                        @if ($user->isMentor())
                            @php $vs = $user->mentorProfile?->verification_status ?? 'none'; @endphp
                            <span class="adm-badge {{ $vs === 'verified' ? 'adm-badge--green' : ($vs === 'pending' ? 'adm-badge--amber' : 'adm-badge--gray') }}">
                                {{ ucfirst($vs) }}
                            </span>
                        @endif
                    </div>
                    @if ($user->bio)
                        <p class="ushow-hero__bio">{{ $user->bio }}</p>
                    @endif
                </div>
            </div>

            {{-- Action buttons --}}
            <div class="ushow-hero__actions">
                {{-- Toggle active/disabled --}}
                <form method="POST"
                      action="{{ route('admin.users.toggle', $user) }}"
                      onsubmit="return confirm('{{ $user->is_active ? 'Disable' : 'Re-enable' }} {{ addslashes($user->full_name) }}?')">
                    @csrf @method('PATCH')
                    <button type="submit"
                            class="adm-btn {{ $user->is_active ? 'adm-btn--amber-action' : 'adm-btn--success' }}">
                        {{ $user->is_active ? '⏸ Disable Account' : '▶ Enable Account' }}
                    </button>
                </form>

                {{-- Permanent removal --}}
                <form method="POST"
                      action="{{ route('admin.users.destroy', $user) }}"
                      onsubmit="return confirm('PERMANENTLY DELETE {{ strtoupper(addslashes($user->full_name)) }}? All their data will be erased. This cannot be undone.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="adm-btn adm-btn--danger">
                        🗑 Remove Account
                    </button>
                </form>
            </div>
        </div>

        {{-- Stats row --}}
        <div class="ushow-stats">

            {{-- Average rating --}}
            <div class="ushow-stat">
                <span class="ushow-stat__value {{ $avgRating !== null && $avgRating <= 1.5 ? 'ushow-stat__value--danger' : '' }}">
                    {{ $avgRating !== null ? number_format($avgRating, 1) : '—' }}
                </span>
                <span class="ushow-stat__label">Avg Rating</span>
                @if ($avgRating !== null)
                    <div class="star-row" style="justify-content:center;margin-top:4px;">
                        @for ($s = 1; $s <= 5; $s++)
                            <svg class="star {{ $s <= round($avgRating) ? 'star--filled' : 'star--empty' }}"
                                 width="14" height="14" viewBox="0 0 20 20" fill="none">
                                <path d="M10 2l2.5 5.08L18 7.9l-4 3.9.94 5.5L10 14.77l-4.94 2.6L6 11.8l-4-3.9 5.5-.82L10 2z"
                                      stroke="currentColor" stroke-width="1.5"
                                      stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        @endfor
                    </div>
                @endif
            </div>

            <div class="ushow-stat">
                <span class="ushow-stat__value">{{ $totalReviews }}</span>
                <span class="ushow-stat__label">Total Reviews</span>
            </div>

            <div class="ushow-stat">
                <span class="ushow-stat__value">{{ $totalBookings }}</span>
                <span class="ushow-stat__label">Bookings</span>
            </div>

            <div class="ushow-stat">
                <span class="ushow-stat__value">{{ $user->created_at->diffForHumans() }}</span>
                <span class="ushow-stat__label">Member Since</span>
            </div>

            @if ($user->isMentor())
            <div class="ushow-stat">
                <span class="ushow-stat__value">{{ $user->gigs->count() }}</span>
                <span class="ushow-stat__label">Active Gigs</span>
            </div>
            @endif
        </div>

        {{-- Low rating warning banner --}}
        @if ($avgRating !== null && $avgRating <= 1.5 && $totalReviews >= 3)
        <div class="ushow-warning">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path d="M10 2l7.66 13.5H2.34L10 2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                <path d="M10 8v4M10 14v.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <div>
                <strong>Quality Review Recommended</strong>
                <p>
                    This user has an average rating of <strong>{{ number_format($avgRating, 1) }} ★</strong>
                    across <strong>{{ $totalReviews }}</strong> reviews — well below the platform standard.
                    Consider disabling or removing this account to maintain service quality.
                </p>
            </div>
        </div>
        @endif

        <div class="adm-grid-3" style="margin-top:20px;">

            {{-- Rating breakdown --}}
            <div class="adm-card">
                <div class="adm-card__header"><div class="adm-card__title">Rating Breakdown</div></div>
                <div class="adm-card__body">
                    @if ($totalReviews > 0)
                        @foreach ($ratingBreakdown as $stars => $count)
                        @php $pct = $totalReviews > 0 ? round($count / $totalReviews * 100) : 0; @endphp
                        <div class="rating-bar">
                            <span class="rating-bar__label">{{ $stars }}★</span>
                            <div class="rating-bar__track">
                                <div class="rating-bar__fill {{ $stars <= 2 ? 'rating-bar__fill--danger' : ($stars === 3 ? 'rating-bar__fill--amber' : 'rating-bar__fill--green') }}"
                                     style="width:{{ $pct }}%"></div>
                            </div>
                            <span class="rating-bar__count">{{ $count }}</span>
                        </div>
                        @endforeach
                    @else
                        <div class="adm-empty"><p class="adm-empty__text">No reviews yet.</p></div>
                    @endif
                </div>
            </div>

            {{-- Mentor profile details (mentor only) --}}
            @if ($user->isMentor() && $user->mentorProfile)
            <div class="adm-card">
                <div class="adm-card__header"><div class="adm-card__title">Mentor Profile</div></div>
                <div class="adm-card__body">
                    @php $mp = $user->mentorProfile; @endphp
                    <dl class="detail-list">
                        @if ($mp->headline)
                            <div class="detail-list__row">
                                <dt>Headline</dt>
                                <dd>{{ $mp->headline }}</dd>
                            </div>
                        @endif
                        @if ($mp->company)
                            <div class="detail-list__row">
                                <dt>Company</dt>
                                <dd>{{ $mp->company }}</dd>
                            </div>
                        @endif
                        @if ($mp->years_experience)
                            <div class="detail-list__row">
                                <dt>Experience</dt>
                                <dd>{{ $mp->years_experience }} years</dd>
                            </div>
                        @endif
                        @if ($mp->hourly_rate)
                            <div class="detail-list__row">
                                <dt>Hourly Rate</dt>
                                <dd>${{ number_format($mp->hourly_rate, 2) }}</dd>
                            </div>
                        @endif
                        <div class="detail-list__row">
                            <dt>Availability</dt>
                            <dd>{{ $mp->availability ?? '—' }}</dd>
                        </div>
                        <div class="detail-list__row">
                            <dt>Verified At</dt>
                            <dd>{{ $mp->verified_at ? $mp->verified_at->format('d M Y') : 'Not verified' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
            @endif

            {{-- Contact info --}}
            <div class="adm-card">
                <div class="adm-card__header"><div class="adm-card__title">Contact & Location</div></div>
                <div class="adm-card__body">
                    <dl class="detail-list">
                        <div class="detail-list__row">
                            <dt>Email</dt>
                            <dd>{{ $user->email }}</dd>
                        </div>
                        <div class="detail-list__row">
                            <dt>Location</dt>
                            <dd>{{ $user->location ?? '—' }}</dd>
                        </div>
                        <div class="detail-list__row">
                            <dt>Timezone</dt>
                            <dd>{{ $user->timezone ?? '—' }}</dd>
                        </div>
                        <div class="detail-list__row">
                            <dt>Email Verified</dt>
                            <dd>{{ $user->email_verified_at ? $user->email_verified_at->format('d M Y') : 'Not verified' }}</dd>
                        </div>
                        <div class="detail-list__row">
                            <dt>Joined</dt>
                            <dd>{{ $user->created_at->format('d M Y, H:i') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

        </div>

        {{-- Reviews list --}}
        <div class="adm-card" style="margin-top:20px;">
            <div class="adm-card__header">
                <div class="adm-card__title">All Received Reviews ({{ $totalReviews }})</div>
            </div>
            <div class="adm-card__body">
                @if ($reviews->count())
                    <div class="review-list">
                        @foreach ($reviews as $review)
                        <div class="review-card {{ $review->rating <= 1 ? 'review-card--danger' : '' }}">
                            <div class="review-card__header">
                                <div style="display:flex;align-items:center;gap:var(--space-2);">
                                    <img src="{{ $review->reviewer?->avatar_url }}"
                                         alt="{{ $review->reviewer?->full_name ?? 'Unknown' }}"
                                         style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
                                    <div>
                                        <p style="font-size:0.85rem;font-weight:600;">
                                            {{ $review->reviewer?->full_name ?? 'Unknown' }}
                                        </p>
                                        @if ($review->gig)
                                            <p style="font-size:0.75rem;color:var(--text-muted);">
                                                Gig: {{ Str::limit($review->gig->title, 40) }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                <div style="text-align:right;">
                                    <div class="star-row" style="justify-content:flex-end;">
                                        @for ($s = 1; $s <= 5; $s++)
                                            <svg class="star {{ $s <= $review->rating ? 'star--filled' : 'star--empty' }}"
                                                 width="13" height="13" viewBox="0 0 20 20" fill="none">
                                                <path d="M10 2l2.5 5.08L18 7.9l-4 3.9.94 5.5L10 14.77l-4.94 2.6L6 11.8l-4-3.9 5.5-.82L10 2z"
                                                      stroke="currentColor" stroke-width="1.5"
                                                      stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        @endfor
                                        <strong style="margin-left:4px;font-size:0.85rem;{{ $review->rating <= 1 ? 'color:#ef4444;' : '' }}">
                                            {{ $review->rating }}/5
                                        </strong>
                                    </div>
                                    <p style="font-size:0.75rem;color:var(--text-muted);">
                                        {{ $review->created_at->format('d M Y') }}
                                    </p>
                                </div>
                            </div>
                            @if ($review->comment)
                                <p class="review-card__comment">{{ $review->comment }}</p>
                            @endif
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="adm-empty"><p class="adm-empty__text">No reviews received yet.</p></div>
                @endif
            </div>
        </div>

</div>

<style>
/* ── Hero ── */
.ushow-hero {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24px;
    flex-wrap: wrap;
    background: var(--adm-surface);
    border: 1px solid var(--adm-border);
    border-radius: var(--adm-radius-lg);
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: var(--adm-shadow-sm);
}
.ushow-hero__left  { display: flex; align-items: flex-start; gap: 16px; flex: 1; }
.ushow-hero__avatar { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; flex-shrink: 0; border: 3px solid var(--adm-border); }
.ushow-hero__name  { font-size: 20px; font-weight: 700; margin: 0; color: var(--adm-text-900); }
.ushow-hero__email { font-size: 13px; color: var(--adm-text-400); margin: 2px 0 0; }
.ushow-hero__bio   { font-size: 13px; color: var(--adm-text-500); margin-top: 8px; max-width: 500px; line-height: 1.6; }
.ushow-hero__actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }

/* ── Stat row ── */
.ushow-stats {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}
.ushow-stat {
    flex: 1;
    min-width: 120px;
    background: var(--adm-surface);
    border: 1px solid var(--adm-border);
    border-radius: var(--adm-radius);
    padding: 16px;
    text-align: center;
    box-shadow: var(--adm-shadow-sm);
}
.ushow-stat__value { display: block; font-size: 26px; font-weight: 800; color: var(--adm-text-900); }
.ushow-stat__value--danger { color: #ef4444; }
.ushow-stat__label { display: block; font-size: 11px; color: var(--adm-text-400); margin-top: 4px; font-weight: 500; text-transform: uppercase; letter-spacing: .04em; }

/* ── Warning banner ── */
.ushow-warning {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: rgba(239,68,68,.08);
    border: 1px solid rgba(239,68,68,.3);
    border-left: 4px solid #ef4444;
    color: #b91c1c;
    border-radius: var(--adm-radius);
    padding: 16px;
    margin-bottom: 20px;
}
.ushow-warning svg { flex-shrink: 0; margin-top: 2px; }
.ushow-warning p { font-size: 13px; margin: 4px 0 0; }

/* ── Rating bar ── */
.rating-bar { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
.rating-bar__label { width: 24px; font-size: 12px; color: var(--adm-text-400); flex-shrink: 0; }
.rating-bar__track { flex: 1; height: 8px; background: var(--adm-border); border-radius: 99px; overflow: hidden; }
.rating-bar__fill  { height: 100%; border-radius: 99px; transition: width .3s ease; }
.rating-bar__fill--green  { background: #10b981; }
.rating-bar__fill--amber  { background: #f59e0b; }
.rating-bar__fill--danger { background: #ef4444; }
.rating-bar__count { width: 24px; font-size: 12px; text-align: right; flex-shrink: 0; color: var(--adm-text-500); font-weight: 600; }

/* ── Detail list ── */
.detail-list { display: flex; flex-direction: column; gap: 8px; }
.detail-list__row { display: flex; gap: 12px; font-size: 13px; }
.detail-list__row dt { width: 120px; flex-shrink: 0; color: var(--adm-text-400); font-weight: 500; }
.detail-list__row dd { margin: 0; color: var(--adm-text-700); }

/* ── Review list ── */
.review-list  { display: flex; flex-direction: column; gap: 12px; }
.review-card  { border: 1px solid var(--adm-border); border-radius: var(--adm-radius-sm); padding: 14px; }
.review-card--danger { border-color: rgba(239,68,68,.35); background: rgba(239,68,68,.04); }
.review-card__header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; }
.review-card__comment { font-size: 13px; color: var(--adm-text-500); margin-top: 10px; line-height: 1.6; }

/* ── Stars ── */
.star-row  { display: flex; align-items: center; gap: 2px; }
.star--filled path { fill: #f59e0b; stroke: #f59e0b; }
.star--empty  path { fill: none; stroke: var(--adm-text-400); }

/* ── Extra buttons ── */
.adm-btn--amber-action { background: #f59e0b; color: #fff; }
.adm-btn--amber-action:hover { background: #d97706; }
.adm-btn--success { background: #10b981; color: #fff; }
.adm-btn--success:hover { background: #059669; }
</style>
@endsection
