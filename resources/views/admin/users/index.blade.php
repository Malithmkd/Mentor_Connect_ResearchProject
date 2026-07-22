@extends('layouts.admin')

@section('title', $title)

@section('content')
<div class="adm-page-header">
    <div>
        <h1 class="adm-page-title">{{ $title }}</h1>
        <p class="adm-page-subtitle">Review, disable, or remove accounts based on community ratings.</p>
    </div>
    <div class="adm-page-header__actions">
        <a href="{{ route('admin.dashboard') }}" class="adm-btn adm-btn--ghost adm-btn--sm">← Back to Dashboard</a>
    </div>
</div>

<div class="dashboard__inner">

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="alert alert--success" style="margin-bottom:var(--space-4);">
                <svg width="18" height="18" viewBox="0 0 20 20" fill="none"><path d="M5 10l3 3 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- Filters --}}
        <form method="GET" action="{{ request()->url() }}"
              style="display:flex;flex-wrap:wrap;gap:var(--space-3);align-items:flex-end;margin-bottom:var(--space-5);">

            <div style="flex:1;min-width:200px;">
                <label class="form__label" for="search">Search by name</label>
                <input id="search" name="search" type="text" class="form__input"
                       placeholder="e.g. John Smith"
                       value="{{ $filters['search'] ?? '' }}">
            </div>

            <div>
                <label class="form__label" for="max_rating">Max avg. rating</label>
                <select id="max_rating" name="max_rating" class="form__select">
                    <option value="">Any</option>
                    @foreach ([1,2,3,4] as $r)
                        <option value="{{ $r }}" {{ ($filters['max_rating'] ?? '') == $r ? 'selected' : '' }}>
                            {{ $r }} star{{ $r > 1 ? 's' : '' }} & below
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form__label" for="status">Status</label>
                <select id="status" name="status" class="form__select">
                    <option value="">All</option>
                    <option value="active"   {{ ($filters['status'] ?? '') === 'active'   ? 'selected' : '' }}>Active</option>
                    <option value="disabled" {{ ($filters['status'] ?? '') === 'disabled' ? 'selected' : '' }}>Disabled</option>
                </select>
            </div>

            <div>
                <label class="form__label" for="sort">Sort by</label>
                <select id="sort" name="sort" class="form__select">
                    <option value="newest"      {{ ($filters['sort'] ?? '') === 'newest'      ? 'selected' : '' }}>Newest</option>
                    <option value="rating_asc"  {{ ($filters['sort'] ?? '') === 'rating_asc'  ? 'selected' : '' }}>Rating ↑</option>
                    <option value="rating_desc" {{ ($filters['sort'] ?? '') === 'rating_desc' ? 'selected' : '' }}>Rating ↓</option>
                    <option value="name"        {{ ($filters['sort'] ?? '') === 'name'        ? 'selected' : '' }}>Name A–Z</option>
                </select>
            </div>

            <div style="display:flex;gap:var(--space-2);">
                <button type="submit" class="btn btn--primary btn--sm">Apply</button>
                <a href="{{ request()->url() }}" class="btn btn--ghost btn--sm">Reset</a>
            </div>
        </form>

        {{-- Table --}}
        <div class="panel">
            <div class="panel__body" style="padding:0;">
                @if ($users->count())
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Rating</th>
                                <th>Reviews</th>
                                @if ($role === 'mentor')
                                <th>Verification</th>
                                @endif
                                <th>Joined</th>
                                <th>Status</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                            @php
                                $avg  = round($user->avg_rating ?? 0, 1);
                                $reviewCount = $user->review_count ?? 0;
                                $isLowRating = $reviewCount >= 3 && $avg <= 1.5;
                            @endphp
                            <tr class="{{ $isLowRating ? 'admin-table__row--warning' : '' }}">
                                {{-- User info --}}
                                <td>
                                    <div style="display:flex;align-items:center;gap:var(--space-3);">
                                        <img src="{{ $user->avatar_url }}"
                                             alt="{{ $user->full_name }}"
                                             class="admin-table__avatar">
                                        <div>
                                            <p class="admin-table__name">{{ $user->full_name }}</p>
                                            <p class="admin-table__email">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Star rating --}}
                                <td>
                                    @if ($reviewCount > 0)
                                        <div class="star-row">
                                            @for ($s = 1; $s <= 5; $s++)
                                                <svg class="star {{ $s <= round($avg) ? 'star--filled' : 'star--empty' }}"
                                                     width="14" height="14" viewBox="0 0 20 20" fill="none">
                                                    <path d="M10 2l2.5 5.08L18 7.9l-4 3.9.94 5.5L10 14.77l-4.94 2.6L6 11.8l-4-3.9 5.5-.82L10 2z"
                                                          stroke="currentColor" stroke-width="1.5"
                                                          stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            @endfor
                                            <span class="star-row__label
                                                {{ $avg <= 1.5 && $reviewCount >= 3 ? 'star-row__label--danger' : '' }}">
                                                {{ number_format($avg, 1) }}
                                            </span>
                                        </div>
                                    @else
                                        <span style="color:var(--text-muted);font-size:0.8rem;">No reviews</span>
                                    @endif
                                </td>

                                {{-- Review count --}}
                                <td>
                                    <span style="font-size:0.875rem;">{{ $reviewCount }}</span>
                                </td>

                                {{-- Verification (mentor only) --}}
                                @if ($role === 'mentor')
                                <td>
                                    @php $vs = $user->mentorProfile?->verification_status ?? 'none'; @endphp
                                    <span class="badge badge--{{ $vs === 'verified' ? 'success' : ($vs === 'pending' ? 'amber' : 'neutral') }}">
                                        {{ ucfirst($vs) }}
                                    </span>
                                </td>
                                @endif

                                {{-- Joined --}}
                                <td style="font-size:0.8rem;color:var(--text-muted);">
                                    {{ $user->created_at->format('d M Y') }}
                                </td>

                                {{-- Status --}}
                                <td>
                                    <span class="badge badge--{{ $user->is_active ? 'success' : 'danger' }}">
                                        {{ $user->is_active ? 'Active' : 'Disabled' }}
                                    </span>
                                </td>

                                {{-- Actions --}}
                                <td>
                                    <div style="display:flex;gap:var(--space-2);justify-content:flex-end;flex-wrap:wrap;">
                                        <a href="{{ route('admin.users.show', $user) }}"
                                           class="btn btn--ghost btn--xs">
                                            View
                                        </a>

                                        {{-- Toggle disable/enable --}}
                                        <form method="POST"
                                              action="{{ route('admin.users.toggle', $user) }}"
                                              onsubmit="return confirm('{{ $user->is_active ? 'Disable' : 'Enable' }} {{ addslashes($user->full_name) }}?')">
                                            @csrf @method('PATCH')
                                            <button type="submit"
                                                    class="btn btn--sm {{ $user->is_active ? 'btn--amber' : 'btn--success' }}">
                                                {{ $user->is_active ? 'Disable' : 'Enable' }}
                                            </button>
                                        </form>

                                        {{-- Remove account --}}
                                        <form method="POST"
                                              action="{{ route('admin.users.destroy', $user) }}"
                                              onsubmit="return confirm('Permanently delete {{ addslashes($user->full_name) }}? This cannot be undone.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn--danger btn--sm">
                                                Remove
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            {{-- Low-rating alert row --}}
                            @if ($isLowRating)
                            <tr>
                                <td colspan="{{ $role === 'mentor' ? 7 : 6 }}"
                                    style="padding:0 var(--space-4) var(--space-3);background:rgba(239,68,68,.05);">
                                    <div class="low-rating-alert">
                                        <svg width="14" height="14" viewBox="0 0 20 20" fill="none">
                                            <path d="M10 2l.75 1.5M10 2l-.75 1.5M10 18v-1M10 18v1m8-8h-1M2 10h1m13.07-5.07l-.71.71M5.64 14.36l-.71.71M14.36 14.36l.71.71M5.64 5.64l.71.71" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                            <circle cx="10" cy="10" r="4" stroke="currentColor" stroke-width="1.5"/>
                                        </svg>
                                        <span>
                                            <strong>Quality alert:</strong>
                                            This account has a very low average rating of
                                            <strong>{{ number_format($avg, 1) }} ★</strong>
                                            across {{ $reviewCount }} reviews. Consider disabling or removing.
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if ($users->hasPages())
                <div style="padding:var(--space-4);">
                    {{ $users->links() }}
                </div>
                @endif

                @else
                <div class="empty" style="padding:var(--space-8);">
                    <p class="empty__text">No {{ $role === 'mentor' ? 'mentors' : 'freelancers' }} found matching your filters.</p>
                </div>
                @endif
            </div>
    </div>
