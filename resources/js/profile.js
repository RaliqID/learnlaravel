/**
 * User Profile controller (UI-03) — Redesigned.
 * Modern social profile: avatar upload, followers/following modals, stats-driven.
 */

import { usersApi, updateStoredUser } from './api/client.js';

const ROOT = document.getElementById('profile-page');

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

function show(id, showState) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.toggle('hidden', !showState);
    el.style.display = '';
}

function showEmpty(id, message, isOwner = false) {
    const el = document.getElementById(id);
    if (!el) return;
    
    if (id === 'posts-list' && isOwner) {
        el.innerHTML = `<div class="py-12 text-center flex flex-col items-center gap-stack-md">
            <p class="font-body-lg text-on-surface">No stories yet.</p>
            <p class="text-sm text-on-surface-variant">Start sharing something worth reading.</p>
            <a href="/posts/create" class="mt-2 font-label text-label px-6 py-3 bg-primary-container text-on-primary-container hover:bg-inverse-primary transition-colors inline-flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]" aria-hidden="true">add</span>
                Create Post
            </a>
        </div>`;
    } else {
        el.innerHTML = `<p class="py-12 text-center text-sm text-on-surface-variant">${escapeHtml(message)}</p>`;
    }
}

function showError(id, message, retry) {
    const el = document.getElementById(id);
    if (!el) return;
    const retryBtn = retry
        ? `<button type="button" data-retry="${escapeHtml(retry)}" class="mt-3 inline-flex items-center justify-center border border-outline-variant px-3 h-9 text-sm font-medium text-on-surface-variant hover:bg-surface-container transition-colors">Retry</button>`
        : '';
    el.innerHTML = `<div class="flex flex-col items-center py-8 text-center">
        <p class="text-sm font-medium text-on-background">${escapeHtml(message)}</p>
        ${retryBtn}
    </div>`;
}

/* ------------------------------------------------------------------ */
/* Avatar rendering                                                    */
/* ------------------------------------------------------------------ */

function avatarInnerHtml(user, sizeClass = 'text-4xl') {
    if (user.avatar) {
        return `<img src="${escapeHtml(user.avatar)}" alt="${escapeHtml(user.display_name || user.username)}" class="size-full object-cover">`;
    }
    const initial = (user.display_name || user.username || '?')[0].toUpperCase();
    return `<span class="inline-flex items-center justify-center size-full bg-surface-container-high ${sizeClass} font-semibold text-on-surface-variant">${escapeHtml(initial)}</span>`;
}

function renderHeaderAvatar(user) {
    const avatarEl = document.getElementById('profile-avatar');
    if (avatarEl) avatarEl.innerHTML = avatarInnerHtml(user, 'text-4xl');

    // Only clickable when avatar image exists
    const btn = document.getElementById('profile-avatar-btn');
    if (btn) {
        if (user.avatar) {
            btn.classList.remove('cursor-default');
            btn.setAttribute('aria-label', 'View avatar full size');
        } else {
            btn.classList.add('cursor-default');
            btn.removeAttribute('aria-label');
        }
    }
}

/* ------------------------------------------------------------------ */
/* Profile header                                                      */
/* ------------------------------------------------------------------ */

let isOwner = false;
let currentUser = null;

