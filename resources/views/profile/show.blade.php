@php $isAdmin = auth()->user()?->isAdmin(); @endphp

@extends($isAdmin ? 'layouts.admin' : 'layouts.app')

@section('title', 'My Profile')

@section('content')

@if ($isAdmin)
{{-- ── Admin-styled profile page ── --}}
<div class="adm-page-header">
    <div>
        <h1 class="adm-page-title">My Profile</h1>
        <p class="adm-page-subtitle">Your administrator account details.</p>
    </div>
    <div class="adm-page-header__actions">
        <a href="{{ route('profile.edit') }}" class="adm-btn adm-btn--primary adm-btn--sm">Edit Profile</a>
        <a href="{{ route('admin.dashboard') }}" class="adm-btn adm-btn--ghost adm-btn--sm">← Dashboard</a>
    </div>
</div>

{{-- Profile card --}}
<div style="display:grid;grid-template-columns:280px 1fr;gap:20px;align-items:start;">

    {{-- Left: Avatar & summary --}}
    <div class="adm-card">
        <div class="adm-card__body" style="text-align:center;">
            <div style="width:88px;height:88px;border-radius:50%;background:var(--adm-primary-100);color:var(--adm-primary-dark);font-size:28px;font-weight:800;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
            </div>
            <div style="font-size:17px;font-weight:700;color:var(--adm-text-900);">{{ $user->full_name }}</div>
            <div style="margin-top:6px;">
                <span class="adm-badge adm-badge--blue">{{ $user->role->label() }}</span>
            </div>
            @if ($user->total_reviews > 0)
                <div style="margin-top:12px;font-size:13px;color:var(--adm-text-500);">
                    ⭐ {{ number_format($user->average_rating, 1) }}
                    <span style="color:var(--adm-text-400);">({{ $user->total_reviews }} review{{ $user->total_reviews > 1 ? 's' : '' }})</span>
                </div>
            @endif
            <div style="margin-top:16px;">
                <a href="{{ route('profile.edit') }}" class="adm-btn adm-btn--primary adm-btn--sm" style="width:100%;justify-content:center;">Edit Profile</a>
            </div>
        </div>
    </div>

    {{-- Right: Details --}}
    <div style="display:flex;flex-direction:column;gap:16px;">
        <div class="adm-card">
            <div class="adm-card__header">
                <div class="adm-card__title">Account Information</div>
            </div>
            <div class="adm-card__body">
                <dl style="display:grid;grid-template-columns:140px 1fr;gap:10px 16px;">
                    <dt style="font-size:11px;font-weight:600;color:var(--adm-text-400);text-transform:uppercase;letter-spacing:.05em;align-self:center;">Email</dt>
                    <dd style="margin:0;font-size:13px;color:var(--adm-text-700);">{{ $user->email }}</dd>

                    <dt style="font-size:11px;font-weight:600;color:var(--adm-text-400);text-transform:uppercase;letter-spacing:.05em;align-self:center;">Location</dt>
                    <dd style="margin:0;font-size:13px;color:var(--adm-text-700);">{{ $user->location ?? '—' }}</dd>

                    <dt style="font-size:11px;font-weight:600;color:var(--adm-text-400);text-transform:uppercase;letter-spacing:.05em;align-self:center;">Timezone</dt>
                    <dd style="margin:0;font-size:13px;color:var(--adm-text-700);">{{ $user->timezone }}</dd>

                    <dt style="font-size:11px;font-weight:600;color:var(--adm-text-400);text-transform:uppercase;letter-spacing:.05em;align-self:center;">Member Since</dt>
                    <dd style="margin:0;font-size:13px;color:var(--adm-text-700);">{{ $user->created_at->format('F Y') }}</dd>
                </dl>
            </div>
        </div>

        @if ($user->bio)
        <div class="adm-card">
            <div class="adm-card__header">
                <div class="adm-card__title">About</div>
            </div>
            <div class="adm-card__body">
                <p style="font-size:13px;color:var(--adm-text-500);line-height:1.7;margin:0;">{{ $user->bio }}</p>
            </div>
        </div>
        @endif

        @if ($user->isMentor() && $user->mentorProfile)
        <div class="adm-card">
            <div class="adm-card__header">
                <div class="adm-card__title">Mentor Profile</div>
            </div>
            <div class="adm-card__body">
                @if ($user->mentorProfile->headline)
                    <p style="font-size:14px;font-weight:600;color:var(--adm-text-900);margin:0 0 12px;">{{ $user->mentorProfile->headline }}</p>
                @endif
                @if ($user->mentorProfile->about)
                    <p style="font-size:13px;color:var(--adm-text-500);line-height:1.6;margin:0 0 14px;">{{ $user->mentorProfile->about }}</p>
                @endif
                <dl style="display:grid;grid-template-columns:140px 1fr;gap:8px 16px;">
                    @if ($user->mentorProfile->company)
                        <dt style="font-size:11px;font-weight:600;color:var(--adm-text-400);text-transform:uppercase;letter-spacing:.05em;">Company</dt>
                        <dd style="margin:0;font-size:13px;color:var(--adm-text-700);">{{ $user->mentorProfile->company }}</dd>
                    @endif
                    @if ($user->mentorProfile->years_experience)
                        <dt style="font-size:11px;font-weight:600;color:var(--adm-text-400);text-transform:uppercase;letter-spacing:.05em;">Experience</dt>
                        <dd style="margin:0;font-size:13px;color:var(--adm-text-700);">{{ $user->mentorProfile->years_experience }}+ years</dd>
                    @endif
                    @if ($user->mentorProfile->website)
                        <dt style="font-size:11px;font-weight:600;color:var(--adm-text-400);text-transform:uppercase;letter-spacing:.05em;">Website</dt>
                        <dd style="margin:0;font-size:13px;"><a href="{{ $user->mentorProfile->website }}" target="_blank" style="color:var(--adm-primary);">Visit</a></dd>
                    @endif
                    <dt style="font-size:11px;font-weight:600;color:var(--adm-text-400);text-transform:uppercase;letter-spacing:.05em;">Status</dt>
                    <dd style="margin:0;">
                        <span class="adm-badge {{ $user->mentorProfile->verification_status === 'verified' ? 'adm-badge--green' : 'adm-badge--amber' }}">
                            {{ ucfirst($user->mentorProfile->verification_status) }}
                        </span>
                    </dd>
                </dl>
            </div>
        </div>
        @endif
    </div>
