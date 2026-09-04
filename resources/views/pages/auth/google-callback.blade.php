@extends('layouts.auth')

@section('title', 'Signing in...')

@section('content')
<main id="auth-page" data-auth-mode="google-callback" class="min-h-screen flex items-center justify-center bg-background text-on-background antialiased px-margin-mobile auth-page-bg overflow-x-hidden">
    <div class="w-full max-w-[400px] text-center py-16">
        <div class="mb-12">
            <a href="{{ url('/') }}" class="font-display text-article-title-sm font-semibold text-primary inline-flex items-center gap-2 hover:opacity-80 transition-opacity">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1; font-size: 20px;">newspaper</span>
                LaraNews
            </a>
        </div>

        <div class="flex flex-col items-center gap-4">
            <div class="size-10 border-2 border-outline-variant border-t-primary rounded-full animate-spin" aria-hidden="true"></div>
            <p id="google-callback-status" class="font-body-md text-body-md text-on-surface-variant">Completing sign-in...</p>
        </div>
    </div>
</main>
@endsection
