<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LaraNews - Welcome</title>
    <meta name="description" content="Stay informed. Think deeper.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center bg-background text-on-background p-margin-mobile md:p-margin-desktop antialiased">
    <!-- Navigation suppressed per welcome/splash screen rules -->
    <main class="w-full max-w-3xl flex flex-col items-center text-center animate-[fadeIn_1s_ease-out]">
        <!-- Brand Cluster -->
        <div class="flex flex-col items-center mb-section-gap">
            <!-- Logo -->
            <div class="mb-stack-lg">
                <img src="{{ asset('images/brand/logo.png') }}" alt="" class="w-32 h-32 md:w-48 md:h-48 object-contain drop-shadow-2xl opacity-90 hover:opacity-100 transition-opacity duration-500">
            </div>
            <!-- Wordmark -->
            <h1 class="font-display font-semibold text-page-title-mobile md:text-[length:var(--text-display-desktop)] md:leading-[var(--text-display-desktop--line-height)] text-on-background mb-stack-sm tracking-tighter leading-tight md:leading-none">
                LaraNews
            </h1>
            <!-- Tagline -->
            <p class="font-article-title-sm text-article-title-sm text-on-surface-variant max-w-md">
                Stay informed. Think deeper.
            </p>
        </div>
        <!-- Action Cluster -->
        <div class="flex flex-col sm:flex-row gap-gutter w-full max-w-md mx-auto">
            <!-- Primary CTA -->
            <a href="{{ url('/') }}" class="flex-1 bg-primary-container text-on-primary-container font-label text-label h-14 flex items-center justify-center transition-colors duration-300 hover:bg-inverse-primary focus:outline-none focus:ring-2 focus:ring-primary-container focus:ring-offset-2 focus:ring-offset-background">
                Explore LaraNews
            </a>
            <!-- Secondary Action -->
            <a href="{{ url('/login') }}" class="flex-1 bg-transparent border border-on-background text-on-background font-label text-label h-14 flex items-center justify-center transition-colors duration-300 hover:bg-surface-container focus:outline-none focus:ring-2 focus:ring-on-background focus:ring-offset-2 focus:ring-offset-background">
                Sign in
            </a>
        </div>
    </main>
</body>
</html>
