/**
 * Post Detail controller (UI-02).
 * Renders the article, action toolbar (vote/bookmark/share), comments, related.
 * Uses only verified endpoints + fields actually present in PostDetailResource.
 */

import { postsApi, commentsApi, voteApi, bookmarkApi } from './api/client.js';

const ROOT = document.getElementById('post-page');

/* ------------------------------------------------------------------ */
/* Utilities                                                           */
/* ------------------------------------------------------------------ */

function escapeHtml(str = '') {
    return String(str ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function relativeTime(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    const diff = Math.floor((Date.now() - d.getTime()) / 1000);
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
    return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
}

function showSection(id, show) {
    const el = document.getElementById(id);
    if (el) el.style.display = show ? '' : 'none';
}

/* ------------------------------------------------------------------ */
/* Vote                                                                */
/* ------------------------------------------------------------------ */

function bindVote(postId, initial) {
    const up = document.getElementById('vote-up');
    const down = document.getElementById('vote-down');
    const scoreEl = document.getElementById('vote-score');

    if (!up || !down || !scoreEl) return;

    let userVote = initial || null;
    const render = () => {
        const score = scoreEl.dataset.score ?? '0';
        scoreEl.textContent = score;
        up.classList.toggle('text-primary', userVote === 1);
        up.classList.toggle('bg-primary-container/15', userVote === 1);
        down.classList.toggle('text-primary', userVote === -1);
        down.classList.toggle('bg-primary-container/15', userVote === -1);
    };

    const click = async (dir) => {
        if (window.LANews.auth !== true) {
            showLoginHint();
            return;
        }
        if (up.disabled) return;
        up.disabled = true; down.disabled = true;
        try {
            let result;
            if (userVote === dir) {
                result = await voteApi.remove(postId);
                userVote = null;
            } else {
                result = dir === 1 ? await voteApi.upvote(postId) : await voteApi.downvote(postId);
                userVote = result.data.user_vote ?? dir;
            }
            scoreEl.dataset.score = String(result.data.vote_score);
            render();
        } catch (e) {
            /* keep stored score — no misleading optimistic UI */
            if (window.laranewsToast) window.laranewsToast.error('Vote failed');
        } finally {
            up.disabled = false; down.disabled = false;
        }
    };

    up.addEventListener('click', () => click(1));
    down.addEventListener('click', () => click(-1));

    /* Do not disable for guests — let the click show the login hint. */
    render();
}

/* ------------------------------------------------------------------ */
/* Bookmark                                                            */
/* ------------------------------------------------------------------ */

function bindBookmark(postId) {
    const btn = document.getElementById('bookmark-btn');
    const label = document.getElementById('bookmark-label');
    const loginHint = document.getElementById('login-hint');
    if (!btn) return;

    let state = 'off';

    const render = () => {
        btn.classList.toggle('text-primary', state === 'on');
        btn.classList.toggle('bg-primary-container/15', state === 'on');
        if (label) label.textContent = state === 'on' ? 'Saved' : 'Save';
    };

    /* Verify real initial state for authenticated users (no is_bookmarked field
       in the detail resource) — fetch the user's bookmark list once. */
    async function resolveInitialState() {
        if (window.LANews.auth !== true) return;
        try {
            const { data } = await bookmarkApi.list({ per_page: 100 });
            const ids = (data || []).map(b => Number(b.post_id || b.post?.id));
            state = ids.includes(Number(postId)) ? 'on' : 'off';
        } catch (_) {
            state = 'off';
        }
        render();
    }

    btn.addEventListener('click', async () => {
        if (window.LANews.auth !== true) {
            if (loginHint) loginHint.classList.remove('hidden');
            return;
        }
        btn.disabled = true;
        const previous = state;
        try {
            if (state === 'on') {
                await bookmarkApi.destroy(postId);
                state = 'off';
            } else {
                await bookmarkApi.store(postId);
                state = 'on';
            }
            render();
        } catch (e) {
            state = previous; /* revert on failure — no misleading optimistic UI */
            if (window.laranewsToast) window.laranewsToast.error('Could not update bookmark');
        } finally {
            btn.disabled = false;
        }
    });

    resolveInitialState();
}

/* ------------------------------------------------------------------ */
/* Share                                                               */
/* ------------------------------------------------------------------ */

function bindShare() {
    const btn = document.getElementById('share-btn');
    if (!btn) return;

    btn.addEventListener('click', async () => {
        const url = window.location.href;
        const title = document.querySelector('h1')?.textContent || 'LaraNews';

        if (navigator.share) {
            try {
                await navigator.share({ title, url });
                return;
            } catch (e) {
                /* cancelled or unavailable */
            }
        }

        // Fallback: copy link + toast
        try {
            await navigator.clipboard.writeText(url);
            if (window.laranewsToast) window.laranewsToast.success('Link copied');
        } catch (e) {
            if (window.laranewsToast) window.laranewsToast.error('Could not copy link');
        }
    });
}

/* ------------------------------------------------------------------ */
/* Article                                                             */
/* ------------------------------------------------------------------ */

function renderArticle(post, related, navigation) {
    const content = document.getElementById('post-content');
    if (!content) return;

    const topic = post.topic;
    const author = post.user;

    document.title = `${post.title} — LaraNews`;
    document.querySelector('meta[name="description"]')?.setAttribute('content', post.meta_description || post.excerpt || '');
    document.querySelector('link[rel="canonical"]')?.setAttribute('href', window.location.href);
    document.querySelector('meta[property="og:title"]')?.setAttribute('content', post.title);
    document.querySelector('meta[property="og:description"]')?.setAttribute('content', post.meta_description || post.excerpt || '');
    if (post.image_url) document.querySelector('meta[property="og:image"]')?.setAttribute('content', post.image_url);

    // Breadcrumb / topic
    const breadcrumb = document.getElementById('post-breadcrumb');
    if (breadcrumb) {
        breadcrumb.innerHTML = topic
            ? `<a href="${'/topics/' + topic.slug}" class="text-sm font-medium text-on-surface-variant hover:text-on-background">${escapeHtml(topic.name)}</a>`
            : '';
    }

    // Title + dek
    document.getElementById('post-title').textContent = post.title;
    document.getElementById('post-dek').textContent = post.excerpt || '';

    // Author + metadata
    const meta = document.getElementById('post-meta');
    if (meta) {
        meta.innerHTML = `
            ${author ? `
                <a href="${'/users/' + author.username}" class="flex items-center gap-2.5">
                    <span class="size-9 rounded-full bg-surface-container-high flex items-center justify-center text-xs font-semibold text-on-surface-variant overflow-hidden">
                        ${author.avatar ? `<img src="${escapeHtml(author.avatar)}" alt="" class="size-full object-cover" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='/storage/images/placeholder.svg'">` : escapeHtml((author.display_name || author.username || '?')[0].toUpperCase())}
                    </span>
                    <span class="text-sm font-medium text-on-background">${escapeHtml(author.display_name || author.username)}</span>
                </a>
            ` : ''}
            <div class="text-sm text-outline">
                ${post.published_at ? `<span>${relativeTime(post.published_at)}</span>` : ''}
                ${post.reading_time ? `<span> · ${post.reading_time} min read</span>` : ''}
                ${post.edited_at ? `<span> · edited</span>` : ''}
            </div>
        `;
    }

    // Source attribution
    const sourceEl = document.getElementById('post-source');
    if (sourceEl && post.source_name && post.source_url) {
        sourceEl.innerHTML = `Source: <a href="${escapeHtml(post.source_url)}" target="_blank" rel="noopener noreferrer" class="text-primary hover:underline">${escapeHtml(post.source_name)}</a>`;
        sourceEl.classList.remove('hidden');
    } else if (sourceEl) {
        sourceEl.classList.add('hidden');
    }

    // Hero image — natural aspect ratio, responsive to any upload size
    const heroImage = document.getElementById('post-hero-image');
    if (heroImage && post.image_url) {
        heroImage.innerHTML = `<img src="${escapeHtml(post.image_url)}" alt="" class="w-full h-auto max-h-[80vh] object-contain bg-surface-container-low rounded-lg border border-outline-variant" loading="eager" decoding="async" onerror="this.onerror=null;this.src='/storage/images/placeholder.svg'">`;
        heroImage.style.display = '';
        heroImage.className = 'mb-content-gap w-full flex items-center justify-center bg-surface-container-low';
    } else if (heroImage) {
        heroImage.style.display = 'none';
    }

    // Content — content_html is server-rendered Markdown (sanitized at creation).
    // Render raw since backend sanitizes HTML input (html_input=strip).
    content.innerHTML = post.content_html || `<p class="whitespace-pre-line">${escapeHtml(post.content || '')}</p>`;

    // Comment count
    const commentCountEl = document.getElementById('post-comment-count');
    if (commentCountEl) commentCountEl.textContent = String(post.comment_count ?? 0);

    // Vote
    bindVote(post.id, null);
    document.getElementById('vote-score').dataset.score = String(post.vote_score ?? 0);

    // Bookmark
    bindBookmark(post.id);

    // Share
    bindShare();

    // Related
    renderRelated(related);

    // Navigation (prev/next)
    renderNavigation(navigation);

    showSection('post-page', true);
}

function renderRelated(related) {
    const el = document.getElementById('related-posts');
    const wrap = document.getElementById('related-section');
    if (!el || !wrap) return;

    const posts = related || [];
    if (!posts.length) { wrap.style.display = 'none'; return; }

    wrap.style.display = '';
    el.innerHTML = posts.map(p => `
        <a href="${'/posts/' + p.slug}" class="group block border border-outline-variant p-4 transition-all hover:border-outline-variant hover:shadow-sm">
            ${p.topic ? `<span class="text-xs font-medium text-primary">${escapeHtml(p.topic.name)}</span>` : ''}
            <h3 class="mt-1 text-sm font-semibold leading-snug text-on-background line-clamp-2 group-hover:text-primary transition-colors">${escapeHtml(p.title)}</h3>
            <p class="mt-2 text-xs text-outline">
                ${p.reading_time ? `${p.reading_time} min · ` : ''}${relativeTime(p.published_at)}
            </p>
        </a>
    `).join('');
}

function renderNavigation(nav) {
    const prev = nav?.previous;
    const next = nav?.next;
    const prevEl = document.getElementById('nav-prev');
    const nextEl = document.getElementById('nav-next');

    if (prevEl) {
        if (prev) {
            prevEl.innerHTML = `<span class="text-xs text-outline">← Newer</span><span class="mt-1 block text-sm font-medium text-on-background line-clamp-1">${escapeHtml(prev.title)}</span>`;
            prevEl.href = '/posts/' + prev.slug;
            prevEl.style.display = '';
        } else prevEl.style.display = 'none';
    }
    if (nextEl) {
        if (next) {
            nextEl.innerHTML = `<span class="text-xs text-outline">Older →</span><span class="mt-1 block text-sm font-medium text-on-background line-clamp-1">${escapeHtml(next.title)}</span>`;
            nextEl.href = '/posts/' + next.slug;
            nextEl.style.display = '';
        } else nextEl.style.display = 'none';
    }
}

/* ------------------------------------------------------------------ */
/* Comments                                                            */
/* ------------------------------------------------------------------ */

let commentState = { page: 1, lastPage: 1, loading: false };

function commentCard(c) {
    const user = c.user;
    const votes = (typeof c.upvote_count === 'number' ? c.upvote_count : 0) - (typeof c.downvote_count === 'number' ? c.downvote_count : 0);
    const replies = c.reply_count ? `<span> · ${c.reply_count} ${c.reply_count === 1 ? 'reply' : 'replies'}</span>` : '';
    return `
        <article class="comment-item border-b border-outline-variant py-4" data-comment-id="${c.id}" data-vote-score="${votes}">
            <div class="flex items-center gap-2 text-xs text-outline">
                ${user ? `<span class="font-medium text-on-background">${escapeHtml(user.display_name || user.username)}</span>` : '<span class="font-medium">[deleted]</span>'}
                <span>·</span>
                <span>${relativeTime(c.created_at)}</span>
                ${c.is_edited ? '<span>· edited</span>' : ''}
                ${replies}
            </div>
            <p class="mt-2 text-sm leading-relaxed text-on-background whitespace-pre-line" data-comment-body>${c.is_deleted ? '<em class="text-outline">Comment removed</em>' : escapeHtml(c.content)}</p>
            <div class="mt-2 flex items-center gap-4">
                <button type="button" class="text-xs text-outline hover:text-on-background" data-comment-vote="up" data-id="${c.id}" aria-label="Upvote comment">▲ <span data-votes>${votes}</span></button>
                <button type="button" class="text-xs text-outline hover:text-on-background" data-comment-vote="down" data-id="${c.id}" aria-label="Downvote comment">▼</button>
                <button type="button" class="text-xs font-medium text-outline hover:text-on-background" data-comment-reply data-id="${c.id}">Reply</button>
                ${c.user && window.LANews.authId && c.user.id === Number(window.LANews.authId) ? `
                    <span class="flex-1"></span>
                    <button type="button" class="text-xs text-outline hover:text-on-background" data-comment-edit data-id="${c.id}">Edit</button>
                    <button type="button" class="text-xs text-danger hover:text-danger" data-comment-delete data-id="${c.id}">Delete</button>
                ` : ''}
            </div>
        </article>
    `;
}

/* Inline reply/edit/delete UI — no native prompt()/confirm(). */
function openInlineReply(commentId) {
    const item = document.querySelector(`[data-comment-id="${commentId}"]`);
    if (!item || item.querySelector('.inline-reply')) return;
    const form = document.createElement('div');
    form.className = 'inline-reply mt-3';
    form.innerHTML = `
        <textarea class="inline-input w-full border border-outline-variant bg-surface-container-low px-3.5 py-2.5 text-sm placeholder:text-outline focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition" rows="3" placeholder="Write a reply…"></textarea>
        <div class="mt-2 flex justify-end gap-2">
            <button class="inline-cancel px-3 h-9 text-sm font-medium text-on-surface-variant hover:bg-surface-container" type="button">Cancel</button>
            <button class="inline-save inline-flex items-center justify-center px-4 h-9 bg-primary-container text-on-primary-container text-sm font-medium hover:bg-inverse-primary" type="button">Reply</button>
        </div>
    `;
    item.appendChild(form);
    form.querySelector('.inline-cancel').addEventListener('click', () => form.remove());
    form.querySelector('.inline-save').addEventListener('click', async () => {
        const content = form.querySelector('.inline-input').value.trim();
        if (!content) return;
        const save = form.querySelector('.inline-save');
        save.disabled = true;
        try {
            await commentsApi.reply(commentId, content);
            form.remove();
            await loadComments(postIdRef, true);
            if (window.laranewsToast) window.laranewsToast.success('Reply posted');
        } catch (_) {
            if (window.laranewsToast) window.laranewsToast.error('Could not post reply');
        } finally {
            save.disabled = false;
        }
    });
}

function openInlineEdit(commentId, current) {
    const item = document.querySelector(`[data-comment-id="${commentId}"]`);
    if (!item || item.querySelector('.inline-edit')) return;
    const body = item.querySelector('[data-comment-body]');
    const holder = document.createElement('div');
    holder.className = 'inline-edit mt-2';
    holder.innerHTML = `
        <textarea class="inline-input w-full border border-outline-variant bg-surface-container-low px-3.5 py-2.5 text-sm placeholder:text-outline focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition" rows="4">${escapeHtml(current)}</textarea>
        <div class="mt-2 flex justify-end gap-2">
            <button class="inline-cancel px-3 h-9 text-sm font-medium text-on-surface-variant hover:bg-surface-container" type="button">Cancel</button>
            <button class="inline-save inline-flex items-center justify-center px-4 h-9 bg-primary-container text-on-primary-container text-sm font-medium hover:bg-inverse-primary" type="button">Save</button>
        </div>
    `;
    if (body) body.style.display = 'none';
    item.appendChild(holder);
    holder.querySelector('.inline-cancel').addEventListener('click', () => { holder.remove(); if (body) body.style.display = ''; });
    holder.querySelector('.inline-save').addEventListener('click', async () => {
        const content = holder.querySelector('.inline-input').value.trim();
        if (!content) return;
        const save = holder.querySelector('.inline-save');
        save.disabled = true;
        try {
            await commentsApi.update(commentId, content);
            holder.remove();
            if (body) body.style.display = '';
            body.innerHTML = escapeHtml(content);
            if (window.laranewsToast) window.laranewsToast.success('Comment updated');
        } catch (_) {
            if (window.laranewsToast) window.laranewsToast.error('Could not update comment');
        } finally {
            save.disabled = false;
        }
    });
}

function openInlineDelete(commentId) {
    const item = document.querySelector(`[data-comment-id="${commentId}"]`);
    if (!item) return;
    const form = document.createElement('div');
    form.className = 'inline-delete mt-2 flex items-center gap-3 text-sm';
    form.innerHTML = `<span class="text-on-surface-variant">Delete this comment?</span>
        <button class="inline-cancel px-3 h-9 text-sm font-medium text-on-surface-variant hover:bg-surface-container" type="button">Cancel</button>
        <button class="inline-confirm inline-flex items-center justify-center px-4 h-9 bg-danger text-white text-sm font-medium hover:opacity-90" type="button">Delete</button>`;
    item.appendChild(form);
    form.querySelector('.inline-cancel').addEventListener('click', () => form.remove());
    form.querySelector('.inline-confirm').addEventListener('click', async () => {
        const btn = form.querySelector('.inline-confirm');
        btn.disabled = true;
        try {
            await commentsApi.destroy(commentId);
            form.remove();
            await loadComments(postIdRef, true);
            if (window.laranewsToast) window.laranewsToast.success('Comment deleted');
        } catch (_) {
            if (window.laranewsToast) window.laranewsToast.error('Could not delete comment');
        } finally {
            btn.disabled = false;
        }
    });
}

let postIdRef = null;

async function loadComments(postId, reset = true) {
    const list = document.getElementById('comments-list');
    const sentinel = document.getElementById('comments-sentinel');
    if (!list || commentState.loading) return;

    commentState.loading = true;
    if (reset) {
        commentState.page = 1;
        commentState.lastPage = 1;
        list.innerHTML = '';
    }

    try {
        const payload = await commentsApi.list(postId, { page: commentState.page, per_page: 10 });
        const comments = payload.data || [];
        commentState.lastPage = payload.meta?.pagination?.last_page ?? 1;

        if (reset && !comments.length) {
            list.innerHTML = '<p class="py-8 text-center text-sm text-on-surface-variant">No comments yet. Start the conversation.</p>';
        } else {
            list.insertAdjacentHTML('beforeend', comments.map(commentCard).join(''));
        }

        commentState.page += 1;
        if (commentState.page > commentState.lastPage && sentinel) {
            sentinel.style.display = 'none';
        } else if (sentinel) {
            sentinel.style.display = '';
        }
    } catch (e) {
        if (reset) list.innerHTML = '<p class="py-8 text-center text-sm text-danger">Could not load comments.</p>';
    } finally {
        commentState.loading = false;
    }
}

function bindComments(postId) {
    postIdRef = postId;
    const form = document.getElementById('comment-form');
    const input = document.getElementById('comment-input');
    const sentinel = document.getElementById('comments-sentinel');

    loadComments(postId, true);

    // Comment form is only for authenticated users — unhide, else show login hint.
    if (form) {
        if (window.LANews.auth === true) form.classList.remove('hidden');
    } else {
        const loginHint = document.getElementById('login-hint');
        if (loginHint) loginHint.classList.remove('hidden');
    }

    if (form && input) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const content = input.value.trim();
            if (!content) return;
            input.disabled = true;
            try {
                await commentsApi.store(postId, content);
                input.value = '';
                await loadComments(postId, true);
                if (window.laranewsToast) window.laranewsToast.success('Comment posted');
            } catch (err) {
                if (window.laranewsToast) window.laranewsToast.error('Could not post comment');
            } finally {
                input.disabled = false;
            }
        });
    }

    if ('IntersectionObserver' in window && sentinel) {
        const obs = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) loadComments(postId, false);
        }, { rootMargin: '200px' });
        obs.observe(sentinel);
    }

    document.addEventListener('click', async (e) => {
        /* Comment vote — patch score in place, no full reload. */
        const vote = e.target.closest('[data-comment-vote]');
        if (vote) {
            if (window.LANews.auth !== true) { showLoginHint(); return; }
            const id = vote.dataset.id;
            const dir = vote.dataset.commentVote === 'up' ? 1 : -1;
            const item = vote.closest('.comment-item');
            const votesEl = vote.querySelector('[data-votes]');
            try {
                let result;
                if (dir === 1) result = await commentsApi.upvote(id);
                else result = await commentsApi.downvote(id);
                if (votesEl) votesEl.textContent = String(result.data.vote_score ?? (item?.dataset.voteScore ?? 0));
                if (item) item.dataset.voteScore = result.data.vote_score;
            } catch (_) {
                if (window.laranewsToast) window.laranewsToast.error('Vote failed');
            }
            return;
        }

        const replyBtn = e.target.closest('[data-comment-reply]');
        if (replyBtn) {
            if (window.LANews.auth !== true) { showLoginHint(); return; }
            openInlineReply(replyBtn.dataset.id);
            return;
        }

        const editBtn = e.target.closest('[data-comment-edit]');
        if (editBtn) {
            const item = editBtn.closest('.comment-item');
            const body = item?.querySelector('[data-comment-body]');
            const current = body?.textContent || '';
            openInlineEdit(editBtn.dataset.id, current);
            return;
        }

        const delBtn = e.target.closest('[data-comment-delete]');
        if (delBtn) {
            openInlineDelete(delBtn.dataset.id);
            return;
        }
    });
}

function showLoginHint() {
    const hint = document.getElementById('login-hint');
    if (hint) hint.classList.remove('hidden');
}

/* ------------------------------------------------------------------ */
/* BOOT                                                                */
/* ------------------------------------------------------------------ */

export default async function initPostPage() {
    const identifier = ROOT?.dataset.postIdentifier;
    if (!identifier) return;

    showSection('post-page', false); // show skeleton first

    bindShare();

    try {
        const { data } = await postsApi.detail(identifier);
        renderArticle(data.post, data.related_posts, data.navigation);
        bindComments(data.post.id);
    } catch (e) {
        document.getElementById('post-page').style.display = '';
        const err = document.getElementById('post-error');
        if (err) err.classList.remove('hidden');
    }
}
