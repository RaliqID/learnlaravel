/**
 * Topics list controller.
 * Displays all topics with filtering and sorting.
 */

import { topicsApi } from './api/client.js';

function escapeHtml(str = '') {
    return String(str ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function truncate(str, maxLength) {
    if (!str || str.length <= maxLength) return str;
    return str.substring(0, maxLength) + '...';
}

function topicRow(topic) {
    return `
        <article class="group flex items-start justify-between gap-3 border-b border-outline-variant py-stack-lg transition-colors hover:bg-surface-container/30">
            <div class="flex min-w-0 flex-1 flex-col gap-stack-sm">
                <h3 class="font-article-title-sm text-article-title-sm text-on-background transition-colors group-hover:text-primary">
                    <a href="/topics/${escapeHtml(topic.slug)}">${escapeHtml(topic.name)}</a>
                </h3>
                ${topic.description ? `<p class="font-body-md text-body-md text-on-surface-variant line-clamp-2">${escapeHtml(truncate(topic.description, 120))}</p>` : ''}
                <div class="flex items-center gap-4 font-metadata text-metadata text-outline">
                    <span>${topic.post_count ?? 0} posts</span>
                    <span>${topic.subscriber_count ?? 0} subscribers</span>
                </div>
            </div>
            ${topic.icon ? `<img src="${escapeHtml(topic.icon)}" alt="" class="size-8 shrink-0">` : ''}
        </article>
    `;
}

function renderTopics(topics) {
    const list = document.getElementById('topics-list');
    if (!list) return;

    if (!topics || topics.length === 0) {
        list.innerHTML = '<p class="py-8 text-center font-body-md text-body-md text-on-surface-variant">No topics found</p>';
        return;
    }

    list.innerHTML = topics.map(topicRow).join('');
}

function renderError(message) {
    const list = document.getElementById('topics-list');
    if (!list) return;

    list.innerHTML = `
        <div class="flex flex-col items-center py-10 text-center">
            <p class="font-body-md text-body-md text-on-surface-variant">${escapeHtml(message)}</p>
            <button type="button" id="topics-retry" class="mt-3 border border-outline-variant px-4 py-2 font-label text-label text-on-surface transition-colors hover:bg-surface-container">Retry</button>
        </div>
    `;
}

function renderLoading() {
    const list = document.getElementById('topics-list');
    if (!list) return;

    const skeleton = Array.from({ length: 6 }).map(() => `
        <div class="border-b border-outline-variant py-stack-lg">
            <div class="h-5 w-32 animate-pulse bg-surface-container"></div>
            <div class="mt-3 h-4 w-full animate-pulse bg-surface-container"></div>
            <div class="mt-2 h-4 w-3/4 animate-pulse bg-surface-container"></div>
            <div class="mt-4 h-3 w-24 animate-pulse bg-surface-container"></div>
        </div>
    `).join('');

    list.innerHTML = skeleton;
}

async function loadTopics(sort = 'popular', isActive = null) {
    renderLoading();

    try {
        const params = { sort };
        if (isActive !== null) params.is_active = isActive;

        const { data } = await topicsApi.list(params);
        renderTopics(data?.topics || []);
    } catch (err) {
        renderError('Failed to load topics. Please try again.');
    }
}

export default async function initTopicsPage() {
    const root = document.getElementById('topics-page');
    if (!root) return;

    let currentSort = 'popular';
    let currentFilter = null;

    // Sort dropdown
    const sortSelect = document.getElementById('topics-sort');
    if (sortSelect) {
        sortSelect.addEventListener('change', (e) => {
            currentSort = e.target.value;
            loadTopics(currentSort, currentFilter);
        });
    }

    // Filter buttons (all/active)
    const navButtons = document.querySelectorAll('[data-topics-filter]');
    navButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const filter = btn.dataset.topicsFilter;
            currentFilter = filter === 'all' ? null : (filter === 'active' ? 'true' : null);

            // Update active state
            navButtons.forEach(b => {
                b.classList.toggle('bg-primary-container', b === btn);
                b.classList.toggle('text-on-primary-container', b === btn);
                b.classList.toggle('text-on-surface-variant', b !== btn);
            });

            loadTopics(currentSort, currentFilter);
        });
    });

    // Retry handler
    document.addEventListener('click', (e) => {
        if (e.target.id === 'topics-retry' || e.target.closest('#topics-retry')) {
            loadTopics(currentSort, currentFilter);
        }
    });

    // Initial load
    await loadTopics(currentSort, currentFilter);
}
