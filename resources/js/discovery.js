import { feedApi, getToken } from './api/client.js';

const API = window.LANews?.apiBase || '/api/v1';

function escapeHtml(s) { return String(s ?? '').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;'); }
function postUrl(post) { return '/posts/' + (post.slug || post.id); }
function formatDate(iso) { return iso ? new Date(iso).toLocaleDateString(undefined, { month:'short', day:'numeric', year:'numeric' }) : ''; }

function renderFeed(posts) {
    const el = document.getElementById('discovery-feed');
    if (!el) return;
    if (!posts || posts.length === 0) {
        el.innerHTML = '<p class="text-on-surface-variant text-center py-8">No posts yet. Be the first!</p>';
        return;
    }
    el.innerHTML = posts.map(p => {
        const img = p.image_url || p.thumbnail_url;
        const isNews = p.source_name;
        return `
            <article class="border-b border-outline-variant pb-6 group">
                <div class="flex items-center gap-2 mb-2">
                    <span class="font-metadata text-metadata text-on-surface-variant">${isNews ? '📰 News' : '👤 Community'}</span>
                    ${p.topic ? `<span class="text-outline-variant">·</span><span class="font-metadata text-metadata text-primary">${escapeHtml(p.topic.name)}</span>` : ''}
                    ${p.source_name ? `<span class="text-outline-variant">·</span><span class="font-metadata text-metadata text-on-surface-variant">via ${escapeHtml(p.source_name)}</span>` : ''}
                </div>
                <a href="${postUrl(p)}" class="block">
                    ${img ? `<img src="${escapeHtml(img)}" alt="" class="w-full h-auto max-h-[70vh] object-contain rounded-lg mb-3 border border-outline-variant bg-surface-container" loading="lazy" onerror="this.onerror=null;this.src='/storage/images/placeholder.svg'">` : ''}
                    <h3 class="font-headline-md text-headline-md text-on-surface group-hover:text-primary transition-colors">${escapeHtml(p.title)}</h3>
                </a>
                ${p.excerpt ? `<p class="font-body-md text-body-md text-on-surface-variant line-clamp-3 mt-1">${escapeHtml(p.excerpt)}</p>` : ''}
                <div class="flex items-center gap-4 mt-3 font-metadata text-metadata text-on-surface-variant">
                    <span>${formatDate(p.published_at)}</span>
                    ${p.vote_score !== undefined ? `<span>${p.vote_score} votes</span>` : ''}
                    ${p.comment_count !== undefined ? `<span>${p.comment_count} comments</span>` : ''}
                </div>
            </article>
        `;
    }).join('');
}

function renderLoading() {
    const el = document.getElementById('discovery-feed');
    if (!el) return;
    el.innerHTML = Array.from({ length: 3 }).map(() => `
        <div class="border-b border-outline-variant pb-6 animate-pulse">
            <div class="h-4 w-32 bg-surface-container rounded"></div>
            <div class="h-48 w-full bg-surface-container rounded mt-2"></div>
            <div class="h-6 w-3/4 bg-surface-container rounded mt-2"></div>
            <div class="h-4 w-full bg-surface-container rounded mt-1"></div>
        </div>
    `).join('');
}

async function loadFeed(type = 'all') {
    renderLoading();
    try {
        let payload;
        if (type === 'news') {
            payload = await feedApi.feed({ sort: 'new', per_page: 10 });
        } else if (type === 'community') {
            // For community posts, we need a separate endpoint. For now, use feed with source_name null.
            // We'll filter client-side or add backend param. Simpler: use feed and filter by source_name null.
            payload = await feedApi.feed({ sort: 'new', per_page: 20 });
            // filter community posts (no source_name)
            const posts = payload.data.posts.filter(p => !p.source_name);
            renderFeed(posts);
            return;
        } else {
            payload = await feedApi.feed({ sort: 'new', per_page: 20 });
        }
        renderFeed(payload.data.posts || []);
    } catch (err) {
        document.getElementById('discovery-feed').innerHTML = '<p class="text-on-surface-variant text-center py-8">Failed to load feed. Please retry.</p>';
    }
}

export default function initDiscoveryPage() {
    const root = document.getElementById('discovery-page');
    if (!root) return;

    let currentTab = 'all';

    const tabs = document.querySelectorAll('[data-discovery-tab]');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            currentTab = tab.dataset.discoveryTab;
            tabs.forEach(t => {
                const active = t === tab;
                t.classList.toggle('bg-primary-container', active);
                t.classList.toggle('text-on-primary-container', active);
                t.classList.toggle('text-on-surface-variant', !active);
                t.setAttribute('aria-selected', active);
            });
            loadFeed(currentTab);
        });
    });

    loadFeed(currentTab);
}