async function renderProfile(username) {
    try {
        const payload = await usersApi.profile(username);
        const user = payload.data || payload;

        currentUser = user;
        isOwner = window.LANews.auth === true && window.LANews.authId === user.id;

        renderHeaderAvatar(user);

        document.getElementById('profile-display-name').textContent = user.display_name || user.username;
        document.getElementById('profile-username').textContent = '@' + user.username;

        const bioEl = document.getElementById('profile-bio');
        if (bioEl) {
            bioEl.textContent = user.bio || '';
            bioEl.classList.toggle('hidden', !user.bio);
        }

        document.getElementById('profile-joined').textContent = 'Joined ' + new Date(user.created_at).toLocaleDateString(undefined, { month: 'long', year: 'numeric' });

        // Stats
        document.getElementById('stat-posts').textContent = String(user.post_count ?? 0);
        document.getElementById('stat-followers').textContent = String(user.followers_count ?? 0);
        document.getElementById('stat-following').textContent = String(user.following_count ?? 0);
        document.getElementById('stat-likes').textContent = String(user.likes_count ?? 0);

        document.title = `${user.display_name || user.username} — LaraNews`;
        document.querySelector('meta[name="description"]')?.setAttribute('content', `${user.display_name || user.username} on LaraNews.`);

        // Edit button (only when authenticated & self)
        if (isOwner) {
            show('profile-edit-wrap', true);
            bindEditProfile(user);
        } else {
            show('profile-edit-wrap', false);
        }

        // Follow button: show for authenticated non-self AND for guests (prompts login)
        if (!isOwner) {
            show('profile-follow-wrap', true);
            bindFollow(user);
        } else {
            show('profile-follow-wrap', false);
        }

        bindStatsModals(user);
        bindAvatarLightbox();

        return user;
    } catch (e) {
        if (e.status === 404) {
            const notFound = document.getElementById('profile-notfound');
            if (notFound) notFound.classList.remove('hidden');
            const header = document.querySelector('#profile-page > main');
            if (header) header.style.display = 'none';
            return null;
        }
        const errEl = document.getElementById('profile-error');
        if (errEl) errEl.classList.remove('hidden');
        const header = document.querySelector('#profile-page > main');
        if (header) header.style.display = 'none';
        return null;
    }
}

/* ------------------------------------------------------------------ */
/* Follow / Unfollow                                                    */
/* ------------------------------------------------------------------ */

function bindFollow(user) {
    const btn = document.getElementById('follow-btn');
    const label = document.getElementById('follow-label');
    if (!btn) return;

    let state = user.is_following ? 'following' : 'not_following';
    let loading = false;

    const render = () => {
        btn.classList.toggle('bg-primary-container', state === 'not_following');
        btn.classList.toggle('text-on-primary-container', state === 'not_following');
        btn.classList.toggle('hover:bg-inverse-primary', state === 'not_following');
        btn.classList.toggle('border', state === 'following');
        btn.classList.toggle('border-outline-variant', state === 'following');
        btn.classList.toggle('text-on-surface', state === 'following');
        btn.classList.toggle('hover:bg-surface-container', state === 'following');
        label.textContent = state === 'following' ? 'Following' : 'Follow';
        btn.setAttribute('aria-pressed', String(state === 'following'));
    };

    btn.addEventListener('click', async () => {
        if (loading) return;
        
        // Guest check
        if (window.LANews.auth !== true) {
            window.location.href = '/login';
            return;
        }
        
        loading = true;
        btn.disabled = true;

        const prev = state;
        try {
            if (state === 'following') {
                await usersApi.unfollow(user.username);
                state = 'not_following';
                user.followers_count = Math.max(0, (user.followers_count ?? 1) - 1);
            } else {
                await usersApi.follow(user.username);
                state = 'following';
                user.followers_count = (user.followers_count ?? 0) + 1;
            }
            const statEl = document.getElementById('stat-followers');
            if (statEl) statEl.textContent = String(user.followers_count);
            render();
        } catch (e) {
            state = prev;
            if (window.laranewsToast) window.laranewsToast.error('Could not update follow');
        } finally {
            loading = false;
            btn.disabled = false;
        }
    });

    render();
}

/* ------------------------------------------------------------------ */
/* Followers / Following modals                                        */
/* ------------------------------------------------------------------ */

const modalCache = { followers: null, following: null };

