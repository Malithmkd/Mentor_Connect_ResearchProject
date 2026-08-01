@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
    <h1 class="auth-layout__title">Welcome Back</h1>
    <p class="auth-layout__subtitle">Sign in to access your dashboard</p>

    {{-- ── Pending-approval notification ── --}}
    @if (session('approval_pending'))
    <div class="login-notice login-notice--pending" role="alert">
        <div class="login-notice__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
                <path d="M12 7v6l3.5 2" stroke="currentColor" stroke-width="1.8"
                      stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="login-notice__body">
            <p class="login-notice__title">Account Approval Pending</p>
            <p class="login-notice__text">
                Your registration is under review. An admin will approve or
                reject your account shortly. Please check back later.
            </p>
        </div>
    </div>
    @endif

    {{-- ── Rejected-account notification ── --}}
    @if (session('approval_rejected'))
    <div class="login-notice login-notice--rejected" role="alert">
        <div class="login-notice__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
                <path d="M15 9l-6 6M9 9l6 6" stroke="currentColor" stroke-width="1.8"
                      stroke-linecap="round"/>
            </svg>
        </div>
        <div class="login-notice__body">
            <p class="login-notice__title">Registration Declined</p>
            @if (session('rejection_reason'))
                <p class="login-notice__text">
                    <strong>Reason:</strong> {{ session('rejection_reason') }}
                </p>
            @else
                <p class="login-notice__text">
                    Your account was not approved. Please contact the platform
                    administrator for more information.
                </p>
            @endif
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="form" novalidate>
        @csrf

        <div class="form__group">
            <label for="email" class="form__label">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                class="form__input @error('email') form__input--error @enderror"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="email"
                placeholder="you@example.com"
            >
            @error('email')
                <span class="form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form__group">
            <label for="password" class="form__label">Password</label>
            <div class="form__password-wrapper">
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form__input @error('password') form__input--error @enderror"
                    required
                    autocomplete="current-password"
                    placeholder="Enter your password"
                >
                <button type="button" class="form__password-toggle" onclick="togglePassword('password', this)" aria-label="Toggle password visibility">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>
                </button>
            </div>
            @error('password')
                <span class="form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form__group form__group--row">
            <label class="form__checkbox-label">
                <input type="checkbox" name="remember" class="form__checkbox" {{ old('remember') ? 'checked' : '' }}>
                <span class="form__checkbox-check"></span>
                <span class="form__checkbox-text">Remember me</span>
            </label>
            <a href="{{ route('password.request') }}" class="form__forgot">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn--primary btn--block">Sign In</button>
    </form>

    <p class="auth-layout__switch">
        Don't have an account? <a href="{{ route('register') }}">Get Started</a>
    </p>
@endsection

@push('styles')
<style>
/* ── Login notice banners ── */
.login-notice {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    border-radius: 10px;
    padding: 14px 16px;
    margin-bottom: 20px;
    font-size: 0.875rem;
    line-height: 1.5;
    animation: notice-in .3s ease both;
}

@keyframes notice-in {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Pending — amber */
.login-notice--pending {
    background: rgba(245, 158, 11, .1);
    border: 1px solid rgba(245, 158, 11, .35);
    border-left: 4px solid #f59e0b;
    color: #92400e;
}
.login-notice--pending .login-notice__icon {
    color: #f59e0b;
    flex-shrink: 0;
    margin-top: 1px;
    animation: icon-pulse 2s ease-in-out infinite;
}
@keyframes icon-pulse {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.5; }
}

/* Rejected — red */
.login-notice--rejected {
    background: rgba(239, 68, 68, .08);
    border: 1px solid rgba(239, 68, 68, .3);
    border-left: 4px solid #ef4444;
    color: #7f1d1d;
}
.login-notice--rejected .login-notice__icon {
    color: #ef4444;
    flex-shrink: 0;
    margin-top: 1px;
}

.login-notice__title {
    font-weight: 700;
    margin: 0 0 3px;
}
.login-notice__text {
    margin: 0;
    opacity: .9;
}
</style>
@endpush
@push('scripts')
@if (session('disabled_account'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        icon: 'error',
        title: 'Account Disabled',
        text: 'Your account has been disabled. Please contact the administrator.',
        confirmButtonText: 'OK',
        confirmButtonColor: '#4f46e5',
        customClass: { popup: 'swal-rounded' }
    });
});
</script>
@endif
@endpush
