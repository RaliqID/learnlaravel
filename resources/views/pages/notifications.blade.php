@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <main id="notifications-page" class="flex-grow py-content-gap min-h-screen w-full max-w-desktop mx-auto px-margin-mobile md:px-margin-desktop">
        <div class="max-w-3xl mx-auto">
            <header class="mb-content-gap">
                <h1 class="font-page-title-mobile md:font-page-title text-page-title-mobile md:text-page-title mb-stack-sm text-on-background">Notifications</h1>
                <p class="font-body-md text-body-md text-on-surface-variant">Stay updated with your latest interactions.</p>
            </header>

            <!-- Filters & Actions -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-content-gap border-b border-outline-variant pb-4">
                <nav class="flex gap-stack-md overflow-x-auto no-scrollbar">
                    <button type="button" data-notification-filter="all" class="font-label text-label text-primary border-b-2 border-primary pb-1 shrink-0 px-2 data-[active=true]:text-primary data-[active=true]:border-primary data-[active=false]:text-on-surface-variant data-[active=false]:border-transparent hover:text-on-surface transition-colors" data-active="true">All</button>
                    <button type="button" data-notification-filter="unread" class="font-label text-label text-on-surface-variant border-b-2 border-transparent pb-1 shrink-0 px-2 data-[active=true]:text-primary data-[active=true]:border-primary data-[active=false]:text-on-surface-variant data-[active=false]:border-transparent hover:text-on-surface transition-colors" data-active="false">Unread</button>
                </nav>
                <div class="flex justify-end">
                    <button type="button" id="mark-all-read" class="font-label text-label text-on-surface-variant hover:text-primary transition-colors flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">done_all</span> Mark all read
                    </button>
                </div>
            </div>

            <!-- Notification List -->
            <div id="notifications-list" class="flex flex-col" aria-live="polite">
                <!-- JS injects results here -->
            </div>
        </div>
    </main>
@endsection
