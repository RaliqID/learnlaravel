<!DOCTYPE html>
<html class="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'LaraNews')</title>
    <meta name="description" content="@yield('meta_description', 'LaraNews - modern technology news and community platform.')">

    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-background text-on-background min-h-screen flex flex-col antialiased">
    <main class="flex-grow">
        @yield('content')
    </main>

    <x-ui.toast />

    @stack('scripts')

    <script>
        window.LANews = window.LANews || {};
        window.LANews.apiBase = '{{ url('/api/v1') }}';
        window.LANews.csrf = '{{ csrf_token() }}';
        window.LANews.auth = {{ Auth::check() ? 'true' : 'false' }};
        window.LANews.authId = {{ Auth::id() ?? 'null' }};
    </script>
</body>
</html>
