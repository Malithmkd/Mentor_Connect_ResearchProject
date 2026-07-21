@extends('layouts.auth')

@section('title', 'Verify Email')

@section('content')
    <div class="auth-verify">
        <div class="auth-verify__icon">
            <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                <rect x="4" y="10" width="40" height="28" rx="4" stroke="currentColor" stroke-width="2"/>
                <path d="M4 14l20 14L44 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="36" cy="36" r="10" fill="#f0fdf4" stroke="currentColor" stroke-width="2"/>
                <path d="M33 36l2 2 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        <h1 class="auth-layout__title">Verify Your Email</h1>
        <p class="auth-layout__subtitle">Thanks for signing up! Please check your inbox for a verification link.</p>

        <div class="auth-verify__info">
            <p>Didn't receive the email?</p>
            <form method="POST" action="{{ route('verification.send') }}" class="auth-verify__form">
                @csrf
                <button type="submit" class="btn btn--secondary">Resend Verification Email</button>
            </form>
        </div>
    </div>
@endsection
