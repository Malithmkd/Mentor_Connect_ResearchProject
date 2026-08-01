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

{{-- Flash messages --}}
@if (session('success'))
    <div class="adm-alert adm-alert--success">
        <svg width="16" height="16" viewBox="0 0 20 20" fill="none" style="flex-shrink:0"><path d="M5 10l3 3 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        {{ session('success') }}
    </div>
@endif

{{-- Filters --}}
<div class="adm-card" style="margin-bottom:20px;">
    <div class="adm-card__body">
        <form method="GET" action="{{ request()->url() }}" class="adm-form-row">
            <div class="adm-form-field adm-form-field--grow">
                <label class="adm-form-label" for="search">Search by name</label>
                <div class="adm-input-wrap">
                    <svg class="adm-input-icon" viewBox="0 0 20 20" fill="none"><circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.5"/><path d="M13.5 13.5l3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    <input id="search" name="search" type="text" class="adm-form-input adm-form-input--icon"
                           placeholder="e.g. John Smith"
                           value="{{ $filters['search'] ?? '' }}">
                </div>
            </div>

            <div class="adm-form-field">
                <label class="adm-form-label" for="max_rating">Max avg. rating</label>
                <select id="max_rating" name="max_rating" class="adm-form-select">
                    <option value="">Any</option>
                    @foreach ([1,2,3,4] as $r)
                        <option value="{{ $r }}" {{ ($filters['max_rating'] ?? '') == $r ? 'selected' : '' }}>
                            {{ $r }} star{{ $r > 1 ? 's' : '' }} & below
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="adm-form-field">
                <label class="adm-form-label" for="status">Status</label>
                <select id="status" name="status" class="adm-form-select">
                    <option value="">All</option>
                    <option value="active"   {{ ($filters['status'] ?? '') === 'active'   ? 'selected' : '' }}>Active</option>
                    <option value="disabled" {{ ($filters['status'] ?? '') === 'disabled' ? 'selected' : '' }}>Disabled</option>
                </select>
            </div>

            <div class="adm-form-field">
                <label class="adm-form-label" for="sort">Sort by</label>
                <select id="sort" name="sort" class="adm-form-select">
                    <option value="newest"      {{ ($filters['sort'] ?? '') === 'newest'      ? 'selected' : '' }}>Newest</option>
                    <option value="rating_asc"  {{ ($filters['sort'] ?? '') === 'rating_asc'  ? 'selected' : '' }}>Rating ↑</option>
                    <option value="rating_desc" {{ ($filters['sort'] ?? '') === 'rating_desc' ? 'selected' : '' }}>Rating ↓</option>
                    <option value="name"        {{ ($filters['sort'] ?? '') === 'name'        ? 'selected' : '' }}>Name A–Z</option>
                </select>
            </div>

            <div class="adm-form-field" style="justify-content:flex-end;">
                <label class="adm-form-label" style="visibility:hidden;">.</label>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="adm-btn adm-btn--primary adm-btn--sm">Apply</button>
                    <a href="{{ request()->url() }}" class="adm-btn adm-btn--ghost adm-btn--sm">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="adm-card">
    @if ($users->count())
    <div class="adm-table-wrap">
        <table class="adm-table">
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
                <tr style="{{ $isLowRating ? 'background:rgba(239,68,68,.04);' : '' }}">
                    {{-- User info --}}
                    <td>
                        <div class="adm-user-row">
                            <img src="{{ $user->avatar_url }}"
                                 alt="{{ $user->full_name }}"
                                 class="adm-user-avatar">
                            <div>
                                <div class="adm-user-name">{{ $user->full_name }}</div>
                                <div class="adm-user-email">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>

                    {{-- Star rating --}}
                    <td>
                        @if ($reviewCount > 0)
                            <div style="display:flex;align-items:center;gap:3px;">
                                @for ($s = 1; $s <= 5; $s++)
                                    <svg width="13" height="13" viewBox="0 0 20 20" fill="{{ $s <= round($avg) ? '#f59e0b' : 'none' }}" style="color:{{ $s <= round($avg) ? '#f59e0b' : '#cbd5e1' }}">
                                        <path d="M10 2l2.5 5.08L18 7.9l-4 3.9.94 5.5L10 14.77l-4.94 2.6L6 11.8l-4-3.9 5.5-.82L10 2z"
                                              stroke="currentColor" stroke-width="1.5"
                                              stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                @endfor
                                <span style="margin-left:4px;font-size:12px;font-weight:600;color:{{ $avg <= 1.5 && $reviewCount >= 3 ? '#ef4444' : 'var(--adm-text-700)' }}">
                                    {{ number_format($avg, 1) }}
                                </span>
                            </div>
                        @else
                            <span style="color:var(--adm-text-400);font-size:12px;">No reviews</span>
                        @endif
                    </td>

                    {{-- Review count --}}
                    <td>
                        <span style="font-size:13px;font-weight:600;color:var(--adm-text-700);">{{ $reviewCount }}</span>
                    </td>

                    {{-- Verification (mentor only) --}}
                    @if ($role === 'mentor')
                    <td>
                        @php $vs = $user->mentorProfile?->verification_status ?? 'none'; @endphp
                        <span class="adm-badge {{ $vs === 'verified' ? 'adm-badge--green' : ($vs === 'pending' ? 'adm-badge--amber' : 'adm-badge--gray') }}">
                            {{ ucfirst($vs) }}
                        </span>
                    </td>
                    @endif

                    {{-- Joined --}}
                    <td style="font-size:12px;color:var(--adm-text-400);">
                        {{ $user->created_at->format('d M Y') }}
                    </td>

                    {{-- Status --}}
                    <td>
                        <span class="adm-badge {{ $user->is_active ? 'adm-badge--green' : 'adm-badge--red' }}">
                            {{ $user->is_active ? 'Active' : 'Disabled' }}
                        </span>
                    </td>

                    {{-- Actions --}}
                    <td>
                        <div style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap;align-items:center;">
                            <a href="{{ route('admin.users.show', $user) }}"
                               class="adm-btn adm-btn--ghost adm-btn--sm">
                                View
                            </a>

                            {{-- Toggle disable/enable --}}
                            <form method="POST"
                                  action="{{ route('admin.users.toggle', $user) }}"
                                  onsubmit="return confirmDisableUser(this, '{{ addslashes($user->full_name) }}', {{ $user->is_active ? 'true' : 'false' }})">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="adm-btn adm-btn--sm {{ $user->is_active ? 'adm-btn--amber-action' : 'adm-btn--success' }}">
                                    {{ $user->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            </form>

                            {{-- Remove account --}}
                            <form method="POST"
                                  action="{{ route('admin.users.destroy', $user) }}"
                                  onsubmit="return confirmRemoveUser(this, '{{ addslashes($user->full_name) }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="adm-btn adm-btn--danger adm-btn--sm">
                                    Remove
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>

                {{-- Low-rating alert row --}}
                @if ($isLowRating)
                <tr>
                    <td colspan="{{ $role === 'mentor' ? 7 : 6 }}" style="padding:0 16px 12px;background:rgba(239,68,68,.04);">
                        <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:#ef4444;background:rgba(239,68,68,.08);border-left:3px solid #ef4444;padding:8px 12px;border-radius:0 6px 6px 0;">
                            <svg width="14" height="14" viewBox="0 0 20 20" fill="none"><path d="M10 2l.75 1.5M10 2l-.75 1.5M10 18v-1M10 18v1m8-8h-1M2 10h1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="10" cy="10" r="4" stroke="currentColor" stroke-width="1.5"/></svg>
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
    <div class="adm-pagination">
        {{ $users->links() }}
    </div>
    @endif

    @else
    <div class="adm-empty">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="5" stroke="currentColor" stroke-width="1.5"/><path d="M3 21v-2a7 7 0 0114 0v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        <p class="adm-empty__text">No {{ $role === 'mentor' ? 'mentors' : 'freelancers' }} found matching your filters.</p>
    </div>
    @endif
</div>

<style>
.adm-btn--amber-action { background: #f59e0b; color: #fff; }
.adm-btn--amber-action:hover { background: #d97706; }
.adm-btn--success { background: #10b981; color: #fff; }
.adm-btn--success:hover { background: #059669; }
</style>
@endsection
