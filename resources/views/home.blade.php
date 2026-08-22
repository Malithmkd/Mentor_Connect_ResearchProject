@extends('layouts.app')

@section('title', 'MentorConnect — Accelerate Your Career with Elite Mentors')

@section('content')
{{-- ══════════════════════════════════════════════════════
     Hero Section
     ══════════════════════════════════════════════════════ --}}
<section class="hero">
    <div class="hero__inner">
        <div class="hero__content">
            <div class="hero__badge-pill">
                <span>✨</span> Empowering 1,000+ Developers & Mentors
            </div>

            <h1 class="hero__title">
                Accelerate Your Career<br>
                <span class="hero__title-highlight">With Elite Mentors</span>
            </h1>

            <p class="hero__subtitle">
                Book personalized 1-on-1 sessions and structured long-term mentorships with verified industry leaders from top tech companies.
            </p>

            <div class="hero__actions">
                <a href="{{ route('gigs.index') }}" class="btn btn--primary btn--lg" style="border-radius:12px; padding:0.9rem 1.75rem; font-weight:700; box-shadow:0 8px 25px rgba(79,70,229,0.35);">
                    🔍 Find a Mentor Now
                </a>
                <a href="{{ route('register') }}" class="btn btn--ghost btn--lg" style="border-radius:12px; padding:0.9rem 1.75rem; font-weight:600;">
                    Become a Mentor →
                </a>
            </div>

            <div class="hero__stats">
                <div class="hero__stat">
                    <span class="hero__stat-number">{{ $stats['mentors'] }}+</span>
                    <span class="hero__stat-label">Verified Mentors</span>
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

        {{-- Floating Glassmorphism Hero Cards --}}
        <div class="hero__visual">

            {{-- Hero illustration --}}
            <div class="hero__illustration">
                <img src="{{ asset('images/Hero.png') }}"
                     alt="A student studying with a mentor online"
                     class="hero__illustration-img">
                <div class="hero__illustration-glow"></div>
            </div>

            <div class="hero__card hero__card--1">
                <div class="hero__card-avatar">SJ</div>
                <div class="hero__card-info">
                    <p class="hero__card-name">Sarah Johnson</p>
                    <p class="hero__card-role">Principal Architect @Google</p>
                </div>
                <div class="hero__card-badge">⭐ 5.0</div>
            </div>

            <div class="hero__card hero__card--3">
                <div class="hero__card-avatar" style="background:linear-gradient(135deg,#ec4899,#8b5cf6)">EC</div>
                <div class="hero__card-info">
                    <p class="hero__card-name">Emily Chen</p>
                    <p class="hero__card-role">Design Director @Figma</p>
                </div>
                <div class="hero__card-badge">⭐ 5.0</div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════
     Category Quick Bar
     ══════════════════════════════════════════════════════ --}}
<div class="cat-bar">
    <div class="cat-bar__inner">
        <span style="font-size:0.8rem; font-weight:700; color:var(--color-gray-400); text-transform:uppercase; letter-spacing:0.05em; margin-right:0.5rem; flex-shrink:0;">Quick Explore:</span>
        <a href="{{ route('gigs.index', ['q' => 'Software']) }}" class="cat-bar__pill">💻 Software Engineering</a>
        <a href="{{ route('gigs.index', ['q' => 'System Design']) }}" class="cat-bar__pill">⚙️ System Design</a>
        <a href="{{ route('gigs.index', ['q' => 'AI']) }}" class="cat-bar__pill">🤖 AI & Machine Learning</a>
        <a href="{{ route('gigs.index', ['q' => 'Design']) }}" class="cat-bar__pill">🎨 UI/UX & Product Design</a>
        <a href="{{ route('gigs.index', ['q' => 'Career']) }}" class="cat-bar__pill">🚀 Career Coaching</a>
        <a href="{{ route('gigs.index', ['q' => 'Cloud']) }}" class="cat-bar__pill">☁️ DevOps & Cloud</a>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     Trust Logos
     ══════════════════════════════════════════════════════ --}}
<section class="trust">
    <div class="trust__inner">
        <p class="trust__text">Mentors trusted by professionals from top tech companies worldwide</p>
        <div class="trust__logos">
            <span class="trust__logo trust__logo--google">Google</span>
            <span class="trust__logo trust__logo--stripe">Stripe</span>
            <span class="trust__logo trust__logo--airbnb">Airbnb</span>
            <span class="trust__logo trust__logo--spotify">Spotify</span>
            <span class="trust__logo trust__logo--shopify">Shopify</span>
            <span class="trust__logo trust__logo--notion">Notion</span>
            <span class="trust__logo trust__logo--meta">Meta</span>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════
     Featured Sessions
     ══════════════════════════════════════════════════════ --}}
