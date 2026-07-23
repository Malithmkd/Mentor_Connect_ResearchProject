@extends('layouts.admin')

@section('title', 'Audit Log')

@section('content')
<div class="adm-page-header">
    <div>
        <h1 class="adm-page-title">Audit Log</h1>
        <p class="adm-page-subtitle">Complete history of all system activities and events across the platform.</p>
    </div>
</div>

{{-- ── Summary Stat Cards ── --}}
<div class="adm-stat-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px;">
    <div class="adm-stat-card">
        <div class="adm-stat-card__icon adm-stat-card__icon--blue">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <rect x="3" y="3" width="18" height="18" rx="3" stroke="currentColor" stroke-width="2"/>
                <path d="M7 8h10M7 12h10M7 16h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
        <div class="adm-stat-card__body">
            <div class="adm-stat-card__value">{{ number_format($stats['total']) }}</div>
            <div class="adm-stat-card__label">Total Events</div>
        </div>
    </div>
    <div class="adm-stat-card">
        <div class="adm-stat-card__icon adm-stat-card__icon--green">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                <path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
        <div class="adm-stat-card__body">
            <div class="adm-stat-card__value">{{ number_format($stats['today']) }}</div>
            <div class="adm-stat-card__label">Today</div>
        </div>
    </div>
    <div class="adm-stat-card">
        <div class="adm-stat-card__icon adm-stat-card__icon--purple">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
                <path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
        <div class="adm-stat-card__body">
            <div class="adm-stat-card__value">{{ number_format($stats['this_week']) }}</div>
            <div class="adm-stat-card__label">This Week</div>
        </div>
    </div>
</div>

{{-- ── Filters ── --}}
<div class="adm-card" style="margin-bottom:20px;">
    <div class="adm-card__body">
        <form method="GET" action="{{ route('admin.audit-log') }}" class="adm-form-row" id="auditFiltersForm">

            {{-- Search --}}
            <div class="adm-form-field adm-form-field--grow">
                <label for="auditSearch" class="adm-form-label">Search</label>
                <div class="adm-input-wrap">
                    <svg class="adm-input-icon" viewBox="0 0 20 20" fill="none">
                        <circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M13.5 13.5l3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    <input id="auditSearch" type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search events or descriptions…"
                           class="adm-form-input adm-form-input--icon">
                </div>
            </div>

            {{-- Area --}}
            <div class="adm-form-field">
                <label for="auditArea" class="adm-form-label">Area</label>
                <select id="auditArea" name="area" class="adm-form-select" onchange="this.form.submit()">
                    <option value="">All Areas</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area }}" {{ request('area') === $area ? 'selected' : '' }}>
                            {{ ucfirst($area) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- From Date --}}
            <div class="adm-form-field">
                <label for="auditFrom" class="adm-form-label">From</label>
                <input id="auditFrom" type="date" name="from" value="{{ request('from') }}"
                       class="adm-form-input" style="min-width:140px;">
            </div>

            {{-- To Date --}}
            <div class="adm-form-field">
                <label for="auditTo" class="adm-form-label">To</label>
                <input id="auditTo" type="date" name="to" value="{{ request('to') }}"
                       class="adm-form-input" style="min-width:140px;">
            </div>

            <div class="adm-form-field" style="justify-content:flex-end;">
                <label class="adm-form-label" style="visibility:hidden;">.</label>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="adm-btn adm-btn--primary adm-btn--sm">Apply</button>
                    <a href="{{ route('admin.audit-log') }}" class="adm-btn adm-btn--ghost adm-btn--sm">Clear</a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ── Log Table ── --}}
