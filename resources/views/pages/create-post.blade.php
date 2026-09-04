@extends('layouts.app')

@section('title', 'Create Post')

@section('content')
    <main id="create-post-page" class="flex-grow pt-32 pb-section-gap px-margin-mobile md:px-margin-desktop flex justify-center w-full">
        <div class="w-full max-w-[800px] flex flex-col gap-content-gap">
            
            <div class="flex flex-col gap-stack-sm">
                <h1 class="font-page-title-mobile md:font-page-title text-page-title-mobile md:text-page-title text-on-surface">Create a Post</h1>
                <p class="font-body-md text-body-md text-on-surface-variant">Share insights, links, or start a discussion.</p>
            </div>
            
            <form id="create-post-form" class="flex flex-col gap-content-gap">
                <!-- Post Type Tabs -->
                <div class="flex flex-col gap-stack-sm">
                    <label class="font-metadata text-metadata text-on-surface-variant uppercase tracking-wider">Format</label>
                    <div class="flex gap-2 border-b border-outline-variant">
                        <button type="button" data-post-type="text" class="post-type-tab flex items-center gap-2 px-4 py-3 font-label text-label text-on-surface-variant hover:text-on-surface transition-all border-b-2 border-transparent -mb-[1px] active">
                            <span class="material-symbols-outlined text-[18px]">article</span>
                            <span>Text</span>
                        </button>
                        <button type="button" data-post-type="link" class="post-type-tab flex items-center gap-2 px-4 py-3 font-label text-label text-on-surface-variant hover:text-on-surface transition-all border-b-2 border-transparent -mb-[1px]">
                            <span class="material-symbols-outlined text-[18px]">link</span>
                            <span>Link</span>
                        </button>
                        <button type="button" data-post-type="image" class="post-type-tab flex items-center gap-2 px-4 py-3 font-label text-label text-on-surface-variant hover:text-on-surface transition-all border-b-2 border-transparent -mb-[1px]">
                            <span class="material-symbols-outlined text-[18px]">image</span>
                            <span>Image</span>
                        </button>
                    </div>
                    <input type="hidden" name="post_type" id="post-type-input" value="text">
                </div>

                <!-- Post Title -->
                <div class="flex flex-col gap-stack-sm">
                    <div class="flex justify-between items-end">
                        <label class="font-metadata text-metadata text-on-surface-variant uppercase tracking-wider" for="post-title-input">Title</label>
                        <span id="title-counter" class="font-metadata text-metadata text-on-surface-variant"></span>
                    </div>
                    <input id="post-title-input" name="title" required placeholder="Give your post a descriptive title" maxlength="300" class="w-full bg-transparent border-0 border-b border-outline-variant focus:border-primary focus:ring-0 px-0 py-2 font-article-title-mobile text-article-title-mobile md:font-article-title-lg md:text-article-title-lg text-on-surface placeholder:text-on-surface-variant/50 transition-colors outline-none rounded-none" type="text">
                    <p id="post-title-error" class="text-sm text-error hidden mt-1"></p>
                </div>

                <!-- URL Wrap -->
                <div id="post-url-wrap" class="flex flex-col gap-stack-sm hidden">
                    <label class="font-metadata text-metadata text-on-surface-variant uppercase tracking-wider" for="post-url">URL</label>
                    <input id="post-url" name="url" placeholder="https://example.com/article" class="w-full bg-transparent border-0 border-b border-outline-variant focus:border-primary focus:ring-0 px-0 py-2 font-body-lg text-body-lg text-on-surface placeholder:text-on-surface-variant/50 transition-colors outline-none rounded-none" type="url">
                    <p id="post-url-error" class="text-sm text-error hidden mt-1"></p>
                </div>

                <!-- Image Wrap -->
                <div id="post-image-wrap" class="flex flex-col gap-stack-sm hidden">
                    <label class="font-metadata text-metadata text-on-surface-variant uppercase tracking-wider">Image</label>
                    
                    <!-- Drag & Drop Zone -->
                    <div id="image-drop-zone" class="image-drop-zone border-2 border-dashed border-outline-variant bg-surface-container-low hover:border-primary hover:bg-surface-container transition-all cursor-pointer p-8 flex flex-col items-center justify-center gap-4 min-h-[200px]">
                        <span class="material-symbols-outlined text-[48px] text-on-surface-variant">cloud_upload</span>
                        <div class="text-center">
                            <p class="font-body-lg text-body-lg text-on-surface mb-1">Drop image here or click to browse</p>
                            <p class="text-sm text-on-surface-variant">Max 10MB • JPEG, PNG, GIF, WebP</p>
                        </div>
                        <input id="post-image" name="image" type="file" accept="image/*" class="hidden">
                    </div>

                    <!-- Image Preview -->
                    <div id="image-preview-container" class="hidden">
                        <div class="relative border border-outline-variant bg-surface-container-low overflow-hidden">
                            <img id="image-preview" src="" alt="Preview" class="w-full h-auto max-h-[400px] object-contain bg-surface-container-lowest">
                            <button type="button" id="remove-image-btn" class="absolute top-2 right-2 w-8 h-8 bg-surface-container-highest/90 hover:bg-error/90 text-on-surface hover:text-on-error transition-colors flex items-center justify-center backdrop-blur-sm">
                                <span class="material-symbols-outlined text-[20px]">close</span>
                            </button>
                        </div>
                        <div id="image-info" class="flex gap-4 text-sm text-on-surface-variant mt-2">
                            <span id="image-dimensions"></span>
                            <span id="image-size"></span>
                        </div>
                    </div>
                    
                    <p id="post-image-error" class="text-sm text-error hidden mt-1"></p>
                </div>

                <!-- Content Wrap -->
                <div id="post-content-wrap" class="flex flex-col gap-stack-sm h-full min-h-[300px]">
                    <div class="flex justify-between items-end">
                        <label class="font-metadata text-metadata text-on-surface-variant uppercase tracking-wider" for="post-content-input">Body</label>
                        <span id="content-counter" class="font-metadata text-metadata text-on-surface-variant"></span>
                    </div>
                    <textarea id="post-content-input" name="content" rows="8" placeholder="Share your thoughts, add context, or start a conversation..." maxlength="10000" class="w-full flex-grow bg-transparent border-0 border-b border-outline-variant focus:border-primary focus:ring-0 px-0 py-4 font-body-lg text-body-lg text-on-surface outline-none resize-none placeholder:text-on-surface-variant/50 transition-colors"></textarea>
                    <p class="text-xs text-on-surface-variant">Markdown supported: **bold**, *italic*, [link](url)</p>
                    <p id="post-content-error" class="text-sm text-error hidden mt-1"></p>
                </div>

                <div id="create-post-message" class="hidden font-body-md text-body-md px-4 py-3 border-l-2"></div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-stack-md pt-content-gap mt-auto border-t border-outline-variant">
                    <a href="{{ url('/') }}" class="px-6 py-3 font-label text-label text-on-surface-variant hover:text-on-surface transition-colors rounded-none border border-transparent hover:border-outline-variant">Cancel</a>
                    <button type="submit" id="submit-btn" class="px-8 py-3 font-label text-label bg-primary-container text-on-primary-container hover:bg-inverse-primary hover:text-on-primary-container transition-all rounded-none shadow-sm flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span id="submit-text">Publish</span>
                        <span id="submit-icon" class="material-symbols-outlined text-[18px]">send</span>
                        <span id="submit-spinner" class="hidden">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </main>
@endsection
