@extends('layouts.app')

@section('title', 'Find Mentors')

@section('content')
<section class="page-header page-header--compact">
    <div class="page-header__inner">
        <h1 class="page-header__title">Find a Mentor</h1>
        <p class="page-header__subtitle">Browse {{ number_format(\App\Models\Gig::published()->count()) }} sessions from verified mentors</p>
    </div>
</section>

<section class="browse">
    <div class="browse__inner">

        {{-- Mobile filter toggle --}}
        @php $activeFilters = collect($filters)->filter(fn($v) => !empty($v))->count(); @endphp
        <button class="browse__filter-toggle" id="filterToggleBtn" onclick="toggleFilters()" aria-expanded="false" aria-controls="filterDrawer">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M2 4h12M4 8h8M6 12h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            <span id="filterToggleLabel">Show Filters</span>
            @if($activeFilters > 0)
                <span class="browse__filter-badge">{{ $activeFilters }}</span>
            @endif
        </button>

        {{-- Search & Filters --}}
        <aside class="browse__filters" id="filterDrawer">
            <form method="GET" action="{{ route('gigs.index') }}" class="filters" id="mainFilterForm">
                <div class="filters__group">
                    <label class="filters__label">Search</label>
                    <input type="text" name="q" class="filters__input" value="{{ $filters['q'] ?? '' }}" placeholder="Keywords...">
                </div>

                <div class="filters__group">
                    <label class="filters__label">Skills</label>
                    <div class="filters__skills">
                        @foreach ($skills as $skill)
                            <label class="filters__skill">
                                <input type="checkbox" name="skills[]" value="{{ $skill->id }}" {{ in_array($skill->id, $filters['skills'] ?? []) ? 'checked' : '' }}>
                                <span>{{ $skill->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="filters__group">
                    <label class="filters__label">Price Range</label>
                    <div class="filters__row">
                        <input type="number" name="min_price" class="filters__input filters__input--sm" value="{{ $filters['min_price'] ?? '' }}" placeholder="Min" min="0">
                        <span class="filters__sep">to</span>
                        <input type="number" name="max_price" class="filters__input filters__input--sm" value="{{ $filters['max_price'] ?? '' }}" placeholder="Max" min="0">
                    </div>
                </div>

                <div class="filters__group">
                    <label class="filters__label">Experience Level</label>
                    <select name="experience_level" class="filters__select">
                        <option value="">All Levels</option>
                        <option value="beginner" {{ ($filters['experience_level'] ?? '') === 'beginner' ? 'selected' : '' }}>Beginner</option>
                        <option value="intermediate" {{ ($filters['experience_level'] ?? '') === 'intermediate' ? 'selected' : '' }}>Intermediate</option>
                        <option value="advanced" {{ ($filters['experience_level'] ?? '') === 'advanced' ? 'selected' : '' }}>Advanced</option>
                        <option value="all_levels" {{ ($filters['experience_level'] ?? '') === 'all_levels' ? 'selected' : '' }}>All Levels</option>
                    </select>
                </div>

                <div class="filters__group">
                    <label class="filters__label">Min Rating</label>
                    <select name="min_rating" class="filters__select">
                        <option value="">Any</option>
                        <option value="4.5" {{ ($filters['min_rating'] ?? '') === '4.5' ? 'selected' : '' }}>4.5+</option>
                        <option value="4.0" {{ ($filters['min_rating'] ?? '') === '4.0' ? 'selected' : '' }}>4.0+</option>
                        <option value="3.0" {{ ($filters['min_rating'] ?? '') === '3.0' ? 'selected' : '' }}>3.0+</option>
                    </select>
                </div>

                <div class="filters__group">
                    <label class="filters__label">Sort By</label>
                    <select name="sort" class="filters__select">
                        <option value="newest" {{ ($filters['sort'] ?? '') === 'newest' ? 'selected' : '' }}>Newest</option>
                        <option value="rating" {{ ($filters['sort'] ?? '') === 'rating' ? 'selected' : '' }}>Highest Rated</option>
                        <option value="price_asc" {{ ($filters['sort'] ?? '') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                        <option value="price_desc" {{ ($filters['sort'] ?? '') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                        <option value="popularity" {{ ($filters['sort'] ?? '') === 'popularity' ? 'selected' : '' }}>Most Popular</option>
                    </select>
                </div>

                <button type="submit" class="btn btn--primary btn--block btn--sm">Apply Filters</button>
                <a href="{{ route('gigs.index') }}" class="btn btn--ghost btn--block btn--sm">Clear All</a>
            </form>
        </aside>

        {{-- Results --}}
        <div class="browse__results">
            <div class="browse__toolbar">
                <p class="browse__count">{{ $gigs->total() }} results</p>
                <select name="sort" class="filters__select filters__select--inline" form="mainFilterForm" onchange="this.form.submit()">
                    <option value="newest" {{ ($filters['sort'] ?? '') === 'newest' ? 'selected' : '' }}>Newest</option>
                    <option value="rating" {{ ($filters['sort'] ?? '') === 'rating' ? 'selected' : '' }}>Highest Rated</option>
                    <option value="price_asc" {{ ($filters['sort'] ?? '') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                    <option value="price_desc" {{ ($filters['sort'] ?? '') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                    <option value="popularity" {{ ($filters['sort'] ?? '') === 'popularity' ? 'selected' : '' }}>Most Popular</option>
                </select>
            </div>

            <div class="gig-grid gig-grid--compact">
                @forelse ($gigs as $gig)
                    <article class="gig-card">
                        <a href="{{ route('gigs.show', $gig->slug) }}" class="gig-card__cover">
                            <img src="{{ $gig->cover_image_url }}" alt="{{ $gig->title }}" class="gig-card__cover-img" loading="lazy">
                        </a>
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
                    <div class="empty empty--wide">
                        <p class="empty__text">No sessions match your filters. Try adjusting your search criteria.</p>
                    </div>
                @endforelse
            </div>

            {{ $gigs->links('partials.pagination') }}
        </div>
    </div>
</section>
@endsection

@push('styles')
<style>
/* ── Responsive Find Mentor ── */
.browse__filter-toggle {
    display: none;
    align-items: center;
    gap: .5rem;
    padding: .6rem 1.25rem;
    margin-bottom: 1rem;
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: .875rem;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
    width: 100%;
    justify-content: center;
    transition: background .15s, box-shadow .15s, border-color .15s;
}
.browse__filter-toggle:hover {
    background: #f8fafc;
    border-color: #c7d2fe;
    box-shadow: 0 1px 4px rgba(79,70,229,.08);
}
.browse__filter-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 5px;
    border-radius: 10px;
    background: #4f46e5;
    color: #fff;
    font-size: .7rem;
    font-weight: 700;
}

@media (max-width: 768px) {
    .browse__filter-toggle {
        display: flex;
    }
    .browse__inner {
        flex-direction: column !important;
        gap: 0 !important;
    }
    .browse__filters {
        width: 100% !important;
        max-width: 100% !important;
        min-width: unset !important;
        overflow: hidden;
        max-height: 0;
        opacity: 0;
        transition: max-height .35s ease, opacity .25s ease, margin .25s ease;
        margin-bottom: 0;
        position: static !important;
    }
    .browse__filters.is-open {
        max-height: 1600px;
        opacity: 1;
        margin-bottom: 1.25rem;
        padding: 1rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
    }
    .gig-grid {
        grid-template-columns: 1fr !important;
    }
    .filters__skills {
        grid-template-columns: repeat(2, 1fr) !important;
    }
    .browse__toolbar {
        flex-direction: column;
        align-items: flex-start;
        gap: .5rem;
    }
    .filters__select--inline {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .filters__skills {
        grid-template-columns: 1fr !important;
    }
    .gig-card__meta {
        flex-wrap: wrap;
    }
    .page-header__title {
        font-size: 1.5rem !important;
    }
}

/* Dark mode support */
[data-theme="dark"] .browse__filter-toggle {
    background: var(--dm-surface, #1e293b);
    border-color: var(--dm-border, #334155);
    color: var(--dm-text, #e2e8f0);
}
[data-theme="dark"] .browse__filters.is-open {
    background: var(--dm-surface, #1e293b);
    border-color: var(--dm-border, #334155);
}
</style>
@endpush

@push('scripts')
<script>
function toggleFilters() {
    const drawer = document.getElementById('filterDrawer');
    const btn    = document.getElementById('filterToggleBtn');
    const label  = document.getElementById('filterToggleLabel');
    const isOpen = drawer.classList.toggle('is-open');
    btn.setAttribute('aria-expanded', isOpen);
    label.textContent = isOpen ? 'Hide Filters' : 'Show Filters';
}

// Auto-open drawer if there are active filters on mobile
(function () {
    const activeFilters = {{ $activeFilters }};
    if (activeFilters > 0 && window.innerWidth <= 768) {
        toggleFilters();
    }
})();
</script>
@endpush
