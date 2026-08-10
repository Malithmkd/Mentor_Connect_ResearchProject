{{-- ═══════════════════════════════════════════════════
     SITE HEADER  —  MentorConnect
     ═══════════════════════════════════════════════════ --}}
<header class="nav" id="siteHeader">
    <div class="nav__bar">

        {{-- Logo --}}
        <a href="{{ route('home') }}" class="nav__logo">
            <svg class="nav__logo-icon" width="26" height="26" viewBox="0 0 32 32" fill="none">
                <circle cx="16" cy="12" r="6" stroke="currentColor" stroke-width="2" fill="none"/>
                <path d="M6 28c0-5.523 4.477-10 10-10s10 4.477 10 10" stroke="currentColor" stroke-width="2" fill="none"/>
            </svg>
            <span class="nav__logo-text">MentorConnect</span>
        </a>

        {{-- Desktop nav links --}}
        <nav class="nav__links" aria-label="Main navigation">
            @if(!auth()->check() || !auth()->user()->isAdmin())
                <a href="{{ route('home') }}"       class="nav__link {{ request()->routeIs('home')    ? 'nav__link--active' : '' }}">Home</a>
                <a href="{{ route('gigs.index') }}" class="nav__link {{ request()->routeIs('gigs.*')  ? 'nav__link--active' : '' }}">Find Mentors</a>
            @endif
            @auth
                @role('freelancer')
                    <a href="{{ route('freelancer.dashboard') }}" class="nav__link {{ request()->routeIs('freelancer.dashboard') ? 'nav__link--active' : '' }}">Dashboard</a>
                    <a href="{{ route('lms.index') }}"            class="nav__link {{ request()->routeIs('lms.*')               ? 'nav__link--active' : '' }}">
                        My Learning
                        @php $lmsBadge = \App\Models\MentorshipRelationship::forFreelancer(auth()->id())->accepted()->whereDoesntHave('enrollments', fn($q) => $q->where('freelancer_id', auth()->id()))->count(); @endphp
                        @if($lmsBadge > 0)<span class="nav__badge nav__badge--inline">{{ $lmsBadge }}</span>@endif
                    </a>
                @endrole
                @role('mentor')
                    <a href="{{ route('mentor.dashboard') }}"              class="nav__link {{ request()->routeIs('mentor.dashboard') ? 'nav__link--active' : '' }}">Dashboard</a>
                    <a href="{{ route('mentor.lms.relationships.index') }}" class="nav__link {{ request()->routeIs('mentor.lms.*')     ? 'nav__link--active' : '' }}">
                        Long-term
                        @php $pendingLmsCount = \App\Models\MentorshipRelationship::forMentor(auth()->id())->pending()->count(); @endphp
                        @if($pendingLmsCount > 0)<span class="nav__badge nav__badge--inline">{{ $pendingLmsCount }}</span>@endif
                    </a>
                @endrole
                @role('admin')
                    <a href="{{ route('admin.dashboard') }}" class="nav__link {{ request()->routeIs('admin.*') ? 'nav__link--active' : '' }}">Admin Dashboard</a>
                @endrole
            @endauth
        </nav>

        {{-- Right-side actions --}}
        <div class="nav__actions">

            {{-- Theme toggle --}}
            <button id="themeToggleBtn" class="nav__icon-btn" onclick="toggleTheme()" aria-label="Toggle theme" title="Toggle dark/light mode">
                <span id="themeToggleIcon">🌙</span>
            </button>

            @guest
                <a href="{{ route('login') }}"    class="btn btn--ghost btn--sm">Sign In</a>
                <a href="{{ route('register') }}" class="btn btn--primary btn--sm">Get Started</a>
            @endguest

            @auth
                {{-- Notifications bell --}}
                <a href="{{ route('notifications.index') }}" class="nav__icon-btn nav__notif" aria-label="Notifications">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M10 2a6 6 0 00-6 6v3l-2 2h16l-2-2V8a6 6 0 00-6-6z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                        <path d="M8 16a2 2 0 004 0" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    @php $unreadCount = \App\Models\Notification::where('user_id', auth()->id())->unread()->count(); @endphp
                    @if($unreadCount > 0)<span class="nav__badge">{{ $unreadCount }}</span>@endif
                </a>

                {{-- User dropdown --}}
                <div class="nav__user" id="navUserMenu">
                    <button class="nav__user-btn" id="navUserToggle" aria-expanded="false" aria-haspopup="true" onclick="navToggleUser(event)">
                        <img src="{{ auth()->user()->avatar_url }}"
                             alt="{{ auth()->user()->first_name }}"
                             class="nav__avatar" width="32" height="32">
                        <span class="nav__user-name">{{ auth()->user()->first_name }}</span>
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" class="nav__chevron">
                            <path d="M3 5l3 3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>

                    <div class="nav__dropdown" id="navDropdown">
                        <div class="nav__dropdown-header">
                            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->full_name }}" class="nav__dropdown-avatar">
                            <div>
                                <p class="nav__dropdown-name">{{ auth()->user()->full_name }}</p>
                                <p class="nav__dropdown-role">{{ auth()->user()->role->label() }}</p>
                            </div>
                        </div>
                        <a href="{{ route('profile.show') }}" class="nav__dropdown-item">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="5" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M2 14c0-3.314 2.686-6 6-6s6 2.686 6 6" stroke="currentColor" stroke-width="1.5"/></svg>
                            My Profile
                        </a>
                        @role('mentor')
                        <a href="{{ route('mentor.gigs.index') }}" class="nav__dropdown-item">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M2 3h12v10H2z" stroke="currentColor" stroke-width="1.5"/><path d="M5 6h6M5 9h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                            My Gigs
                        </a>
                        @endrole
                        <div class="nav__dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}" id="logoutFormDesktop">
                            @csrf
                            <button type="button" class="nav__dropdown-item nav__dropdown-item--danger" onclick="confirmLogout('logoutFormDesktop')">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M6 3H3v10h3M6 8h7m0 0l-3-3m3 3l-3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                Sign Out
                            </button>
                        </form>
                    </div>
                </div>
            @endauth

            {{-- Hamburger (mobile only) --}}
            <button class="nav__hamburger" id="navHamburger" aria-label="Open menu" aria-expanded="false" onclick="navToggleMobile()">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>

    {{-- Mobile drawer --}}
    <div class="nav__drawer" id="navDrawer" aria-hidden="true">
        @if(!auth()->check() || !auth()->user()->isAdmin())
            <a href="{{ route('home') }}"       class="nav__drawer-link {{ request()->routeIs('home')   ? 'nav__drawer-link--active' : '' }}" onclick="navCloseMobile()">Home</a>
            <a href="{{ route('gigs.index') }}" class="nav__drawer-link {{ request()->routeIs('gigs.*') ? 'nav__drawer-link--active' : '' }}" onclick="navCloseMobile()">Find Mentors</a>
        @endif
        @auth
            @role('freelancer')
                <a href="{{ route('freelancer.dashboard') }}" class="nav__drawer-link {{ request()->routeIs('freelancer.dashboard') ? 'nav__drawer-link--active' : '' }}" onclick="navCloseMobile()">Dashboard</a>
                <a href="{{ route('lms.index') }}"            class="nav__drawer-link {{ request()->routeIs('lms.*')               ? 'nav__drawer-link--active' : '' }}" onclick="navCloseMobile()">My Learning</a>
            @endrole
            @role('mentor')
                <a href="{{ route('mentor.dashboard') }}"               class="nav__drawer-link {{ request()->routeIs('mentor.dashboard') ? 'nav__drawer-link--active' : '' }}" onclick="navCloseMobile()">Dashboard</a>
                <a href="{{ route('mentor.lms.relationships.index') }}" class="nav__drawer-link {{ request()->routeIs('mentor.lms.*')     ? 'nav__drawer-link--active' : '' }}" onclick="navCloseMobile()">Long-term Mentorship</a>
                <a href="{{ route('mentor.gigs.index') }}"              class="nav__drawer-link {{ request()->routeIs('mentor.gigs.*')    ? 'nav__drawer-link--active' : '' }}" onclick="navCloseMobile()">My Gigs</a>
            @endrole
            @role('admin')
                <a href="{{ route('admin.dashboard') }}" class="nav__drawer-link {{ request()->routeIs('admin.*') ? 'nav__drawer-link--active' : '' }}" onclick="navCloseMobile()">Admin Dashboard</a>
            @endrole
            <hr class="nav__drawer-hr">
            <a href="{{ route('profile.show') }}"        class="nav__drawer-link" onclick="navCloseMobile()">My Profile</a>
            <a href="{{ route('notifications.index') }}" class="nav__drawer-link" onclick="navCloseMobile()">Notifications</a>
            <hr class="nav__drawer-hr">
            <form method="POST" action="{{ route('logout') }}" id="logoutFormMobile">
                @csrf
                <button type="button" class="nav__drawer-link nav__drawer-link--btn nav__drawer-link--danger" onclick="confirmLogout('logoutFormMobile')">Sign Out</button>
            </form>
        @endauth
        @guest
            <hr class="nav__drawer-hr">
            <a href="{{ route('login') }}"    class="nav__drawer-link" onclick="navCloseMobile()">Sign In</a>
            <a href="{{ route('register') }}" class="nav__drawer-link nav__drawer-link--primary" onclick="navCloseMobile()">Get Started</a>
        @endguest
    </div>
