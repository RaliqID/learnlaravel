@extends('layouts.reading')

@section('title', 'Reading')

@section('content')
    @push('scripts')
    <script>
        // Back button: history.back() when available, else home.
        (function () {
            const btn = document.getElementById('post-back-btn');
            if (!btn) return;
            btn.addEventListener('click', () => {
                if (window.history.length > 1) {
                    window.history.back();
                } else {
                    window.location.href = '/';
                }
            });
        })();
    </script>
    @endpush

    <div id="post-page" data-post-identifier="{{ $identifier }}" style="display: none;">
        <!-- Back Button (Sticky Top-Left) -->
        <div class="fixed top-4 left-4 z-50 md:top-6 md:left-6">
            <button type="button" id="post-back-btn" class="flex items-center gap-2 px-4 py-2 rounded-full border border-outline-variant text-on-surface-variant hover:text-primary hover:border-primary transition-colors duration-200 bg-background/80 backdrop-blur-sm font-label text-label">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                <span>Back</span>
            </button>
        </div>

        <main class="max-w-3xl mx-auto px-margin-mobile md:px-margin-desktop w-full py-section-gap pt-20">
            <article>
                <header class="mb-content-gap">
                    <nav id="post-breadcrumb" class="font-metadata text-metadata text-primary uppercase tracking-wider mb-stack-lg" aria-label="Breadcrumb"></nav>

                    <h1 id="post-title" class="font-page-title-mobile md:font-page-title text-page-title-mobile md:text-page-title text-on-background mb-stack-lg leading-tight"></h1>
                    <p id="post-dek" class="font-article-title-sm text-article-title-sm text-on-surface-variant mb-content-gap leading-relaxed"></p>

                    <div class="flex items-center justify-between border-y border-outline-variant py-stack-md">
                        <div id="post-meta" class="flex items-center gap-stack-md"></div>
                        <div class="flex items-center gap-stack-md">
                            <button type="button" id="bookmark-btn" class="text-on-surface-variant hover:text-primary transition-colors p-2" aria-label="Bookmark this post">
                                <span class="material-symbols-outlined">bookmark</span>
                                <span id="bookmark-label" class="sr-only">Save</span>
                            </button>
                            <button type="button" id="share-btn" class="text-on-surface-variant hover:text-primary transition-colors p-2" aria-label="Share this post">
                                <span class="material-symbols-outlined">share</span>
                            </button>
                        </div>
                    </div>

                    <p id="post-source" class="mt-stack-sm hidden font-metadata text-metadata text-on-surface-variant"></p>

                    <div class="mt-stack-md flex items-center justify-between">
                        <div class="flex items-center gap-stack-md">
                            <div id="vote-group" class="flex items-center gap-stack-sm bg-surface-container rounded-none px-4 py-2" role="group" aria-label="Vote">
                                <button type="button" id="vote-up" class="text-on-surface-variant hover:text-primary transition-colors flex items-center justify-center" aria-label="Upvote">
                                    <span class="material-symbols-outlined text-[16px]">arrow_upward</span>
                                </button>
                                <span id="vote-score" data-score="0" class="font-label text-label text-on-surface min-w-[20px] text-center">0</span>
                                <button type="button" id="vote-down" class="text-on-surface-variant hover:text-danger transition-colors flex items-center justify-center" aria-label="Downvote">
                                    <span class="material-symbols-outlined text-[16px]">arrow_downward</span>
                                </button>
                            </div>
                            <span class="font-metadata text-metadata text-on-surface-variant flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px]">chat_bubble</span>
                                <span id="post-comment-count"></span>
                            </span>
                        </div>
                    </div>

                    <p id="login-hint" class="mt-stack-sm hidden font-metadata text-metadata text-on-surface-variant">
                        <a href="{{ url('/login') }}" class="font-semibold text-primary hover:text-primary-container underline underline-offset-4">Sign in</a> to vote, save, or comment.
                    </p>
                </header>

                <figure id="post-hero-image" class="mb-content-gap w-full aspect-video overflow-hidden bg-surface-container-low flex items-center justify-center hidden"></figure>

                <div id="post-content" class="font-body-lg text-body-lg text-on-surface space-y-stack-lg leading-relaxed prose-article"></div>

                <nav class="mt-content-gap grid gap-stack-md border-t border-outline-variant pt-stack-lg md:grid-cols-2" aria-label="Post navigation">
                    <a id="nav-prev" class="hidden border border-outline-variant p-stack-md font-label text-label text-on-surface-variant hover:border-primary hover:text-primary transition-colors"></a>
                    <a id="nav-next" class="hidden border border-outline-variant p-stack-md font-label text-label text-on-surface-variant hover:border-primary hover:text-primary transition-colors md:text-right"></a>
                </nav>

                <section id="related-section" class="mt-content-gap hidden" aria-labelledby="related-heading">
                    <h2 id="related-heading" class="font-section-title text-section-title text-on-background mb-stack-lg">Related</h2>
                    <div id="related-posts" class="grid gap-stack-md sm:grid-cols-2"></div>
                </section>

                <section class="mt-section-gap border-t border-outline-variant pt-content-gap" aria-labelledby="comments-heading">
                    <div class="mb-stack-lg flex items-center justify-between">
                        <h2 id="comments-heading" class="font-section-title text-section-title text-on-background">Discussion</h2>
                    </div>

                    <form id="comment-form" class="mb-content-gap flex flex-col gap-stack-sm hidden">
                        @csrf
                        <label for="comment-input" class="font-metadata text-metadata text-on-surface-variant">Add to the discussion</label>
                        <div class="relative border-b border-outline-variant focus-within:border-primary transition-colors pb-stack-sm">
                            <textarea id="comment-input" name="content" rows="3" required
                                class="w-full bg-transparent border-none outline-none font-body-md text-on-surface placeholder-on-surface-variant focus:ring-0 resize-none py-2"
                                placeholder="Add your perspective..."></textarea>
                        </div>
                        <div class="mt-stack-sm flex justify-end">
                            <button type="submit" class="bg-primary text-on-primary font-label text-label px-6 py-2 hover:bg-primary-container transition-colors rounded-none">Post</button>
                        </div>
                    </form>

                    <div id="comments-list" class="space-y-stack-lg" aria-live="polite"></div>
                    <div id="comments-sentinel" class="h-px" aria-hidden="true"></div>
                </section>
            </article>
        </main>
    </div>

    <div id="post-error" class="hidden max-w-3xl mx-auto px-margin-mobile md:px-margin-desktop py-section-gap text-center">
        <h1 class="font-page-title-mobile text-page-title-mobile text-on-background">Couldn't load this story.</h1>
        <p class="mt-stack-md font-body-md text-body-md text-on-surface-variant">It may have been removed or is temporarily unavailable.</p>
        <a href="{{ url('/') }}" class="mt-stack-lg inline-flex items-center px-6 py-2 border border-outline-variant font-label text-label text-on-surface hover:border-primary transition-colors rounded-none">Back to home</a>
    </div>
@endsection