function userRowHtml(u, kind) {
    const name = u.display_name || u.username;
    const isSelf = window.LANews.auth === true && window.LANews.authId === u.id;
    const following = Boolean(u.is_following);

    let actionBtn = '';
    if (window.LANews.auth === true && !isSelf) {
        actionBtn = kind === 'followers'
            ? `<button type="button" data-follow-user="${escapeHtml(u.username)}" data-following="${following}"
                 class="shrink-0 font-label text-label px-4 py-1.5 transition-colors ${following
                     ? 'border border-outline-variant text-on-surface hover:bg-surface-container'
                     : 'bg-primary-container text-on-primary-container hover:bg-inverse-primary'}">${following ? 'Following' : 'Follow'}</button>`
            : `<button type="button" data-follow-user="${escapeHtml(u.username)}" data-following="true"
                 class="shrink-0 font-label text-label px-4 py-1.5 border border-outline-variant text-on-surface hover:bg-surface-container transition-colors">Following</button>`;
    }

    const avatar = u.avatar
        ? `<img src="${escapeHtml(u.avatar)}" alt="" class="size-full object-cover">`
        : `<span class="inline-flex items-center justify-center size-full bg-surface-container-high text-sm font-semibold text-on-surface-variant">${escapeHtml(name[0].toUpperCase())}</span>`;

    return `<div class="flex items-center gap-3 py-3 border-b border-outline-variant last:border-0">
        <a href="/users/${escapeHtml(u.username)}" class="w-10 h-10 shrink-0 rounded-full overflow-hidden border border-outline-variant">${avatar}</a>
        <a href="/users/${escapeHtml(u.username)}" class="flex-1 min-w-0 group">
            <p class="font-body-md text-body-md text-on-surface truncate group-hover:text-primary transition-colors">${escapeHtml(name)}</p>
            <p class="font-metadata text-metadata text-on-surface-variant truncate">@${escapeHtml(u.username)}</p>
        </a>
        ${actionBtn}
    </div>`;
}

function renderUserList(kind, users) {
    const listEl = document.getElementById(`${kind}-list`);
    if (!listEl) return;

    if (!users.length) {
        listEl.innerHTML = `<p class="py-10 text-center text-sm text-on-surface-variant">${kind === 'followers' ? 'No followers yet' : 'Not following anyone yet'}</p>`;
        return;
    }
    listEl.innerHTML = users.map(u => userRowHtml(u, kind)).join('');
}

function openUserListModal(kind) {
    const modal = document.getElementById(`${kind}-modal`);
    if (!modal) return;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    const listEl = document.getElementById(`${kind}-list`);
    if (modalCache[kind]) {
        renderUserList(kind, modalCache[kind]);
        return;
    }

    if (listEl) {
        listEl.innerHTML = Array.from({ length: 4 }).map(() =>
            `<div class="flex items-center gap-3 py-3">
                <div class="w-10 h-10 rounded-full animate-pulse bg-surface-container-high"></div>
                <div class="flex-1">
                    <div class="h-3 w-32 animate-pulse bg-surface-container-high mb-2"></div>
                    <div class="h-2.5 w-20 animate-pulse bg-surface-container-high"></div>
                </div>
            </div>`
        ).join('');
    }

    const username = ROOT?.dataset.username;
    const fetcher = kind === 'followers' ? usersApi.followers : usersApi.following;
    fetcher(username, { page: 1, per_page: 50 })
        .then(payload => {
            const users = payload.data || [];
            modalCache[kind] = users;
            renderUserList(kind, users);
        })
        .catch(() => {
            if (listEl) listEl.innerHTML = `<p class="py-10 text-center text-sm text-on-surface-variant">Could not load list.</p>`;
        });
}

function closeUserListModal(kind) {
    const modal = document.getElementById(`${kind}-modal`);
    if (!modal) return;
    modal.classList.add('hidden');
    document.body.style.overflow = '';
}

