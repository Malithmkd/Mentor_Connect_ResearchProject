@extends('layouts.admin')

@section('title', 'Gig Management')

@section('content')
<div class="adm-page-header">
    <div>
        <h1 class="adm-page-title">Gig Management</h1>
        <p class="adm-page-subtitle">Monitor and manage all mentor session offerings on the platform.</p>
    </div>
    <div class="adm-page-header__actions">
        <a href="{{ route('admin.dashboard') }}" class="adm-btn adm-btn--ghost adm-btn--sm">← Back to Dashboard</a>
    </div>
</div>

{{-- Summary stats --}}
<div class="adm-stat-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px;">
    <div class="adm-stat-card">
        <div class="adm-stat-card__icon adm-stat-card__icon--blue">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><rect x="2" y="4" width="20" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 8h8M8 12h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </div>
        <div class="adm-stat-card__body">
            <div class="adm-stat-card__value">{{ $stats['total'] }}</div>
            <div class="adm-stat-card__label">Total Gigs</div>
        </div>
    </div>
    <div class="adm-stat-card">
        <div class="adm-stat-card__icon adm-stat-card__icon--green">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M8 12l3 3 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div class="adm-stat-card__body">
            <div class="adm-stat-card__value">{{ $stats['published'] }}</div>
            <div class="adm-stat-card__label">Published</div>
        </div>
    </div>
    <div class="adm-stat-card">
        <div class="adm-stat-card__icon adm-stat-card__icon--amber">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 8v4M12 16v.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </div>
        <div class="adm-stat-card__body">
            <div class="adm-stat-card__value">{{ $stats['draft'] }}</div>
            <div class="adm-stat-card__label">Drafts</div>
        </div>
    </div>
    <div class="adm-stat-card">
        <div class="adm-stat-card__icon adm-stat-card__icon--red">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M15 9l-6 6M9 9l6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </div>
        <div class="adm-stat-card__body">
            <div class="adm-stat-card__value">{{ $stats['paused'] }}</div>
            <div class="adm-stat-card__label">Paused / Archived</div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="adm-card" style="margin-bottom:20px;">
    <div class="adm-card__body">
        <form method="GET" action="{{ route('admin.gigs.index') }}" class="adm-form-row">
            <div class="adm-form-field adm-form-field--grow">
                <label class="adm-form-label" for="search">Search gigs</label>
                <div class="adm-input-wrap">
                    <svg class="adm-input-icon" viewBox="0 0 20 20" fill="none"><circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.5"/><path d="M13.5 13.5l3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    <input id="search" name="search" type="text" class="adm-form-input adm-form-input--icon"
                           placeholder="Title or mentor name..."
                           value="{{ $filters['search'] ?? '' }}">
                </div>
            </div>
            <div class="adm-form-field">
                <label class="adm-form-label" for="status">Status</label>
                <select id="status" name="status" class="adm-form-select">
                    <option value="">All</option>
                    <option value="published" {{ ($filters['status'] ?? '') === 'published' ? 'selected' : '' }}>Published</option>
                    <option value="draft"     {{ ($filters['status'] ?? '') === 'draft'     ? 'selected' : '' }}>Draft</option>
                    <option value="paused"    {{ ($filters['status'] ?? '') === 'paused'    ? 'selected' : '' }}>Paused</option>
                    <option value="archived"  {{ ($filters['status'] ?? '') === 'archived'  ? 'selected' : '' }}>Archived</option>
                </select>
            </div>
            <div class="adm-form-field">
                <label class="adm-form-label" for="sort">Sort by</label>
                <select id="sort" name="sort" class="adm-form-select">
                    <option value="newest"     {{ ($filters['sort'] ?? '') === 'newest'     ? 'selected' : '' }}>Newest</option>
                    <option value="oldest"     {{ ($filters['sort'] ?? '') === 'oldest'     ? 'selected' : '' }}>Oldest</option>
                    <option value="price_asc"  {{ ($filters['sort'] ?? '') === 'price_asc'  ? 'selected' : '' }}>Price ↑</option>
                    <option value="price_desc" {{ ($filters['sort'] ?? '') === 'price_desc' ? 'selected' : '' }}>Price ↓</option>
                    <option value="rating"     {{ ($filters['sort'] ?? '') === 'rating'     ? 'selected' : '' }}>Top Rated</option>
                </select>
            </div>
            <div class="adm-form-field" style="justify-content:flex-end;">
                <label class="adm-form-label" style="visibility:hidden;">.</label>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="adm-btn adm-btn--primary adm-btn--sm">Apply</button>
                    <a href="{{ route('admin.gigs.index') }}" class="adm-btn adm-btn--ghost adm-btn--sm">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Gig table --}}
