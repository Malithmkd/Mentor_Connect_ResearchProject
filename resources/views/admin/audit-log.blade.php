@extends('layouts.admin')

@section('title', 'Audit Log')

@section('content')
<div class="adm-page-header">
    <div>
        <h1 class="adm-page-title">Audit Log</h1>
        <p class="adm-page-subtitle">Complete history of all system activities and events across the platform.</p>
    </div>
</div>

<div class="audit-log-page__inner">


        {{-- ── Summary Stat Chips ── --}}
        <div class="audit-stat-row">
            <div class="audit-stat-chip audit-stat-chip--blue">
                <span class="audit-stat-chip__val">{{ number_format($stats['total']) }}</span>
                <span class="audit-stat-chip__lbl">Total Events</span>
            </div>
            <div class="audit-stat-chip audit-stat-chip--green">
                <span class="audit-stat-chip__val">{{ number_format($stats['today']) }}</span>
                <span class="audit-stat-chip__lbl">Today</span>
            </div>
            <div class="audit-stat-chip audit-stat-chip--purple">
                <span class="audit-stat-chip__val">{{ number_format($stats['this_week']) }}</span>
                <span class="audit-stat-chip__lbl">This Week</span>
            </div>
        </div>

        {{-- ── Filters ── --}}
        <form method="GET" action="{{ route('admin.audit-log') }}" class="audit-filters" id="auditFiltersForm">
            <div class="audit-filters__row">
                {{-- Search --}}
                <div class="audit-filters__field audit-filters__field--grow">
                    <label for="auditSearch" class="audit-filters__label">Search</label>
                    <div class="audit-filters__input-wrap">
                        <svg class="audit-filters__icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <circle cx="7" cy="7" r="5" stroke="currentColor" stroke-width="1.5"/>
                            <path d="M11 11l3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                        <input id="auditSearch" type="text" name="search" value="{{ request('search') }}"
                               placeholder="Search events or descriptions…"
                               class="audit-filters__input">
                    </div>
                </div>

                {{-- Area --}}
                <div class="audit-filters__field">
                    <label for="auditArea" class="audit-filters__label">Area</label>
                    <select id="auditArea" name="area" class="audit-filters__select" onchange="this.form.submit()">
                        <option value="">All Areas</option>
                        @foreach ($areas as $area)
                            <option value="{{ $area }}" {{ request('area') === $area ? 'selected' : '' }}>
                                {{ ucfirst($area) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- From Date --}}
                <div class="audit-filters__field">
                    <label for="auditFrom" class="audit-filters__label">From</label>
                    <input id="auditFrom" type="date" name="from" value="{{ request('from') }}"
                           class="audit-filters__input audit-filters__input--date">
                </div>

                {{-- To Date --}}
                <div class="audit-filters__field">
                    <label for="auditTo" class="audit-filters__label">To</label>
                    <input id="auditTo" type="date" name="to" value="{{ request('to') }}"
                           class="audit-filters__input audit-filters__input--date">
                </div>

                <div class="audit-filters__actions">
                    <button type="submit" class="btn btn--primary btn--sm">Apply</button>
                    <a href="{{ route('admin.audit-log') }}" class="btn btn--ghost btn--sm">Clear</a>
                </div>
            </div>
        </form>

        {{-- ── Log Table ── --}}
        <div class="audit-table-wrap">
            @if ($logs->count() > 0)
                <table class="audit-table">
                    <thead>
                        <tr>
                            <th class="audit-table__th audit-table__th--time">Time</th>
                            <th class="audit-table__th">Area</th>
                            <th class="audit-table__th">Event</th>
                            <th class="audit-table__th">Description</th>
                            <th class="audit-table__th">Actor</th>
                            <th class="audit-table__th audit-table__th--ip">IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr class="audit-table__row" onclick="toggleAuditDetail('detail-{{ $log->id }}')" title="Click to expand details">
                                <td class="audit-table__td audit-table__td--time" data-label="Time">
                                    <span class="audit-time__date">{{ $log->created_at->format('d M Y') }}</span>
                                    <span class="audit-time__clock">{{ $log->created_at->format('H:i:s') }}</span>
                                </td>
                                <td class="audit-table__td" data-label="Area">
                                    <span class="badge badge--{{ $log->areaColor() }}">
                                        {{ ucfirst($log->area) }}
                                    </span>
                                </td>
                                <td class="audit-table__td audit-table__td--event" data-label="Event">
                                    <code class="audit-event-code">{{ $log->event }}</code>
                                </td>
                                <td class="audit-table__td audit-table__td--desc" data-label="Description">
                                    {{ $log->description ?? '—' }}
                                </td>
                                <td class="audit-table__td" data-label="Actor">
                                    @if ($log->user)
                                        <div class="audit-actor">
                                            <img src="{{ $log->user->avatar_url }}" alt="{{ $log->user->first_name }}"
                                                 class="audit-actor__avatar" width="24" height="24">
                                            <span class="audit-actor__name">{{ $log->user->full_name }}</span>
                                        </div>
                                    @else
                                        <span class="audit-system-badge">System</span>
                                    @endif
                                </td>
                                <td class="audit-table__td audit-table__td--ip" data-label="IP">
                                    {{ $log->ip_address ?? '—' }}
                                </td>
                            </tr>

                            {{-- Expandable detail row --}}
                            @if ($log->old_values || $log->new_values)
                                <tr class="audit-table__detail" id="detail-{{ $log->id }}">
                                    <td colspan="6" class="audit-table__detail-cell">
                                        <div class="audit-detail-grid">
                                            @if ($log->old_values)
                                                <div class="audit-detail-block audit-detail-block--old">
                                                    <h4 class="audit-detail-block__title">
                                                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" style="margin-right:4px;color:#ef4444"><path d="M7 1v6M4 4l3-3 3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="7" cy="11" r="1" fill="currentColor"/></svg>
                                                        Before
                                                    </h4>
                                                    <pre class="audit-detail-block__pre">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                            @endif
                                            @if ($log->new_values)
                                                <div class="audit-detail-block audit-detail-block--new">
                                                    <h4 class="audit-detail-block__title">
                                                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" style="margin-right:4px;color:#10b981"><path d="M7 13V7M4 10l3 3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="7" cy="3" r="1" fill="currentColor"/></svg>
                                                        After
                                                    </h4>
                                                    <pre class="audit-detail-block__pre">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>

                {{-- Pagination --}}
                <div class="audit-pagination">
                    <span class="audit-pagination__info">
                        Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ number_format($logs->total()) }} events
                    </span>
                    {{ $logs->links('partials.pagination') }}
                </div>

            @else
                <div class="empty" style="padding: var(--space-12) var(--space-8); text-align:center">
                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none" style="margin: 0 auto var(--space-4);opacity:.4">
                        <rect x="6" y="8" width="36" height="32" rx="4" stroke="currentColor" stroke-width="2"/>
                        <path d="M14 18h20M14 26h20M14 34h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <p class="empty__text">No audit events found for the selected filters.</p>
                    <a href="{{ route('admin.audit-log') }}" class="btn btn--ghost btn--sm" style="margin-top:var(--space-3)">Clear Filters</a>
                </div>
            @endif
    </div>
