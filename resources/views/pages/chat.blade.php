@extends('layouts.app')

@section('title', 'Messages')

@section('content')
    <main id="chat-page" class="flex-1 max-w-desktop mx-auto w-full px-margin-mobile md:px-margin-desktop py-content-gap">
        <h1 class="font-page-title text-page-title text-on-surface mb-8">Messages</h1>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-gutter">
            <!-- Conversation list -->
            <aside class="border-r border-outline-variant pr-gutter">
                <div id="chat-conversations" class="flex flex-col gap-2">
                    <!-- JS injects conversation list -->
                </div>
            </aside>
            <!-- Message area -->
            <div class="md:col-span-2 flex flex-col">
                <div id="chat-messages" class="flex-1 overflow-y-auto max-h-[600px] border border-outline-variant p-4 rounded-lg bg-surface-container-low">
                    <!-- JS injects messages -->
                </div>
                <form id="chat-send-form" class="mt-4 flex gap-2">
                    <input type="text" id="chat-input" placeholder="Type a message..." class="flex-1 border border-outline-variant bg-surface px-4 py-2 rounded-lg focus:outline-none focus:border-primary" required>
                    <button type="submit" class="bg-primary text-on-primary px-6 py-2 rounded-lg hover:bg-primary-fixed-dim transition-colors">Send</button>
                </form>
                <p id="chat-no-conversation" class="text-center text-on-surface-variant mt-8 text-sm">Select a conversation to start messaging.</p>
            </div>
        </div>
    </main>
@endsection