function bindStatsModals(user) {
    const followersBtn = document.getElementById('stat-followers-btn');
    const followingBtn = document.getElementById('stat-following-btn');

    if (followersBtn) followersBtn.addEventListener('click', () => openUserListModal('followers'));
    if (followingBtn) followingBtn.addEventListener('click', () => openUserListModal('following'));

    // Close buttons + backdrops
    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => closeUserListModal(btn.dataset.modalClose));
    });
    document.querySelectorAll('[data-modal-backdrop]').forEach(bd => {
        bd.addEventListener('click', () => closeUserListModal(bd.dataset.modalBackdrop));
    });

    // Follow/unfollow inside lists (event delegation)
    ['followers-list', 'following-list'].forEach(listId => {
        const listEl = document.getElementById(listId);
        if (!listEl) return;
        listEl.addEventListener('click', async (e) => {
            const btn = e.target.closest('[data-follow-user]');
            if (!btn || btn.disabled) return;
            btn.disabled = true;

            const targetUsername = btn.dataset.followUser;
            const wasFollowing = btn.dataset.following === 'true';
            try {
                if (wasFollowing) {
                    await usersApi.unfollow(targetUsername);
                    btn.dataset.following = 'false';
                    btn.textContent = 'Follow';
                    btn.className = 'shrink-0 font-label text-label px-4 py-1.5 transition-colors bg-primary-container text-on-primary-container hover:bg-inverse-primary';
                    // If viewing own profile, following count drops
                    if (isOwner && listId === 'following-list') {
                        user.following_count = Math.max(0, (user.following_count ?? 1) - 1);
                        document.getElementById('stat-following').textContent = String(user.following_count);
                    }
                } else {
                    await usersApi.follow(targetUsername);
                    btn.dataset.following = 'true';
                    btn.textContent = 'Following';
                    btn.className = 'shrink-0 font-label text-label px-4 py-1.5 border border-outline-variant text-on-surface hover:bg-surface-container transition-colors';
                    if (isOwner && listId === 'following-list') {
                        user.following_count = (user.following_count ?? 0) + 1;
                        document.getElementById('stat-following').textContent = String(user.following_count);
                    }
                }
            } catch (err) {
                if (window.laranewsToast) window.laranewsToast.error('Could not update follow');
            } finally {
                btn.disabled = false;
            }
        });
    });
}

/* ------------------------------------------------------------------ */
/* Avatar lightbox                                                     */
/* ------------------------------------------------------------------ */

function bindAvatarLightbox() {
    const btn = document.getElementById('profile-avatar-btn');
    const lightbox = document.getElementById('avatar-lightbox');
    if (!btn || !lightbox) return;

    btn.addEventListener('click', () => {
        if (!currentUser?.avatar) return;
        const content = document.getElementById('avatar-lightbox-content');
        if (content) {
            content.innerHTML = `<img src="${escapeHtml(currentUser.avatar)}" alt="${escapeHtml(currentUser.display_name || currentUser.username)}" class="max-w-full max-h-[85vh] rounded-full border border-outline-variant object-contain">`;
        }
        lightbox.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    });

    const close = () => {
        lightbox.classList.add('hidden');
        document.body.style.overflow = '';
    };
    document.getElementById('avatar-lightbox-backdrop')?.addEventListener('click', close);
    document.getElementById('avatar-lightbox-close')?.addEventListener('click', close);
}

/* ------------------------------------------------------------------ */
/* Edit Profile                                                        */
/* ------------------------------------------------------------------ */

function openEditModal() {
    if (!currentUser) return;
    
    const modal = document.getElementById('edit-profile-modal');
    const form = document.getElementById('edit-profile-form');
    if (!modal || !form) return;

    // Prefill
    form['display_name'].value = currentUser.display_name || '';
    form['username'].value = currentUser.username || '';
    form['bio'].value = currentUser.bio || '';
    form['website'].value = currentUser.website || '';
    form['location'].value = currentUser.location || '';
    
    renderEditAvatarPreview();
    updateBioCount();
    clearEditErrors();
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    const modal = document.getElementById('edit-profile-modal');
    if (!modal) return;
    modal.classList.add('hidden');
    document.body.style.overflow = '';
}

