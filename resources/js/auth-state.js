/**
 * Global auth state manager.
 * Syncs header UI with authentication state.
 */

import { isAuthenticated, getStoredUser, clearSession, authApi } from './api/client.js';

export function updateHeaderAuthState() {
    const isAuth = isAuthenticated();
    const user = getStoredUser();

    // Guest/auth toggle
    document.querySelectorAll('[data-auth-link]').forEach(el => {
        const expectedState = el.dataset.authLink;
        if (expectedState === 'guest') {
            el.style.display = isAuth ? 'none' : '';
        } else if (expectedState === 'auth') {
            el.style.display = isAuth ? '' : 'none';
            el.classList.toggle('hidden', !isAuth);
            el.classList.toggle('inline-flex', isAuth);
        }
    });

    // Profile link: keep pointing at /profile (resolves current user via API token,
    // which stays valid even after username changes).
    document.querySelectorAll('[data-profile-link]').forEach(el => {
        el.href = '/profile';
    });
}

// Logout handler
async function handleLogout() {
    try {
        await authApi.logout();
    } catch (err) {
        // Logout API error non-fatal — clear session anyway
        console.warn('Logout API failed:', err);
    }
    clearSession();
    window.location.href = '/login';
}

// Wire logout buttons
function wireLogoutButtons() {
    document.querySelectorAll('[data-logout-button]').forEach(btn => {
        btn.addEventListener('click', handleLogout);
    });
}

// Run on page load
if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            updateHeaderAuthState();
            wireLogoutButtons();
        });
    } else {
        updateHeaderAuthState();
        wireLogoutButtons();
    }
}

