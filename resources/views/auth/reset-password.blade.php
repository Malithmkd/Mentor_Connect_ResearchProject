@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
    <h1 class="auth-layout__title">New Password</h1>
    <p class="auth-layout__subtitle">Create a new password for your account</p>

    <form method="POST" action="{{ route('password.store') }}" class="form" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        <div class="form__group">
            <label for="password" class="form__label">New Password</label>
            <div class="form__password-wrapper">
                <input type="password" id="password" name="password" class="form__input @error('password') form__input--error @enderror" required placeholder="Min 8 characters">
                <button type="button" class="form__password-toggle" onclick="togglePassword('password', this)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>
                </button>
            </div>
            @error('password')<span class="form__error">{{ $message }}</span>@enderror
        </div>

        <div class="form__group">
            <label for="password_confirmation" class="form__label">Confirm Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="form__input" required placeholder="Confirm new password">
        </div>

        <button type="submit" class="btn btn--primary btn--block">Reset Password</button>
    </form>
@endsection
