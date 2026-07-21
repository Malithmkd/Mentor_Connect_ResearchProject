@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
    <h1 class="auth-layout__title">Reset Password</h1>
    <p class="auth-layout__subtitle">Enter your email and we'll send you a reset link</p>

    <form method="POST" action="{{ route('password.email') }}" class="form" novalidate>
        @csrf

        <div class="form__group">
            <label for="email" class="form__label">Email Address</label>
            <input type="email" id="email" name="email" class="form__input @error('email') form__input--error @enderror" value="{{ old('email') }}" required autofocus placeholder="you@example.com">
            @error('email')<span class="form__error">{{ $message }}</span>@enderror
        </div>

        <button type="submit" class="btn btn--primary btn--block">Send Reset Link</button>
    </form>

    <p class="auth-layout__switch">
        <a href="{{ route('login') }}">Back to Sign In</a>
    </p>
@endsection