function renderEditAvatarPreview() {
    const preview = document.getElementById('edit-avatar-preview');
    const removeBtn = document.getElementById('edit-avatar-remove');
    if (!preview) return;

    if (currentUser?.avatar) {
        preview.innerHTML = `<img src="${escapeHtml(currentUser.avatar)}" alt="" class="size-full object-cover">`;
        if (removeBtn) removeBtn.classList.remove('hidden');
    } else {
        const initial = ((currentUser?.display_name || currentUser?.username || '?')[0] || '?').toUpperCase();
        preview.innerHTML = `<span class="inline-flex items-center justify-center size-full text-2xl font-semibold text-on-surface-variant">${escapeHtml(initial)}</span>`;
        if (removeBtn) removeBtn.classList.add('hidden');
    }
}

function clearEditErrors() {
    const msg = document.getElementById('edit-profile-message');
    if (msg) {
        msg.style.display = 'none';
        msg.textContent = '';
        msg.className = 'hidden text-sm';
    }
    ['display_name', 'username', 'bio', 'website', 'location'].forEach(field => {
        const err = document.getElementById(`err-${field}`);
        if (err) err.classList.add('hidden');
    });
}

function showEditMessage(text, isError) {
    const msg = document.getElementById('edit-profile-message');
    if (!msg) return;
    msg.textContent = text;
    msg.className = isError ? 'text-sm text-danger' : 'text-sm text-primary';
    msg.style.display = 'block';
}

function showFieldError(field, text) {
    const err = document.getElementById(`err-${field}`);
    if (!err) return;
    err.textContent = text;
    err.classList.remove('hidden');
}

function updateBioCount() {
    const bio = document.getElementById('edit-bio');
    const count = document.getElementById('bio-count');
    if (!bio || !count) return;
    count.textContent = String(bio.value.length);
}

/* Avatar upload */
async function handleAvatarUpload(file) {
    if (!file || !currentUser) return;

    // Validate
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowed.includes(file.type)) {
        showEditMessage('Please select a valid image (JPG, PNG, GIF, WEBP).', true);
        return;
    }
    if (file.size > maxSize) {
        showEditMessage('Image must be under 5MB.', true);
        return;
    }

    const progressWrap = document.getElementById('edit-avatar-progress');
    const progressBar = document.getElementById('edit-avatar-progress-bar');
    const progressLabel = document.getElementById('edit-avatar-progress-label');
    const hint = document.getElementById('edit-avatar-hint');

    if (progressWrap) progressWrap.classList.remove('hidden');
    if (hint) hint.classList.add('hidden');
    if (progressBar) progressBar.style.width = '0%';
    if (progressLabel) progressLabel.textContent = 'Uploading…';

    try {
        // Simulate progress (real upload doesn't support progress easily via fetch)
        if (progressBar) progressBar.style.width = '50%';
        
        const response = await usersApi.uploadAvatar(currentUser.username, file);
        const updated = response.data || response;

        if (progressBar) progressBar.style.width = '100%';
        if (progressLabel) progressLabel.textContent = 'Done';

        // Update current user
        currentUser.avatar = updated.avatar || updated.avatar_url;
        renderEditAvatarPreview();
        renderHeaderAvatar(currentUser);

        setTimeout(() => {
            if (progressWrap) progressWrap.classList.add('hidden');
            if (hint) hint.classList.remove('hidden');
        }, 800);

        if (window.laranewsToast) window.laranewsToast.success('Avatar updated');
    } catch (err) {
        console.error('Avatar upload failed:', err);
        showEditMessage(err.payload?.message || 'Could not upload avatar.', true);
        if (progressWrap) progressWrap.classList.add('hidden');
        if (hint) hint.classList.remove('hidden');
    }
}

async function handleAvatarRemove() {
    if (!currentUser) return;
    if (!currentUser.avatar) return;

    const confirmRemove = confirm('Remove your avatar?');
    if (!confirmRemove) return;

    try {
        await usersApi.removeAvatar(currentUser.username);
        currentUser.avatar = null;
        renderEditAvatarPreview();
        renderHeaderAvatar(currentUser);
        if (window.laranewsToast) window.laranewsToast.success('Avatar removed');
    } catch (err) {
        console.error('Avatar remove failed:', err);
        showEditMessage(err.payload?.message || 'Could not remove avatar.', true);
    }
}