<div class="adm-card">
    @if ($logs->count() > 0)
        <div class="adm-table-wrap">
            <table class="adm-table adm-audit-table">
                <thead>
                    <tr>
                        <th style="width:130px;">Time</th>
                        <th style="width:110px;">Area</th>
                        <th style="width:160px;">Event</th>
                        <th>Description</th>
                        <th style="width:160px;">Actor</th>
                        <th style="width:120px;">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        {{-- Main row --}}
                        <tr class="adm-audit-row {{ ($log->old_values || $log->new_values) ? 'adm-audit-row--expandable' : '' }}"
                            @if ($log->old_values || $log->new_values)
                                onclick="toggleAuditDetail('detail-{{ $log->id }}')"
                                title="Click to expand details"
                            @endif>

                            {{-- Time --}}
                            <td data-label="Time">
                                <span class="adm-audit-date">{{ $log->created_at->format('d M Y') }}</span>
                                <span class="adm-audit-time">{{ $log->created_at->format('H:i:s') }}</span>
                            </td>

                            {{-- Area --}}
                            <td data-label="Area">
                                @php
                                    $areaColorMap = [
                                        'auth'    => 'adm-badge--blue',
                                        'user'    => 'adm-badge--indigo',
                                        'gig'     => 'adm-badge--purple',
                                        'booking' => 'adm-badge--amber',
                                        'admin'   => 'adm-badge--red',
                                        'system'  => 'adm-badge--gray',
                                    ];
                                    $areaColor = $areaColorMap[strtolower($log->area)] ?? 'adm-badge--gray';
                                @endphp
                                <span class="adm-badge {{ $areaColor }}">
                                    {{ ucfirst($log->area) }}
                                </span>
                            </td>

                            {{-- Event code --}}
                            <td data-label="Event" style="white-space:nowrap;">
                                <code class="adm-audit-event-code">{{ $log->event }}</code>
                            </td>

                            {{-- Description --}}
                            <td data-label="Description" style="max-width:320px;font-size:13px;color:var(--adm-text-500);">
                                {{ $log->description ?? '—' }}
                                @if ($log->old_values || $log->new_values)
                                    <span class="adm-audit-expand-hint">
                                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" id="icon-{{ $log->id }}">
                                            <path d="M3 5l3 3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        details
                                    </span>
                                @endif
                            </td>

                            {{-- Actor --}}
                            <td data-label="Actor">
                                @if ($log->user)
                                    <div class="adm-audit-actor">
                                        <img src="{{ $log->user->avatar_url }}"
                                             alt="{{ $log->user->first_name }}"
                                             class="adm-audit-actor__avatar">
                                        <span class="adm-audit-actor__name">{{ $log->user->full_name }}</span>
                                    </div>
                                @else
                                    <span class="adm-audit-system-tag">System</span>
                                @endif
                            </td>

                            {{-- IP --}}
                            <td data-label="IP" style="font-size:12px;font-family:monospace;color:var(--adm-text-400);white-space:nowrap;">
                                {{ $log->ip_address ?? '—' }}
                            </td>
                        </tr>

                        {{-- Expandable detail row --}}
                        @if ($log->old_values || $log->new_values)
                            <tr class="adm-audit-detail" id="detail-{{ $log->id }}">
                                <td colspan="6" class="adm-audit-detail__cell">
                                    <div class="adm-audit-detail-grid">
                                        @if ($log->old_values)
                                            <div class="adm-audit-detail-block adm-audit-detail-block--old">
                                                <h4 class="adm-audit-detail-block__title">
                                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" style="color:#ef4444;flex-shrink:0;">
                                                        <path d="M7 1v6M4 4l3-3 3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                        <circle cx="7" cy="11" r="1" fill="currentColor"/>
                                                    </svg>
                                                    Before
                                                </h4>
                                                <pre class="adm-audit-detail-block__pre">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                        @endif
                                        @if ($log->new_values)
                                            <div class="adm-audit-detail-block adm-audit-detail-block--new">
                                                <h4 class="adm-audit-detail-block__title">
                                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" style="color:#10b981;flex-shrink:0;">
                                                        <path d="M7 13V7M4 10l3 3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                        <circle cx="7" cy="3" r="1" fill="currentColor"/>
                                                    </svg>
                                                    After
                                                </h4>
                                                <pre class="adm-audit-detail-block__pre">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="adm-pagination">
            <span style="font-size:12px;color:var(--adm-text-400);">
                Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ number_format($logs->total()) }} events
            </span>
            {{ $logs->links('partials.pagination') }}
        </div>

    @else
        <div class="adm-empty" style="padding:60px 20px;">
            <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                <rect x="6" y="8" width="36" height="32" rx="4" stroke="currentColor" stroke-width="2"/>
                <path d="M14 18h20M14 26h20M14 34h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <p class="adm-empty__text">No audit events found for the selected filters.</p>
            <a href="{{ route('admin.audit-log') }}" class="adm-btn adm-btn--ghost adm-btn--sm" style="margin-top:12px;">Clear Filters</a>
        </div>
    @endif
</div>

<style>
/* ── Audit-specific table styles ── */
.adm-audit-table td { vertical-align: middle; }

