<header class="site-header" id="siteHeader">
    <div class="site-header__inner">
        <a href="{{ route('home') }}" class="site-header__logo">
            <svg class="site-header__logo-icon" width="28" height="28" viewBox="0 0 32 32" fill="none">
                <circle cx="16" cy="12" r="6" stroke="currentColor" stroke-width="2" fill="none"/>
                <path d="M6 28c0-5.523 4.477-10 10-10s10 4.477 10 10" stroke="currentColor" stroke-width="2" fill="none"/>
            </svg>
            <span class="site-header__logo-text">MentorConnect</span>
        </a>

        <nav class="site-header__nav" aria-label="Main navigation">
            @if(!auth()->check() || !auth()->user()->isAdmin())
                <a href="{{ route('home') }}" class="site-header__link {{ request()->routeIs('home') ? 'site-header__link--active' : '' }}">
                    Home
                </a>
                <a href="{{ route('gigs.index') }}" class="site-header__link {{ request()->routeIs('gigs.*') ? 'site-header__link--active' : '' }}">
                    Find Mentors
                </a>
            @endif
            @auth
                @role('freelancer')
                    <a href="{{ route('freelancer.dashboard') }}" class="site-header__link {{ request()->routeIs('freelancer.dashboard') ? 'site-header__link--active' : '' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('lms.index') }}" class="site-header__link {{ request()->routeIs('lms.*') ? 'site-header__link--active' : '' }}">
                        My Learning
                        @php
                            $lmsBadge = \App\Models\MentorshipRelationship::forFreelancer(auth()->id())
                                ->accepted()
                                ->whereDoesntHave('enrollments', fn($q) => $q->where('freelancer_id', auth()->id()))
                                ->count();
                        @endphp
                        @if($lmsBadge > 0)
                            <span class="site-header__notif-badge" style="position:relative;top:auto;right:auto;margin-left:4px">{{ $lmsBadge }}</span>
                        @endif
                    </a>
                @endrole
                @role('mentor')
                    <a href="{{ route('mentor.dashboard') }}" class="site-header__link {{ request()->routeIs('mentor.dashboard') ? 'site-header__link--active' : '' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('mentor.lms.relationships.index') }}" class="site-header__link {{ request()->routeIs('mentor.lms.*') ? 'site-header__link--active' : '' }}">
                        Long-term
                        @php
                            $pendingLmsCount = \App\Models\MentorshipRelationship::forMentor(auth()->id())->pending()->count();
                        @endphp
                        @if($pendingLmsCount > 0)
                            <span class="site-header__notif-badge" style="position:relative;top:auto;right:auto;margin-left:4px">{{ $pendingLmsCount }}</span>
                        @endif
                    </a>
                @endrole
                @role('admin')
                    <a href="{{ route('admin.dashboard') }}" class="site-header__link {{ request()->routeIs('admin.*') ? 'site-header__link--active' : '' }}">
                        Admin Dashboard
                    </a>
                @endrole
            @endauth
        </nav>

        <div class="site-header__actions">
            {{-- Theme toggle (always visible) --}}
            <button id="themeToggleBtn" class="site-header__theme-toggle" onclick="toggleTheme()" aria-label="Toggle dark/light mode" title="Toggle dark/light mode">
                <span id="themeToggleIcon">🌙</span>
            </button>

            @guest
                <a href="{{ route('login') }}" class="btn btn--ghost btn--sm">Sign In</a>
                <a href="{{ route('register') }}" class="btn btn--primary btn--sm">Get Started</a>
            @endguest

            @auth
                <a href="{{ route('notifications.index') }}" class="site-header__notif" aria-label="Notifications">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M10 2a6 6 0 00-6 6v3l-2 2h16l-2-2V8a6 6 0 00-6-6z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                        <path d="M8 16a2 2 0 004 0" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    @php
                        $unreadCount = \App\Models\Notification::where('user_id', auth()->id())->unread()->count();
                    @endphp
                    @if ($unreadCount > 0)
                        <span class="site-header__notif-badge">{{ $unreadCount }}</span>
                    @endif
                </a>

                <div class="site-header__user" id="userMenu">
                    <button class="site-header__user-toggle" aria-expanded="false" aria-haspopup="true" onclick="document.getElementById('userDropdown').classList.toggle('site-header__dropdown--open')">
                        <img src="{{ auth()->user()->avatar_url }}"
                             alt="{{ auth()->user()->first_name }}"
                             class="site-header__user-avatar site-header__user-avatar--img"
                             width="32" height="32">
                        <span class="site-header__user-name">{{ auth()->user()->first_name }}</span>
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                            <path d="M3 5l3 3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>

                    <div class="site-header__dropdown" id="userDropdown">
                        <div class="site-header__dropdown-header" style="display:flex;align-items:center;gap:.75rem">
                            <img src="{{ auth()->user()->avatar_url }}"
                                 alt="{{ auth()->user()->full_name }}"
                                 style="width:40px;height:40px;border-radius:50%;object-fit:cover;flex-shrink:0">
                            <div>
                                <p class="site-header__dropdown-name">{{ auth()->user()->full_name }}</p>
                                <p class="site-header__dropdown-role">{{ auth()->user()->role->label() }}</p>
                            </div>
                        </div>
                        <a href="{{ route('profile.show') }}" class="site-header__dropdown-item">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <circle cx="8" cy="5" r="3" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M2 14c0-3.314 2.686-6 6-6s6 2.686 6 6" stroke="currentColor" stroke-width="1.5"/>
                            </svg>
                            My Profile
                        </a>
                        @role('mentor')
                            <a href="{{ route('mentor.gigs.index') }}" class="site-header__dropdown-item">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                    <path d="M2 3h12v10H2z" stroke="currentColor" stroke-width="1.5"/>
                                    <path d="M5 6h6M5 9h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                                My Gigs
                            </a>
                        @endrole
                        <div class="site-header__dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}" class="site-header__dropdown-form" id="logoutFormDesktop">
                            @csrf
                            <button type="button" class="site-header__dropdown-item site-header__dropdown-item--danger" onclick="confirmLogout('logoutFormDesktop')">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                    <path d="M6 3H3v10h3M6 8h7m0 0l-3-3m3 3l-3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Sign Out
                            </button>
                        </form>
                    </div>
                </div>
            @endauth
        </div>

        <button class="site-header__mobile-toggle" aria-label="Toggle navigation" onclick="document.getElementById('mobileNav').classList.toggle('site-header__mobile-nav--open')">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>
    </div>

    <nav class="site-header__mobile-nav" id="mobileNav" aria-label="Mobile navigation">
        @if(!auth()->check() || !auth()->user()->isAdmin())
            <a href="{{ route('home') }}" class="site-header__mobile-link">Home</a>
            <a href="{{ route('gigs.index') }}" class="site-header__mobile-link">Find Mentors</a>
        @endif
        @auth
            <a href="{{ route('profile.show') }}" class="site-header__mobile-link">My Profile</a>
            <a href="{{ route('notifications.index') }}" class="site-header__mobile-link">Notifications</a>
            <form method="POST" action="{{ route('logout') }}" id="logoutFormMobile">
                @csrf
                <button type="button" class="site-header__mobile-link site-header__mobile-link--btn" onclick="confirmLogout('logoutFormMobile')">Sign Out</button>
            </form>
        @endauth
        @guest
            <a href="{{ route('login') }}" class="site-header__mobile-link">Sign In</a>
            <a href="{{ route('register') }}" class="site-header__mobile-link site-header__mobile-link--primary">Get Started</a>
        @endguest
    </nav>
</header>

<script>
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
    var current = (document.getElementById('htmlRoot') || document.documentElement).getAttribute('data-theme');
    var icon = document.getElementById('themeToggleIcon');
    if (icon) icon.textContent = current === 'dark' ? '☀️' : '🌙';
}
document.addEventListener('DOMContentLoaded', updateThemeIcon);
</script>