async function handleEditSubmit(e) {
    e.preventDefault();
    if (!currentUser) return;

    clearEditErrors();
    
    const form = e.target;
    const saveBtn = document.getElementById('edit-profile-save');
    const saveLabel = document.getElementById('edit-profile-save-label');
    
    if (!saveBtn || !saveLabel) return;
    
    const originalText = saveLabel.textContent;
    saveBtn.disabled = true;
    saveLabel.textContent = 'Saving...';

    const data = {
        display_name: form['display_name'].value.trim(),
        username: form['username'].value.trim(),
        bio: form['bio'].value.trim(),
        website: form['website'].value.trim(),
        location: form['location'].value.trim(),
    };

    try {
        const response = await usersApi.update(currentUser.username, data);
        const updatedUser = response.data || response;

        // Sync stored user so navbar/avatar links point to the (possibly new) username
        updateStoredUser(updatedUser);
        
        // Update in-memory reference
        currentUser = updatedUser;

        // Update page URL if username changed (without full reload)
        if (updatedUser.username && updatedUser.username !== ROOT.dataset.username) {
            ROOT.dataset.username = updatedUser.username;
            history.replaceState(null, '', '/users/' + updatedUser.username);
        }
        
        // Update header display
        document.getElementById('profile-display-name').textContent = updatedUser.display_name || updatedUser.username;
        document.getElementById('profile-username').textContent = '@' + updatedUser.username;
        
        const bioEl = document.getElementById('profile-bio');
        if (bioEl) {
            bioEl.textContent = updatedUser.bio || '';
            bioEl.classList.toggle('hidden', !updatedUser.bio);
        }
        
        renderHeaderAvatar(updatedUser);
        
        if (window.laranewsToast) {
            window.laranewsToast.success('Profile updated', 'Your changes have been saved.');
        }
        
        closeEditModal();
    } catch (err) {
        console.error('Profile update failed:', err);
        
        // Handle validation errors
        if (err.payload?.errors) {
            Object.entries(err.payload.errors).forEach(([field, messages]) => {
                if (Array.isArray(messages) && messages.length) {
                    showFieldError(field, messages[0]);
                }
            });
            showEditMessage('Please fix the errors below.', true);
        } else {
            showEditMessage(err.payload?.message || err.message || 'Could not save changes.', true);
        }
    } finally {
        saveBtn.disabled = false;
        saveLabel.textContent = originalText;
    }
}

function bindEditProfile(user) {
    currentUser = user;
    
    const editBtn = document.getElementById('edit-profile-btn');
    const closeBtn = document.getElementById('edit-profile-close');
    const cancelBtn = document.getElementById('edit-profile-cancel');
    const backdrop = document.getElementById('edit-profile-backdrop');
    const form = document.getElementById('edit-profile-form');
    const bioInput = document.getElementById('edit-bio');
    const avatarChangeBtn = document.getElementById('edit-avatar-change');
    const avatarRemoveBtn = document.getElementById('edit-avatar-remove');
    const avatarInput = document.getElementById('edit-avatar-input');
    
    if (editBtn) editBtn.addEventListener('click', openEditModal);
    if (closeBtn) closeBtn.addEventListener('click', closeEditModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeEditModal);
    if (backdrop) backdrop.addEventListener('click', closeEditModal);
    if (form) form.addEventListener('submit', handleEditSubmit);
    if (bioInput) bioInput.addEventListener('input', updateBioCount);
    
    // Avatar upload
    if (avatarChangeBtn && avatarInput) {
        avatarChangeBtn.addEventListener('click', () => avatarInput.click());
        avatarInput.addEventListener('change', (e) => {
            const file = e.target.files?.[0];
            if (file) handleAvatarUpload(file);
        });
    }
    if (avatarRemoveBtn) {
        avatarRemoveBtn.addEventListener('click', handleAvatarRemove);
    }
    
    // ESC to close
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const modal = document.getElementById('edit-profile-modal');
            if (modal && !modal.classList.contains('hidden')) {
                closeEditModal();
            }
        }
    });
}

