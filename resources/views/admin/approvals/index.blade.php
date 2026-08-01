@extends('layouts.admin')

@section('title', 'Registration Approvals')

@section('content')
<div class="adm-page-header">
    <div>
        <h1 class="adm-page-title">
            Registration Approvals
            @if ($totalPending > 0)
                <span class="adm-approval-count">{{ $totalPending }}</span>
            @endif
        </h1>
        <p class="adm-page-subtitle">Review and approve or reject new account registrations.</p>
    </div>
    <div class="adm-page-header__actions">
        <a href="{{ route('admin.dashboard') }}" class="adm-btn adm-btn--ghost adm-btn--sm">← Back to Dashboard</a>
    </div>
</div>

{{-- Flash --}}
@if (session('success'))
    <div class="adm-alert adm-alert--success">
        <svg width="16" height="16" viewBox="0 0 20 20" fill="none" style="flex-shrink:0"><path d="M5 10l3 3 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        {{ session('success') }}
    </div>
@endif

{{-- Filters --}}
<div class="adm-card" style="margin-bottom:20px;">
    <div class="adm-card__body">
        <form method="GET" action="{{ route('admin.approvals.index') }}" class="adm-form-row">
            <div class="adm-form-field adm-form-field--grow">
                <label class="adm-form-label" for="search">Search name</label>
                <div class="adm-input-wrap">
                    <svg class="adm-input-icon" viewBox="0 0 20 20" fill="none"><circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.5"/><path d="M13.5 13.5l3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    <input id="search" name="search" type="text" class="adm-form-input adm-form-input--icon"
                           placeholder="e.g. John Smith"
                           value="{{ $filters['search'] ?? '' }}">
                </div>
            </div>
            <div class="adm-form-field">
                <label class="adm-form-label" for="role">Role</label>
                <select id="role" name="role" class="adm-form-select">
                    <option value="">All roles</option>
                    <option value="mentor"     {{ ($filters['role'] ?? '') === 'mentor'     ? 'selected' : '' }}>Mentor</option>
                    <option value="freelancer" {{ ($filters['role'] ?? '') === 'freelancer' ? 'selected' : '' }}>Freelancer</option>
                </select>
            </div>
            <div class="adm-form-field" style="justify-content:flex-end;">
                <label class="adm-form-label" style="visibility:hidden;">.</label>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="adm-btn adm-btn--primary adm-btn--sm">Filter</button>
                    <a href="{{ route('admin.approvals.index') }}" class="adm-btn adm-btn--ghost adm-btn--sm">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Pending queue --}}