</header>

<script>
/* ── Theme ── */
function toggleTheme() {
    var html = document.getElementById('htmlRoot');
    var next = (html.getAttribute('data-theme') || 'light') === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('mc-theme', next);
    updateThemeIcon();
}
function updateThemeIcon() {
    var t = (document.getElementById('htmlRoot') || document.documentElement).getAttribute('data-theme');
    var el = document.getElementById('themeToggleIcon');
    if (el) el.textContent = t === 'dark' ? '☀️' : '🌙';
}
document.addEventListener('DOMContentLoaded', updateThemeIcon);

/* ── User dropdown ── */
function navToggleUser(e) {
    if (e) e.stopPropagation();
    var d = document.getElementById('navDropdown');
    var b = document.getElementById('navUserToggle');
    if (!d) return;
    var open = d.classList.toggle('nav__dropdown--open');
    if (b) b.setAttribute('aria-expanded', String(open));
}
function navCloseUser() {
    var d = document.getElementById('navDropdown');
    var b = document.getElementById('navUserToggle');
    if (d) d.classList.remove('nav__dropdown--open');
    if (b) b.setAttribute('aria-expanded', 'false');
}

/* ── Mobile drawer ── */
function navToggleMobile() {
    var drawer  = document.getElementById('navDrawer');
    var burger  = document.getElementById('navHamburger');
    if (!drawer) return;
    var open = drawer.classList.toggle('nav__drawer--open');
    if (burger) {
        burger.classList.toggle('nav__hamburger--open', open);
        burger.setAttribute('aria-expanded', String(open));
    }
    drawer.setAttribute('aria-hidden', String(!open));
    document.body.style.overflow = open ? 'hidden' : '';
}
function navCloseMobile() {
    var drawer = document.getElementById('navDrawer');
    var burger = document.getElementById('navHamburger');
    if (drawer) { drawer.classList.remove('nav__drawer--open'); drawer.setAttribute('aria-hidden', 'true'); }
    if (burger) { burger.classList.remove('nav__hamburger--open'); burger.setAttribute('aria-expanded', 'false'); }
    document.body.style.overflow = '';
}

/* ── Global close on outside-click / Escape / resize ── */
document.addEventListener('click', function(e) {
    var um = document.getElementById('navUserMenu');
    if (um && !um.contains(e.target)) navCloseUser();

    var header = document.getElementById('siteHeader');
    var drawer = document.getElementById('navDrawer');
    if (drawer && drawer.classList.contains('nav__drawer--open')) {
        if (!header || !header.contains(e.target)) navCloseMobile();
    }
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { navCloseUser(); navCloseMobile(); }
});
window.addEventListener('resize', function() {
    if (window.innerWidth >= 768) navCloseMobile();
});
</script>
