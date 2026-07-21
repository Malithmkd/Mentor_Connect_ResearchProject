@extends('layouts.auth')

@section('title', 'Awaiting Admin Approval')

@section('content')
    <div class="auth-status">
        <div class="auth-status__icon auth-status__icon--pending">
            <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                <circle cx="24" cy="24" r="20" stroke="currentColor" stroke-width="2"/>
                <path d="M24 14v12l7 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        <h1 class="auth-layout__title">Application Submitted!</h1>
        <p class="auth-layout__subtitle">
            Your account is currently under review. An admin will approve or reject your registration shortly.
        </p>

        <div class="auth-status__steps">
            <div class="auth-status__step auth-status__step--done">
                <span class="auth-status__step-dot">✓</span>
                <span>Account created</span>
            </div>
            <div class="auth-status__step auth-status__step--active">
                <span class="auth-status__step-dot auth-status__step-dot--pulse"></span>
                <span>Admin review in progress</span>
            </div>
            <div class="auth-status__step">
                <span class="auth-status__step-dot auth-status__step-dot--empty"></span>
                <span>Access granted</span>
            </div>
        </div>

        <div class="auth-status__info">
            <p>Once approved, you will be able to sign in using your credentials.</p>
            <p style="margin-top:var(--space-2);font-size:0.8rem;color:var(--text-muted);">
                This usually takes less than 24 hours. If you haven't heard back after 48 hours,
                please contact support.
            </p>
        </div>

        <a href="{{ route('login') }}" class="btn btn--primary btn--block" style="margin-top:var(--space-5);">
            Back to Sign In
        </a>
    </div>
@endsection

<style>
.auth-status { text-align:center; }

.auth-status__icon {
    width: 80px; height: 80px;
    border-radius: 50%;
    display: flex; align-items:center; justify-content:center;
    margin: 0 auto var(--space-5);
}
.auth-status__icon--pending {
    background: rgba(245,158,11,.12);
    color: #f59e0b;
}
.auth-status__icon--rejected {
    background: rgba(239,68,68,.12);
    color: #ef4444;
}

.auth-status__steps {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    text-align: left;
    background: var(--bg-elevated, rgba(0,0,0,.03));
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: var(--space-4);
    margin: var(--space-5) 0;
}
.auth-status__step {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    font-size: 0.875rem;
    color: var(--text-muted);
}
.auth-status__step--done  { color: #10b981; }
.auth-status__step--active { color: var(--text-primary); font-weight: 600; }

.auth-status__step-dot {
    width: 22px; height: 22px; border-radius: 50%;
    display: flex; align-items:center; justify-content:center;
    flex-shrink: 0; font-size: 0.75rem;
    background: #10b981; color: #fff;
}
.auth-status__step-dot--pulse {
    background: #f59e0b;
    animation: pulse-dot 1.5s ease-in-out infinite;
}
.auth-status__step-dot--empty {
    background: var(--border, #e5e7eb);
}
@keyframes pulse-dot {
    0%, 100% { box-shadow: 0 0 0 0 rgba(245,158,11,.5); }
    50%       { box-shadow: 0 0 0 8px rgba(245,158,11,0); }
}

.auth-status__info {
    background: rgba(99,102,241,.06);
    border: 1px solid rgba(99,102,241,.15);
    border-radius: 8px;
    padding: var(--space-4);
    font-size: 0.875rem;
    text-align: left;
    color: var(--text-secondary);
}
</style>
