<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') - MentorConnect</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    @stack('styles')
</head>
<body class="auth-layout">
    <div class="auth-layout__container">
        <div class="auth-layout__brand">
            <a href="{{ route('home') }}" class="auth-layout__logo">
                <span class="auth-layout__logo-icon">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                        <circle cx="16" cy="12" r="6" stroke="currentColor" stroke-width="2" fill="none"/>
                        <path d="M6 28c0-5.523 4.477-10 10-10s10 4.477 10 10" stroke="currentColor" stroke-width="2" fill="none"/>
                    </svg>
                </span>
                <span class="auth-layout__logo-text">MentorConnect</span>
            </a>
            <p class="auth-layout__tagline">Learn from the best. Grow your career.</p>
        </div>

        <div class="auth-layout__card">
            @include('partials.flash')
            @yield('content')
        </div>

        <p class="auth-layout__footer">
            &copy; {{ date('Y') }} MentorConnect. All rights reserved.
        </p>
    </div>
</body>
</html>
