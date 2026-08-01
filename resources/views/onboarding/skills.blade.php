@extends('layouts.app')

@section('title', 'Choose Your Skills')

@section('content')
<section class="onboarding-page">
    <div class="onboarding-card">
        <div class="onboarding-card__hero">
            <div class="onboarding-card__emoji">🎯</div>
            <h1 class="onboarding-card__title">What are you looking to learn?</h1>
            <p class="onboarding-card__subtitle">
                Select the skills you're interested in. We'll use these to personalize your
                mentor recommendations on the <strong>Find Mentors</strong> page.
            </p>
            <p class="onboarding-card__skip-hint">You can update this anytime from your profile.</p>
        </div>

        <form method="POST" action="{{ route('onboarding.skills.store') }}" id="onboardingForm">
            @csrf

            <div class="onboarding-counter">
                <span id="selectedCount">0</span> skill<span id="selectedPlural">s</span> selected
            </div>

            @forelse($skills as $category => $categorySkills)
            <div class="onboarding-category">
                <h2 class="onboarding-category__title">
                    {{ $category ?: 'Other' }}
                </h2>
                <div class="onboarding-skills-grid">
                    @foreach($categorySkills as $skill)
                    <label class="onboarding-skill" for="skill_{{ $skill->id }}">
                        <input type="checkbox"
                               name="skills[]"
                               id="skill_{{ $skill->id }}"
                               value="{{ $skill->id }}"
                               class="onboarding-skill__check"
                               onchange="updateCount()">
                        <span class="onboarding-skill__content">
                            @if($skill->icon)
                                <span class="onboarding-skill__icon">{{ $skill->icon }}</span>
                            @endif
                            <span class="onboarding-skill__name">{{ $skill->name }}</span>
                        </span>
                        <svg class="onboarding-skill__tick" viewBox="0 0 16 16" fill="none">
                            <path d="M3 8l4 4 6-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </label>
                    @endforeach
                </div>
            </div>
            @empty
            <div style="text-align:center;padding:2rem;color:var(--color-gray-500)">
                No skills available yet. The admin will add them shortly.
            </div>
            @endforelse

            <div class="onboarding-actions">
                <button type="submit" class="btn btn--primary btn--block onboarding-submit" id="submitBtn">
                    Save My Preferences & Continue →
                </button>
                <form method="POST" action="{{ route('onboarding.skills.store') }}" style="display:none" id="skipForm">
                    @csrf
                </form>
                <button type="button" class="btn btn--ghost btn--block" onclick="document.getElementById('skipForm').submit()">
                    Skip for now
                </button>
            </div>
        </form>
    </div>
</section>
@endsection

@push('styles')
<style>
/* ── Onboarding Page ── */
.onboarding-page {
    min-height: 100vh;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 3rem 1rem 4rem;
    background: linear-gradient(135deg, #eef2ff 0%, #f5f3ff 50%, #ecfdf5 100%);
}
[data-theme="dark"] .onboarding-page {
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #052e16 100%);
}

.onboarding-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 25px 60px rgba(79,70,229,.12);
    max-width: 720px;
    width: 100%;
    overflow: hidden;
    animation: slideUp .4s ease both;
}
@keyframes slideUp { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: none; } }

[data-theme="dark"] .onboarding-card { background: #1e293b; box-shadow: 0 25px 60px rgba(0,0,0,.4); }

.onboarding-card__hero {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    padding: 2.5rem 2rem 2rem;
    text-align: center;
    color: #fff;
}
.onboarding-card__emoji {
    font-size: 3.5rem;
    margin-bottom: 1rem;
    animation: bounce .6s ease .3s both;
}
@keyframes bounce { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
.onboarding-card__title {
    font-size: 1.75rem;
    font-weight: 800;
    margin-bottom: .5rem;
    color: #fff !important;
}
.onboarding-card__subtitle {
    font-size: 1rem;
    opacity: .9;
    max-width: 480px;
    margin: 0 auto .75rem;
    line-height: 1.6;
}
.onboarding-card__skip-hint { font-size: .8rem; opacity: .7; }

.onboarding-counter {
    text-align: center;
    padding: 1rem;
    font-size: .9rem;
    font-weight: 600;
    color: #6b7280;
    border-bottom: 1px solid #f3f4f6;
}
[data-theme="dark"] .onboarding-counter { color: #94a3b8; border-color: #334155; }

.onboarding-category {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #f3f4f6;
}
[data-theme="dark"] .onboarding-category { border-color: #334155; }

.onboarding-category__title {
    font-size: .8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #6b7280;
    margin-bottom: 1rem !important;
}
[data-theme="dark"] .onboarding-category__title { color: #94a3b8; }

.onboarding-skills-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: .6rem;
}

.onboarding-skill {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
    padding: .6rem .875rem;
    border: 1.5px solid #e5e7eb;
    border-radius: 10px;
    cursor: pointer;
    background: #f9fafb;
    transition: border-color .15s, background .15s, transform .1s, box-shadow .15s;
    position: relative;
}
[data-theme="dark"] .onboarding-skill { border-color: #334155; background: #0f172a; }

.onboarding-skill:hover {
    border-color: #a5b4fc;
    background: #eef2ff;
    transform: translateY(-1px);
}
[data-theme="dark"] .onboarding-skill:hover { border-color: #6366f1; background: #1e1b4b; }

.onboarding-skill__check { display: none; }
.onboarding-skill__check:checked ~ .onboarding-skill__content { color: #4f46e5; }
.onboarding-skill:has(.onboarding-skill__check:checked) {
    border-color: #4f46e5;
    background: #eef2ff;
    box-shadow: 0 0 0 3px rgba(79,70,229,.12);
}
[data-theme="dark"] .onboarding-skill:has(.onboarding-skill__check:checked) {
    border-color: #818cf8;
    background: #1e1b4b;
    box-shadow: 0 0 0 3px rgba(129,140,248,.15);
}

.onboarding-skill__content {
    display: flex;
    align-items: center;
    gap: .4rem;
    font-size: .85rem;
    font-weight: 600;
    color: #374151;
    transition: color .15s;
}
[data-theme="dark"] .onboarding-skill__content { color: #e2e8f0; }

.onboarding-skill__icon { font-size: 1rem; }
.onboarding-skill__tick {
    width: 16px; height: 16px;
    color: #4f46e5;
    opacity: 0;
    flex-shrink: 0;
    transition: opacity .15s;
}
.onboarding-skill:has(.onboarding-skill__check:checked) .onboarding-skill__tick { opacity: 1; }

.onboarding-actions {
    padding: 1.5rem 2rem;
    display: flex;
    flex-direction: column;
    gap: .75rem;
}

@media (max-width: 600px) {
    .onboarding-card__hero { padding: 2rem 1.25rem 1.5rem; }
    .onboarding-card__title { font-size: 1.4rem; }
    .onboarding-category { padding: 1.25rem; }
    .onboarding-skills-grid { grid-template-columns: repeat(2, 1fr); }
    .onboarding-actions { padding: 1.25rem; }
}
</style>
@endpush

@push('scripts')
<script>
function updateCount() {
    var checked = document.querySelectorAll('.onboarding-skill__check:checked').length;
    document.getElementById('selectedCount').textContent = checked;
    document.getElementById('selectedPlural').textContent = checked === 1 ? '' : 's';
}
// Initialize count
updateCount();
</script>
@endpush
