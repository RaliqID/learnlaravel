@extends('layouts.app')

@section('title', 'Community')

@section('content')
    <main id="topic-page" data-topic-slug="{{ $slug }}" class="flex-1 pt-content-gap px-margin-mobile md:px-margin-tablet lg:px-margin-desktop pb-section-gap max-w-[1200px] mx-auto w-full">
        <!-- Community Header -->
        <section class="mb-section-gap">
            <div class="flex flex-col md:flex-row gap-stack-lg items-start md:items-end border-b border-outline-variant pb-stack-lg">
                <div class="w-24 h-24 md:w-32 md:h-32 rounded-full overflow-hidden flex-shrink-0 bg-surface-container-high border-2 border-primary">
                    <div class="w-full h-full flex items-center justify-center text-outline">
                        <span class="material-symbols-outlined text-4xl">tag</span>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <h1 id="topic-title" class="font-page-title-mobile md:font-page-title text-page-title-mobile md:text-page-title text-on-surface mb-stack-sm truncate">Loading...</h1>
                    <p id="topic-description" class="font-body-md text-body-md text-on-surface-variant mb-stack-md max-w-3xl"></p>
                    <div id="topic-meta" class="flex flex-wrap items-center gap-gutter font-metadata text-metadata text-on-surface">
                        <!-- JS fills this -->
                    </div>
                </div>
                <div class="flex-shrink-0 mt-stack-md md:mt-0 flex gap-4">
                    <button type="button" id="topic-subscribe" class="bg-primary-container text-on-primary-container font-label text-label px-6 py-2 hover:bg-inverse-primary transition-colors hidden">Join</button>
                </div>
            </div>
        </section>

        <!-- Feed Container -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">
            <!-- Main Feed -->
            <div class="lg:col-span-8 flex flex-col">
                <div class="flex gap-4 border-b border-outline-variant mb-6 pb-2">
                    <button type="button" data-topic-feed-tab="hot" class="px-4 py-2 font-label text-label transition-colors bg-primary-container text-on-primary-container" aria-selected="true">Hot</button>
                    <button type="button" data-topic-feed-tab="new" class="px-4 py-2 font-label text-label transition-colors text-on-surface-variant hover:bg-surface-container" aria-selected="false">New</button>
                    <button type="button" data-topic-feed-tab="top" class="px-4 py-2 font-label text-label transition-colors text-on-surface-variant hover:bg-surface-container" aria-selected="false">Top</button>
                </div>
                <div id="topic-feed" class="flex flex-col gap-0" aria-live="polite"></div>
            </div>


        </div>
    </main>
@endsection
