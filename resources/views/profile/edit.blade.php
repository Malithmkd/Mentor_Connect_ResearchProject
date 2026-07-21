@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
<section class="dashboard">
    <div class="dashboard__inner">
        <header class="dashboard__header">
            <div>
                <h1 class="dashboard__title">Edit Profile</h1>
                <p class="dashboard__subtitle">Update your personal information and password.</p>
            </div>
        </header>

        {{-- ─── Profile Picture ─── --}}
        <div class="profile__card" style="max-width:700px;margin-bottom:2rem">
            <h2 style="font-size:1.1rem;font-weight:700;margin-bottom:1.25rem;color:var(--color-gray-900,#111)">Profile Picture</h2>

            @if (session('avatar_success'))
                <div class="alert alert--success" style="margin-bottom:1rem">{{ session('avatar_success') }}</div>
            @endif
            @error('avatar')
                <div class="alert alert--error" style="margin-bottom:1rem">{{ $message }}</div>
            @enderror

            <form method="POST" action="{{ route('profile.avatar') }}" enctype="multipart/form-data" id="avatar-form">
                @csrf
                <div style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap">
                    {{-- Current avatar --}}
                    <div style="position:relative;flex-shrink:0">
                        <img id="avatar-preview"
                             src="{{ $user->avatar_url }}"
                             alt="{{ $user->full_name }}"
                             style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid var(--primary,#4f46e5);display:block">
                        <label for="avatar-input"
                               title="Change picture"
                               style="position:absolute;bottom:0;right:0;width:28px;height:28px;border-radius:50%;background:var(--primary,#4f46e5);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 1px 4px rgba(0,0,0,.25)">
                            <svg width="13" height="13" viewBox="0 0 16 16" fill="none">
                                <path d="M11.5 2.5a2.12 2.12 0 013 3L5 15H1v-4L11.5 2.5z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                            </svg>
                        </label>
                    </div>

                    <div style="flex:1;min-width:200px">
                        <p style="font-weight:600;margin:0 0 .25rem;font-size:.9rem">{{ $user->full_name }}</p>
                        <p style="font-size:.8rem;color:var(--text-secondary,#666);margin:0 0 .75rem">
                            JPG, PNG, GIF or WebP &middot; max 2 MB
                        </p>
                        <input type="file" id="avatar-input" name="avatar"
                               accept="image/jpeg,image/png,image/gif,image/webp"
                               style="display:none"
                               onchange="previewAvatar(this)">
                        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                            <label for="avatar-input" class="btn btn--primary btn--sm" style="cursor:pointer">Choose Photo</label>
                            <button type="submit" id="avatar-submit" class="btn btn--ghost btn--sm" style="display:none">Upload</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="profile__card" style="max-width: 700px;">

            <form method="POST" action="{{ route('profile.update') }}" class="form">
                @csrf
                @method('PATCH')

                <div class="form__row">
                    <div class="form__group">
                        <label for="first_name" class="form__label">First Name</label>
                        <input type="text" id="first_name" name="first_name" class="form__input" value="{{ old('first_name', $user->first_name) }}" required>
                    </div>
                    <div class="form__group">
                        <label for="last_name" class="form__label">Last Name</label>
                        <input type="text" id="last_name" name="last_name" class="form__input" value="{{ old('last_name', $user->last_name) }}" required>
                    </div>
                </div>

                <div class="form__group">
                    <label for="email" class="form__label">Email</label>
                    <input type="email" id="email" name="email" class="form__input" value="{{ old('email', $user->email) }}" required>
                </div>

                <div class="form__group">
                    <label for="bio" class="form__label">Bio</label>
                    <textarea id="bio" name="bio" class="form__textarea" rows="3">{{ old('bio', $user->bio) }}</textarea>
                </div>

                <div class="form__row">
                    <div class="form__group">
                        <label for="location" class="form__label">Location</label>
                        <input type="text" id="location" name="location" class="form__input" value="{{ old('location', $user->location) }}" placeholder="City, Country">
                    </div>
                    <div class="form__group">
                        <label for="timezone" class="form__label">Timezone</label>
                        <input type="text" id="timezone" name="timezone" class="form__input" value="{{ old('timezone', $user->timezone) }}" placeholder="UTC">
                    </div>
                </div>

                @if ($user->isMentor() && $user->mentorProfile)
                    <hr style="border: 0; border-top: 1px solid var(--color-gray-200); margin: var(--space-6) 0;">
                    <h3 style="font-size: var(--text-lg); font-weight: 600; color: var(--color-gray-900); margin-bottom: var(--space-4);">Mentor Details</h3>

                    <div class="form__group">
                        <label for="headline" class="form__label">Headline</label>
                        <input type="text" id="headline" name="headline" class="form__input" value="{{ old('headline', $user->mentorProfile->headline) }}" placeholder="e.g., Senior Architect @ Google">
                    </div>

                    <div class="form__group">
                        <label for="about" class="form__label">About</label>
                        <textarea id="about" name="about" class="form__textarea" rows="4">{{ old('about', $user->mentorProfile->about) }}</textarea>
                    </div>

                    <div class="form__row">
                        <div class="form__group">
                            <label for="company" class="form__label">Company</label>
                            <input type="text" id="company" name="company" class="form__input" value="{{ old('company', $user->mentorProfile->company) }}">
                        </div>
                        <div class="form__group">
                            <label for="years_experience" class="form__label">Years Experience</label>
                            <input type="number" id="years_experience" name="years_experience" class="form__input" value="{{ old('years_experience', $user->mentorProfile->years_experience) }}" min="0" max="60">
                        </div>
                    </div>

                    <div class="form__group">
                        <label for="website" class="form__label">Website</label>
                        <input type="url" id="website" name="website" class="form__input" value="{{ old('website', $user->mentorProfile->website) }}" placeholder="https://">
                    </div>

                    <div class="form__row">
                        <div class="form__group">
                            <label for="linkedin_url" class="form__label">LinkedIn</label>
                            <input type="url" id="linkedin_url" name="linkedin_url" class="form__input" value="{{ old('linkedin_url', $user->mentorProfile->linkedin_url) }}" placeholder="https://linkedin.com/in/...">
                        </div>
                        <div class="form__group">
                            <label for="github_url" class="form__label">GitHub</label>
                            <input type="url" id="github_url" name="github_url" class="form__input" value="{{ old('github_url', $user->mentorProfile->github_url) }}" placeholder="https://github.com/...">
                        </div>
                    </div>
                @endif

                <div class="form__group" style="margin-top: var(--space-6);">
                    <button type="submit" class="btn btn--primary">Save Changes</button>
                    <a href="{{ route('profile.show') }}" class="btn btn--ghost">Cancel</a>
                </div>
            </form>
        </div>

        {{-- ─── Change Password ─── --}}
        <div class="profile__card" style="max-width: 700px; margin-top: 2rem;">
            <h2 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--color-gray-900, #111);">Change Password</h2>

            @if (session('password_success'))
                <div class="alert alert--success" style="margin-bottom:1rem">
                    {{ session('password_success') }}
                </div>
            @endif
            @if (session('password_error'))
                <div class="alert alert--error" style="margin-bottom:1rem">
                    {{ session('password_error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('profile.password') }}" class="form">
                @csrf
                @method('PATCH')

                <div class="form__group">
                    <label for="current_password" class="form__label">Current Password</label>
                    <input type="password" id="current_password" name="current_password"
                           class="form__input @error('current_password', 'password') form__input--error @enderror"
                           autocomplete="current-password" required>
                    @error('current_password', 'password')
                        <p class="form__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form__group">
                    <label for="password" class="form__label">New Password</label>
                    <input type="password" id="password" name="password"
                           class="form__input @error('password', 'password') form__input--error @enderror"
                           autocomplete="new-password" required>
                    @error('password', 'password')
                        <p class="form__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form__group">
                    <label for="password_confirmation" class="form__label">Confirm New Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="form__input" autocomplete="new-password" required>
                </div>

                <div class="form__group" style="margin-top: var(--space-6);">
                    <button type="submit" class="btn btn--primary">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</section>

@push('scripts')
<script>
function previewAvatar(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('avatar-preview').src = e.target.result;
        document.getElementById('avatar-submit').style.display = 'inline-flex';
    };
    reader.readAsDataURL(input.files[0]);
}
</script>
@endpush
@endsection