</div>

@else
{{-- ── Regular (non-admin) profile page ── --}}
<section class="profile">
    <div class="profile__header">
        <div class="profile__avatar">{{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}</div>
        <h1 class="profile__name">{{ $user->full_name }}</h1>
        <p class="profile__role">{{ $user->role->label() }}</p>
        <div style="margin-top: var(--space-4);">
            <a href="{{ route('profile.edit') }}" class="btn btn--primary btn--sm">Edit Profile</a>
        </div>
    </div>

    <div class="profile__card">
        <div class="gig-detail__meta-grid">
            <div class="gig-detail__meta-item">
                <span class="gig-detail__meta-label">Email</span>
                <span class="gig-detail__meta-value">{{ $user->email }}</span>
            </div>
            <div class="gig-detail__meta-item">
                <span class="gig-detail__meta-label">Location</span>
                <span class="gig-detail__meta-value">{{ $user->location ?? 'Not set' }}</span>
            </div>
            <div class="gig-detail__meta-item">
                <span class="gig-detail__meta-label">Timezone</span>
                <span class="gig-detail__meta-value">{{ $user->timezone }}</span>
            </div>
            <div class="gig-detail__meta-item">
                <span class="gig-detail__meta-label">Member Since</span>
                <span class="gig-detail__meta-value">{{ $user->created_at->format('F Y') }}</span>
            </div>
            @if ($user->total_reviews > 0)
                <div class="gig-detail__meta-item">
                    <span class="gig-detail__meta-label">Rating</span>
                    <span class="gig-detail__meta-value" style="color: var(--warning); font-weight: 600;">&#9733; {{ number_format($user->average_rating, 1) }} ({{ $user->total_reviews }} review{{ $user->total_reviews > 1 ? 's' : '' }})</span>
                </div>
            @endif
        </div>

        @if ($user->bio)
            <div style="margin-top: var(--space-6);">
                <h3 class="gig-detail__section-title">About</h3>
                <p class="gig-detail__text">{{ $user->bio }}</p>
            </div>
        @endif

        @if ($user->isMentor() && $user->mentorProfile)
            <div style="margin-top: var(--space-6);">
                <h3 class="gig-detail__section-title">Mentor Profile</h3>
                @if ($user->mentorProfile->headline)
                    <p class="gig-detail__text font-semibold">{{ $user->mentorProfile->headline }}</p>
                @endif
                @if ($user->mentorProfile->about)
                    <p class="gig-detail__text" style="margin-top: var(--space-2);">{{ $user->mentorProfile->about }}</p>
                @endif
                <div class="gig-detail__meta-grid" style="margin-top: var(--space-4);">
                    @if ($user->mentorProfile->company)
                        <div class="gig-detail__meta-item">
                            <span class="gig-detail__meta-label">Company</span>
                            <span class="gig-detail__meta-value">{{ $user->mentorProfile->company }}</span>
                        </div>
                    @endif
                    @if ($user->mentorProfile->years_experience)
                        <div class="gig-detail__meta-item">
                            <span class="gig-detail__meta-label">Experience</span>
                            <span class="gig-detail__meta-value">{{ $user->mentorProfile->years_experience }}+ years</span>
                        </div>
                    @endif
                    @if ($user->mentorProfile->website)
                        <div class="gig-detail__meta-item">
                            <span class="gig-detail__meta-label">Website</span>
                            <a href="{{ $user->mentorProfile->website }}" target="_blank" class="gig-detail__meta-value">Visit</a>
                        </div>
                    @endif
                    <div class="gig-detail__meta-item">
                        <span class="gig-detail__meta-label">Status</span>
                        <span class="gig-detail__meta-value">
                            <span class="badge badge--{{ $user->mentorProfile->verification_status === 'verified' ? 'success' : 'warning' }}">
                                {{ ucfirst($user->mentorProfile->verification_status) }}
                            </span>
                        </span>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
@endif
@endsection
