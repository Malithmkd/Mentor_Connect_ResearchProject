<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" id="htmlRoot">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — MentorConnect Admin</title>
    <meta name="description" content="MentorConnect Admin Panel">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    {{-- SweetAlert2 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    @stack('styles')
    {{-- Apply saved theme BEFORE paint --}}
    <script>
        (function () {
            var t = localStorage.getItem('mc-theme') || 'light';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
</head>
<body class="adm-body">

{{-- ══ Overlay (mobile sidebar) ══ --}}
<div class="adm-overlay" id="admOverlay" onclick="closeSidebar()"></div>

{{-- ════════════════════════════════════
     SIDEBAR
════════════════════════════════════ --}}
<aside class="adm-sidebar" id="admSidebar">

    {{-- Logo --}}
    <a href="{{ route('admin.dashboard') }}" class="adm-sidebar__logo">
        <div class="adm-sidebar__logo-icon">
            <svg width="18" height="18" viewBox="0 0 20 20" fill="none">
                <circle cx="10" cy="7" r="4" stroke="white" stroke-width="1.8"/>
                <path d="M3 18c0-3.866 3.134-7 7-7s7 3.134 7 7" stroke="white" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
        </div>
        <span class="adm-sidebar__logo-text">MentorConnect</span>
        <span class="adm-sidebar__logo-badge">Admin</span>
    </a>

    {{-- Navigation --}}
    <nav class="adm-sidebar__nav">

        {{-- Dashboard --}}
        <a href="{{ route('admin.dashboard') }}"
           class="adm-sidebar__link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
            <svg class="adm-sidebar__icon" viewBox="0 0 20 20" fill="none">
                <rect x="2" y="2" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
                <rect x="11" y="2" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
                <rect x="2" y="11" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
                <rect x="11" y="11" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
            </svg>
            Dashboard
        </a>

        {{-- User Management --}}
        <span class="adm-sidebar__section-label">Users</span>

        <button class="adm-sidebar__group-toggle {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.approvals.*') ? 'is-open' : '' }}"
                onclick="toggleGroup(this)">
            <svg class="adm-sidebar__icon" viewBox="0 0 20 20" fill="none">
                <circle cx="8" cy="7" r="3.5" stroke="currentColor" stroke-width="1.5"/>
                <path d="M1 17c0-3.314 3.134-6 7-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M13 11.5l1.5 1.5 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="15" cy="13" r="3.5" stroke="currentColor" stroke-width="1.5"/>
            </svg>
            User Management
            <svg class="adm-sidebar__chevron" viewBox="0 0 16 16" fill="none">
                <path d="M6 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <div class="adm-sidebar__group-body {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.approvals.*') ? 'is-open' : '' }}">
            <div class="adm-sidebar__sub">
                <a href="{{ route('admin.users.mentors') }}"
                   class="adm-sidebar__sub-link {{ request()->routeIs('admin.users.mentors') ? 'is-active' : '' }}">
                    <span class="adm-sidebar__sub-dot"></span>Mentors
                </a>
                <a href="{{ route('admin.users.freelancers') }}"
                   class="adm-sidebar__sub-link {{ request()->routeIs('admin.users.freelancers') ? 'is-active' : '' }}">
                    <span class="adm-sidebar__sub-dot"></span>Freelancers
                </a>
                <a href="{{ route('admin.approvals.index') }}"
                   class="adm-sidebar__sub-link {{ request()->routeIs('admin.approvals.*') ? 'is-active' : '' }}">
                    <span class="adm-sidebar__sub-dot"></span>
                    Pending Approvals
                    @php $pendingCount = \App\Models\User::where('account_status','pending')->count(); @endphp
                    @if($pendingCount > 0)
                        <span class="adm-sidebar__badge">{{ $pendingCount }}</span>
                    @endif
                </a>
            </div>
        </div>

        {{-- Gig Management --}}
        <span class="adm-sidebar__section-label">Platform</span>

        <a href="{{ route('admin.gigs.index') }}"
           class="adm-sidebar__link {{ request()->routeIs('admin.gigs.*') ? 'is-active' : '' }}">
            <svg class="adm-sidebar__icon" viewBox="0 0 20 20" fill="none">
                <rect x="2" y="4" width="16" height="12" rx="2" stroke="currentColor" stroke-width="1.5"/>
                <path d="M6 8h8M6 12h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            Gig Management
        </a>

        <a href="{{ route('admin.dashboard') }}"
           class="adm-sidebar__link {{ false ? 'is-active' : '' }}">
            <svg class="adm-sidebar__icon" viewBox="0 0 20 20" fill="none">
                <path d="M3 6h14M5 10h10M8 14h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            Bookings
        </a>

        {{-- Analytics --}}
        <span class="adm-sidebar__section-label">Analytics</span>

        <a href="{{ route('admin.dashboard') }}"
           class="adm-sidebar__link">
            <svg class="adm-sidebar__icon" viewBox="0 0 20 20" fill="none">
                <path d="M2 16l4-6 4 3 4-8 4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Reports &amp; Analytics
        </a>

        <a href="{{ route('admin.audit-log') }}"
           class="adm-sidebar__link {{ request()->routeIs('admin.audit-log') ? 'is-active' : '' }}">
            <svg class="adm-sidebar__icon" viewBox="0 0 20 20" fill="none">
                <rect x="2" y="3" width="16" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/>
                <path d="M5 7h10M5 11h10M5 15h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            Audit Logs
        </a>

    </nav>

    {{-- Footer --}}
    <div class="adm-sidebar__footer">
        <div class="adm-sidebar__user-row">
            <img src="{{ auth()->user()?->avatar_url ?? asset('images/default-avatar.png') }}" alt="{{ auth()->user()?->first_name ?? 'Admin' }}"
                 class="adm-sidebar__user-avatar">
            <div>
                <div class="adm-sidebar__user-name">{{ auth()->user()?->first_name ?? 'Admin' }}</div>
                <div class="adm-sidebar__user-role">Administrator</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" id="sidebarLogoutForm">
            @csrf
            <button type="button" class="adm-sidebar__link" style="width:100%;border:none;cursor:pointer;background:none;font-family:inherit;" onclick="confirmLogout('sidebarLogoutForm')">
                <svg class="adm-sidebar__icon" viewBox="0 0 20 20" fill="none">
                    <path d="M7 3H4a1 1 0 00-1 1v12a1 1 0 001 1h3M13 14l3-4-3-4M16 10H8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Sign Out
            </button>
        </form>
    </div>
</aside>

{{-- ════════════════════════════════════
     TOPBAR
════════════════════════════════════ --}}
<header class="adm-topbar">
    <button class="adm-topbar__mobile-toggle" onclick="openSidebar()" aria-label="Open menu">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M3 5h14M3 10h14M3 15h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
    </button>

    <div class="adm-topbar__breadcrumb">
        <span>Admin</span>
        <span class="adm-topbar__breadcrumb-sep">/</span>
        <span class="adm-topbar__breadcrumb-current">@yield('title', 'Dashboard')</span>
    </div>

    <div class="adm-topbar__actions">
        {{-- Theme toggle --}}
        <button id="adminThemeToggle" class="adm-topbar__icon-btn" title="Toggle Dark/Light Mode" onclick="toggleTheme()" style="font-size:1.1rem;line-height:1">
            <span id="adminThemeIcon">🌙</span>
        </button>

        {{-- Notifications --}}
        <a href="{{ route('notifications.index') }}" class="adm-topbar__icon-btn" title="Notifications">
            <svg width="18" height="18" viewBox="0 0 20 20" fill="none">
                <path d="M10 2a6 6 0 00-6 6v3l-2 2h16l-2-2V8a6 6 0 00-6-6z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                <path d="M8 16a2 2 0 004 0" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            @php $unreadCount = \App\Models\Notification::where('user_id', auth()->id())->unread()->count(); @endphp
            @if($unreadCount > 0)
                <span class="adm-topbar__notif-dot"></span>
            @endif
        </a>

        {{-- User dropdown --}}
        <div class="adm-dropdown-wrap">
            <button class="adm-topbar__avatar-btn" onclick="toggleDropdown('topUserDrop')" id="topUserDropBtn">
                <img src="{{ auth()->user()?->avatar_url ?? asset('images/default-avatar.png') }}" alt="{{ auth()->user()?->first_name ?? 'Admin' }}"
                     class="adm-topbar__avatar">
                <span class="adm-topbar__name">{{ auth()->user()?->first_name ?? 'Admin' }}</span>
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                    <path d="M4 6l3 3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            <div class="adm-dropdown" id="topUserDrop">
                <a href="{{ route('profile.show') }}" class="adm-dropdown__item">
                    <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                        <circle cx="8" cy="5" r="3" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M2 14c0-3.314 2.686-6 6-6s6 2.686 6 6" stroke="currentColor" stroke-width="1.5"/>
                    </svg>
                    My Profile
                </a>
                <div class="adm-dropdown__divider"></div>
                <form method="POST" action="{{ route('logout') }}" id="topbarLogoutForm">
                    @csrf
                    <button type="button" class="adm-dropdown__item adm-dropdown__item--danger" onclick="confirmLogout('topbarLogoutForm')">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                            <path d="M6 3H3v10h3M6 8h7m0 0l-3-3m3 3l-3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Sign Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

{{-- ════════════════════════════════════
     MAIN CONTENT
════════════════════════════════════ --}}
<main class="adm-main">
    @if(session('success'))
        <div class="adm-alert adm-alert--success">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" style="flex-shrink:0;margin-top:1px">
                <path d="M3 8l3 3 7-7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="adm-alert adm-alert--error">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" style="flex-shrink:0;margin-top:1px">
                <circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="1.5"/>
                <path d="M8 5v4M8 11v.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
/* ── Sidebar toggle ── */
function openSidebar() {
    document.getElementById('admSidebar').classList.add('is-open');
    document.getElementById('admOverlay').classList.add('is-open');
}
function closeSidebar() {
    document.getElementById('admSidebar').classList.remove('is-open');
    document.getElementById('admOverlay').classList.remove('is-open');
}

/* ── Collapsible nav groups ── */
function toggleGroup(btn) {
    btn.classList.toggle('is-open');
    const body = btn.nextElementSibling;
    body.classList.toggle('is-open');
}

/* ── Dropdown ── */
function toggleDropdown(id) {
    const el = document.getElementById(id);
    el.classList.toggle('is-open');
}
document.addEventListener('click', function(e) {
    const btn = document.getElementById('topUserDropBtn');
    const drop = document.getElementById('topUserDrop');
    if (btn && drop && !btn.contains(e.target) && !drop.contains(e.target)) {
        drop.classList.remove('is-open');
    }
});

/* ── Logout confirmation ── */
function confirmLogout(formId) {
    Swal.fire({
        title: 'Sign Out?',
        text: 'Are you sure you want to sign out?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sign Out',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        reverseButtons: true
    }).then(function (result) {
        if (result.isConfirmed) {
            document.getElementById(formId).submit();
        }
    });
}

/* ── Disable / Enable User SweetAlert Confirmation ── */
function confirmDisableUser(formElement, userName, isActive) {
    var titleText = isActive ? 'Disable Account?' : 'Enable Account?';
    var bodyText = isActive 
        ? 'Are you sure you want to disable ' + userName + '? They will not be able to log in.'
        : 'Are you sure you want to re-enable ' + userName + '\'s account?';
    
    Swal.fire({
        title: titleText,
        text: bodyText,
        icon: isActive ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonText: isActive ? 'Yes, Disable Account' : 'Yes, Enable Account',
        cancelButtonText: 'Cancel',
        confirmButtonColor: isActive ? '#f59e0b' : '#10b981',
        cancelButtonColor: '#6b7280',
        reverseButtons: true
    }).then(function (result) {
        if (result.isConfirmed) {
            formElement.submit();
        }
    });
    return false;
}

/* ── Remove User SweetAlert Confirmation ── */
function confirmRemoveUser(formElement, userName) {
    Swal.fire({
        title: 'Permanently Delete Account?',
        html: 'Are you sure you want to permanently delete <strong>' + userName + '</strong>?<br><span style="color:#ef4444;font-size:0.875rem;margin-top:4px;display:block;">This action is permanent and cannot be undone!</span>',
        icon: 'error',
        showCancelButton: true,
        confirmButtonText: 'Yes, Remove Account',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        reverseButtons: true
    }).then(function (result) {
        if (result.isConfirmed) {
            formElement.submit();
        }
    });
    return false;
}

/* ── Approve User SweetAlert Confirmation ── */
function confirmApproveUser(formElement, userName) {
    Swal.fire({
        title: 'Approve Registration?',
        html: 'Are you sure you want to approve <strong>' + userName + '</strong>\'s registration?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Approve',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        reverseButtons: true
    }).then(function (result) {
        if (result.isConfirmed) {
            formElement.submit();
        }
    });
    return false;
}

/* ── Theme toggle ── */
function toggleTheme() {
    var html = document.getElementById('htmlRoot');
    var current = html.getAttribute('data-theme') || 'light';
    var next = current === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('mc-theme', next);
    updateThemeIcon();
}
function updateThemeIcon() {
    var current = document.getElementById('htmlRoot').getAttribute('data-theme');
    var icon = document.getElementById('adminThemeIcon');
    if (icon) icon.textContent = current === 'dark' ? '☀️' : '🌙';
}
document.addEventListener('DOMContentLoaded', updateThemeIcon);
</script>

@stack('scripts')
</body>
</html>