</div>

<style>
/* ── Admin table ── */
.admin-table {
    width: 100%;
    border-collapse: collapse;
}
.admin-table th {
    padding: var(--space-3) var(--space-4);
    text-align: left;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    border-bottom: 1px solid var(--border);
    white-space: nowrap;
}
.admin-table td {
    padding: var(--space-3) var(--space-4);
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
}
.admin-table tbody tr:last-child td { border-bottom: none; }
.admin-table tbody tr:hover td { background: var(--bg-hover, rgba(0,0,0,.03)); }
.admin-table__row--warning td { background: rgba(239,68,68,.04); }
.admin-table__avatar {
    width: 38px; height: 38px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}
.admin-table__name  { font-size: 0.9rem; font-weight: 600; }
.admin-table__email { font-size: 0.78rem; color: var(--text-muted); }

/* ── Stars ── */
.star-row { display: flex; align-items: center; gap: 2px; }
.star--filled path { fill: #f59e0b; stroke: #f59e0b; }
.star--empty  path { fill: none;    stroke: var(--text-muted); }
.star-row__label { margin-left: 4px; font-size: 0.85rem; font-weight: 600; }
.star-row__label--danger { color: #ef4444; }

/* ── Low rating alert ── */
.low-rating-alert {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    font-size: 0.8rem;
    color: #ef4444;
    background: rgba(239,68,68,.08);
    border-left: 3px solid #ef4444;
    padding: var(--space-2) var(--space-3);
    border-radius: 0 6px 6px 0;
}

/* ── Extra button sizes ── */
.btn--xs { padding: 3px 10px; font-size: 0.75rem; }
.btn--amber  { background: #f59e0b; color: #fff; border-color: #f59e0b; }
.btn--amber:hover { background: #d97706; border-color: #d97706; }
.btn--success { background: #10b981; color: #fff; border-color: #10b981; }
.btn--success:hover { background: #059669; border-color: #059669; }
.btn--danger  { background: #ef4444; color: #fff; border-color: #ef4444; }
.btn--danger:hover { background: #dc2626; border-color: #dc2626; }

/* ── Alert ── */
.alert { display:flex; align-items:center; gap:var(--space-2); padding:var(--space-3) var(--space-4); border-radius: 8px; font-size:0.9rem; }
.alert--success { background: rgba(16,185,129,.12); color:#059669; }
</style>
@endsection
