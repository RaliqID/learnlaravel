@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
<main id="auth-page" data-auth-mode="reset" class="min-h-screen flex items-center justify-center bg-background text-on-background antialiased selection:bg-primary-container selection:text-on-primary-container px-margin-mobile auth-page-bg overflow-x-hidden">
    <div class="w-full max-w-[400px] py-16">
        {{-- Branding --}}
        <a href="{{ url('/') }}" class="font-display text-article-title-sm font-semibold text-primary inline-flex items-center gap-2 hover:opacity-80 transition-opacity">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1; font-size: 20px;">newspaper</span>
            LaraNews
        </a>

        {{-- Heading --}}
        <div class="mt-12 mb-10">
            <h1 class="font-page-title-mobile md:font-page-title text-page-title-mobile md:text-page-title text-on-background mb-3">Reset your password</h1>
            <p class="font-body-md text-body-md text-on-surface-variant">Choose a new password for your account.</p>
        </div>

        {{-- Form --}}
        <form id="reset-password-form" class="space-y-stack-lg">
            <input type="hidden" name="token" id="token" value="{{ $token }}" />

            <div>
                <label for="email" class="font-metadata text-metadata uppercase tracking-wider text-on-surface-variant block mb-1">Email address</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    autocomplete="email"
                    required
                    value="{{ $email }}"
                    placeholder="name@example.com"
                    class="w-full bg-transparent border-0 border-b border-outline-variant focus:ring-0 px-0 py-2.5 font-body-lg text-body-lg text-on-surface transition-colors focus:border-primary placeholder:text-outline"
                />
            </div>

            <div>
                <label for="password" class="font-metadata text-metadata uppercase tracking-wider text-on-surface-variant block mb-1">New password</label>
                <div class="flex items-center border-b border-outline-variant focus-within:border-primary transition-colors">
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        required
                        placeholder="••••••••"
                        class="w-full bg-transparent border-0 p-0 py-2.5 font-body-lg text-body-lg text-on-surface placeholder:text-outline focus:ring-0"
                    />
                    <button
                        type="button"
                        onclick="togglePasswordVisibility('password', 'pw-icon')"
                        aria-label="Toggle password visibility"
                        class="text-on-surface-variant hover:text-on-surface transition-colors p-2 -mr-2"
                    >
                        <span class="material-symbols-outlined text-[20px]" id="pw-icon">visibility_off</span>
                    </button>
                </div>
            </div>

            <div>
                <label for="password_confirmation" class="font-metadata text-metadata uppercase tracking-wider text-on-surface-variant block mb-1">Confirm new password</label>
                <div class="flex items-center border-b border-outline-variant focus-within:border-primary transition-colors">
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        required
                        placeholder="••••••••"
                        class="w-full bg-transparent border-0 p-0 py-2.5 font-body-lg text-body-lg text-on-surface placeholder:text-outline focus:ring-0"
                    />
                    <button
                        type="button"
                        onclick="togglePasswordVisibility('password_confirmation', 'pw-confirm-icon')"
                        aria-label="Toggle password confirmation visibility"
                        class="text-on-surface-variant hover:text-on-surface transition-colors p-2 -mr-2"
                    >
                        <span class="material-symbols-outlined text-[20px]" id="pw-confirm-icon">visibility_off</span>
                    </button>
                </div>
            </div>

            <p id="auth-message" style="display: none;" class="text-sm"></p>

            <div class="pt-stack-md">
                <button
                    type="submit"
                    class="w-full bg-primary-container text-on-primary-container font-label text-label h-14 px-6 hover:bg-inverse-primary transition-colors duration-300 flex justify-center items-center gap-2 group"
                >
                    Reset password
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

<script>
    function togglePasswordVisibility(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.textContent = 'visibility';
        } else {
            input.type = 'password';
            icon.textContent = 'visibility_off';
        }
    }
</script>
@endsection