<section class="featured">
    <div class="featured__inner">
        <div class="featured__header">
            <div>
                <h2 class="featured__title">Featured Mentorship Sessions</h2>
                <p style="font-size:0.9rem; color:var(--color-gray-500); margin-top:0.25rem;">Hand-picked sessions with top-rated mentors ready to help you grow.</p>
            </div>
            <a href="{{ route('gigs.index') }}" class="featured__link" style="font-weight:700;">
                View All Sessions
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        </div>

        <div class="gig-grid">
            @forelse ($featuredGigs as $gig)
                <article class="gig-card" style="border-radius:16px; transition:all 0.2s ease;">
                    <a href="{{ route('gigs.show', $gig->slug) }}" class="gig-card__cover">
                        <img src="{{ $gig->cover_image_url }}" alt="{{ $gig->title }}" class="gig-card__cover-img" loading="lazy">
                    </a>
                    <div class="gig-card__header">
                        <div class="gig-card__mentor">
                            <div class="gig-card__avatar" style="border-radius:50%; width:44px; height:44px; background:linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff; font-weight:700; display:flex; align-items:center; justify-content:center;">
                                {{ strtoupper(substr($gig->mentor->first_name, 0, 1) . substr($gig->mentor->last_name, 0, 1)) }}
                            </div>
                            <div class="gig-card__mentor-info">
                                <p class="gig-card__mentor-name" style="font-weight:700;">{{ $gig->mentor->full_name }}</p>
                                @if ($gig->mentor->mentorProfile)
                                    <p class="gig-card__mentor-headline">{{ $gig->mentor->mentorProfile->headline }}</p>
                                @endif
                            </div>
                        </div>
                        @if ($gig->average_rating > 0)
                            <div class="gig-card__rating" style="background:#fef3c7; color:#b45309; padding:0.2rem 0.6rem; border-radius:20px; font-weight:700;">
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1l2.12 4.3 4.74.7-3.43 3.34.81 4.72L8 11.77l-4.24 2.29.81-4.72L1.14 6l4.74-.7z"/></svg>
                                <span>{{ number_format($gig->average_rating, 1) }}</span>
                            </div>
                        @endif
                    </div>
                    <h3 class="gig-card__title" style="font-weight:700; font-size:1.1rem; margin:0.75rem 0;">{{ $gig->title }}</h3>
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
                        <span class="gig-card__price" style="font-weight:800; font-size:1.2rem;">{{ $gig->formatted_price }}<span class="gig-card__price-unit">/session</span></span>
                        <a href="{{ route('gigs.show', $gig->slug) }}" class="btn btn--primary btn--sm" style="border-radius:8px; padding:0.5rem 1rem; font-weight:700;">
                            Book Session
                        </a>
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

{{-- ══════════════════════════════════════════════════════
     Long-Term Mentorship LMS Spotlight Banner
     ══════════════════════════════════════════════════════ --}}
