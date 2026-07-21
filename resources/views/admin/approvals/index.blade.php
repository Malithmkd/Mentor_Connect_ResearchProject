@extends('layouts.app')

@section('title', 'Registration Approvals')

@section('content')
<section class="dashboard">
    <div class="dashboard__inner">

        {{-- Header --}}
        <header class="dashboard__header">
            <div>
                <h1 class="dashboard__title">
                    Registration Approvals
                    @if ($totalPending > 0)
                        <span class="approval-badge">{{ $totalPending }}</span>
                    @endif
                </h1>
                <p class="dashboard__subtitle">
                    Review and approve or reject new account registrations.
                </p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="btn btn--ghost btn--sm">
                ← Back to Dashboard
            </a>
        </header>

        {{-- Flash --}}
        @if (session('success'))
            <div class="alert alert--success" style="margin-bottom:var(--space-4);">
                <svg width="18" height="18" viewBox="0 0 20 20" fill="none"><path d="M5 10l3 3 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.approvals.index') }}"
              style="display:flex;gap:var(--space-3);flex-wrap:wrap;align-items:flex-end;margin-bottom:var(--space-5);">
            <div style="flex:1;min-width:180px;">
                <label class="form__label" for="search">Search name</label>
                <input id="search" name="search" type="text" class="form__input"
                       placeholder="e.g. John Smith"
                       value="{{ $filters['search'] ?? '' }}">
            </div>
            <div>
                <label class="form__label" for="role">Role</label>
                <select id="role" name="role" class="form__select">
                    <option value="">All roles</option>
                    <option value="mentor"     {{ ($filters['role'] ?? '') === 'mentor'     ? 'selected' : '' }}>Mentor</option>
                    <option value="freelancer" {{ ($filters['role'] ?? '') === 'freelancer' ? 'selected' : '' }}>Freelancer</option>
                </select>
            </div>
            <div style="display:flex;gap:var(--space-2);">
                <button type="submit" class="btn btn--primary btn--sm">Filter</button>
                <a href="{{ route('admin.approvals.index') }}" class="btn btn--ghost btn--sm">Reset</a>
            </div>
        </form>

        {{-- Pending queue --}}
        <div class="panel" style="margin-bottom:var(--space-6);">
            <div class="panel__header">
                <h2 class="panel__title">Pending Queue ({{ $pending->total() }})</h2>
            </div>
            <div class="panel__body" style="padding:0;">
                @if ($pending->count())
                <div style="overflow-x:auto;">
                    <table class="admin-table">
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
                                    <div style="display:flex;align-items:center;gap:var(--space-3);">
                                        <img src="{{ $user->avatar_url }}" alt="{{ $user->full_name }}"
                                             class="admin-table__avatar">
                                        <div>
                                            <p class="admin-table__name">{{ $user->full_name }}</p>
                                            <p class="admin-table__email">{{ $user->email }}</p>
                                            @if ($user->location)
                                                <p style="font-size:0.75rem;color:var(--text-muted);">📍 {{ $user->location }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Role --}}
                                <td>
                                    <span class="badge badge--{{ $user->isMentor() ? 'info' : 'purple' }}">
                                        {{ $user->role->label() }}
                                    </span>
                                    @if ($user->isMentor() && $user->mentorProfile)
                                        <p style="font-size:0.75rem;color:var(--text-muted);margin-top:3px;">
                                            {{ $user->mentorProfile->headline ?? '' }}
                                        </p>
                                    @endif
                                </td>

                                {{-- Bio / mentor details --}}
                                <td style="max-width:260px;">
                                    @if ($user->bio)
                                        <p style="font-size:0.8rem;color:var(--text-secondary);line-height:1.5;">
                                            {{ Str::limit($user->bio, 120) }}
                                        </p>
                                    @endif
                                    @if ($user->isMentor() && $user->mentorProfile)
                                        @php $mp = $user->mentorProfile; @endphp
                                        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;display:flex;gap:8px;flex-wrap:wrap;">
                                            @if ($mp->company)  <span>🏢 {{ $mp->company }}</span>  @endif
                                            @if ($mp->years_experience) <span>💼 {{ $mp->years_experience }}yr exp</span> @endif
                                            @if ($mp->hourly_rate) <span>💵 ${{ number_format($mp->hourly_rate,2) }}/h</span> @endif
                                        </div>
                                    @endif
                                </td>

                                {{-- Applied --}}
                                <td style="font-size:0.8rem;color:var(--text-muted);white-space:nowrap;">
                                    {{ $user->created_at->diffForHumans() }}<br>
                                    <span style="font-size:0.7rem;">{{ $user->created_at->format('d M Y') }}</span>
                                </td>

                                {{-- Actions --}}
                                <td>
                                    <div style="display:flex;flex-direction:column;gap:var(--space-2);align-items:flex-end;">

                                        {{-- Approve --}}
                                        <form method="POST"
                                              action="{{ route('admin.approvals.approve', $user) }}"
                                              onsubmit="return confirm('Approve {{ addslashes($user->full_name) }}?')">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn--success btn--sm" style="width:110px;">
                                                ✓ Approve
                                            </button>
                                        </form>

                                        {{-- Reject (toggles inline form) --}}
                                        <button type="button"
                                                class="btn btn--danger btn--sm"
                                                style="width:110px;"
                                                onclick="toggleRejectForm('reject-{{ $user->id }}')">
                                            ✗ Reject
                                        </button>

                                        {{-- Rejection reason form (hidden by default) --}}
                                        <div id="reject-{{ $user->id }}"
                                             style="display:none;width:260px;background:var(--bg-elevated,#fff);
                                                    border:1px solid var(--border);border-radius:8px;
                                                    padding:var(--space-3);margin-top:var(--space-2);">
                                            <form method="POST"
                                                  action="{{ route('admin.approvals.reject', $user) }}">
                                                @csrf @method('PATCH')
                                                <label class="form__label" style="font-size:0.78rem;">
                                                    Reason (optional)
                                                </label>
                                                <textarea name="rejection_reason"
                                                          class="form__input"
                                                          rows="2"
                                                          style="font-size:0.8rem;resize:vertical;"
                                                          placeholder="e.g. Incomplete profile information"></textarea>
                                                <div style="display:flex;gap:var(--space-2);margin-top:var(--space-2);">
                                                    <button type="submit" class="btn btn--danger btn--sm">Confirm Reject</button>
                                                    <button type="button" class="btn btn--ghost btn--sm"
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
                    <div style="padding:var(--space-4);">{{ $pending->links() }}</div>
                @endif

                @else
                    <div class="empty" style="padding:var(--space-8);">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="color:var(--text-muted);margin:0 auto var(--space-3);">
                            <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <p class="empty__text">No pending registrations — all caught up!</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Recently processed --}}
        @if ($recentlyProcessed->count())
        <div class="panel">
            <div class="panel__header"><h2 class="panel__title">Recently Processed</h2></div>
            <div class="panel__body" style="padding:0;">
                <table class="admin-table">
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
                                <div style="display:flex;align-items:center;gap:var(--space-3);">
                                    <img src="{{ $user->avatar_url }}" alt="{{ $user->full_name }}" class="admin-table__avatar">
                                    <div>
                                        <p class="admin-table__name">{{ $user->full_name }}</p>
                                        <p class="admin-table__email">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge--{{ $user->isMentor() ? 'info' : 'purple' }}">
                                    {{ $user->role->label() }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge--{{ $user->account_status === 'approved' ? 'success' : 'danger' }}">
                                    {{ ucfirst($user->account_status) }}
                                </span>
                            </td>
                            <td style="font-size:0.8rem;color:var(--text-muted);max-width:200px;">
                                {{ $user->rejection_reason ? Str::limit($user->rejection_reason, 60) : '—' }}
                            </td>
                            <td style="text-align:right;">
                                @if ($user->account_status === 'rejected')
                                    <form method="POST"
                                          action="{{ route('admin.approvals.reopen', $user) }}"
                                          style="display:inline;">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn--ghost btn--xs">Re-open</button>
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

    </div>
</section>

{{-- Toggle reject form --}}
<script>
function toggleRejectForm(id) {
    const el = document.getElementById(id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>

<style>
/* ── Approval badge on page title ── */
.approval-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #ef4444;
    color: #fff;
    font-size: 0.75rem;
    font-weight: 700;
    border-radius: 99px;
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    vertical-align: middle;
    margin-left: 8px;
    line-height: 1;
}

/* ── Reuse admin-table from admin/users/index ── */
.admin-table { width:100%; border-collapse:collapse; }
.admin-table th {
    padding: var(--space-3) var(--space-4);
    text-align:left; font-size:0.75rem; font-weight:600;
    text-transform:uppercase; letter-spacing:.05em;
    color:var(--text-muted); border-bottom:1px solid var(--border); white-space:nowrap;
}
.admin-table td {
    padding: var(--space-3) var(--space-4);
    border-bottom:1px solid var(--border); vertical-align:top;
}
.admin-table tbody tr:last-child td { border-bottom:none; }
.admin-table tbody tr:hover td { background:var(--bg-hover, rgba(0,0,0,.03)); }
.admin-table__avatar { width:36px; height:36px; border-radius:50%; object-fit:cover; flex-shrink:0; }
.admin-table__name  { font-size:.875rem; font-weight:600; }
.admin-table__email { font-size:.78rem; color:var(--text-muted); }

/* ── Buttons ── */
.btn--xs { padding:3px 10px; font-size:.75rem; }
.btn--success { background:#10b981; color:#fff; border-color:#10b981; }
.btn--success:hover { background:#059669; border-color:#059669; }
.btn--danger  { background:#ef4444; color:#fff; border-color:#ef4444; }
.btn--danger:hover  { background:#dc2626; border-color:#dc2626; }

/* ── Alert ── */
.alert { display:flex; align-items:center; gap:var(--space-2);
         padding:var(--space-3) var(--space-4); border-radius:8px; font-size:.9rem; }
.alert--success { background:rgba(16,185,129,.12); color:#059669; }
</style>
@endsection
