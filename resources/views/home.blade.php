@extends('layouts.app')

@section('title', 'Home')

@section('content')
{{-- Hero Section --}}
<section class="hero">
    <div class="hero__inner">
        <div class="hero__content">
            <h1 class="hero__title">
                Accelerate Your Career<br>
                <span class="hero__title-highlight">With Expert Mentors</span>
            </h1>
            <p class="hero__subtitle">Book personalized 1-on-1 sessions with industry professionals. Get guidance on code reviews, architecture decisions, career growth, and more.</p>
            <div class="hero__actions">
                <a href="{{ route('gigs.index') }}" class="btn btn--primary btn--lg">Find a Mentor</a>
                <a href="{{ route('register') }}" class="btn btn--ghost btn--lg">Become a Mentor</a>
            </div>
            <div class="hero__stats">
                <div class="hero__stat">
                    <span class="hero__stat-number">{{ $stats['mentors'] }}+</span>
                    <span class="hero__stat-label">Expert Mentors</span>
                </div>
                <div class="hero__stat">
                    <span class="hero__stat-number">{{ $stats['sessions'] }}+</span>
                    <span class="hero__stat-label">Sessions Completed</span>
                </div>
                <div class="hero__stat">
                    <span class="hero__stat-number">{{ $stats['skills'] }}+</span>
                    <span class="hero__stat-label">Skills Covered</span>
                </div>
            </div>
        </div>
        <div class="hero__visual">
            <div class="hero__card hero__card--1">
                <div class="hero__card-avatar">SJ</div>
                <div class="hero__card-info">
                    <p class="hero__card-name">Sarah Johnson</p>
                    <p class="hero__card-role">Senior Architect @Google</p>
                </div>
                <div class="hero__card-badge">5.0</div>
            </div>
            <div class="hero__card hero__card--2">
                <div class="hero__card-avatar">MR</div>
                <div class="hero__card-info">
                    <p class="hero__card-name">Michael Ross</p>
                    <p class="hero__card-role">Lead Dev @Stripe</p>
                </div>
                <div class="hero__card-badge">4.9</div>
            </div>
            <div class="hero__card hero__card--3">
                <div class="hero__card-avatar">EC</div>
                <div class="hero__card-info">
                    <p class="hero__card-name">Emily Chen</p>
                    <p class="hero__card-role">Design Lead @Figma</p>
                </div>
                <div class="hero__card-badge">5.0</div>
            </div>
        </div>
    </div>
</section>

{{-- Trust Logos --}}
<section class="trust">
    <div class="trust__inner">
        <p class="trust__text">Trusted by professionals from leading companies</p>
        <div class="trust__logos">
            <span class="trust__logo">Google</span>
            <span class="trust__logo">Stripe</span>
            <span class="trust__logo">Airbnb</span>
            <span class="trust__logo">Spotify</span>
            <span class="trust__logo">Shopify</span>
            <span class="trust__logo">Notion</span>
        </div>
    </div>
</section>

{{-- Featured Gigs --}}
<section class="featured">
    <div class="featured__inner">
        <div class="featured__header">
            <h2 class="featured__title">Featured Sessions</h2>
            <a href="{{ route('gigs.index') }}" class="featured__link">
                View All
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        </div>

        <div class="gig-grid">
            @forelse ($featuredGigs as $gig)
                <article class="gig-card">
                    <div class="gig-card__header">
                        <div class="gig-card__mentor">
                            <div class="gig-card__avatar">{{ strtoupper(substr($gig->mentor->first_name, 0, 1) . substr($gig->mentor->last_name, 0, 1)) }}</div>
                            <div class="gig-card__mentor-info">
                                <p class="gig-card__mentor-name">{{ $gig->mentor->full_name }}</p>
                                @if ($gig->mentor->mentorProfile)
                                    <p class="gig-card__mentor-headline">{{ $gig->mentor->mentorProfile->headline }}</p>
                                @endif
                            </div>
                        </div>
                        @if ($gig->average_rating > 0)
                            <div class="gig-card__rating">
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1l2.12 4.3 4.74.7-3.43 3.34.81 4.72L8 11.77l-4.24 2.29.81-4.72L1.14 6l4.74-.7z"/></svg>
                                <span>{{ number_format($gig->average_rating, 1) }}</span>
                            </div>
                        @endif
                    </div>
                    <h3 class="gig-card__title">{{ $gig->title }}</h3>
                    <div class="gig-card__meta">
                        <span class="gig-card__meta-item">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5"/><path d="M8 4v4l3 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                            {{ $gig->formatted_duration }}
                        </span>
                        <span class="gig-card__meta-item">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M2 6h12M2 6v7a1 1 0 001 1h10a1 1 0 001-1V6M2 6V4a1 1 0 011-1h10a1 1 0 011 1v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 3V1m6 2V1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                            {{ $gig->delivery_format }}
                        </span>
                    </div>
                    <div class="gig-card__skills">
                        @foreach ($gig->skills->take(3) as $skill)
                            <span class="gig-card__skill">{{ $skill->name }}</span>
                        @endforeach
                        @if ($gig->skills->count() > 3)
                            <span class="gig-card__skill gig-card__skill--more">+{{ $gig->skills->count() - 3 }}</span>
                        @endif
                    </div>
                    <div class="gig-card__footer">
                        <span class="gig-card__price">{{ $gig->formatted_price }}<span class="gig-card__price-unit">/session</span></span>
                        <a href="{{ route('gigs.show', $gig->slug) }}" class="btn btn--primary btn--sm">View</a>
                    </div>
                </article>
            @empty
                <div class="empty">
                    <p class="empty__text">No sessions available yet. Check back soon!</p>
                </div>
            @endforelse
        </div>
    </div>
</section>

{{-- How It Works --}}
<section class="steps">
    <div class="steps__inner">
        <h2 class="steps__title">How It Works</h2>
        <div class="steps__grid">
            <div class="step">
                <div class="step__number">01</div>
                <h3 class="step__title">Find Your Mentor</h3>
                <p class="step__desc">Browse by skill, experience level, or price. Filter to find the perfect match for your goals.</p>
            </div>
            <div class="step">
                <div class="step__number">02</div>
                <h3 class="step__title">Book a Session</h3>
                <p class="step__desc">Choose a gig and request a session. The mentor will respond within 24 hours.</p>
            </div>
            <div class="step">
                <div class="step__number">03</div>
                <h3 class="step__title">Learn & Grow</h3>
                <p class="step__desc">Connect via video call, get personalized guidance, and leave a review to help others.</p>
            </div>
        </div>
    </div>
</section>
@endsection