<section class="lms-spotlight">
    <div class="lms-spotlight__inner">
        <div>
            <div style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.35rem 0.85rem; border-radius:20px; background:rgba(168,85,247,0.2); color:#c084fc; font-size:0.8rem; font-weight:700; margin-bottom:1rem;">
                🎓 Long-Term LMS Learning
            </div>
            <h2 class="lms-spotlight__title">Go Beyond One-Off Sessions with Structured Course Modules</h2>
            <p class="lms-spotlight__desc">
                Partner with your favorite mentor for ongoing multi-month mentorships. Access tailored learning paths, weekly lesson milestones, progress analytics, and direct course feedback.
            </p>
            <div class="lms-spotlight__features">
                <div class="lms-spotlight__feat">
                    <div class="lms-spotlight__feat-icon">✓</div>
                    <span>Tailored learning modules custom-built by your mentor</span>
                </div>
                <div class="lms-spotlight__feat">
                    <div class="lms-spotlight__feat-icon">✓</div>
                    <span>Flexible subscription durations (1, 3, 6, or 12 months with easy renewals)</span>
                </div>
                <div class="lms-spotlight__feat">
                    <div class="lms-spotlight__feat-icon">✓</div>
                    <span>Track progress with analytics and completion badges</span>
                </div>
            </div>
            <a href="{{ route('gigs.index') }}" class="btn btn--primary" style="background:#a855f7; border:none; border-radius:12px; padding:0.85rem 1.5rem; font-weight:700;">
                Explore Mentors & Long-Term Programs →
            </a>
        </div>
        <div style="background:rgba(255,255,255,0.05); border:1.5px solid rgba(255,255,255,0.1); border-radius:20px; padding:2rem; backdrop-filter:blur(10px);">
            <div style="font-size:2.5rem; margin-bottom:1rem;">📚</div>
            <h3 style="font-size:1.2rem; font-weight:700; color:#fff; margin-bottom:0.5rem;">Dedicated Learning Portal</h3>
            <p style="font-size:0.875rem; color:#94a3b8; line-height:1.6; margin-bottom:1.5rem;">
                When a mentor accepts your request, your personal LMS dashboard unlocks with interactive lessons, downloadable resources, and progress tracking.
            </p>
            <div style="padding:0.75rem 1rem; background:rgba(255,255,255,0.08); border-radius:10px; font-size:0.85rem; color:#e2e8f0; font-weight:600; display:flex; align-items:center; justify-content:space-between;">
                <span>🟢 1 Active Mentorship</span>
                <span style="color:#c084fc;">30 Days Remaining</span>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════
     How It Works
     ══════════════════════════════════════════════════════ --}}
<section class="steps">
    <div class="steps__inner">
        <h2 class="steps__title" style="text-align:center; font-size:2rem; font-weight:800; margin-bottom:2.5rem;">How MentorConnect Works</h2>
        <div class="steps__grid">
            <div class="step" style="border-radius:16px; padding:2rem; transition:transform 0.2s ease;">
                <div class="step__number" style="background:linear-gradient(135deg,#4f46e5,#7c3aed); -webkit-background-clip:text; -webkit-text-fill-color:transparent; font-size:2.5rem; font-weight:800;">01</div>
                <h3 class="step__title" style="font-size:1.15rem; font-weight:700; margin:0.75rem 0 0.5rem;">Find Your Ideal Mentor</h3>
                <p class="step__desc">Browse by skill, experience level, price, or rating. Use skill-based personalized recommendations to find your match.</p>
            </div>
            <div class="step" style="border-radius:16px; padding:2rem; transition:transform 0.2s ease;">
                <div class="step__number" style="background:linear-gradient(135deg,#7c3aed,#ec4899); -webkit-background-clip:text; -webkit-text-fill-color:transparent; font-size:2.5rem; font-weight:800;">02</div>
                <h3 class="step__title" style="font-size:1.15rem; font-weight:700; margin:0.75rem 0 0.5rem;">Book a 1-on-1 Session</h3>
                <p class="step__desc">Select a gig, request a date, and add your note. Get quick response confirmations with video call meeting links.</p>
            </div>
            <div class="step" style="border-radius:16px; padding:2rem; transition:transform 0.2s ease;">
                <div class="step__number" style="background:linear-gradient(135deg,#ec4899,#f59e0b); -webkit-background-clip:text; -webkit-text-fill-color:transparent; font-size:2.5rem; font-weight:800;">03</div>
                <h3 class="step__title" style="font-size:1.15rem; font-weight:700; margin:0.75rem 0 0.5rem;">Learn, Review & Extend</h3>
                <p class="step__desc">Connect via video call, receive feedback, leave interactive 5-star reviews, and upgrade to long-term mentorship modules.</p>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════
     Ready to Start CTA Banner
     ══════════════════════════════════════════════════════ --}}
<section class="home-cta">
    <div style="max-width:700px; margin:0 auto;">
        <h2 class="home-cta__title">Ready to Elevate Your Tech Career?</h2>
        <p class="home-cta__sub">Join thousands of developers, designers, and tech professionals learning directly from industry experts.</p>
        <div class="home-cta__actions">
            <a href="{{ route('gigs.index') }}" class="btn btn--primary btn--lg" style="background:#ffffff; color:#4f46e5; border:none; border-radius:12px; font-weight:800; padding:0.9rem 1.75rem;">
                🔍 Find Your Mentor
            </a>
            <a href="{{ route('register') }}" class="btn btn--ghost btn--lg" style="border-color:rgba(255,255,255,0.4); color:#ffffff; border-radius:12px; font-weight:700; padding:0.9rem 1.75rem;">
                Become a Mentor →
            </a>
        </div>
    </div>
</section>
@endsection
