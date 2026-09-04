@extends('layouts.app')

@section('title', 'LaraNews - Home')

@section('content')
<div id="homepage-root" class="w-full">
    <main class="flex-1 w-full max-w-[1280px] mx-auto px-[20px] md:px-[24px] pt-[80px] pb-[80px] flex flex-col">

        <!-- Categories Chips -->
        <section class="mb-[40px]">
            <div id="home-categories" class="flex flex-wrap gap-[8px]">
                <!-- Injected via JS -->
            </div>
        </section>

        <!-- Hero Section: Primary (8) + Secondary (4) -->
        <section class="grid grid-cols-1 md:grid-cols-12 gap-[24px] mb-[80px]">
            <div id="home-hero" class="md:col-span-8">
                <!-- Injected via JS -->
            </div>
            <div id="home-secondary" class="md:col-span-4 flex flex-col gap-[24px]">
                <!-- Injected via JS -->
            </div>
        </section>

        <!-- Divider -->
        <div class="w-full border-t border-outline-variant my-[80px] opacity-50"></div>

        <!-- Latest Dispatches + Trending -->
        <section class="grid grid-cols-1 md:grid-cols-12 gap-[24px]">
            <div class="md:col-span-8 flex flex-col">
                <div class="flex items-baseline justify-between mb-[32px] pb-[8px] border-b border-outline-variant">
                    <h3 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-surface tracking-tight">Latest Dispatches</h3>
                    <a class="font-label-sm text-label-sm text-primary hover:text-on-surface transition-colors uppercase tracking-widest" href="/posts">View All</a>
                </div>
                <div id="feed-posts" class="flex flex-col">
                    <!-- Injected via JS -->
                </div>
            </div>
            <aside class="md:col-span-4 flex flex-col mt-[80px] md:mt-0 pl-0 md:pl-[24px] md:border-l border-outline-variant">
                <div class="flex items-baseline justify-between mb-[32px] pb-[8px] border-b border-outline-variant">
                    <h3 class="font-headline-md text-headline-md text-on-surface tracking-tight">Trending</h3>
                </div>
                <div id="home-trending" class="flex flex-col gap-[32px]">
                    <!-- Injected via JS -->
                </div>
            </aside>
        </section>

        <!-- Divider -->
        <div class="w-full border-t border-outline-variant my-[80px] opacity-50"></div>

        <!-- Deep Dives -->
        <section class="flex flex-col mb-[80px]">
            <div class="flex items-baseline justify-between mb-[32px] pb-[8px] border-b border-outline-variant">
                <h3 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-surface tracking-tight">Deep Dives</h3>
            </div>
            <div id="home-deepdives" class="grid grid-cols-1 md:grid-cols-12 gap-[24px] items-start">
                <!-- Injected via JS -->
            </div>
        </section>

        <!-- Divider -->
        <div class="w-full border-t border-outline-variant my-[80px] opacity-50"></div>

        <!-- News Category Area Chart -->
        <section class="flex flex-col mb-[80px]">
            <div class="flex items-baseline justify-between mb-[32px] pb-[8px] border-b border-outline-variant">
                <h3 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-surface tracking-tight">News by Category</h3>
                <div id="chart-filter" class="flex gap-2">
                    <button data-days="1" class="px-3 py-1 text-sm border border-outline-variant hover:border-primary transition-colors">1D</button>
                    <button data-days="7" class="px-3 py-1 text-sm border border-outline-variant hover:border-primary transition-colors bg-primary text-on-primary border-primary">7D</button>
                    <button data-days="30" class="px-3 py-1 text-sm border border-outline-variant hover:border-primary transition-colors">30D</button>
                    <button data-days="60" class="px-3 py-1 text-sm border border-outline-variant hover:border-primary transition-colors">60D</button>
                </div>
            </div>
            <div id="home-chart-category" class="w-full bg-surface-container-low border border-outline-variant p-4" style="height: 340px;">
                <!-- Stacked area chart rendered by Chart.js -->
            </div>
        </section>

        <!-- Divider -->
        <div class="w-full border-t border-outline-variant my-[80px] opacity-50"></div>

        <!-- The Archive -->
        <section class="flex flex-col mb-[80px] max-w-[900px] mx-auto w-full">
            <div class="flex items-center justify-center mb-[32px]">
                <h3 class="font-headline-md text-headline-md text-on-surface tracking-widest uppercase">The Archive</h3>
            </div>
            <div id="home-archive" class="flex flex-col border-t border-outline-variant/30">
                <!-- Injected via JS -->
            </div>
        </section>

        <!-- Divider -->
        <div class="w-full border-t border-outline-variant my-[80px] opacity-50"></div>

        <!-- Global Intelligence -->
        <section class="flex flex-col mb-[80px]">
            <div class="flex items-baseline justify-between mb-[32px] pb-[8px] border-b border-outline-variant">
                <h3 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-surface tracking-tight">Global Intelligence</h3>
            </div>
            <div id="home-global" class="grid grid-cols-1 md:grid-cols-3 gap-[24px]">
                <!-- Injected via JS -->
            </div>
        </section>

        <!-- Newsletter CTA -->
        <div class="w-full bg-surface-container-low border-y border-outline-variant py-[80px] mt-[80px] -mx-[20px] md:-mx-[24px] px-[20px] md:px-[24px]">
            <div class="max-w-[720px] mx-auto flex flex-col items-center text-center gap-[32px]">
                <div class="flex flex-col gap-[8px] items-center">
                    <h3 class="font-headline-lg-mobile md:font-display-xl text-headline-lg-mobile md:text-display-xl text-on-surface tracking-tight">The Signal in the Noise.</h3>
                    <p class="font-body-lg text-body-lg text-on-surface-variant max-w-[500px]">Join thousands of industry leaders receiving our curated dispatch of essential technological, cultural, and geopolitical intelligence.</p>
                </div>
                <form class="flex flex-col sm:flex-row gap-[8px] w-full max-w-[500px]">
                    <input class="flex-1 bg-surface border border-outline-variant px-4 py-3 font-body-md text-on-surface placeholder:text-outline-variant focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all" placeholder="Enter your email address" type="email">
                    <button class="bg-primary-container text-white hover:bg-primary-fixed-dim transition-colors px-6 py-3 font-label-md tracking-wider" type="submit">Join the Conversation</button>
                </form>
            </div>
        </div>

        <!-- Hidden old containers for compatibility -->
        <div id="home-recommended" style="display:none;"></div>
        <div id="home-editors" style="display:none;"></div>

    </main>
</div>
@endsection