/**
 * Search controller.
 * Handles search form, filters, and result rendering.
 */

import { searchApi } from './api/client.js';

function escapeHtml(str = '') {
    return String(str ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function relativeTime(iso) {
    if (!iso) return '';
    const diff = Math.floor((Date.now() - new Date(iso)) / 1000);
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
    return new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
}

function renderPostResult(post) {
    const imageHtml = post.thumbnail_url || post.image_url
        ? `<img src="${escapeHtml(post.thumbnail_url || post.image_url)}" alt="" class="h-24 w-32 shrink-0 object-cover bg-surface-container" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='/storage/images/placeholder.svg'">`
        : '';

    return `
        <article class="group flex gap-4 border-b border-outline-variant py-stack-lg transition-colors hover:bg-surface-container/30">
            <div class="min-w-0 flex-1">
                <h3 class="font-article-title-sm text-article-title-sm text-on-background line-clamp-2 transition-colors group-hover:text-primary">
                    <a href="/posts/${post.slug || post.id}">${escapeHtml(post.title)}</a>
                </h3>
                ${post.excerpt ? `<p class="mt-1 font-body-md text-body-md text-on-surface-variant line-clamp-2">${escapeHtml(post.excerpt)}</p>` : ''}
                <div class="mt-2 flex items-center gap-2 font-metadata text-metadata text-outline">
                    ${post.topic ? `<span class="text-primary">${escapeHtml(post.topic.name)}</span>` : ''}
                    ${post.published_at ? `<span>${relativeTime(post.published_at)}</span>` : ''}
                    ${typeof post.vote_score === 'number' ? `<span>${post.vote_score} votes</span>` : ''}
                </div>
            </div>
            ${imageHtml}
        </article>
    `;
}

function renderTopicResult(topic) {
    return `
        <a href="/topics/${escapeHtml(topic.slug)}" class="flex items-center gap-2 border-b border-outline-variant py-stack-md font-body-md text-body-md text-on-background transition-colors hover:bg-surface-container/30 hover:text-primary">
            ${topic.icon ? `<img src="${escapeHtml(topic.icon)}" alt="" class="size-5 shrink-0" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='/storage/images/placeholder.svg'">` : ''}
            ${escapeHtml(topic.name)}
            <span class="font-metadata text-metadata text-outline">${topic.post_count ?? 0} posts</span>
        </a>
    `;
}

function renderUserResult(user) {
    const avatarHtml = user.avatar
        ? `<img src="${escapeHtml(user.avatar)}" alt="" class="size-10 rounded-full object-cover" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='/storage/images/placeholder.svg'">`
        : `<span class="inline-flex size-10 items-center justify-center rounded-full bg-surface-container-high font-body-md text-body-md text-on-surface-variant">${escapeHtml((user.display_name || user.username || '?')[0].toUpperCase())}</span>`;

    return `
        <a href="/users/${escapeHtml(user.username)}" class="flex items-center gap-3 border-b border-outline-variant py-stack-md transition-colors hover:bg-surface-container/30">
            ${avatarHtml}
            <div class="min-w-0 flex-1">
                <p class="font-body-md text-body-md text-on-background">${escapeHtml(user.display_name || user.username)}</p>
                <p class="font-metadata text-metadata text-outline">@${escapeHtml(user.username)}</p>
            </div>
        </a>
    `;
}

function renderResults(data, filter) {
    const container = document.getElementById('search-results');
    if (!container) return;

    let html = '';
    let count = 0;

    if (filter === 'all') {
        // Render all types
        const posts = data.posts || [];
        const topics = data.topics || [];
        const users = data.users || [];

        if (posts.length > 0) {
            html += '<h2 class="mb-3 font-article-title-sm text-article-title-sm text-on-background">Posts</h2>';
            html += '<div class="mb-6">' + posts.map(renderPostResult).join('') + '</div>';
            count += posts.length;
        }

        if (topics.length > 0) {
            html += '<h2 class="mb-3 font-article-title-sm text-article-title-sm text-on-background">Topics</h2>';
            html += '<div class="mb-6">' + topics.map(renderTopicResult).join('') + '</div>';
            count += topics.length;
        }

        if (users.length > 0) {
            html += '<h2 class="mb-3 font-article-title-sm text-article-title-sm text-on-background">Users</h2>';
            html += '<div>' + users.map(renderUserResult).join('') + '</div>';
            count += users.length;
        }
    } else if (filter === 'posts') {
        const posts = data.posts || [];
        html = posts.map(renderPostResult).join('');
        count = posts.length;
    } else if (filter === 'topics') {
        const topics = data.topics || [];
        html = '<div>' + topics.map(renderTopicResult).join('') + '</div>';
        count = topics.length;
    } else if (filter === 'users') {
        const users = data.users || [];
        html = '<div>' + users.map(renderUserResult).join('') + '</div>';
        count = users.length;
    }

    if (count === 0) {
        container.innerHTML = '<p class="py-8 text-center font-body-md text-body-md text-on-surface-variant">No results found</p>';
    } else {
        container.innerHTML = html;
    }

    // Update count
    const countEl = document.getElementById('search-count');
    if (countEl) {
        countEl.textContent = count === 1 ? '1 result' : `${count} results`;
    }
}

function renderLoading() {
    const container = document.getElementById('search-results');
    if (!container) return;

    const skeleton = Array.from({ length: 3 }).map(() => `
        <div class="border-b border-outline-variant py-stack-lg">
            <div class="h-4 w-3/4 animate-pulse bg-surface-container"></div>
            <div class="mt-2 h-3 w-full animate-pulse bg-surface-container"></div>
            <div class="mt-3 h-3 w-32 animate-pulse bg-surface-container"></div>
        </div>
    `).join('');

    container.innerHTML = skeleton;
}

function renderError() {
    const container = document.getElementById('search-results');
    if (!container) return;

    container.innerHTML = `
        <div class="flex flex-col items-center py-10 text-center">
            <p class="font-body-md text-body-md text-on-surface-variant">Search failed</p>
            <p class="mt-1 font-body-md text-body-md text-on-surface-variant">Please try again</p>
        </div>
    `;
}

export default async function initSearchPage() {
    const root = document.getElementById('search-page');
    if (!root) return;

    const form = document.getElementById('search-form');
    const input = document.getElementById('search-input');
    if (!form || !input) return;

    let currentFilter = 'all';
    let currentQuery = '';

    // Read initial query from URL
    const params = new URLSearchParams(window.location.search);
    const initialQuery = params.get('q') || '';
    if (initialQuery) {
        input.value = initialQuery;
        currentQuery = initialQuery;
    }

    async function performSearch(query, filter) {
        if (!query.trim()) {
            document.getElementById('search-results').innerHTML = '<p class="py-8 text-center font-body-md text-body-md text-on-surface-variant">Enter a search term</p>';
            document.getElementById('search-count').textContent = '';
            return;
        }

        renderLoading();

        try {
            let data;
            if (filter === 'all') {
                const [postsRes, topicsRes, usersRes] = await Promise.allSettled([
                    searchApi.posts(query),
                    searchApi.topics(query),
                    searchApi.users(query),
                ]);

                data = {
                    posts: postsRes.status === 'fulfilled' ? (postsRes.value.data || []) : [],
                    topics: topicsRes.status === 'fulfilled' ? (topicsRes.value.data || []) : [],
                    users: usersRes.status === 'fulfilled' ? (usersRes.value.data || []) : [],
                };
            } else if (filter === 'posts') {
                const result = await searchApi.posts(query);
                data = { posts: result.data || [] };
            } else if (filter === 'topics') {
                const result = await searchApi.topics(query);
                data = { topics: result.data || [] };
            } else if (filter === 'users') {
                const result = await searchApi.users(query);
                data = { users: result.data || [] };
            }

            renderResults(data, filter);
        } catch (err) {
            renderError();
        }
    }

    // Form submit
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const query = input.value.trim();
        if (!query) return;

        currentQuery = query;

        // Update URL
        const url = new URL(window.location);
        url.searchParams.set('q', query);
        window.history.pushState({}, '', url);

        performSearch(query, currentFilter);
    });

    // Filter buttons
    const filterButtons = document.querySelectorAll('[data-search-filter]');
    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            currentFilter = btn.dataset.searchFilter;

            filterButtons.forEach(b => {
                const active = b === btn;
                b.classList.toggle('bg-primary-container', active);
                b.classList.toggle('text-on-primary-container', active);
                b.classList.toggle('text-on-surface-variant', !active);
            });

            if (currentQuery) {
                performSearch(currentQuery, currentFilter);
            }
        });
    });

    // Initial search if query exists
    if (initialQuery) {
        await performSearch(initialQuery, currentFilter);
    }
}