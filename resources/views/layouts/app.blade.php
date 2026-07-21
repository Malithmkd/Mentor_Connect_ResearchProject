<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'MentorConnect') - MentorConnect</title>
    <meta name="description" content="Connect with professional mentors for 1-on-1 sessions.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    @stack('styles')
</head>
<body class="app">
    @include('partials.header')

    <main class="app__main">
        @include('partials.flash')
        @yield('content')
    </main>

    @include('partials.footer')

    <script src="{{ asset('js/main.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
