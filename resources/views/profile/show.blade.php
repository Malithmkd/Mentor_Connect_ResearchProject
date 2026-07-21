@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
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
@endsection
