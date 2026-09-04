@extends('layouts.app')

@section('title', 'Search')

@section('content')
    <main id="search-page" class="flex-grow py-content-gap w-full max-w-desktop mx-auto px-margin-mobile md:px-margin-desktop">
        <!-- Search Hero -->
        <section class="mb-section-gap w-full max-w-[960px]">
            <form id="search-form" action="{{ url('/search') }}" method="GET" class="relative w-full mb-stack-lg group">
                <span class="material-symbols-outlined absolute left-0 bottom-[14px] text-on-surface-variant group-focus-within:text-primary transition-colors text-[32px]">search</span>
                <input id="search-input" name="q" value="{{ request('q') }}" class="w-full bg-transparent border-0 border-b border-outline-variant pb-stack-sm pl-[48px] focus:ring-0 focus:border-primary font-page-title-mobile text-page-title-mobile md:font-page-title md:text-page-title text-on-background placeholder:text-on-surface-variant transition-colors outline-none h-[64px]" placeholder="Explore LaraNews..." type="text" autocomplete="off" />
            </form>
            
            <!-- Filters (Chips) -->
            <div class="flex flex-wrap gap-stack-md mt-content-gap" role="tablist" aria-label="Search filters">
                <button type="button" data-search-filter="all" class="px-6 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface font-label text-label transition-colors tracking-wide data-[active=true]:bg-primary-container data-[active=true]:text-on-primary-container data-[active=true]:hover:bg-primary-container" data-active="true">All Results</button>
                <button type="button" data-search-filter="posts" class="px-6 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface font-label text-label transition-colors tracking-wide data-[active=true]:bg-primary-container data-[active=true]:text-on-primary-container data-[active=true]:hover:bg-primary-container" data-active="false">Posts</button>
                <button type="button" data-search-filter="topics" class="px-6 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface font-label text-label transition-colors tracking-wide data-[active=true]:bg-primary-container data-[active=true]:text-on-primary-container data-[active=true]:hover:bg-primary-container" data-active="false">Topics</button>
                <button type="button" data-search-filter="users" class="px-6 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface font-label text-label transition-colors tracking-wide data-[active=true]:bg-primary-container data-[active=true]:text-on-primary-container data-[active=true]:hover:bg-primary-container" data-active="false">Users</button>
            </div>
        </section>

        <!-- Content Layout: Asymmetric Editorial Grid -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter md:gap-margin-desktop">
            <!-- Left Column: Search Results (Wider Lane) -->
            <div class="md:col-span-8 flex flex-col gap-content-gap">
                <h2 id="search-count" class="font-metadata text-metadata text-on-surface-variant uppercase tracking-widest border-b border-outline-variant pb-stack-sm w-max mb-stack-md">Enter a search query</h2>
                
                <div id="search-results" aria-live="polite" class="flex flex-col gap-content-gap w-full">
                    <!-- JS injects results here -->
                </div>
            </div>

            <!-- Right Column: Context & Metadata (Narrower Lane) -->
            <aside class="md:col-span-4 flex flex-col gap-section-gap md:sticky md:top-32 h-fit">
                <!-- Recent Searches -->
                <div>
                    <h4 class="font-section-title text-section-title text-on-background mb-stack-lg flex items-center gap-stack-sm">
                        <span class="material-symbols-outlined text-outline">history</span> Recent
                    </h4>
                    <div id="search-topics" class="flex flex-col font-body-md text-body-md text-on-surface-variant">
                        <!-- JS can inject recent searches here -->
                        <p class="py-stack-sm text-on-surface-variant">No recent searches</p>
                    </div>
                </div>

                <!-- Trending Topics (Static placeholder per design) -->
                <div>
                    <h4 class="font-section-title text-section-title text-on-background mb-stack-lg flex items-center gap-stack-sm">
                        <span class="material-symbols-outlined text-outline">trending_up</span> Trending
                    </h4>
                    <div class="flex flex-wrap gap-stack-sm">
                        <span class="px-4 py-2 bg-surface hover:bg-surface-container-high text-on-surface font-metadata text-metadata cursor-pointer transition-colors">#Technology</span>
                        <span class="px-4 py-2 bg-surface hover:bg-surface-container-high text-on-surface font-metadata text-metadata cursor-pointer transition-colors">#Design</span>
                        <span class="px-4 py-2 bg-surface hover:bg-surface-container-high text-on-surface font-metadata text-metadata cursor-pointer transition-colors">#Laravel</span>
                    </div>
                </div>
            </aside>
        </div>
    </main>
@endsection
