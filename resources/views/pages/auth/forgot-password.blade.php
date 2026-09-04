@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
<main id="auth-page" data-auth-mode="forgot" class="min-h-screen flex items-center justify-center bg-background text-on-background antialiased selection:bg-primary-container selection:text-on-primary-container px-margin-mobile auth-page-bg overflow-x-hidden">
    <div class="w-full max-w-[400px] py-16">
        {{-- Branding --}}
        <a href="{{ url('/') }}" class="font-display text-article-title-sm font-semibold text-primary inline-flex items-center gap-2 hover:opacity-80 transition-opacity">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1; font-size: 20px;">newspaper</span>
            LaraNews
        </a>

        {{-- Heading --}}
        <div class="mt-12 mb-10">
            <h1 class="font-page-title-mobile md:font-page-title text-page-title-mobile md:text-page-title text-on-background mb-3">Forgot your password?</h1>
            <p class="font-body-md text-body-md text-on-surface-variant">Enter your email and we'll send you a link to reset it.</p>
        </div>

        {{-- Form --}}
        <form id="forgot-password-form" class="space-y-stack-lg">
            <div>
                <label for="email" class="font-metadata text-metadata uppercase tracking-wider text-on-surface-variant block mb-1">Email address</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    autocomplete="email"
                    required
                    placeholder="name@example.com"
                    class="w-full bg-transparent border-0 border-b border-outline-variant focus:ring-0 px-0 py-2.5 font-body-lg text-body-lg text-on-surface placeholder:text-outline transition-colors focus:border-primary"
                />
            </div>

            <p id="auth-message" style="display: none;" class="text-sm"></p>

            <div class="pt-stack-md">
                <button
                    type="submit"
                    class="w-full bg-primary-container text-on-primary-container font-label text-label h-14 px-6 hover:bg-inverse-primary transition-colors duration-300 flex justify-center items-center gap-2 group"
                >
                    Send reset link
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </button>
            </div>
        </form>

        {{-- Back to sign in --}}
        <div class="mt-stack-lg text-center">
            <p class="font-metadata text-metadata text-on-surface-variant">
                Remember your password?
                <a href="{{ url('/login') }}" class="text-primary hover:text-primary underline underline-offset-4 transition-colors ml-1">Sign in</a>
            </p>
        </div>
    </div>
</main>
@endsection