/* Time cell */
.adm-audit-date {
    display: block;
    font-size: 11px;
    color: var(--adm-text-400);
}
.adm-audit-time {
    display: block;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--adm-text-900);
    font-family: 'SF Mono', 'Fira Code', monospace;
}

/* Event code pill */
.adm-audit-event-code {
    font-family: 'SF Mono', 'Fira Code', monospace;
    font-size: 11.5px;
    background: var(--adm-primary-50);
    color: var(--adm-primary-dark);
    padding: 2px 8px;
    border-radius: 5px;
    font-weight: 600;
    white-space: nowrap;
    border: 1px solid var(--adm-primary-100);
}

/* Expand hint */
.adm-audit-expand-hint {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 11px;
    color: var(--adm-primary);
    margin-left: 6px;
    font-weight: 500;
    vertical-align: middle;
    opacity: .7;
}

/* Row states */
.adm-audit-row--expandable { cursor: pointer; }
.adm-audit-row--expandable:hover td { background: var(--adm-bg); }
.adm-audit-row--expandable:hover .adm-audit-expand-hint { opacity: 1; }

/* Actor */
.adm-audit-actor {
    display: flex;
    align-items: center;
    gap: 8px;
}
.adm-audit-actor__avatar {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    border: 1.5px solid var(--adm-border);
}
.adm-audit-actor__name {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--adm-text-700);
    white-space: nowrap;
}
.adm-audit-system-tag {
    display: inline-flex;
    align-items: center;
    font-size: 10.5px;
    font-weight: 600;
    background: var(--adm-border-light);
    color: var(--adm-text-400);
    padding: 2px 8px;
    border-radius: 99px;
    letter-spacing: .05em;
    text-transform: uppercase;
}

/* Expandable detail row */
.adm-audit-detail {
    display: none;
    background: #f8faff;
    border-bottom: 1px solid var(--adm-border);
}
.adm-audit-detail.is-open {
    display: table-row;
    animation: auditSlide .2s ease;
}
@keyframes auditSlide {
    from { opacity: 0; transform: translateY(-4px); }
    to   { opacity: 1; transform: translateY(0); }
}
.adm-audit-detail__cell {
    padding: 14px 20px !important;
    border-bottom: 1px solid var(--adm-border-light) !important;
}
.adm-audit-detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 12px;
}
.adm-audit-detail-block {
    border-radius: var(--adm-radius-sm);
    padding: 12px 14px;
}
.adm-audit-detail-block--old {
    background: #fff5f5;
    border: 1px solid rgba(239,68,68,.15);
}
.adm-audit-detail-block--new {
    background: #f0fdf4;
    border: 1px solid rgba(16,185,129,.15);
}
.adm-audit-detail-block__title {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: var(--adm-text-500);
    margin-bottom: 8px;
}
.adm-audit-detail-block__pre {
    font-family: 'SF Mono', 'Fira Code', monospace;
    font-size: 11.5px;
    color: var(--adm-text-700);
    white-space: pre-wrap;
    word-break: break-all;
    margin: 0;
    line-height: 1.65;
}

/* ── Responsive ── */
@media (max-width: 768px) {
    .adm-audit-table thead { display: none; }
    .adm-audit-table,
    .adm-audit-table tbody,
    .adm-audit-row,
    .adm-audit-table td {
        display: block;
        width: 100%;
    }
    .adm-audit-row {
        padding: 12px 16px;
        border-bottom: 1px solid var(--adm-border-light);
    }
    .adm-audit-table td {
        padding: 4px 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .adm-audit-table td::before {
        content: attr(data-label);
        font-size: 10px;
        font-weight: 600;
        color: var(--adm-text-400);
        text-transform: uppercase;
        letter-spacing: .04em;
        min-width: 76px;
        flex-shrink: 0;
    }
    .adm-audit-table td[data-label="IP"] { display: none; }
}
</style>

@push('scripts')
<script>
function toggleAuditDetail(id) {
    const row = document.getElementById(id);
    if (!row) return;
    row.classList.toggle('is-open');

    // Rotate the chevron icon in the clicked row
    const prevRow = row.previousElementSibling;
    if (prevRow) {
        const icon = prevRow.querySelector('[id^="icon-"]');
        if (icon) {
            icon.style.transform = row.classList.contains('is-open')
                ? 'rotate(180deg)'
                : 'rotate(0)';
            icon.style.transition = 'transform 0.2s ease';
        }
    }
}
</script>
@endpush
@endsection
