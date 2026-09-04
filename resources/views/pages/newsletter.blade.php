@extends('layouts.app')

@section('title', 'Newsletter')

@section('content')
    <main class="max-w-3xl mx-auto px-margin-mobile md:px-margin-desktop py-section-gap">
        <h1 class="font-page-title text-page-title text-on-surface mb-stack-lg">Newsletter</h1>
        <p class="font-body-lg text-on-surface-variant leading-relaxed">Subscribe to our newsletter for weekly tech insights.</p>
        <form class="mt-stack-lg flex gap-2 max-w-md">
            <input type="email" placeholder="Your email" class="flex-1 bg-transparent border-b border-outline-variant focus:border-primary px-0 py-2 outline-none text-on-surface">
            <button type="submit" class="px-6 py-2 bg-primary-container text-on-primary-container hover:bg-inverse-primary transition-colors font-label text-label">Subscribe</button>
        </form>
    </main>
@endsection