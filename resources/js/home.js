/**
 * Homepage controller (UI-01). Small, focused module.
 * Loads each section once via verified endpoints.
 */

import { feedApi } from './api/client.js';
import { renderCategoryChart } from './category-chart.js';

function escapeHtml(str = '') {
    return String(str ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

/* Link to post detail using slug when available (SEO), fallback id. */
function postUrl(post) {
    return '/posts/' + (post.slug || post.id);
}

function formatDate(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
}

function topicHtml(topic) {
    if (!topic) return '';
    return `<span class="uppercase tracking-widest">${escapeHtml(topic.name)}</span>`;
}

function authorHtml(user) {
    if (!user) return '';
    return `<span>By ${escapeHtml(user.display_name || user.username || '')}</span>`;
}

function renderOrEmpty(el, html, emptyMessage) {
    el.innerHTML = html || `<p class="font-body-md text-body-md text-on-surface-variant">${escapeHtml(emptyMessage)}</p>`;
}

function renderError(el, message, retry) {
    const btn = retry ? `<button type="button" class="mt-4 px-4 py-2 border border-outline-variant text-on-surface-variant hover:text-on-surface transition-colors" data-retry="${retry}">Retry</button>` : '';
    el.innerHTML = `
        <div class="flex flex-col items-start py-8">
            <p class="font-body-md text-body-md text-on-background">Couldn't load ${escapeHtml(message)}.</p>
            ${btn}
        </div>
    `;
}

/* ------------------------------------------------------------------ */
/* HERO                                                                */
/* ------------------------------------------------------------------ */

function renderHero(data) {
    const root = document.getElementById('home-hero');
    if (!root) return;

    const { headline, secondary } = data;
    laneState.secondary = secondary || [];

    if (!headline) {
        root.style.display = 'none';
        renderLane();
        return;
    }
    
    // Fallbacks
    const imageSrc = headline.image_url || headline.thumbnail_url;
    const authorSection = headline.user ? `
        <div class="font-metadata text-metadata text-on-surface-variant mt-stack-sm flex items-center gap-2">
            ${authorHtml(headline.user)}
            <span class="w-[1px] h-3 bg-outline-variant"></span>
            <span>${formatDate(headline.published_at)}</span>
        </div>
    ` : '';

    const sourceHtml = headline.source_name ? `<span class="font-metadata text-metadata text-on-surface-variant">via ${escapeHtml(headline.source_name)}</span>` : '';
    
    const heroHtml = `
        <article class="flex flex-col gap-[16px] group cursor-pointer">
            <a href="${postUrl(headline)}" class="relative w-full aspect-[4/3] md:aspect-[16/9] overflow-hidden border border-outline-variant bg-surface-container-low">
                ${imageSrc ? `<img alt="${escapeHtml(headline.title)}" class="object-cover w-full h-full group-hover:scale-[1.02] transition-transform duration-700 ease-out" src="${escapeHtml(imageSrc)}" loading="eager" decoding="async" onerror="this.onerror=null;this.src='/storage/images/placeholder.svg'">` : '<div class="w-full h-full bg-surface-container flex items-center justify-center text-on-surface-variant">No image</div>'}
            </a>
            <div class="flex flex-col gap-[8px] pr-0 md:pr-12">
                <div class="flex items-center gap-[8px]">
                    <span class="font-label-sm text-label-sm text-primary tracking-widest uppercase">${headline.topic ? escapeHtml(headline.topic.name) : ''}</span>
                    ${sourceHtml ? `<span class="text-outline-variant">•</span>${sourceHtml}` : ''}
                </div>
                <a href="${postUrl(headline)}">
                    <h1 class="font-headline-lg-mobile md:font-display-xl text-headline-lg-mobile md:text-display-xl text-on-surface tracking-tight group-hover:text-primary transition-colors duration-300">
                        ${escapeHtml(headline.title)}
                    </h1>
                </a>
                ${headline.excerpt ? `<p class="font-body-md md:font-body-lg text-body-md md:text-body-lg text-on-surface-variant max-w-[720px] mt-2">${escapeHtml(headline.excerpt)}</p>` : ''}
                ${authorSection}
            </div>
        </article>
    `;

    root.innerHTML = heroHtml;
    renderSecondary(laneState.secondary);
    renderLane();
}

/* ------------------------------------------------------------------ */
/* SECONDARY STORIES (Right column in hero section)                    */
/* ------------------------------------------------------------------ */

function renderSecondary(posts) {
    const root = document.getElementById('home-secondary');
    if (!root) return;

    const cards = (posts || []).slice(0, 4).map((post, i) => {
        const imageSrc = post.image_url || post.thumbnail_url;
        const isLast = i === 3 || i === (posts.length - 1);
        return `
            <article class="group cursor-pointer flex flex-col gap-stack-sm pb-gutter ${isLast ? '' : 'border-b border-outline-variant'} h-full justify-between">
                <div class="flex flex-col gap-stack-sm">
                    <div class="relative w-full aspect-video overflow-hidden bg-surface-container-low mb-2">
                        ${imageSrc ? `<img class="object-cover w-full h-full group-hover:scale-[1.02] transition-transform duration-700 ease-out" alt="${escapeHtml(post.title)}" src="${escapeHtml(imageSrc)}" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='/storage/images/placeholder.svg'">` : '<div class="w-full h-full bg-surface-container flex items-center justify-center text-on-surface-variant">No image</div>'}
                    </div>
                    <div class="flex items-center gap-stack-sm">
                        <span class="font-metadata text-metadata text-primary tracking-widest uppercase">${post.topic ? escapeHtml(post.topic.name) : ''}</span>
                    </div>
                    <a href="${postUrl(post)}">
                        <h2 class="font-article-title-sm text-article-title-sm text-on-background group-hover:text-primary transition-colors leading-tight">${escapeHtml(post.title)}</h2>
                    </a>
                    ${post.excerpt ? `<p class="font-body-md text-body-md text-on-surface-variant line-clamp-2 mt-1">${escapeHtml(post.excerpt)}</p>` : ''}
                </div>
            </article>
        `;
    }).join('');

    renderOrEmpty(root, cards, 'No secondary stories available.');
}

/* ------------------------------------------------------------------ */
/* TRENDING                                                            */
/* ------------------------------------------------------------------ */

let laneState = { trending: [], secondary: [] };

function laneRowHtml(p, i) {
    return `
        <article class="group cursor-pointer flex gap-[16px] items-start">
            <span class="font-headline-md text-headline-md text-surface-variant font-bold tabular-nums">${String(i + 1).padStart(2, '0')}</span>
            <div class="flex flex-col gap-1">
                <a href="${postUrl(p)}">
                    <h5 class="font-body-lg text-body-lg text-on-surface group-hover:text-primary transition-colors leading-tight">${escapeHtml(p.title)}</h5>
                </a>
                <span class="font-label-sm text-label-sm text-on-surface-variant">${p.topic ? escapeHtml(p.topic.name) : 'News'}</span>
            </div>
        </article>
    `;
}

function renderLane() {
    const root = document.getElementById('home-trending');
    if (!root) return;

    const seen = new Set();
    const rows = [];
    [...laneState.trending].forEach(p => {
        if (!p || seen.has(p.id)) return;
        seen.add(p.id);
        rows.push(p);
    });

    renderOrEmpty(root, rows.slice(0, 4).map((p, i) => laneRowHtml(p, i)).join(''), 'Nothing trending right now.');
}

function renderTrending(posts) {
    laneState.trending = posts || [];
    renderLane();
}

/* ------------------------------------------------------------------ */
/* LATEST / FEED                                                       */
/* ------------------------------------------------------------------ */

async function loadLatest() {
    const root = document.getElementById('feed-posts');
    if (!root) return;
    
    // Skeleton
    root.innerHTML = Array.from({ length: 3 }).map(() => `
        <article class="py-[16px] border-b border-outline-variant/50 flex gap-[16px]">
            <div class="flex-1 h-20 bg-surface-container animate-pulse"></div>
            <div class="w-[240px] aspect-video bg-surface-container animate-pulse"></div>
        </article>
    `).join('');

    try {
        const payload = await feedApi.feed({ sort: 'new', per_page: 6, source: 'news' });
        const posts = payload.data.posts || [];
        
        const cards = posts.map(post => {
            const imageSrc = post.thumbnail_url || post.image_url;
            const sourceHtml = post.source_name ? `<span class="font-label-sm text-label-sm text-on-surface-variant">via ${escapeHtml(post.source_name)}</span>` : '';
            return `
                <article class="py-[16px] border-b border-outline-variant/50 group cursor-pointer flex flex-col md:flex-row gap-[16px] md:gap-[24px] items-start">
                    <div class="flex-1 flex flex-col gap-[8px] order-2 md:order-1">
                        <div class="flex items-center gap-[8px]">
                            <span class="font-label-sm text-label-sm text-primary tracking-widest uppercase">${post.topic ? escapeHtml(post.topic.name) : ''}</span>
                            <span class="text-outline-variant">•</span>
                            <span class="font-label-sm text-label-sm text-on-surface-variant">${formatDate(post.published_at)}</span>
                        </div>
                        <a href="${postUrl(post)}">
                            <h4 class="font-headline-md text-headline-md text-on-surface group-hover:text-primary transition-colors">${escapeHtml(post.title)}</h4>
                        </a>
                        ${post.excerpt ? `<p class="font-body-md text-body-md text-on-surface-variant line-clamp-2 mt-1">${escapeHtml(post.excerpt)}</p>` : ''}
                    </div>
                    <div class="w-full md:w-[240px] aspect-video border border-outline-variant overflow-hidden bg-surface-container-low shrink-0 order-1 md:order-2">
                        ${imageSrc ? `<img alt="${escapeHtml(post.title)}" class="object-cover w-full h-full group-hover:opacity-80 transition-opacity" src="${escapeHtml(imageSrc)}" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='/storage/images/placeholder.svg'">` : '<div class="w-full h-full bg-surface-container flex items-center justify-center text-on-surface-variant text-sm">No image</div>'}
                    </div>
                </article>
            `;
        }).join('');

        renderOrEmpty(root, cards, 'No stories yet — check back soon.');
    } catch (err) {
        renderError(root, 'latest dispatches', 'feed');
    }
}


/* ------------------------------------------------------------------ */
/* CATEGORY SECTIONS                                                    */
/* ------------------------------------------------------------------ */

function sectionPostCard(post) {
    const imageSrc = post.thumbnail_url || post.image_url;
    const sourceHtml = post.source_name ? `<span class="font-metadata text-metadata text-on-surface-variant">via ${escapeHtml(post.source_name)}</span>` : '';
    return `
        <article class="flex flex-col gap-stack-md group border-t border-outline-variant pt-stack-md">
            <a href="${postUrl(post)}" class="w-full aspect-[4/3] overflow-hidden bg-surface-container relative mb-stack-sm">
                ${imageSrc ? `<img alt="${escapeHtml(post.title)}" class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-700 ease-out" src="${escapeHtml(imageSrc)}" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='/storage/images/placeholder.svg'">` : '<div class="w-full h-full bg-surface-container flex items-center justify-center text-on-surface-variant">No image</div>'}
            </a>
            <a href="${postUrl(post)}">
                <h4 class="font-article-title-sm text-article-title-sm text-on-background group-hover:text-primary transition-colors">
                    ${escapeHtml(post.title)}
                </h4>
            </a>
            ${post.excerpt ? `<p class="font-body-md text-body-md text-on-surface-variant line-clamp-3">${escapeHtml(post.excerpt)}</p>` : ''}
            ${sourceHtml}
        </article>
    `;
}

function renderSections(sections) {
    const root = document.getElementById('home-sections');
    if (!root) return;

    const html = (sections || []).map(section => {
        const topic = section.topic;
        if (!topic || !(section.posts || []).length) return '';

        return `
            <section class="mt-section-gap" aria-labelledby="section-${topic.id}">
                <div class="flex items-end justify-between border-b border-outline-variant pb-stack-sm mb-content-gap">
                    <h3 id="section-${topic.id}" class="font-section-title text-section-title text-on-background">${escapeHtml(topic.name)}</h3>
                    <a href="${'/topics/' + topic.slug}" class="flex items-center gap-1 font-metadata text-metadata text-on-surface-variant hover:text-primary uppercase tracking-widest transition-colors">
                        <span>Read more</span>
                        <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                    </a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-gutter">
                    ${section.posts.slice(0, 4).map(sectionPostCard).join('')}
                </div>
            </section>
        `;
    }).join('');

    renderOrEmpty(root, html, 'No category sections available.');
}

async function loadSections() {
    const root = document.getElementById('home-sections');
    if (!root) return;

    try {
        const payload = await feedApi.sections();
        renderSections(payload.data?.sections || []);
    } catch (err) {
        renderError(root, 'category sections', 'sections');
    }
}

/* ------------------------------------------------------------------ */
/* CATEGORIES                                                           */
/* ------------------------------------------------------------------ */

function renderCategories(categories) {
    const root = document.getElementById('home-categories');
    if (!root) return;

    const chips = (categories || []).map(c => `
        <a class="px-4 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface font-metadata text-metadata transition-colors" href="/topics/${c.slug}">
            ${escapeHtml(c.name)}
        </a>
    `).join('');

    renderOrEmpty(root, chips, 'No categories yet.');
}

/* ------------------------------------------------------------------ */
/* DEEP DIVES                                                          */
/* ------------------------------------------------------------------ */

function renderDeepDives(posts) {
    const root = document.getElementById('home-deepdives');
    if (!root) return;

    const list = (posts || []).slice(0, 3);
    if (!list.length) {
        renderOrEmpty(root, '', 'No deep dive articles yet.');
        return;
    }

    const main = list[0];
    const mainImg = main.image_url || main.thumbnail_url;
    const mainHtml = `
        <div class="md:col-span-7 group cursor-pointer flex flex-col gap-[16px]">
            <a href="${postUrl(main)}" class="relative w-full aspect-[4/3] md:aspect-square overflow-hidden border border-outline-variant bg-surface-container-low">
                ${mainImg ? `<img alt="${escapeHtml(main.title)}" class="object-cover w-full h-full group-hover:scale-[1.02] transition-transform duration-700 ease-out" src="${escapeHtml(mainImg)}" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='/storage/images/placeholder.svg'">` : '<div class="w-full h-full bg-surface-container flex items-center justify-center text-on-surface-variant text-sm">No image</div>'}
            </a>
            <div class="flex flex-col gap-[8px]">
                <span class="font-label-sm text-label-sm text-primary tracking-widest uppercase">${main.topic ? escapeHtml(main.topic.name) : 'Featured'}</span>
                <a href="${postUrl(main)}">
                    <h2 class="font-headline-lg-mobile md:font-display-xl text-headline-lg-mobile md:text-display-xl text-on-surface tracking-tight group-hover:text-primary transition-colors">${escapeHtml(main.title)}</h2>
                </a>
                ${main.excerpt ? `<p class="font-body-md md:font-body-lg text-body-md md:text-body-lg text-on-surface-variant mt-2">${escapeHtml(main.excerpt)}</p>` : ''}
            </div>
        </div>
    `;

    const sideHtml = list.slice(1).map((post, i) => {
        const img = post.image_url || post.thumbnail_url;
        const border = i > 0 ? 'pt-4 border-t border-outline-variant/50' : '';
        return `
            <div class="group cursor-pointer flex flex-col gap-[8px] ${border}">
                ${img ? `
                    <a href="${postUrl(post)}" class="relative w-full aspect-video border border-outline-variant bg-surface-container-low mb-2 overflow-hidden">
                        <img alt="${escapeHtml(post.title)}" class="object-cover w-full h-full group-hover:scale-[1.02] transition-transform duration-700 ease-out" src="${escapeHtml(img)}" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='/storage/images/placeholder.svg'">
                    </a>
                ` : ''}
                <span class="font-label-sm text-label-sm text-primary tracking-widest uppercase">${post.topic ? escapeHtml(post.topic.name) : ''}</span>
                <a href="${postUrl(post)}">
                    <h3 class="font-headline-md text-headline-md text-on-surface leading-tight group-hover:text-primary transition-colors">${escapeHtml(post.title)}</h3>
                </a>
                ${post.excerpt ? `<p class="font-body-md text-body-md text-on-surface-variant mt-1 line-clamp-2">${escapeHtml(post.excerpt)}</p>` : ''}
            </div>
        `;
    }).join('');

    root.innerHTML = mainHtml + `<div class="md:col-span-5 flex flex-col gap-[24px]">${sideHtml}</div>`;
}

/* ------------------------------------------------------------------ */
/* INDUSTRY PERSPECTIVES                                               */
/* ------------------------------------------------------------------ */

function renderIndustryPerspectives(posts) {
    const root = document.getElementById('home-industry');
    if (!root) return;

    const cards = (posts || []).slice(0, 3).map(post => {
        const author = post.user;
        const authorName = author ? (author.display_name || author.username) : post.source_name || 'Staff';
        const avatar = author?.avatar;
        return `
            <a href="${postUrl(post)}" class="group cursor-pointer flex flex-col gap-[16px] p-6 bg-surface-container-low border border-outline-variant hover:border-primary transition-colors">
                <div class="flex items-center gap-[8px]">
                    ${avatar
                        ? `<img src="${escapeHtml(avatar)}" alt="${escapeHtml(authorName)}" class="w-10 h-10 rounded-full object-cover border border-outline-variant" onerror="this.style.display='none'">`
                        : `<div class="w-10 h-10 rounded-full bg-surface-container border border-outline-variant flex items-center justify-center text-on-surface-variant text-sm font-bold">${escapeHtml(authorName.charAt(0).toUpperCase())}</div>`
                    }
                    <div class="flex flex-col">
                        <span class="font-label-sm text-label-sm text-on-surface">${escapeHtml(authorName)}</span>
                        <span class="font-label-sm text-[10px] text-on-surface-variant uppercase tracking-widest">${post.topic ? escapeHtml(post.topic.name) : 'Analysis'}</span>
                    </div>
                </div>
                <h4 class="font-headline-md text-headline-md text-on-surface group-hover:text-primary transition-colors">${escapeHtml(post.title)}</h4>
                ${post.excerpt ? `<p class="font-body-md text-body-md text-on-surface-variant line-clamp-3">${escapeHtml(post.excerpt)}</p>` : ''}
            </a>
        `;
    }).join('');

    renderOrEmpty(root, cards, 'No perspectives yet.');
}

/* ------------------------------------------------------------------ */
/* GLOBAL INTELLIGENCE                                                 */
/* ------------------------------------------------------------------ */

function renderGlobalIntelligence(posts) {
    const root = document.getElementById('home-global');
    if (!root) return;

    const cards = (posts || []).slice(0, 6).map(post => {
        const img = post.thumbnail_url || post.image_url;
        return `
            <a href="${postUrl(post)}" class="group cursor-pointer flex flex-col gap-[8px]">
                <div class="relative w-full aspect-video border border-outline-variant bg-surface-container-low overflow-hidden">
                    ${img ? `<img alt="${escapeHtml(post.title)}" class="object-cover w-full h-full group-hover:scale-[1.02] transition-transform duration-700 ease-out" src="${escapeHtml(img)}" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='/storage/images/placeholder.svg'">` : '<div class="w-full h-full bg-surface-container flex items-center justify-center text-on-surface-variant text-sm">No image</div>'}
                </div>
                <div class="flex flex-col mt-2">
                    <span class="font-label-sm text-label-sm text-primary uppercase tracking-widest mb-1">${post.topic ? escapeHtml(post.topic.name) : 'News'}</span>
                    <h4 class="font-body-lg text-body-lg font-medium text-on-surface leading-snug group-hover:text-primary transition-colors">${escapeHtml(post.title)}</h4>
                </div>
            </a>
        `;
    }).join('');

    renderOrEmpty(root, cards, 'No global intelligence yet.');
}

/* ------------------------------------------------------------------ */
/* THE ARCHIVE                                                         */
/* ------------------------------------------------------------------ */

function renderArchive(posts) {
    const root = document.getElementById('home-archive');
    if (!root) return;

    const rows = (posts || []).slice(0, 4).map(post => {
        const year = post.published_at ? new Date(post.published_at).getFullYear() : '';
        return `
            <a href="${postUrl(post)}" class="group cursor-pointer flex flex-col md:flex-row md:items-center justify-between py-[16px] border-b border-outline-variant/30 gap-[8px]">
                <h4 class="font-headline-md text-headline-md text-on-surface md:w-2/3 group-hover:text-primary transition-colors">${escapeHtml(post.title)}</h4>
                <div class="flex items-center gap-[8px] md:w-1/3 md:justify-end">
                    <span class="font-label-sm text-label-sm text-outline-variant">${year}</span>
                    <span class="text-outline-variant/50">•</span>
                    <span class="font-label-sm text-label-sm text-on-surface-variant uppercase">${post.topic ? escapeHtml(post.topic.name) : ''}</span>
                </div>
            </a>
        `;
    }).join('');

    renderOrEmpty(root, rows, 'No archive items yet.');
}

/* ------------------------------------------------------------------ */
/* RECOMMENDED (hidden container — kept for API contract)              */
/* ------------------------------------------------------------------ */

function renderRecommended(posts) {
    const root = document.getElementById('home-recommended');
    if (!root) return;
    renderOrEmpty(root, (posts || []).slice(0, 6).map(p => `<a href="${postUrl(p)}" class="block text-on-background">${escapeHtml(p.title)}</a>`).join(''), 'Nothing to recommend yet.');
}

/* ------------------------------------------------------------------ */
/* EDITOR'S PICK (hidden container — kept for API contract)            */
/* ------------------------------------------------------------------ */

function renderEditors(posts) {
    const root = document.getElementById('home-editors');
    if (!root) return;
    renderOrEmpty(root, (posts || []).slice(0, 3).map(p => `<a href="${postUrl(p)}" class="block text-on-background">${escapeHtml(p.title)}</a>`).join(''), 'No editor picks yet.');
}

/* ------------------------------------------------------------------ */
/* REFRESH                                                             */
/* ------------------------------------------------------------------ */

async function refreshHomeData() {
    const promises = [];
    
    // Hero
    promises.push(
        feedApi.hero()
            .then(payload => renderHero(payload.data))
            .catch(() => {
                const hero = document.getElementById('home-hero');
                if (hero) renderError(hero, 'main story', 'hero');
            })
    );
    
    // Trending
    promises.push(
        feedApi.trending({ time: 'day' })
            .then(payload => renderTrending(payload.data.trending))
            .catch(() => {
                const trending = document.getElementById('home-trending');
                if (trending) renderError(trending, 'trending', 'trending');
            })
    );
    
    // Latest
    promises.push(loadLatest());
    
    // Sections
    promises.push(loadSections());
    
    // Categories
    promises.push(
        feedApi.categories()
            .then(payload => renderCategories(payload.data.categories))
            .catch(() => {
                const cats = document.getElementById('home-categories');
                if (cats) renderError(cats, 'categories', 'categories');
            })
    );
    
    await Promise.allSettled(promises);
    
    // Update latestPostId after refresh
    try {
        const payload = await feedApi.feed({ sort: 'new', per_page: 1, source: 'news' });
        latestPostId = payload.data.posts?.[0]?.id ?? null;
    } catch (e) { /* ignore */ }
}

/* ------------------------------------------------------------------ */
/* BOOT                                                                */
/* ------------------------------------------------------------------ */

export default async function initHomepage() {
    const sectionErrors = [];

    // Attach retry handler
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-retry]');
        if (!btn) return;
        if (btn.dataset.retry === 'feed') loadLatest();
        if (btn.dataset.retry === 'sections') loadSections();
        if (btn.dataset.retry === 'hero') {
            feedApi.hero()
                .then(payload => renderHero(payload.data))
                .catch(() => {
                    const hero = document.getElementById('home-hero');
                    if (hero) renderError(hero, 'main story', 'hero');
                });
        }
        if (btn.dataset.retry === 'trending') {
            feedApi.trending({ time: 'day' })
                .then(payload => renderTrending(payload.data.trending))
                .catch(() => {
                    const trending = document.getElementById('home-trending');
                    if (trending) renderError(trending, 'trending', 'trending');
                });
        }
        if (btn.dataset.retry === 'categories') {
            feedApi.categories()
                .then(payload => renderCategories(payload.data.categories))
                .catch(() => {
                    const cats = document.getElementById('home-categories');
                    if (cats) renderError(cats, 'categories', 'categories');
                });
        }
    });

    try {
        const payload = await feedApi.hero();
        renderHero(payload.data);
    } catch (e) {
        sectionErrors.push('hero');
        const hero = document.getElementById('home-hero');
        if (hero) renderError(hero, 'main story', 'hero');
    }

    try {
        const payload = await feedApi.trending({ time: 'day' });
        renderTrending(payload.data.trending);
    } catch (e) {
        sectionErrors.push('trending');
        const trending = document.getElementById('home-trending');
        if (trending) renderError(trending, 'trending', 'trending');
    }

    loadLatest();

    try {
        await loadSections();
    } catch (e) {
        sectionErrors.push('sections');
        renderError(document.getElementById('home-sections'), 'category sections', 'sections');
    }

    try {
        const payload = await feedApi.recommended();
        renderRecommended(payload.data.posts);
    } catch (e) {
        sectionErrors.push('recommended');
        renderError(document.getElementById('home-recommended'), 'recommended stories', null);
    }

    try {
        const payload = await feedApi.categories();
        renderCategories(payload.data.categories);
    } catch (e) {
        sectionErrors.push('categories');
        renderError(document.getElementById('home-categories'), 'categories', 'categories');
    }

    try {
        const payload = await feedApi.editorsPicks();
        renderEditors(payload.data.posts);
    } catch (e) {
        sectionErrors.push('editors');
        renderError(document.getElementById('home-editors'), "editor's picks", null);
    }

    // Deep Dives
    try {
        const payload = await feedApi.feed({ sort: 'top', per_page: 3, time: 'week', source: 'news' });
        renderDeepDives(payload.data.posts);
    } catch (e) {
        sectionErrors.push('deepdives');
        renderError(document.getElementById('home-deepdives'), 'deep dives', null);
    }

    // Category Distribution Chart (Stacked Area)
    const categoryChartContainer = document.getElementById('home-chart-category');
    if (categoryChartContainer) {
        try {
            await renderCategoryChart(categoryChartContainer, feedApi.categoryDistribution);
        } catch (e) {
            categoryChartContainer.innerHTML = `<p class="text-on-surface-variant text-sm">Could not load category chart.</p>`;
        }
    }

    // Archive
    try {
        const payload = await feedApi.feed({ sort: 'new', per_page: 4, source: 'news' });
        renderArchive(payload.data.posts);
    } catch (e) {
        sectionErrors.push('archive');
        renderError(document.getElementById('home-archive'), 'archive', null);
    }

    // Global Intelligence
    try {
        const payload = await feedApi.feed({ sort: 'new', per_page: 6, source: 'news' });
        renderGlobalIntelligence(payload.data.posts);
    } catch (e) {
        sectionErrors.push('global');
        renderError(document.getElementById('home-global'), 'global intelligence', null);
    }

    // Initialize for new content detection
    try {
        const payload = await feedApi.feed({ sort: 'new', per_page: 1, source: 'news' });
        latestPostId = payload.data.posts?.[0]?.id ?? null;
    } catch (e) { /* ignore */ }

    return sectionErrors;
}

let lastCheckTimestamp = Date.now();
let latestPostId = null;

async function checkForNewContent() {
    try {
        const payload = await feedApi.feed({ sort: 'new', per_page: 1, source: 'news' });
        const newest = payload.data.posts?.[0];
        
        if (!newest) return;
        
        // Initialize on first check
        if (latestPostId === null) {
            latestPostId = newest.id;
            return;
        }
        
        // New content available — show banner, do NOT auto-reload.
        // latestPostId updates only after the user clicks "Load new stories".
        if (newest.id !== latestPostId) {
            showNewContentBanner();
        }
    } catch (e) {
        // Silently fail - don't interrupt user experience
    }
}

function showNewContentBanner() {
    const existing = document.getElementById('new-content-banner');
    if (existing) return; // Already showing
    
    const banner = document.createElement('div');
    banner.id = 'new-content-banner';
    banner.className = 'fixed top-20 left-1/2 -translate-x-1/2 z-50 bg-primary text-on-primary px-6 py-3 shadow-lg flex items-center gap-3 animate-slide-down';
    banner.innerHTML = `
        <span class="font-label text-label">New stories available</span>
        <button type="button" id="load-new-stories-btn" class="bg-on-primary text-primary px-4 py-1 font-label text-label hover:opacity-90 transition-opacity">
            Load new stories
        </button>
        <button type="button" id="dismiss-banner-btn" class="ml-2 text-on-primary hover:opacity-70">
            <span class="material-symbols-outlined text-sm">close</span>
        </button>
    `;
    document.body.appendChild(banner);
    
    // Load new stories button
    document.getElementById('load-new-stories-btn')?.addEventListener('click', async () => {
        await refreshHomeData();
        banner.remove();
    });
    
    // Dismiss button
    document.getElementById('dismiss-banner-btn')?.addEventListener('click', () => {
        banner.remove();
    });
}

// Start polling every 5 minutes (300000ms)
setInterval(checkForNewContent, 300000);
// Initial check after 5 minutes
setTimeout(checkForNewContent, 300000);