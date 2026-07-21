@extends('layouts.app')

@section('title', $user->full_name . ' — Profile')

@section('content')
<section class="gig-detail">
    <div class="gig-detail__inner">

        {{-- Breadcrumb --}}
        <nav class="breadcrumb" style="margin-bottom: var(--space-6)">
            <a href="{{ route('home') }}" class="breadcrumb__item">Home</a>
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span class="breadcrumb__item breadcrumb__item--current">{{ $user->full_name }}</span>
        </nav>

        <div style="display:grid; grid-template-columns:1fr; gap:2rem; max-width:900px; margin:0 auto">
            @media(min-width:768px) {
                <style>.profile-view-grid { grid-template-columns: 280px 1fr !important; }</style>
            }

            {{-- ── LEFT SIDEBAR ── --}}
            <div class="profile-view-grid" style="display:grid; grid-template-columns:1fr; gap:2rem; align-items:start">
                {{-- Profile card --}}
                <div class="gig-detail__card" style="text-align:center">

                    {{-- Avatar --}}
                    <div style="width:80px; height:80px; border-radius:50%; background:var(--color-primary);
                                color:#fff; font-size:2rem; font-weight:700; display:flex; align-items:center;
                                justify-content:center; margin:0 auto var(--space-4)">
                        {{ strtoupper(substr($user->first_name,0,1).substr($user->last_name,0,1)) }}
                    </div>

                    <h1 style="font-size:var(--text-xl); font-weight:700; color:var(--color-gray-900); margin-bottom:var(--space-1)">
                        {{ $user->full_name }}
                    </h1>
                    <p style="font-size:var(--text-sm); color:var(--color-gray-500); margin-bottom:var(--space-4)">
                        {{ $user->role->label() }}
                        @if ($user->isMentor() && $user->mentorProfile?->headline)
                            &middot; {{ $user->mentorProfile->headline }}
                        @endif
                    </p>

                    {{-- ★ Star rating block --}}
                    @if ($totalReviews > 0)
                        @php $fullStars = floor($averageRating); $halfStar = ($averageRating - $fullStars) >= 0.5; @endphp
                        <div style="display:flex; flex-direction:column; align-items:center; gap:var(--space-1); margin-bottom:var(--space-4)">
                            <div style="display:flex; gap:3px">
                                @for ($i = 1; $i <= 5; $i++)
                                    @if ($i <= $fullStars)
                                        {{-- Full star --}}
                                        <svg width="20" height="20" viewBox="0 0 16 16" fill="var(--color-warning)" class="review__star review__star--filled">
                                            <path d="M8 1l2.12 4.3 4.74.7-3.43 3.34.81 4.72L8 11.77l-4.24 2.29.81-4.72L1.14 6l4.74-.7z"/>
                                        </svg>
                                    @elseif ($halfStar && $i == $fullStars + 1)
                                        {{-- Half star (filled + overlay trick) --}}
                                        <svg width="20" height="20" viewBox="0 0 16 16" style="position:relative">
                                            <defs>
                                                <linearGradient id="half-{{ $i }}">
                                                    <stop offset="50%" stop-color="var(--color-warning)"/>
                                                    <stop offset="50%" stop-color="none" stop-opacity="0"/>
                                                </linearGradient>
                                            </defs>
                                            <path d="M8 1l2.12 4.3 4.74.7-3.43 3.34.81 4.72L8 11.77l-4.24 2.29.81-4.72L1.14 6l4.74-.7z"
                                                  fill="url(#half-{{ $i }})" stroke="var(--color-warning)" stroke-width="0.5"/>
                                        </svg>
                                    @else
                                        {{-- Empty star --}}
                                        <svg width="20" height="20" viewBox="0 0 16 16" fill="none" stroke="var(--color-gray-300)" stroke-width="0.8" class="review__star">
                                            <path d="M8 1l2.12 4.3 4.74.7-3.43 3.34.81 4.72L8 11.77l-4.24 2.29.81-4.72L1.14 6l4.74-.7z"/>
                                        </svg>
                                    @endif
                                @endfor
                            </div>
                            <div style="font-size:var(--text-lg); font-weight:700; color:var(--color-gray-900)">
                                {{ number_format($averageRating, 1) }}<span style="font-size:var(--text-sm); color:var(--color-gray-400); font-weight:400">/5</span>
                            </div>
                            <div style="font-size:var(--text-xs); color:var(--color-gray-500)">
                                Based on {{ $totalReviews }} review{{ $totalReviews > 1 ? 's' : '' }}
                            </div>
                        </div>
                    @else
                        <p style="font-size:var(--text-sm); color:var(--color-gray-400); margin-bottom:var(--space-4)">No reviews yet</p>
                    @endif

                    {{-- Meta info --}}
                    <div style="text-align:left; border-top:1px solid var(--color-gray-100); padding-top:var(--space-4)">
                        @if ($user->location)
                            <div style="display:flex; align-items:center; gap:var(--space-2); margin-bottom:var(--space-2); font-size:var(--text-sm); color:var(--color-gray-600)">
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M8 1a5 5 0 00-5 5c0 3.5 5 9 5 9s5-5.5 5-9a5 5 0 00-5-5zm0 7a2 2 0 110-4 2 2 0 010 4z" stroke="currentColor" stroke-width="1.2"/></svg>
                                {{ $user->location }}
                            </div>
                        @endif
                        <div style="display:flex; align-items:center; gap:var(--space-2); font-size:var(--text-sm); color:var(--color-gray-500)">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><rect x="2" y="3" width="12" height="11" rx="1" stroke="currentColor" stroke-width="1.2"/><path d="M5 1v4M11 1v4M2 7h12" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
                            Member since {{ $user->created_at->format('M Y') }}
                        </div>
                    </div>

                    {{-- Mentor extra links --}}
                    @if ($user->isMentor() && $user->mentorProfile)
                        <div style="border-top:1px solid var(--color-gray-100); padding-top:var(--space-4); margin-top:var(--space-4); display:flex; flex-direction:column; gap:var(--space-2)">
                            @if ($user->mentorProfile->website)
                                <a href="{{ $user->mentorProfile->website }}" target="_blank" rel="noopener"
                                   style="font-size:var(--text-sm); color:var(--color-primary); display:flex; align-items:center; gap:var(--space-2)">
                                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="1.2"/><path d="M8 2s-3 3-3 6 3 6 3 6M8 2s3 3 3 6-3 6-3 6M2 8h12" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
                                    Website
                                </a>
                            @endif
                            @if ($user->mentorProfile->linkedin_url)
                                <a href="{{ $user->mentorProfile->linkedin_url }}" target="_blank" rel="noopener"
                                   style="font-size:var(--text-sm); color:var(--color-primary); display:flex; align-items:center; gap:var(--space-2)">
                                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M13.5 0h-11A2.5 2.5 0 000 2.5v11A2.5 2.5 0 002.5 16h11a2.5 2.5 0 002.5-2.5v-11A2.5 2.5 0 0013.5 0zM5 13H3V6h2v7zm-1-8a1 1 0 110-2 1 1 0 010 2zm9 8h-2v-3.5c0-1-.4-1.5-1.1-1.5-.8 0-1.2.6-1.2 1.5V13H7.5V6H9.5v1a2.5 2.5 0 012.2-1.1c1.5 0 2.3 1 2.3 3V13z"/></svg>
                                    LinkedIn
                                </a>
                            @endif
                            @if ($user->mentorProfile->github_url)
                                <a href="{{ $user->mentorProfile->github_url }}" target="_blank" rel="noopener"
                                   style="font-size:var(--text-sm); color:var(--color-primary); display:flex; align-items:center; gap:var(--space-2)">
                                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/></svg>
                                    GitHub
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- ── RIGHT MAIN CONTENT ── --}}
                <div style="display:flex; flex-direction:column; gap:1.5rem">

                    {{-- Bio / About --}}
                    @if ($user->bio || ($user->isMentor() && $user->mentorProfile?->about))
                        <div class="panel">
                            <div class="panel__header">
                                <h2 class="panel__title">About</h2>
                            </div>
                            <div class="panel__body">
                                @if ($user->isMentor() && $user->mentorProfile?->about)
                                    <p style="color:var(--color-gray-700); line-height:1.7">{{ $user->mentorProfile->about }}</p>
                                @elseif ($user->bio)
                                    <p style="color:var(--color-gray-700); line-height:1.7">{{ $user->bio }}</p>
                                @endif

                                @if ($user->isMentor() && $user->mentorProfile)
                                    <div class="gig-detail__meta-grid" style="margin-top:var(--space-5)">
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
                                        <div class="gig-detail__meta-item">
                                            <span class="gig-detail__meta-label">Status</span>
                                            <span class="gig-detail__meta-value">
                                                <span class="badge badge--{{ $user->mentorProfile->verification_status === 'verified' ? 'success' : 'warning' }}">
                                                    {{ ucfirst($user->mentorProfile->verification_status) }}
                                                </span>
                                            </span>
                                        </div>
                                        @if ($user->mentorProfile->total_sessions > 0)
                                            <div class="gig-detail__meta-item">
                                                <span class="gig-detail__meta-label">Sessions</span>
                                                <span class="gig-detail__meta-value">{{ $user->mentorProfile->total_sessions }} completed</span>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Mentor's published gigs --}}
                    @if ($user->isMentor() && $user->gigs->count() > 0)
                        <div class="panel">
                            <div class="panel__header">
                                <h2 class="panel__title">Sessions Offered</h2>
                            </div>
                            <div class="panel__body">
                                <div class="gig-list">
                                    @foreach ($user->gigs as $gig)
                                        <a href="{{ route('gigs.show', $gig->slug) }}" class="gig-list__item">
                                            <div class="gig-list__info">
                                                <p class="gig-list__title">{{ $gig->title }}</p>
                                                <p class="gig-list__subtitle">{{ $gig->formatted_duration }} &middot; {{ ucfirst($gig->experience_level) }}</p>
                                            </div>
                                            <span class="gig-list__price">{{ $gig->formatted_price }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Reviews received --}}
                    <div class="panel">
                        <div class="panel__header">
                            <h2 class="panel__title">
                                Reviews
                                @if ($totalReviews > 0)
                                    <span style="font-size:var(--text-sm); color:var(--color-gray-400); font-weight:400">({{ $totalReviews }})</span>
                                @endif
                            </h2>
                            @if ($totalReviews > 0)
                                <span style="font-size:var(--text-sm); color:var(--color-warning); font-weight:600">
                                    &#9733; {{ number_format($averageRating, 1) }}/5
                                </span>
                            @endif
                        </div>
                        <div class="panel__body">
                            @if ($receivedReviews->count() > 0)
                                <div class="reviews-list" style="display:flex; flex-direction:column; gap:1.25rem">
                                    @foreach ($receivedReviews as $review)
                                        <div class="review" style="padding-bottom:1.25rem; border-bottom:1px solid var(--color-gray-100)">
                                            <div class="review__header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:var(--space-2)">
                                                <div style="display:flex; align-items:center; gap:var(--space-3)">
                                                    <div style="width:36px; height:36px; border-radius:50%; background:var(--color-primary-50);
                                                                color:var(--color-primary); font-weight:700; font-size:var(--text-sm);
                                                                display:flex; align-items:center; justify-content:center; flex-shrink:0">
                                                        {{ strtoupper(substr($review->reviewer->first_name,0,1)) }}
                                                    </div>
                                                    <div>
                                                        <p style="font-size:var(--text-sm); font-weight:600; color:var(--color-gray-900)">
                                                            {{ $review->reviewer->full_name }}
                                                        </p>
                                                        {{-- Star rating row --}}
                                                        <div style="display:flex; align-items:center; gap:var(--space-2); margin-top:2px">
                                                            <div style="display:flex; gap:2px">
                                                                @for ($i = 1; $i <= 5; $i++)
                                                                    <svg width="12" height="12" viewBox="0 0 16 16"
                                                                         fill="{{ $i <= $review->rating ? 'var(--color-warning)' : 'none' }}"
                                                                         stroke="{{ $i <= $review->rating ? 'none' : 'var(--color-gray-300)' }}"
                                                                         stroke-width="0.8">
                                                                        <path d="M8 1l2.12 4.3 4.74.7-3.43 3.34.81 4.72L8 11.77l-4.24 2.29.81-4.72L1.14 6l4.74-.7z"/>
                                                                    </svg>
                                                                @endfor
                                                            </div>
                                                            <span style="font-size:var(--text-xs); font-weight:600; color:var(--color-warning)">{{ $review->rating }}/5</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span style="font-size:var(--text-xs); color:var(--color-gray-400)">{{ $review->created_at->diffForHumans() }}</span>
                                            </div>
                                            @if ($review->comment)
                                                <p style="font-size:var(--text-sm); color:var(--color-gray-700); line-height:1.6; padding-left:calc(36px + var(--space-3))">
                                                    "{{ $review->comment }}"
                                                </p>
                                            @endif
                                            @if ($review->gig)
                                                <p style="font-size:var(--text-xs); color:var(--color-gray-400); margin-top:var(--space-2); padding-left:calc(36px + var(--space-3))">
                                                    on <em>{{ $review->gig->title }}</em>
                                                </p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty">
                                    <p class="empty__text">No public reviews yet.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>{{-- end right column --}}
            </div>{{-- end grid --}}
        </div>

    </div>
</section>

<style>
@media (min-width: 768px) {
    .profile-view-grid {
        grid-template-columns: 280px 1fr !important;
    }
}
</style>
@endsection
