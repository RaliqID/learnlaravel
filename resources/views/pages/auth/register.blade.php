@extends('layouts.auth')

@section('title', 'Create Account')

@section('content')
<main id="auth-page" data-auth-mode="register" class="min-h-screen grid grid-cols-1 lg:grid-cols-2 bg-background text-on-background antialiased selection:bg-primary-container selection:text-on-primary-container overflow-x-hidden">
    {{-- Left Side: Minimal Register Form --}}
    <section class="auth-page-bg flex items-center justify-center px-margin-mobile md:px-margin-tablet lg:px-margin-desktop py-16 lg:py-24">
        <div class="w-full max-w-[400px]">
            {{-- Branding --}}
            <a href="{{ url('/') }}" class="font-display text-article-title-sm font-semibold text-primary inline-flex items-center gap-2 hover:opacity-80 transition-opacity">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1; font-size: 20px;">newspaper</span>
                LaraNews
            </a>

            {{-- Page Title --}}
            <div class="mt-12 mb-10">
                <h1 class="font-page-title-mobile md:font-page-title text-page-title-mobile md:text-page-title text-on-surface mb-3">Join the conversation</h1>
                <p class="font-body-md text-body-md text-on-surface-variant">Create an account to access premium editorial content and participate in discussions.</p>
            </div>

            {{-- Register Form --}}
            <form id="register-form" class="space-y-stack-lg w-full">
                {{-- Display Name Input --}}
                <div>
                    <label for="display_name" class="font-metadata text-metadata uppercase tracking-wider text-on-surface-variant block mb-1">Display Name</label>
                    <input
                        id="display_name"
                        name="display_name"
                        type="text"
                        autocomplete="name"
                        required
                        class="w-full bg-transparent border-0 border-b border-outline-variant focus:ring-0 px-0 py-2.5 font-body-lg text-body-lg text-on-surface transition-colors focus:border-primary"
                    />
                </div>

                {{-- Username Input --}}
                <div>
                    <label for="username" class="font-metadata text-metadata uppercase tracking-wider text-on-surface-variant block mb-1">Username</label>
                    <input
                        id="username"
                        name="username"
                        type="text"
                        autocomplete="username"
                        required
                        placeholder="@"
                        class="w-full bg-transparent border-0 border-b border-outline-variant focus:ring-0 px-0 py-2.5 font-body-lg text-body-lg text-on-surface transition-colors focus:border-primary placeholder:text-outline"
                    />
                </div>

                {{-- Email Input --}}
                <div>
                    <label for="email" class="font-metadata text-metadata uppercase tracking-wider text-on-surface-variant block mb-1">Email Address</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        autocomplete="email"
                        required
                        class="w-full bg-transparent border-0 border-b border-outline-variant focus:ring-0 px-0 py-2.5 font-body-lg text-body-lg text-on-surface transition-colors focus:border-primary"
                    />
                </div>

                {{-- Password Input --}}
                <div>
                    <label for="password" class="font-metadata text-metadata uppercase tracking-wider text-on-surface-variant block mb-1">Password</label>
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
                            onclick="togglePasswordVisibility('password', 'password-icon')"
                            aria-label="Toggle password visibility"
                            class="text-on-surface-variant hover:text-on-surface transition-colors p-2 -mr-2"
                        >
                            <span class="material-symbols-outlined text-[20px]" id="password-icon">visibility_off</span>
                        </button>
                    </div>
                </div>

                {{-- Confirm Password Input --}}
                <div>
                    <label for="password_confirmation" class="font-metadata text-metadata uppercase tracking-wider text-on-surface-variant block mb-1">Confirm Password</label>
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
                            onclick="togglePasswordVisibility('password_confirmation', 'confirm-icon')"
                            aria-label="Toggle password confirmation visibility"
                            class="text-on-surface-variant hover:text-on-surface transition-colors p-2 -mr-2"
                        >
                            <span class="material-symbols-outlined text-[20px]" id="confirm-icon">visibility_off</span>
                        </button>
                    </div>
                </div>

                {{-- Message Display --}}
                <p id="auth-message" style="display: none;" class="text-sm"></p>

                {{-- Create Account Button --}}
                <div class="pt-stack-md">
                    <button
                        type="submit"
                        class="w-full bg-primary-container text-on-primary-container font-label text-label h-14 hover:bg-inverse-primary transition-colors duration-300 flex items-center justify-center gap-2 group"
                    >
                        <span>Create Account</span>
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

            {{-- Sign In Link --}}
            <div class="mt-stack-lg text-center">
                <p class="font-metadata text-metadata text-on-surface-variant">
                    Already have an account?
                    <a href="{{ url('/login') }}" class="text-primary hover:text-primary underline underline-offset-4 transition-colors font-medium ml-1">Sign in</a>
                </p>
            </div>

            {{-- Terms & Privacy --}}
            <div class="mt-stack-lg text-center">
                <p class="font-metadata text-metadata text-on-surface-variant opacity-70">
                    By creating an account, you agree to our <a href="#" class="underline hover:text-on-surface">Terms of Service</a> and <a href="#" class="underline hover:text-on-surface">Privacy Policy</a>.
                </p>
            </div>
        </div>
    </section>

    {{-- Right Side: Editorial Visual (lg+) --}}
    <aside class="auth-backdrop relative hidden lg:flex flex-col justify-end overflow-hidden border-l border-outline-variant p-margin-desktop" aria-hidden="true">
        <div class="auth-texture absolute inset-0 pointer-events-none"></div>
        <div class="relative z-10">
            <span class="block h-px w-10 bg-primary mb-stack-lg"></span>
            <blockquote class="font-display text-article-title-lg text-on-surface leading-tight max-w-md">"Information is the oxygen of the modern age."</blockquote>
            <figcaption class="mt-stack-md font-metadata text-metadata uppercase tracking-widest text-on-surface-variant">— The Editorial Board</figcaption>
        </div>
    </aside>
</main>

<script>
    function togglePasswordVisibility(inputId, iconId) {
        const passwordInput = document.getElementById(inputId);
        const visibilityIcon = document.getElementById(iconId);

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