<div class="adm-card" style="margin-bottom:20px;">
    <div class="adm-card__header">
        <div>
            <div class="adm-card__title">Pending Queue</div>
            <div class="adm-card__subtitle">{{ $pending->total() }} application{{ $pending->total() !== 1 ? 's' : '' }} awaiting review</div>
        </div>
        @if ($pending->total() > 0)
            <span class="adm-badge adm-badge--red">{{ $pending->total() }} pending</span>
        @endif
    </div>

    @if ($pending->count())
    <div class="adm-table-wrap">
        <table class="adm-table">
            <thead>
                <tr>
                    <th>Applicant</th>
                    <th>Role</th>
                    <th>Bio / Details</th>
                    <th>Applied</th>
                    <th style="text-align:right;">Decision</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pending as $user)
                <tr>
                    {{-- Identity --}}
                    <td>
                        <div class="adm-user-row">
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->full_name }}"
                                 class="adm-user-avatar">
                            <div>
                                <div class="adm-user-name">{{ $user->full_name }}</div>
                                <div class="adm-user-email">{{ $user->email }}</div>
                                @if ($user->location)
                                    <div style="font-size:11px;color:var(--adm-text-400);margin-top:2px;">📍 {{ $user->location }}</div>
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- Role --}}
                    <td>
                        <span class="adm-badge {{ $user->isMentor() ? 'adm-badge--blue' : 'adm-badge--purple' }}">
                            {{ $user->role->label() }}
                        </span>
                        @if ($user->isMentor() && $user->mentorProfile)
                            <div style="font-size:11px;color:var(--adm-text-400);margin-top:3px;">
                                {{ $user->mentorProfile->headline ?? '' }}
                            </div>
                        @endif
                    </td>

                    {{-- Bio / mentor details --}}
                    <td style="max-width:260px;">
                        @if ($user->bio)
                            <p style="font-size:12px;color:var(--adm-text-500);line-height:1.5;">
                                {{ Str::limit($user->bio, 120) }}
                            </p>
                        @endif
                        @if ($user->isMentor() && $user->mentorProfile)
                            @php $mp = $user->mentorProfile; @endphp
                            <div style="font-size:11px;color:var(--adm-text-400);margin-top:4px;display:flex;gap:8px;flex-wrap:wrap;">
                                @if ($mp->company)  <span>🏢 {{ $mp->company }}</span>  @endif
                                @if ($mp->years_experience) <span>💼 {{ $mp->years_experience }}yr exp</span> @endif
                                @if ($mp->hourly_rate) <span>💵 ${{ number_format($mp->hourly_rate,2) }}/h</span> @endif
                            </div>
                        @endif
                    </td>

                    {{-- Applied --}}
                    <td style="font-size:12px;color:var(--adm-text-400);white-space:nowrap;">
                        {{ $user->created_at->diffForHumans() }}<br>
                        <span style="font-size:11px;">{{ $user->created_at->format('d M Y') }}</span>
                    </td>

                    {{-- Actions --}}
                    <td>
                        <div style="display:flex;flex-direction:column;gap:6px;align-items:flex-end;">

                            {{-- Approve --}}
                            <form method="POST"
                                  action="{{ route('admin.approvals.approve', $user) }}"
                                  onsubmit="return confirmApproveUser(this, '{{ addslashes($user->full_name) }}')">
                                @csrf @method('PATCH')
                                <button type="submit" class="adm-btn adm-btn--success adm-btn--sm" style="width:110px;">
                                    ✓ Approve
                                </button>
                            </form>

                            {{-- Reject (toggles inline form) --}}
                            <button type="button"
                                    class="adm-btn adm-btn--danger adm-btn--sm"
                                    style="width:110px;"
                                    onclick="toggleRejectForm('reject-{{ $user->id }}')">
                                ✗ Reject
                            </button>

                            {{-- Rejection reason form (hidden by default) --}}
                            <div id="reject-{{ $user->id }}"
                                 style="display:none;width:270px;background:var(--adm-surface);
                                        border:1px solid var(--adm-border);border-radius:var(--adm-radius);
                                        padding:12px;margin-top:4px;box-shadow:var(--adm-shadow);">
                                <form method="POST"
                                      action="{{ route('admin.approvals.reject', $user) }}">
                                    @csrf @method('PATCH')
                                    <label class="adm-form-label" style="margin-bottom:4px;display:block;">
                                        Reason (optional)
                                    </label>
                                    <textarea name="rejection_reason"
                                              class="adm-form-input"
                                              rows="2"
                                              style="font-size:12px;resize:vertical;width:100%;"
                                              placeholder="e.g. Incomplete profile information"></textarea>
                                    <div style="display:flex;gap:6px;margin-top:8px;">
                                        <button type="submit" class="adm-btn adm-btn--danger adm-btn--sm">Confirm Reject</button>
                                        <button type="button" class="adm-btn adm-btn--ghost adm-btn--sm"
                                                onclick="toggleRejectForm('reject-{{ $user->id }}')">Cancel</button>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($pending->hasPages())
        <div class="adm-pagination">{{ $pending->links() }}</div>
    @endif

    @else
        <div class="adm-empty">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <p class="adm-empty__text">No pending registrations — all caught up!</p>
        </div>
    @endif
</div>

{{-- Recently processed --}}
@if ($recentlyProcessed->count())
<div class="adm-card">
    <div class="adm-card__header">
        <div>
            <div class="adm-card__title">Recently Processed</div>
            <div class="adm-card__subtitle">Last approved or rejected applications</div>
        </div>
    </div>
    <div class="adm-table-wrap">
        <table class="adm-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Decision</th>
                    <th>Reason</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recentlyProcessed as $user)
                <tr>
                    <td>
                        <div class="adm-user-row">
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->full_name }}" class="adm-user-avatar">
                            <div>
                                <div class="adm-user-name">{{ $user->full_name }}</div>
                                <div class="adm-user-email">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="adm-badge {{ $user->isMentor() ? 'adm-badge--blue' : 'adm-badge--purple' }}">
                            {{ $user->role->label() }}
                        </span>
                    </td>
                    <td>
                        <span class="adm-badge {{ $user->account_status === 'approved' ? 'adm-badge--green' : 'adm-badge--red' }}">
                            {{ ucfirst($user->account_status) }}
                        </span>
                    </td>
                    <td style="font-size:12px;color:var(--adm-text-400);max-width:200px;">
                        {{ $user->rejection_reason ? Str::limit($user->rejection_reason, 60) : '—' }}
                    </td>
                    <td style="text-align:right;">
                        @if ($user->account_status === 'rejected')
                            <form method="POST"
                                  action="{{ route('admin.approvals.reopen', $user) }}"
                                  style="display:inline;">
                                @csrf @method('PATCH')
                                <button type="submit" class="adm-btn adm-btn--ghost adm-btn--sm">Re-open</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Toggle reject form --}}
<script>
function toggleRejectForm(id) {
    const el = document.getElementById(id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>

<style>
/* ── Approval count badge on page title ── */
.adm-approval-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #ef4444;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    border-radius: 99px;
    min-width: 24px;
    height: 24px;
    padding: 0 7px;
    vertical-align: middle;
    margin-left: 10px;
    line-height: 1;
}
/* Success button for approvals */
.adm-btn--success { background: #10b981; color: #fff; }
.adm-btn--success:hover { background: #059669; }
</style>
@endsection
