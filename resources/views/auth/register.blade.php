@extends('layouts.auth')

@section('title', 'Create Account')

@section('content')
    <h1 class="auth-layout__title">Get Started</h1>
    <p class="auth-layout__subtitle">Join MentorConnect and accelerate your career</p>

    <form method="POST" action="{{ route('register') }}" class="form" novalidate>
        @csrf

        <div class="form__row">
            <div class="form__group">
                <label for="first_name" class="form__label">First Name</label>
                <input type="text" id="first_name" name="first_name" class="form__input @error('first_name') form__input--error @enderror" value="{{ old('first_name') }}" required placeholder="John">
                @error('first_name')<span class="form__error">{{ $message }}</span>@enderror
            </div>
            <div class="form__group">
                <label for="last_name" class="form__label">Last Name</label>
                <input type="text" id="last_name" name="last_name" class="form__input @error('last_name') form__input--error @enderror" value="{{ old('last_name') }}" required placeholder="Doe">
                @error('last_name')<span class="form__error">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="form__group">
            <label for="email" class="form__label">Email Address</label>
            <input type="email" id="email" name="email" class="form__input @error('email') form__input--error @enderror" value="{{ old('email') }}" required autocomplete="email" placeholder="you@example.com">
            @error('email')<span class="form__error">{{ $message }}</span>@enderror
        </div>

        <div class="form__row">
            <div class="form__group">
                <label for="password" class="form__label">Password</label>
                <div class="form__password-wrapper">
                    <input type="password" id="password" name="password" class="form__input @error('password') form__input--error @enderror" required placeholder="Min 8 characters">
                    <button type="button" class="form__password-toggle" onclick="togglePassword('password', this)" aria-label="Toggle password visibility">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>
                    </button>
                </div>
                @error('password')<span class="form__error">{{ $message }}</span>@enderror
            </div>
            <div class="form__group">
                <label for="password_confirmation" class="form__label">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form__input" required placeholder="Confirm password">
            </div>
        </div>

        <div class="form__group">
            <label class="form__label">I am joining as</label>
            <div class="role-select">
                <label class="role-select__option @error('role') role-select__option--error @enderror">
                    <input type="radio" name="role" value="freelancer" class="role-select__input" {{ old('role', 'freelancer') === 'freelancer' ? 'checked' : '' }} required>
                    <span class="role-select__card">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" class="role-select__icon">
                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="role-select__title">Freelancer</span>
                        <span class="role-select__desc">I want to learn from mentors</span>
                    </span>
                </label>
                <label class="role-select__option">
                    <input type="radio" name="role" value="mentor" class="role-select__input" {{ old('role') === 'mentor' ? 'checked' : '' }} required>
                    <span class="role-select__card">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" class="role-select__icon">
                            <path d="M12 2L2 7l10 5 10-5-10-5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 17l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="role-select__title">Mentor</span>
                        <span class="role-select__desc">I want to share my expertise</span>
                    </span>
                </label>
            </div>
            @error('role')<span class="form__error">{{ $message }}</span>@enderror
        </div>

        <div class="form__group">
            <label class="form__checkbox-label">
                <input type="checkbox" name="terms" class="form__checkbox" {{ old('terms') ? 'checked' : '' }} required>
                <span class="form__checkbox-check"></span>
                <span class="form__checkbox-text">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></span>
            </label>
            @error('terms')<span class="form__error">{{ $message }}</span>@enderror
        </div>

        <button type="submit" class="btn btn--primary btn--block">Create Account</button>
    </form>

    <p class="auth-layout__switch">
        Already have an account? <a href="{{ route('login') }}">Sign In</a>
    </p>
@endsection