</div>

<style>
/* ── Audit Log Page Layout ── */
.audit-log-page {
    padding: var(--space-8) 0;
}
.audit-log-page__inner {
    max-width: var(--container-xl);
    margin: 0 auto;
    padding: 0 var(--space-6);
    display: flex;
    flex-direction: column;
    gap: var(--space-6);
}

/* ── Stat Row ── */
.audit-stat-row {
    display: flex;
    gap: var(--space-4);
    flex-wrap: wrap;
}
.audit-stat-chip {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    padding: var(--space-4) var(--space-6);
    border-radius: var(--radius-lg);
    min-width: 140px;
    border: 1px solid transparent;
}
.audit-stat-chip--blue   { background: var(--color-info-50);    border-color: rgba(59,130,246,.15); }
.audit-stat-chip--green  { background: var(--color-success-50); border-color: rgba(16,185,129,.15); }
.audit-stat-chip--purple { background: #f5f3ff;                 border-color: rgba(139,92,246,.15); }
.audit-stat-chip__val {
    font-size: var(--text-2xl);
    font-weight: 700;
    color: var(--color-gray-900);
    line-height: 1;
}
.audit-stat-chip__lbl {
    font-size: var(--text-xs);
    font-weight: 500;
    color: var(--color-gray-500);
    margin-top: var(--space-1);
    text-transform: uppercase;
    letter-spacing: .05em;
}

/* ── Filters ── */
.audit-filters {
    background: var(--color-white);
    border: 1px solid var(--color-gray-200);
    border-radius: var(--radius-lg);
    padding: var(--space-5);
    box-shadow: var(--shadow-sm);
}
.audit-filters__row {
    display: flex;
    gap: var(--space-3);
    align-items: flex-end;
    flex-wrap: wrap;
}
.audit-filters__field {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.audit-filters__field--grow { flex: 1; min-width: 200px; }
.audit-filters__label {
    font-size: var(--text-xs);
    font-weight: 600;
    color: var(--color-gray-600);
    text-transform: uppercase;
    letter-spacing: .04em;
}
.audit-filters__input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.audit-filters__icon {
    position: absolute;
    left: 10px;
    color: var(--color-gray-400);
    pointer-events: none;
}
.audit-filters__input {
    width: 100%;
    padding: 9px 12px 9px 34px;
    border: 1px solid var(--color-gray-300);
    border-radius: var(--radius);
    font-size: var(--text-sm);
    color: var(--color-gray-800);
    background: var(--color-white);
    transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
    font-family: var(--font-sans);
}
.audit-filters__input--date {
    padding-left: 12px;
    min-width: 140px;
}
.audit-filters__input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(79,70,229,.12);
}
.audit-filters__select {
    padding: 9px 36px 9px 12px;
    border: 1px solid var(--color-gray-300);
    border-radius: var(--radius);
    font-size: var(--text-sm);
    color: var(--color-gray-800);
    background: var(--color-white);
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath d='M3 5l3 3 3-3' stroke='%236b7280' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    transition: border-color var(--transition-fast);
    font-family: var(--font-sans);
    cursor: pointer;
}
.audit-filters__select:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(79,70,229,.12);
}
.audit-filters__actions {
    display: flex;
    gap: var(--space-2);
    align-items: center;
    padding-bottom: 1px;
}

