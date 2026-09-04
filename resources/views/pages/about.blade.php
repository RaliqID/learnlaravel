@extends('layouts.app')

@section('title', 'About LaraNews')

@section('content')
    <main class="max-w-3xl mx-auto px-margin-mobile md:px-margin-desktop py-section-gap">
        <h1 class="font-page-title text-page-title text-on-surface mb-stack-lg">About LaraNews</h1>
        <div class="space-y-stack-lg font-body-lg text-on-surface-variant leading-relaxed">
            <p>LaraNews is a technology news and community platform built on Laravel. We aggregate real-time tech news from trusted sources (TechCrunch, Ars Technica, BBC, and more) and provide a space for thoughtful discussion.</p>
            <p>Our mission is to deliver signal in the noise — curated intelligence on artificial intelligence, software development, startups, cybersecurity, and the future of technology.</p>
            <p>We believe in open conversation. Every article is a starting point for community insight. Join thousands of developers, founders, and technologists who read and discuss tech daily.</p>
            <p class="text-primary">— The LaraNews Team</p>
        </div>
    </main>
@endsection