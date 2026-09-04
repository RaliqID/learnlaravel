@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
<main id="auth-page" data-auth-mode="login" class="min-h-screen grid grid-cols-1 lg:grid-cols-2 bg-background text-on-background antialiased selection:bg-primary-container selection:text-on-primary-container overflow-x-hidden">
    {{-- Left Side: Editorial Visual (lg+) --}}
    <aside class="auth-backdrop relative hidden lg:flex flex-col justify-end overflow-hidden border-r border-outline-variant p-margin-desktop" aria-hidden="true">
        <div class="auth-texture absolute inset-0 pointer-events-none"></div>
        <div class="relative z-10">
            <span class="block h-px w-10 bg-primary mb-stack-lg"></span>
            <blockquote class="font-display text-article-title-lg text-on-background leading-tight max-w-md">"The signal, not the noise."</blockquote>
            <figcaption class="mt-stack-md font-metadata text-metadata uppercase tracking-widest text-on-surface-variant">LaraNews Editorial</figcaption>
        </div>
    </aside>

    {{-- Right Side: Minimal Login Form --}}
    <section class="auth-page-bg flex items-center justify-center px-margin-mobile md:px-margin-tablet lg:px-margin-desktop py-16 lg:py-24">
        <div class="w-full max-w-[400px]">
            {{-- Branding --}}
            <a href="{{ url('/') }}" class="font-display text-article-title-sm font-semibold text-primary inline-flex items-center gap-2 hover:opacity-80 transition-opacity">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1; font-size: 20px;">newspaper</span>
                LaraNews
            </a>

            {{-- Page Title --}}
            <div class="mt-12 mb-10">
                <h1 class="font-page-title-mobile md:font-page-title text-page-title-mobile md:text-page-title text-on-background mb-3">Welcome back</h1>
                <p class="font-body-md text-body-md text-on-surface-variant">Sign in to continue to your editorial dashboard.</p>
            </div>

            {{-- Login Form --}}
            <form id="login-form" class="space-y-stack-lg">
                {{-- Email Input --}}
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

                {{-- Password Input --}}
                <div>
                    <div class="flex justify-between items-end mb-1">
                        <label for="password" class="font-metadata text-metadata uppercase tracking-wider text-on-surface-variant block">Password</label>
                        <a href="{{ url('/forgot-password') }}" class="font-metadata text-metadata text-primary hover:text-primary underline underline-offset-4 transition-colors">Forgot password?</a>
                    </div>
                    <div class="flex items-center border-b border-outline-variant focus-within:border-primary transition-colors">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                            placeholder="••••••••"
                            class="w-full bg-transparent border-0 p-0 py-2.5 font-body-lg text-body-lg text-on-surface placeholder:text-outline focus:ring-0"
                        />
                        <button
                            type="button"
                            onclick="togglePasswordVisibility()"
                            aria-label="Toggle password visibility"
                            class="text-on-surface-variant hover:text-on-surface transition-colors p-2 -mr-2"
                        >
                            <span class="material-symbols-outlined text-[20px]" id="visibility-icon">visibility_off</span>
                        </button>
                    </div>
                </div>

                {{-- Message Display --}}
                <p id="auth-message" style="display: none;" class="text-sm"></p>

                {{-- Sign In Button --}}
                <div class="pt-stack-md">
                    <button
                        type="submit"
                        class="w-full bg-primary-container text-on-primary-container font-label text-label h-14 px-6 hover:bg-inverse-primary transition-colors duration-300 flex justify-center items-center gap-2 group"
                    >
                        Sign in
                        <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                    </button>
                </div>
            </form>

            {{-- Divider --}}
            <div class="my-stack-lg flex items-center gap-4" aria-hidden="true">
                <span class="h-px flex-1 bg-outline-variant"></span>
                <span class="font-metadata text-metadata text-on-surface-variant">or</span>
                <span class="h-px flex-1 bg-outline-variant"></span>
            </div>

            {{-- Google Login --}}
            <div>
                <button
                    type="button"
                    data-google-login
                    class="w-full h-14 border border-outline-variant text-on-surface font-label text-label flex justify-center items-center gap-2 hover:bg-surface-container transition-colors duration-300"
                >
                    <span class="material-symbols-outlined text-[18px]">google</span>
                    Continue with Google
                </button>
            </div>

            {{-- Create Account Link --}}
            <div class="mt-stack-lg text-center">
                <p class="font-metadata text-metadata text-on-surface-variant">
                    Don't have an account?
                    <a href="{{ url('/register') }}" class="text-primary hover:text-primary underline underline-offset-4 transition-colors ml-1">Create account</a>
                </p>
            </div>
        </div>
    </section>
</main>

<script>
    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const visibilityIcon = document.getElementById('visibility-icon');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            visibilityIcon.textContent = 'visibility';
        } else {
            passwordInput.type = 'password';
            visibilityIcon.textContent = 'visibility_off';
        }
    }
</script>
@endsection