/* ── Audit Table ── */
.audit-table-wrap {
    background: var(--color-white);
    border: 1px solid var(--color-gray-200);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}
.audit-table {
    width: 100%;
    border-collapse: collapse;
    font-size: var(--text-sm);
}
.audit-table__th {
    padding: 12px 16px;
    text-align: left;
    font-size: var(--text-xs);
    font-weight: 600;
    color: var(--color-gray-500);
    text-transform: uppercase;
    letter-spacing: .05em;
    background: var(--color-gray-50);
    border-bottom: 1px solid var(--color-gray-200);
    white-space: nowrap;
}
.audit-table__th--time { width: 130px; }
.audit-table__th--ip   { width: 110px; }

.audit-table__row {
    cursor: pointer;
    transition: background var(--transition-fast);
    border-bottom: 1px solid var(--color-gray-100);
}
.audit-table__row:hover { background: var(--color-gray-50); }
.audit-table__row:last-child { border-bottom: none; }

.audit-table__td {
    padding: 12px 16px;
    color: var(--color-gray-700);
    vertical-align: middle;
}
.audit-table__td--time {
    white-space: nowrap;
}
.audit-table__td--event {
    white-space: nowrap;
}
.audit-table__td--ip {
    color: var(--color-gray-500);
    font-family: var(--font-mono);
    font-size: 0.78rem;
    white-space: nowrap;
}
.audit-table__td--desc {
    max-width: 340px;
}

