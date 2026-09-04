/**
 * Single topic detail + feed controller.
 * Shows topic info, subscribe button, and filtered post feed.
 */

import { topicsApi, feedApi, isAuthenticated } from './api/client.js';

function escapeHtml(str = '') {
    return String(str ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function relativeTime(iso) {
    if (!iso) return 'Just now';
    const diff = Math.floor((Date.now() - new Date(iso)) / 1000);
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
    return new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
}

function postUrl(post) {
    return '/posts/' + (post.slug || post.id);
}

function postRow(post) {
    const imageHtml = post.thumbnail_url || post.image_url
        ? `<img src="${escapeHtml(post.thumbnail_url || post.image_url)}" alt="" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='/storage/images/placeholder.svg'" class="h-40 w-full object-cover bg-surface-container">`
        : '';

    const authorHtml = post.user
        ? `<span>${escapeHtml(post.user.display_name || post.user.username || '')}</span>`
        : '';

    const metaBits = [];
    if (post.published_at) metaBits.push(relativeTime(post.published_at));
    if (typeof post.vote_score === 'number') metaBits.push(`${post.vote_score} votes`);
    if (typeof post.comment_count === 'number') metaBits.push(`${post.comment_count} comments`);
    const metaHtml = metaBits.join(' · ');

    return `
        <article class="group flex flex-col gap-stack-sm border-b border-outline-variant py-stack-lg transition-colors hover:bg-surface-container/30">
            ${imageHtml}
            <h3 class="font-article-title-sm text-article-title-sm text-on-background line-clamp-2 transition-colors group-hover:text-primary">
                <a href="${postUrl(post)}">${escapeHtml(post.title)}</a>
            </h3>
            ${post.excerpt ? `<p class="font-body-md text-body-md text-on-surface-variant line-clamp-2">${escapeHtml(post.excerpt)}</p>` : ''}
            <div class="mt-auto flex items-center gap-2 pt-1 font-metadata text-metadata text-outline">
                ${authorHtml}
                <span class="flex-1"></span>
                ${metaHtml}
            </div>
        </article>
    `;
}

function renderLoading() {
    const feed = document.getElementById('topic-feed');
    if (!feed) return;

    const skeleton = Array.from({ length: 6 }).map(() => `
        <div class="border-b border-outline-variant py-stack-lg">
            <div class="h-40 w-full animate-pulse bg-surface-container"></div>
            <div class="mt-3 h-4 w-3/4 animate-pulse bg-surface-container"></div>
            <div class="mt-2 h-4 w-full animate-pulse bg-surface-container"></div>
            <div class="mt-4 h-3 w-32 animate-pulse bg-surface-container"></div>
        </div>
    `).join('');

    feed.innerHTML = skeleton;
}

function renderFeed(posts) {
    const feed = document.getElementById('topic-feed');
    if (!feed) return;

    if (!posts || posts.length === 0) {
        feed.innerHTML = '<p class="py-8 text-center font-body-md text-body-md text-on-surface-variant">No posts in this topic yet</p>';
        return;
    }

    feed.innerHTML = posts.map(postRow).join('');
}

function renderError() {
    const feed = document.getElementById('topic-feed');
    if (!feed) return;

    feed.innerHTML = `
        <div class="flex flex-col items-center py-10 text-center">
            <p class="font-body-md text-body-md text-on-surface-variant">Failed to load posts</p>
            <button type="button" id="topic-feed-retry" class="mt-3 border border-outline-variant px-4 py-2 font-label text-label text-on-surface transition-colors hover:bg-surface-container">Retry</button>
        </div>
    `;
}

export default async function initTopicPage() {
    const root = document.getElementById('topic-page');
    if (!root) return;

    const slug = root.dataset.topicSlug;
    if (!slug) return;

    let topicId = null;
    let isSubscribed = false;
    let currentSort = 'hot';

    // Load topic details
    try {
        const { data } = await topicsApi.show(slug);
        const topic = data?.topic || data;
        topicId = topic.id;
        isSubscribed = topic.is_subscribed || false;

        document.getElementById('topic-title').textContent = topic.name || '';
        document.getElementById('topic-description').textContent = topic.description || '';

        const metaEl = document.getElementById('topic-meta');
        if (metaEl) {
            metaEl.innerHTML = `
                <span>${topic.post_count ?? 0} posts</span>
                <span class="text-outline">·</span>
                <span>${topic.subscriber_count ?? 0} subscribers</span>
            `;
        }

        // Subscribe button
        const subscribeBtn = document.getElementById('topic-subscribe');
        if (subscribeBtn) {
            if (!isAuthenticated()) {
                subscribeBtn.style.display = 'none';
            } else {
                subscribeBtn.textContent = isSubscribed ? 'Joined' : 'Join';
                subscribeBtn.addEventListener('click', async () => {
                    subscribeBtn.disabled = true;
                    try {
                        if (isSubscribed) {
                            await topicsApi.unsubscribe(slug);
                            isSubscribed = false;
                            subscribeBtn.textContent = 'Join';
                        } else {
                            await topicsApi.subscribe(slug);
                            isSubscribed = true;
                            subscribeBtn.textContent = 'Joined';
                        }
                    } catch (err) {
                        console.error('Subscribe failed:', err);
                    } finally {
                        subscribeBtn.disabled = false;
                    }
                });
            }
        }
    } catch (err) {
        console.error('Failed to load topic:', err);
    }

    // Load feed
    async function loadFeed(sort = 'hot') {
        renderLoading();

        try {
            const { data } = await feedApi.feed({ topic_id: topicId, sort });
            renderFeed(data?.posts || []);
        } catch (err) {
            renderError();
        }
    }

    // Feed tabs
    const tabs = document.querySelectorAll('[data-topic-feed-tab]');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            currentSort = tab.dataset.topicFeedTab;

            tabs.forEach(t => {
                const active = t === tab;
                t.classList.toggle('bg-primary-container', active);
                t.classList.toggle('text-on-primary-container', active);
                t.classList.toggle('text-on-surface-variant', !active);
            });

            loadFeed(currentSort);
        });
    });

    // Retry handler
    document.addEventListener('click', (e) => {
        if (e.target.id === 'topic-feed-retry' || e.target.closest('#topic-feed-retry')) {
            loadFeed(currentSort);
        }
    });

    // Initial feed load
    if (topicId) {
        await loadFeed(currentSort);
    }
}
