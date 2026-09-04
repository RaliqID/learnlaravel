/**
 * Settings controller.
 * Manages user settings and preferences.
 */

import { settingsApi, isAuthenticated } from './api/client.js';

function escapeHtml(str = '') {
    return String(str ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function showMessage(message, isError = false) {
    const el = document.getElementById('settings-message');
    if (!el) return;
    el.textContent = message;
    el.className = isError ? 'text-red-500 text-sm' : 'text-green-500 text-sm';
    el.style.display = 'block';

    // Auto-hide success messages
    if (!isError) {
        setTimeout(() => {
            el.style.display = 'none';
        }, 3000);
    }
}

function clearMessage() {
    const el = document.getElementById('settings-message');
    if (el) el.style.display = 'none';
}

export default async function initSettingsPage() {
    const root = document.getElementById('settings-page');
    if (!root) return;

    // Auth check
    if (!isAuthenticated()) {
        window.location.href = '/login';
        return;
    }

    const form = document.getElementById('settings-form');
    if (!form) return;

    // Load current settings
    try {
        const { data: settings } = await settingsApi.show();

        const showEmailCheckbox = document.getElementById('show_email');
        const emailNotificationsCheckbox = document.getElementById('email_notifications');

        if (showEmailCheckbox) {
            showEmailCheckbox.checked = settings.show_email === true || settings.show_email === 1;
        }

        if (emailNotificationsCheckbox) {
            emailNotificationsCheckbox.checked = settings.email_notifications === true || settings.email_notifications === 1;
        }
    } catch (err) {
        showMessage('Failed to load settings', true);
    }

    // Form submit
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearMessage();

        const showEmail = document.getElementById('show_email')?.checked || false;
        const emailNotifications = document.getElementById('email_notifications')?.checked || false;

        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.dataset.originalText = submitBtn.textContent;
            submitBtn.textContent = 'Saving...';
        }

        try {
            await settingsApi.update({
                show_email: showEmail,
                email_notifications: emailNotifications,
            });

            showMessage('Settings saved', false);
        } catch (err) {
            const message = err.payload?.message || err.message || 'Failed to save settings';
            showMessage(message, true);
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = submitBtn.dataset.originalText || 'Save';
            }
        }
    });
}