/* ------------------------------------------------------------------ */
/* Tabs + lazy content                                                 */
/* ------------------------------------------------------------------ */

let activeTab = 'posts';
let loadedTab = { posts: false, comments: false };
let pending = { posts: null, comments: null };

function tabBtn(kind) {
    return document.querySelector(`[data-profile-tab="${kind}"]`);
}

function setActiveTab(kind) {
    if (activeTab === kind) return;
    activeTab = kind;

    ['posts', 'comments'].forEach(k => {
        const b = tabBtn(k);
        const active = k === kind;
        if (!b) return;
        b.setAttribute('aria-selected', String(active));
        b.classList.toggle('bg-primary-container', active);
        b.classList.toggle('text-on-primary-container', active);
        b.classList.toggle('text-on-surface-variant', !active);
    });

    show('panel-posts', kind === 'posts');
    show('panel-comments', kind === 'comments');

    if (!loadedTab[kind]) {
        loadTabContent(kind);
    }
}

function postCardHtml(p) {
    return `
        <a href="${'/posts/' + (p.slug || p.id)}" class="group block border border-outline-variant p-4 transition-all hover:border-outline-variant hover:shadow-xs">
            <div class="flex items-center gap-1.5">
                ${p.topic ? `<span class="text-xs font-medium text-primary">${escapeHtml(p.topic.name)}</span>` : ''}
                <span class="text-xs text-outline">·</span>
                <span class="text-xs text-outline">${relativeTime(p.published_at)}</span>
            </div>
            <h3 class="mt-1 text-sm font-semibold leading-snug text-on-background line-clamp-2 group-hover:text-primary transition-colors">${escapeHtml(p.title)}</h3>
            <p class="mt-1 text-xs text-outline">${typeof p.comment_count === 'number' ? p.comment_count + ' comments · ' : ''}${typeof p.vote_score === 'number' ? p.vote_score + ' votes' : ''}</p>
        </a>
    `;
}

function commentCardHtml(c) {
    const user = c.user;
    return `
        <article class="border-b border-outline-variant py-3 last:border-0" data-comment-id="${c.id}">
            <div class="flex items-center gap-2 text-xs text-outline">
                ${user ? `<span class="font-medium text-on-background">${escapeHtml(user.display_name || user.username)}</span>` : '<span class="font-medium">[deleted]</span>'}
                <span>·</span>
                <span>${relativeTime(c.created_at)}</span>
                ${c.is_edited ? '<span>· edited</span>' : ''}
            </div>
            <p class="mt-1.5 text-sm leading-relaxed text-on-background whitespace-pre-line">${c.is_deleted ? '<em class="text-outline">Comment removed</em>' : escapeHtml(c.content)}</p>
            <p class="mt-1 text-xs text-outline">
                ${typeof c.upvote_count === 'number' ? `${c.upvote_count} up` : ''}${typeof c.downvote_count === 'number' ? ` · ${c.downvote_count} down` : ''}
            </p>
        </article>
    `;
}

async function loadTabContent(kind) {
    const username = ROOT?.dataset.username;
    const target = document.getElementById(`${kind}-list`);
    if (!target) return;

    pending[kind] = true;

    try {
        const paginator = kind === 'posts'
            ? await usersApi.posts(username, { page: 1, per_page: 20 })
            : await usersApi.comments(username, { page: 1, per_page: 20 });

        const items = paginator.data || [];

        if (!items.length) {
            showEmpty(`${kind}-list`, kind === 'posts' ? 'No stories yet.' : 'No comments yet.', kind === 'posts' ? isOwner : false);
        } else {
            target.innerHTML = kind === 'posts'
                ? items.map(postCardHtml).join('')
                : items.map(commentCardHtml).join('');
        }

        loadedTab[kind] = true;
    } catch (e) {
        showError(`${kind}-list`, kind === 'posts' ? 'Could not load posts.' : 'Could not load comments.', kind);
    } finally {
        pending[kind] = false;
    }
}

