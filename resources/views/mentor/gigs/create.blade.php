@extends('layouts.app')

@section('title', 'Create Gig')

@section('content')
<section class="dashboard">
    <div class="dashboard__inner">
        <header class="dashboard__header">
            <div>
                <h1 class="dashboard__title">Create New Gig</h1>
                <p class="dashboard__subtitle">Set up your mentoring session offering.</p>
            </div>
            <a href="{{ route('mentor.gigs.index') }}" class="btn btn--ghost btn--sm">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M10 12L6 8l4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Back to Gigs
            </a>
        </header>

        <div class="profile__card" style="max-width: 700px;">
            <form method="POST" action="{{ route('mentor.gigs.store') }}" class="form">
                @csrf

                <div class="form__group">
                    <label for="title" class="form__label">Session Title</label>
                    <input type="text" id="title" name="title" class="form__input @error('title') form__input--error @enderror" value="{{ old('title') }}" required placeholder="e.g., Laravel Architecture Deep Dive">
                    @error('title')<span class="form__error">{{ $message }}</span>@enderror
                </div>

                <div class="form__group">
                    <label for="description" class="form__label">Description</label>
                    <textarea id="description" name="description" class="form__textarea @error('description') form__input--error @enderror" rows="5" required placeholder="Describe what this session covers...">{{ old('description') }}</textarea>
                    @error('description')<span class="form__error">{{ $message }}</span>@enderror
                </div>

                <div class="form__row">
                    <div class="form__group">
                        <label for="what_to_expect" class="form__label">What to Expect</label>
                        <textarea id="what_to_expect" name="what_to_expect" class="form__textarea" rows="3" placeholder="What will the attendee learn?">{{ old('what_to_expect') }}</textarea>
                    </div>
                    <div class="form__group">
                        <label for="prerequisites" class="form__label">Prerequisites</label>
                        <textarea id="prerequisites" name="prerequisites" class="form__textarea" rows="3" placeholder="What should they know beforehand?">{{ old('prerequisites') }}</textarea>
                    </div>
                </div>

                <div class="form__row">
                    <div class="form__group">
                        <label for="delivery_format" class="form__label">Delivery Format</label>
                        <select id="delivery_format" name="delivery_format" class="form__select" required>
                            <option value="video_call" {{ old('delivery_format') === 'video_call' ? 'selected' : '' }}>Video Call</option>
                            <option value="voice_call" {{ old('delivery_format') === 'voice_call' ? 'selected' : '' }}>Voice Call</option>
                            <option value="chat" {{ old('delivery_format') === 'chat' ? 'selected' : '' }}>Chat</option>
                            <option value="async" {{ old('delivery_format') === 'async' ? 'selected' : '' }}>Async</option>
                        </select>
                    </div>
                    <div class="form__group">
                        <label for="experience_level" class="form__label">Experience Level</label>
                        <select id="experience_level" name="experience_level" class="form__select" required>
                            <option value="beginner" {{ old('experience_level') === 'beginner' ? 'selected' : '' }}>Beginner</option>
                            <option value="intermediate" {{ old('experience_level') === 'intermediate' ? 'selected' : '' }}>Intermediate</option>
                            <option value="advanced" {{ old('experience_level') === 'advanced' ? 'selected' : '' }}>Advanced</option>
                            <option value="all_levels" {{ old('experience_level') === 'all_levels' ? 'selected' : '' }}>All Levels</option>
                        </select>
                    </div>
                </div>

                <div class="form__row">
                    <div class="form__group">
                        <label for="duration_minutes" class="form__label">Duration (minutes)</label>
                        <input type="number" id="duration_minutes" name="duration_minutes" class="form__input @error('duration_minutes') form__input--error @enderror" value="{{ old('duration_minutes', 60) }}" required min="15" max="480">
                        @error('duration_minutes')<span class="form__error">{{ $message }}</span>@enderror
                    </div>
                    <div class="form__group">
                        <label for="price" class="form__label">Price ($)</label>
                        <input type="number" id="price" name="price" class="form__input @error('price') form__input--error @enderror" value="{{ old('price') }}" required min="0" step="0.01" placeholder="0.00">
                        @error('price')<span class="form__error">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="form__row">
                    <div class="form__group">
                        <label for="max_sessions_per_week" class="form__label">Max Sessions/Week</label>
                        <input type="number" id="max_sessions_per_week" name="max_sessions_per_week" class="form__input" value="{{ old('max_sessions_per_week', 10) }}" required min="1" max="50">
                    </div>
                    <div class="form__group">
                        <label for="booking_lead_time_hours" class="form__label">Lead Time (hours)</label>
                        <input type="number" id="booking_lead_time_hours" name="booking_lead_time_hours" class="form__input" value="{{ old('booking_lead_time_hours', 24) }}" required min="0" max="168">
                    </div>
                </div>

                <div class="form__group">
                    <label for="status" class="form__label">Status</label>
                    <select id="status" name="status" class="form__select" required>
                        <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Published</option>
                    </select>
                </div>

                <div class="form__group">
                    <label class="form__label">Skills</label>
                    <div class="filters__skills">
                        @foreach ($skills as $skill)
                            <label class="filters__skill">
                                <input type="checkbox" name="skills[]" value="{{ $skill->id }}" {{ in_array($skill->id, old('skills', [])) ? 'checked' : '' }}>
                                <span>{{ $skill->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('skills')<span class="form__error">{{ $message }}</span>@enderror
                </div>

                <div class="form__group" style="margin-top: var(--space-4);">
                    <button type="submit" class="btn btn--primary">Create Gig</button>
                    <a href="{{ route('mentor.gigs.index') }}" class="btn btn--ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
