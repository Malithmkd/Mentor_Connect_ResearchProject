@extends('layouts.auth')

@section('title', 'Registration Rejected')

@section('content')
    <div class="auth-status" style="text-align:center;">
        <div class="auth-status__icon auth-status__icon--rejected">
            <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                <circle cx="24" cy="24" r="20" stroke="currentColor" stroke-width="2"/>
                <path d="M16 16l16 16M32 16L16 32" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>

        <h1 class="auth-layout__title">Registration Declined</h1>
        <p class="auth-layout__subtitle">
            Unfortunately, your account registration was not approved by the admin.
        </p>

        @if (session('reason'))
            <div class="auth-status__reason">
                <p style="font-size:0.78rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:var(--space-2);">
                    Reason provided:
                </p>
                <p style="font-size:0.9rem;">{{ session('reason') }}</p>
            </div>
        @else
            <div class="auth-status__reason">
                <p style="font-size:0.875rem;color:var(--text-muted);">
                    No specific reason was provided. Please contact support for further information.
                </p>
            </div>
        @endif

        <div class="auth-status__info" style="margin-top:var(--space-4);">
            <p>If you believe this is an error, please contact the platform administrator.</p>
        </div>

        <a href="{{ route('login') }}" class="btn btn--ghost btn--block" style="margin-top:var(--space-5);">
            Back to Sign In
        </a>
    </div>
@endsection

<style>
.auth-status__icon {
    width:80px; height:80px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    margin: 0 auto var(--space-5);
}
.auth-status__icon--rejected { background:rgba(239,68,68,.12); color:#ef4444; }

.auth-status__reason {
    background: rgba(239,68,68,.06);
    border: 1px solid rgba(239,68,68,.25);
    border-left: 4px solid #ef4444;
    border-radius: 8px;
    padding: var(--space-4);
    text-align: left;
    margin-top: var(--space-4);
}
.auth-status__info {
    background: rgba(99,102,241,.06);
    border: 1px solid rgba(99,102,241,.15);
    border-radius: 8px;
    padding: var(--space-4);
    font-size: 0.875rem;
    color: var(--text-secondary);
}
</style>