<div class="adm-card">
    @if ($gigs->count())
    <div class="adm-table-wrap">
        <table class="adm-table">
            <thead>
                <tr>
                    <th>Gig</th>
                    <th>Mentor</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Rating</th>
                    <th>Bookings</th>
                    <th>Created</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($gigs as $gig)
                <tr>
                    {{-- Gig title & details --}}
                    <td style="max-width:260px;">
                        <div style="font-size:13px;font-weight:600;color:var(--adm-text-900);line-height:1.4;">
                            {{ Str::limit($gig->title, 55) }}
                        </div>
                        <div style="font-size:11px;color:var(--adm-text-400);margin-top:3px;display:flex;gap:8px;flex-wrap:wrap;">
                            <span>⏱ {{ $gig->formatted_duration }}</span>
                            <span>📋 {{ $gig->delivery_format }}</span>
                            @if ($gig->experience_level)
                                <span>🎯 {{ ucfirst(str_replace('_', ' ', $gig->experience_level)) }}</span>
                            @endif
                        </div>
                    </td>

                    {{-- Mentor --}}
                    <td>
                        <div class="adm-user-row">
                            <img src="{{ $gig->mentor->avatar_url }}"
                                 alt="{{ $gig->mentor->full_name }}"
                                 class="adm-user-avatar">
                            <div>
                                <div class="adm-user-name">{{ $gig->mentor->full_name }}</div>
                                <div class="adm-user-email">{{ $gig->mentor->email }}</div>
                            </div>
                        </div>
                    </td>

                    {{-- Price --}}
                    <td>
                        <span style="font-size:14px;font-weight:700;color:var(--adm-text-900);">{{ $gig->formatted_price }}</span>
                        <div style="font-size:11px;color:var(--adm-text-400);">/session</div>
                    </td>

                    {{-- Status --}}
                    <td>
                        @php
                            $statusMap = [
                                'published' => 'adm-badge--green',
                                'draft'     => 'adm-badge--gray',
                                'paused'    => 'adm-badge--amber',
                                'archived'  => 'adm-badge--red',
                            ];
                            $statusVal = is_object($gig->status) ? $gig->status->value : $gig->status;
                        @endphp
                        <span class="adm-badge {{ $statusMap[$statusVal] ?? 'adm-badge--gray' }}">
                            {{ ucfirst($statusVal) }}
                        </span>
                    </td>

                    {{-- Rating --}}
                    <td>
                        @if ($gig->average_rating > 0)
                            <div style="display:flex;align-items:center;gap:3px;">
                                <svg width="12" height="12" viewBox="0 0 16 16" fill="#f59e0b"><path d="M8 1l2.12 4.3 4.74.7-3.43 3.34.81 4.72L8 11.77l-4.24 2.29.81-4.72L1.14 6l4.74-.7z"/></svg>
                                <span style="font-size:12px;font-weight:600;">{{ number_format($gig->average_rating, 1) }}</span>
                            </div>
                        @else
                            <span style="font-size:12px;color:var(--adm-text-400);">No reviews</span>
                        @endif
                    </td>

                    {{-- Bookings count --}}
                    <td>
                        <span style="font-size:13px;font-weight:600;color:var(--adm-text-700);">{{ $gig->bookings_count ?? 0 }}</span>
                    </td>

                    {{-- Created --}}
                    <td style="font-size:12px;color:var(--adm-text-400);">
                        {{ $gig->created_at->format('d M Y') }}
                    </td>

                    {{-- Actions --}}
                    <td>
                        <div style="display:flex;gap:6px;justify-content:flex-end;align-items:center;">
                            <a href="{{ route('gigs.show', $gig->slug) }}"
                               target="_blank"
                               class="adm-btn adm-btn--ghost adm-btn--sm">
                                View
                            </a>
                            <a href="{{ route('admin.users.show', $gig->mentor) }}"
                               class="adm-btn adm-btn--ghost adm-btn--sm">
                                Mentor
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($gigs->hasPages())
    <div class="adm-pagination">
        {{ $gigs->links() }}
    </div>
    @endif

    @else
    <div class="adm-empty">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none"><rect x="2" y="4" width="20" height="16" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M8 8h8M8 12h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        <p class="adm-empty__text">No gigs found matching your filters.</p>
    </div>
    @endif
</div>
@endsection
