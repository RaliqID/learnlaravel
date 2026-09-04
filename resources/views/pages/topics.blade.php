@extends('layouts.app')

@section('title', 'Explore Topics')

@section('content')
    <main id="topics-page" class="flex-grow max-w-desktop mx-auto w-full px-margin-mobile md:px-margin-desktop py-content-gap flex flex-col md:flex-row gap-gutter">
        <!-- Sidebar Navigation -->
        <aside class="w-full md:w-64 flex-shrink-0">
            <div class="sticky top-32">
                <h1 class="font-page-title-mobile md:font-page-title text-page-title-mobile md:text-page-title mb-8">Explore topics</h1>
                <div class="flex flex-col border-y border-outline-variant" id="topics-nav">
                    <button type="button" data-topics-filter="all" class="flex justify-between items-center w-full py-4 font-label text-label text-left transition-colors px-4 border-b border-outline-variant bg-primary-container text-on-primary-container group">
                        All topics
                        <span class="material-symbols-outlined opacity-100 transition-opacity" data-icon="arrow_forward">arrow_forward</span>
                    </button>
                    <button type="button" data-topics-filter="active" class="flex justify-between items-center w-full py-4 font-label text-label text-left transition-colors px-4 border-b border-outline-variant text-on-surface-variant hover:text-on-surface hover:bg-surface-container group">
                        Active
                        <span class="material-symbols-outlined opacity-0 group-hover:opacity-100 transition-opacity" data-icon="arrow_forward">arrow_forward</span>
                    </button>
                </div>
            </div>
        </aside>
        
        <!-- Topic Content -->
        <section class="flex-grow md:pl-gutter pt-8 md:pt-0">
            <div class="mb-8 pb-4 border-b border-outline-variant flex flex-wrap items-center justify-between gap-gutter">
                <h2 class="font-section-title text-section-title">Latest in Technology</h2>
                <div class="relative group">
                    <select id="topics-sort" class="w-full appearance-none bg-transparent border-0 border-b border-outline-variant focus:border-primary focus:ring-0 px-0 py-2 font-label text-label text-on-surface cursor-pointer transition-colors outline-none rounded-none pr-6">
                        <option class="bg-surface-container text-on-surface" value="popular">Popular</option>
                        <option class="bg-surface-container text-on-surface" value="name">Name</option>
                        <option class="bg-surface-container text-on-surface" value="newest">Newest</option>
                    </select>
                    <span class="material-symbols-outlined absolute right-0 top-1/2 -translate-y-1/2 pointer-events-none text-on-surface-variant group-hover:text-primary transition-colors text-sm">expand_more</span>
                </div>
            </div>
            <div id="topics-list" aria-live="polite"></div>
        </section>
    </main>
@endsection
