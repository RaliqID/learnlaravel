@extends('layouts.app')

@section('title', 'Discovery')

@section('content')
    <main id="discovery-page" class="flex-1 max-w-desktop mx-auto w-full px-margin-mobile md:px-margin-desktop py-content-gap">
        <div class="flex items-center justify-between mb-8">
            <h1 class="font-page-title text-page-title text-on-surface">Community Discovery</h1>
            <a href="{{ route('posts.create') }}" class="bg-primary text-on-primary px-6 py-2 rounded-lg hover:bg-primary-fixed-dim transition-colors font-label text-label">Create Post</a>
        </div>

        <!-- Filter tabs -->
        <div class="flex gap-4 border-b border-outline-variant mb-6">
            <button data-discovery-tab="all" class="px-4 py-2 font-label text-label transition-colors bg-primary-container text-on-primary-container" aria-selected="true">All</button>
            <button data-discovery-tab="news" class="px-4 py-2 font-label text-label transition-colors text-on-surface-variant hover:bg-surface-container" aria-selected="false">News</button>
            <button data-discovery-tab="community" class="px-4 py-2 font-label text-label transition-colors text-on-surface-variant hover:bg-surface-container" aria-selected="false">Community</button>
        </div>

        <div id="discovery-feed" class="flex flex-col gap-6">
            <!-- JS injects posts -->
        </div>
    </main>
@endsection