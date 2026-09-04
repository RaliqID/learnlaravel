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
    <div class="reading-progress" id="progress-bar"></div>

    <main class="flex-grow">
        @yield('content')
    </main>

    <x-ui.toast />

    @stack('scripts')

    <script>
        window.LANews = window.LANews || {};
        window.LANews.apiBase = '{{ url('/api/v1') }}';
        window.LANews.csrf = '{{ csrf_token() }}';
        // Auth state: prefer Sanctum token from localStorage (SPA-style auth),
        // fall back to Blade session auth for SSR pages.
        (function () {
            var token = null;
            var storedUser = null;
            try {
                token = localStorage.getItem('laranews.authToken');
                storedUser = JSON.parse(localStorage.getItem('laranews.user') || 'null');
            } catch (e) { /* ignore */ }
            if (token && storedUser) {
                window.LANews.auth = true;
                window.LANews.authId = storedUser.id ?? null;
            } else {
                window.LANews.auth = {{ Auth::check() ? 'true' : 'false' }};
                window.LANews.authId = {{ Auth::id() ?? 'null' }};
            }
        })();

        // Scroll progress bar.
        window.addEventListener('scroll', () => {
            const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = (winScroll / height) * 100;
            document.getElementById('progress-bar').style.width = scrolled + '%';
        });
    </script>
</body>
</html>
