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
    {{-- SweetAlert2 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .auth-layout {
            background: linear-gradient(135deg, #f3e7e9 0%, #e3eeff 99%, #e3eeff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        [data-theme="dark"] .auth-layout {
            background: linear-gradient(135deg, #1f1c2c 0%, #928dab 100%);
        }
        .auth-layout__card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-radius: 16px;
        }
        [data-theme="dark"] .auth-layout__card {
            background: rgba(30, 30, 30, 0.85);
            border-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
        }
    </style>
    <script>
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                button.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/><path d="M2 2l20 20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
            } else {
                input.type = 'password';
                button.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>';
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