/* ── Time display ── */
.audit-time__date {
    display: block;
    font-size: var(--text-xs);
    color: var(--color-gray-500);
}
.audit-time__clock {
    display: block;
    font-size: var(--text-sm);
    font-weight: 600;
    color: var(--color-gray-800);
    font-family: var(--font-mono);
}

/* ── Event code ── */
.audit-event-code {
    font-family: var(--font-mono);
    font-size: 0.78rem;
    background: var(--color-gray-100);
    color: var(--color-primary);
    padding: 2px 6px;
    border-radius: var(--radius-sm);
    font-weight: 500;
    white-space: nowrap;
}

/* ── Actor ── */
.audit-actor {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}
.audit-actor__avatar {
    width: 24px;
    height: 24px;
    border-radius: var(--radius-full);
    object-fit: cover;
    flex-shrink: 0;
}
.audit-actor__name {
    font-weight: 500;
    color: var(--color-gray-800);
    white-space: nowrap;
    font-size: var(--text-sm);
}
.audit-system-badge {
    font-size: 0.72rem;
    font-weight: 600;
    background: var(--color-gray-100);
    color: var(--color-gray-500);
    padding: 2px 8px;
    border-radius: var(--radius-full);
    letter-spacing: .04em;
    text-transform: uppercase;
}

/* ── Expandable Detail Row ── */
.audit-table__detail {
    display: none;
    background: #fafbff;
    border-bottom: 1px solid var(--color-gray-200);
}
.audit-table__detail.is-open {
    display: table-row;
    animation: slideDown .2s ease;
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-4px); }
    to   { opacity: 1; transform: translateY(0); }
}
.audit-table__detail-cell {
    padding: var(--space-4) var(--space-5);
}
.audit-detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: var(--space-4);
}
.audit-detail-block {
    border-radius: var(--radius);
    padding: var(--space-3) var(--space-4);
}
.audit-detail-block--old {
    background: #fff5f5;
    border: 1px solid rgba(239,68,68,.15);
}
.audit-detail-block--new {
    background: #f0fdf4;
    border: 1px solid rgba(16,185,129,.15);
}
.audit-detail-block__title {
    font-size: var(--text-xs);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--color-gray-600);
    margin-bottom: var(--space-2);
    display: flex;
    align-items: center;
}
.audit-detail-block__pre {
    font-family: var(--font-mono);
    font-size: 0.75rem;
    color: var(--color-gray-700);
    white-space: pre-wrap;
    word-break: break-all;
    margin: 0;
    line-height: 1.6;
}

/* ── Pagination ── */
.audit-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--space-4) var(--space-5);
    border-top: 1px solid var(--color-gray-200);
    gap: var(--space-4);
    flex-wrap: wrap;
}
.audit-pagination__info {
    font-size: var(--text-sm);
    color: var(--color-gray-500);
}

/* ── Responsive ── */
@media (max-width: 768px) {
    .audit-table thead { display: none; }
    .audit-table, .audit-table tbody, .audit-table__row, .audit-table__td {
        display: block;
        width: 100%;
    }
    .audit-table__row {
        padding: var(--space-3) var(--space-4);
        border-bottom: 1px solid var(--color-gray-200);
    }
    .audit-table__td {
        padding: 4px var(--space-4);
        display: flex;
        align-items: center;
        gap: var(--space-2);
    }
    .audit-table__td::before {
        content: attr(data-label);
        font-size: var(--text-xs);
        font-weight: 600;
        color: var(--color-gray-400);
        text-transform: uppercase;
        letter-spacing: .04em;
        min-width: 80px;
        flex-shrink: 0;
    }
    .audit-table__td--ip { display: none; }
    .audit-filters__row { flex-direction: column; }
    .audit-filters__field--grow { width: 100%; }
    .audit-stat-row { gap: var(--space-3); }
    .audit-stat-chip { min-width: 0; flex: 1; }
}
</style>

@push('scripts')
<script>
function toggleAuditDetail(id) {
    const row = document.getElementById(id);
    if (!row) return;
    row.classList.toggle('is-open');
}
</script>
@endpush
@endsection
