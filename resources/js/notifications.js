/**
 * Notifications controller.
 * Lists and manages user notifications.
 */

import { notificationsApi, isAuthenticated } from './api/client.js';

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

function notificationIcon(type) {
    const icons = {
        vote: '⬆️',
        comment: '💬',
        reply: '↩️',
        follow: '👤',
        mention: '@',
    };
    return icons[type] || '🔔';
}

function renderNotification(notif) {
    const isRead = notif.read_at !== null;
    const opacity = isRead ? 'opacity-60' : '';

    return `
        <li class="flex items-start gap-4 border-b border-outline-variant py-stack-lg transition-colors hover:bg-surface-container/30 ${opacity}" data-notification-id="${notif.id}">
            <span class="shrink-0 text-2xl">${notificationIcon(notif.type)}</span>
            <div class="min-w-0 flex-1">
                <p class="font-body-md text-body-md text-on-background">${escapeHtml(notif.data?.message || 'New notification')}</p>
                <p class="mt-1 font-metadata text-metadata text-outline">${relativeTime(notif.created_at)}</p>
            </div>
            ${!isRead ? '<span class="mt-2 size-2 shrink-0 rounded-full bg-primary"></span>' : ''}
        </li>
    `;
}

function renderNotifications(notifications) {
    const list = document.getElementById('notifications-list');
    if (!list) return;

    if (!notifications || notifications.length === 0) {
        list.innerHTML = '<p class="py-8 text-center font-body-md text-body-md text-on-surface-variant">No notifications</p>';
        return;
    }

    const items = notifications.map(renderNotification).join('');
    list.innerHTML = `<ul>${items}</ul>`;
}

function renderLoading() {
    const list = document.getElementById('notifications-list');
    if (!list) return;

    const skeleton = Array.from({ length: 5 }).map(() => `
        <div class="flex animate-pulse gap-4 border-b border-outline-variant py-stack-lg">
            <div class="size-8 shrink-0 rounded-full bg-surface-container"></div>
            <div class="flex-1">
                <div class="h-4 w-3/4 bg-surface-container"></div>
                <div class="mt-2 h-3 w-24 bg-surface-container"></div>
            </div>
        </div>
    `).join('');

    list.innerHTML = `<div>${skeleton}</div>`;
}

function renderError() {
    const list = document.getElementById('notifications-list');
    if (!list) return;

    list.innerHTML = `
        <div class="flex flex-col items-center py-10 text-center">
            <p class="font-body-md text-body-md text-on-surface-variant">Failed to load notifications</p>
            <button type="button" id="notifications-retry" class="mt-3 border border-outline-variant px-4 py-2 font-label text-label text-on-surface transition-colors hover:bg-surface-container">Retry</button>
        </div>
    `;
}

export default async function initNotificationsPage() {
    const root = document.getElementById('notifications-page');
    if (!root) return;

    // Auth check
    if (!isAuthenticated()) {
        window.location.href = '/login';
        return;
    }

    let currentFilter = 'all';

    async function loadNotifications(filter = 'all') {
        renderLoading();

        try {
            const params = {};
            if (filter === 'unread') {
                params.unread = 1;
            }

            const payload = await notificationsApi.list(params);
            const list = Array.isArray(payload.data) ? payload.data : (payload.data?.notifications || []);
            renderNotifications(list);
        } catch (err) {
            renderError();
        }
    }

    // Filter buttons
    const filterButtons = document.querySelectorAll('[data-notification-filter]');
    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            currentFilter = btn.dataset.notificationFilter;

            filterButtons.forEach(b => {
                const active = b === btn;
                b.classList.toggle('bg-primary-container', active);
                b.classList.toggle('text-on-primary-container', active);
                b.classList.toggle('text-on-surface-variant', !active);
            });

            loadNotifications(currentFilter);
        });
    });

    // Mark all read
    const markAllBtn = document.getElementById('mark-all-read');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', async () => {
            markAllBtn.disabled = true;
            try {
                await notificationsApi.readAll();
                await loadNotifications(currentFilter);
            } catch (err) {
                console.error('Mark all read failed:', err);
            } finally {
                markAllBtn.disabled = false;
            }
        });
    }

    // Click notification to mark as read
    document.addEventListener('click', async (e) => {
        const notifEl = e.target.closest('[data-notification-id]');
        if (notifEl) {
            const id = notifEl.dataset.notificationId;
            if (!notifEl.classList.contains('opacity-60')) {
                try {
                    await notificationsApi.read(id);
                    notifEl.classList.add('opacity-60');
                    const indicator = notifEl.querySelector('.bg-primary');
                    if (indicator) indicator.remove();
                } catch (err) {
                    console.error('Mark read failed:', err);
                }
            }
        }

        // Retry button
        if (e.target.id === 'notifications-retry' || e.target.closest('#notifications-retry')) {
            loadNotifications(currentFilter);
        }
    });

    // Initial load
    await loadNotifications(currentFilter);
}