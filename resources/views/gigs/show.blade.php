@extends('layouts.app')

@section('title', $gig->title)

@section('content')
<section class="gig-detail">
    <div class="gig-detail__inner">
        <nav class="breadcrumb">
            <a href="{{ route('home') }}" class="breadcrumb__item">Home</a>
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <a href="{{ route('gigs.index') }}" class="breadcrumb__item">Mentors</a>
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span class="breadcrumb__item breadcrumb__item--current">{{ Str::limit($gig->title, 40) }}</span>
        </nav>

        <div class="gig-detail__grid">
            <div class="gig-detail__main">
                <h1 class="gig-detail__title">{{ $gig->title }}</h1>

                <div class="gig-detail__mentor">
                    <div class="gig-detail__avatar">{{ strtoupper(substr($gig->mentor->first_name, 0, 1) . substr($gig->mentor->last_name, 0, 1)) }}</div>
                    <div class="gig-detail__mentor-info">
                        <p class="gig-detail__mentor-name">{{ $gig->mentor->full_name }}</p>
                        @if ($gig->mentor->mentorProfile)
                            <p class="gig-detail__mentor-headline">{{ $gig->mentor->mentorProfile->headline }}</p>
                        @endif
                        <div class="gig-detail__mentor-meta">
                            @if ($gig->mentor->mentorProfile)
                                <span class="gig-detail__badge">
                                    <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1l2.12 4.3 4.74.7-3.43 3.34.81 4.72L8 11.77l-4.24 2.29.81-4.72L1.14 6l4.74-.7z"/></svg>
                                    {{ number_format($gig->mentor->mentorProfile->average_rating, 1) }} ({{ $gig->mentor->mentorProfile->total_reviews }})
                                </span>
                                <span class="gig-detail__badge">{{ $gig->mentor->mentorProfile->total_sessions }} sessions</span>
                            @endif
                            <a href="{{ route('users.profile', $gig->mentor) }}" class="gig-detail__badge" style="color:var(--color-primary); font-weight:600">
                                View Full Profile &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                <div class="gig-detail__section">
                    <h2 class="gig-detail__section-title">About This Session</h2>
                    <div class="gig-detail__text">{!! nl2br(e($gig->description)) !!}</div>
                </div>

                @if ($gig->what_to_expect)
                    <div class="gig-detail__section">
                        <h2 class="gig-detail__section-title">What to Expect</h2>
                        <div class="gig-detail__text">{!! nl2br(e($gig->what_to_expect)) !!}</div>
                    </div>
                @endif

                @if ($gig->prerequisites)
                    <div class="gig-detail__section">
                        <h2 class="gig-detail__section-title">Prerequisites</h2>
                        <div class="gig-detail__text">{!! nl2br(e($gig->prerequisites)) !!}</div>
                    </div>
                @endif

                <div class="gig-detail__meta-grid">
                    <div class="gig-detail__meta-item">
                        <span class="gig-detail__meta-label">Duration</span>
                        <span class="gig-detail__meta-value">{{ $gig->formatted_duration }}</span>
                    </div>
                    <div class="gig-detail__meta-item">
                        <span class="gig-detail__meta-label">Format</span>
                        <span class="gig-detail__meta-value">{{ $gig->delivery_format }}</span>
                    </div>
                    <div class="gig-detail__meta-item">
                        <span class="gig-detail__meta-label">Level</span>
                        <span class="gig-detail__meta-value">{{ ucfirst($gig->experience_level) }}</span>
                    </div>
                    <div class="gig-detail__meta-item">
                        <span class="gig-detail__meta-label">Skills</span>
                        <div class="gig-detail__meta-tags">
                            @foreach ($gig->skills as $skill)
                                <span class="gig-detail__meta-tag">{{ $skill->name }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>

                @if ($reviews->count() > 0)
                    <div class="gig-detail__section">
                        <h2 class="gig-detail__section-title">Reviews ({{ $reviews->count() }})</h2>
                        <div class="reviews-list">
                            @foreach ($reviews as $review)
                                <div class="review">
                                    <div class="review__header">
                                        <div class="review__user">
                                            <div class="review__avatar">{{ strtoupper(substr($review->freelancer->first_name, 0, 1)) }}</div>
                                            <div>
                                                <p class="review__name">{{ $review->freelancer->full_name }}</p>
                                                <div class="review__stars">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        <svg width="14" height="14" viewBox="0 0 16 16" fill="{{ $i <= $review->rating ? 'currentColor' : 'none' }}" class="review__star {{ $i <= $review->rating ? 'review__star--filled' : '' }}"><path d="M8 1l2.12 4.3 4.74.7-3.43 3.34.81 4.72L8 11.77l-4.24 2.29.81-4.72L1.14 6l4.74-.7z"/></svg>
                                                    @endfor
                                                </div>
                                            </div>
                                        </div>
                                        <span class="review__date">{{ $review->created_at->diffForHumans() }}</span>
                                    </div>
                                    @if ($review->comment)
                                        <p class="review__text">{{ $review->comment }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <aside class="gig-detail__sidebar">
                <div class="gig-detail__card">
                    <p class="gig-detail__price">{{ $gig->formatted_price }}<span class="gig-detail__price-unit">/session</span></p>
                    <div class="gig-detail__card-meta">
                        <div class="gig-detail__card-meta-item">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5"/><path d="M8 4v4l3 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                            {{ $gig->formatted_duration }}
                        </div>
                        <div class="gig-detail__card-meta-item">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M8 2a6 6 0 00-6 6v2H2v4h12v-4h-1V8a6 6 0 00-5-5.916V2h0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ $gig->delivery_format }}
                        </div>
                    </div>

                    @auth
                        @can('book-session')
                            <form method="POST" action="{{ route('bookings.store') }}" class="form">
                                @csrf
                                <input type="hidden" name="gig_id" value="{{ $gig->id }}">
                                <div class="form__group">
                                    <label class="form__label">Preferred Date (optional)</label>
                                    <input type="date" name="proposed_date" class="form__input"
                                           min="{{ now()->addDay()->format('Y-m-d') }}"
                                           value="{{ old('proposed_date') }}"
                                           placeholder="Select a preferred date">
                                    <span class="form__hint">Let the mentor know your preferred date. They may schedule a different time.</span>
                                </div>
                                <div class="form__group">
                                    <label class="form__label">Note for mentor (optional)</label>
                                    <textarea name="freelancer_note" class="form__textarea" rows="3" placeholder="What would you like to focus on?"></textarea>
                                </div>
                                <button type="submit" class="btn btn--primary btn--block btn--lg">Request Session</button>
                            </form>
                        @else
                            <div class="gig-detail__notice">
                                @role('mentor')
                                    <p>You cannot book sessions as a mentor.</p>
                                @endrole
                                @if (!auth()->user()->hasVerifiedEmail())
                                    <p>Please <a href="{{ route('verification.notice') }}">verify your email</a> to book sessions.</p>
                                @endif
                            </div>
                        @endcan
                    @else
                        <a href="{{ route('login') }}" class="btn btn--primary btn--block btn--lg">Sign In to Book</a>
                    @endauth
                </div>

                @if ($gig->mentor->mentorProfile)
                    <div class="gig-detail__card">
                        <h3 class="gig-detail__card-title">About the Mentor</h3>
                        <p class="gig-detail__card-text">{{ Str::limit($gig->mentor->mentorProfile->about, 300) }}</p>
                        @if ($gig->mentor->mentorProfile->years_experience > 0)
                            <p class="gig-detail__card-info"><strong>{{ $gig->mentor->mentorProfile->years_experience }}+</strong> years experience</p>
                        @endif
                        @if ($gig->mentor->location)
                            <p class="gig-detail__card-info">{{ $gig->mentor->location }}</p>
                        @endif
                    </div>
                @endif
            </aside>
        </div>
    </div>
</section>
@endsection