function bindTabs() {
    ['posts', 'comments'].forEach(kind => {
        const b = tabBtn(kind);
        if (!b) return;
        b.addEventListener('click', () => setActiveTab(kind));
    });
}

/* Retry dispatch */
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-retry]');
    if (!btn) return;
    const kind = btn.dataset.retry;
    if (kind === 'profile') {
        renderProfile(ROOT.dataset.username).then(() => bindTabs()).then(() => setActiveTab('posts'));
    } else {
        loadTabContent(kind);
    }
});

/* ------------------------------------------------------------------ */
/* BOOT                                                                */
/* ------------------------------------------------------------------ */

export default async function initProfilePage() {
    const username = ROOT?.dataset.username;

    // If no username in data attr, resolve current user from API (token-based).
    if (!username) {
        try {
            const payload = await usersApi.me();
            const me = payload.data || payload;
            if (me?.username) {
                updateStoredUser(me);
                window.location.replace('/users/' + me.username);
                return;
            }
        } catch (_) { /* not authenticated */ }
        window.location.href = '/login';
        return;
    }

    bindTabs();
    const user = await renderProfile(username);
    if (!user) return;

    // Load the default tab (posts) only after profile is confirmed.
    await loadTabContent('posts');

    // ----- Account: password change & logout -----
    const passwordForm = document.getElementById('password-form');
    if (passwordForm) {
        passwordForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const current = document.getElementById('current-password').value;
            const newPass = document.getElementById('new-password').value;
            const confirm = document.getElementById('new-password-confirm').value;
            const msg = document.getElementById('password-message');
            // clear previous errors
            document.querySelectorAll('.err-current-password, .err-new-password').forEach(el => el.classList.add('hidden'));
            msg.className = 'hidden';
            msg.textContent = '';
            if (newPass !== confirm) {
                const errEl = document.querySelector('.err-new-password');
                errEl.textContent = 'Passwords do not match.';
                errEl.classList.remove('hidden');
                return;
            }
            try {
                const token = localStorage.getItem('laranews.authToken');
                const res = await fetch('/api/v1/user/password', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + token
                    },
                    body: JSON.stringify({
                        current_password: current,
                        new_password: newPass,
                        new_password_confirmation: confirm
                    })
                });
                const data = await res.json();
                if (res.ok) {
                    msg.textContent = 'Password updated successfully.';
                    msg.className = 'text-sm text-primary';
                    msg.style.display = 'block';
                    passwordForm.reset();
                    setTimeout(() => { msg.style.display = 'none'; }, 4000);
                } else {
                    if (data.errors) {
                        if (data.errors.current_password) {
                            const el = document.querySelector('.err-current-password');
                            el.textContent = data.errors.current_password[0];
                            el.classList.remove('hidden');
                        }
                        if (data.errors.new_password) {
                            const el = document.querySelector('.err-new-password');
                            el.textContent = data.errors.new_password[0];
                            el.classList.remove('hidden');
                        }
                    } else {
                        msg.textContent = data.message || 'Failed to update password.';
                        msg.className = 'text-sm text-danger';
                        msg.style.display = 'block';
                    }
                }
            } catch (err) {
                msg.textContent = 'Something went wrong.';
                msg.className = 'text-sm text-danger';
                msg.style.display = 'block';
            }
        });
    }

    const logoutBtn = document.getElementById('profile-logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            const token = localStorage.getItem('laranews.authToken');
            if (token) {
                try {
                    await fetch('/api/v1/auth/logout', {
                        method: 'POST',
                        headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
                    });
                } catch (_) {}
                localStorage.removeItem('laranews.authToken');
                localStorage.removeItem('laranews.user');
                window.LANews.auth = false;
                window.LANews.authId = null;
                window.location.href = '/';
            } else {
                window.location.href = '/login';
            }
        });
    }
}