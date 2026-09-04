/**
 * Auth controller (Login + Register).
 * Handles login and register forms with client validation and API integration.
 */

import { authApi, setSession } from './api/client.js';

function escapeHtml(str = '') {
    return String(str ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function showMessage(message, isError = false) {
    const el = document.getElementById('auth-message');
    if (!el) return;
    el.textContent = message;
    el.className = isError ? 'text-red-500 text-sm' : 'text-green-500 text-sm';
    el.style.display = 'block';
}

function clearMessage() {
    const el = document.getElementById('auth-message');
    if (el) el.style.display = 'none';
}

function validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function setButtonLoading(btn, loading, loadingText) {
    if (!btn) return;
    btn.disabled = loading;
    if (loading) {
        btn.dataset.originalText = btn.textContent;
        btn.textContent = loadingText;
    } else {
        btn.textContent = btn.dataset.originalText || btn.textContent;
    }
}

/* Show first validation error from Laravel's `errors` object when present. */
function extractError(err, fallback) {
    const payload = err.payload;
    if (payload?.errors) {
        const first = Object.values(payload.errors)[0];
        if (Array.isArray(first) && first.length) return first[0];
    }
    return payload?.message || err.message || fallback;
}

/* ------------------------------------------------------------------ */
/* Login                                                               */
/* ------------------------------------------------------------------ */

function initLogin() {
    const form = document.getElementById('login-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearMessage();

        const email = form.email.value.trim();
        const password = form.password.value;

        // Client validation
        if (!email) {
            showMessage('Email is required', true);
            return;
        }
        if (!validateEmail(email)) {
            showMessage('Please enter a valid email', true);
            return;
        }
        if (!password) {
            showMessage('Password is required', true);
            return;
        }
        if (password.length < 8) {
            showMessage('Password must be at least 8 characters', true);
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        setButtonLoading(submitBtn, true, 'Signing in...');

        try {
            const { data } = await authApi.login({ email, password });
            setSession({ token: data.token, user: data.user });
            showMessage('Login successful! Redirecting...', false);
            setTimeout(() => {
                window.location.href = '/';
            }, 500);
        } catch (err) {
            const message = err.payload?.message || err.message || 'Login failed';
            showMessage(message, true);
            setButtonLoading(submitBtn, false);
        }
    });
}

/* ------------------------------------------------------------------ */
/* Register                                                            */
/* ------------------------------------------------------------------ */

function initRegister() {
    const form = document.getElementById('register-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearMessage();

        const display_name = form.display_name.value.trim();
        const username = form.username.value.trim();
        const email = form.email.value.trim();
        const password = form.password.value;
        const password_confirmation = form.password_confirmation.value;

        // Client validation
        if (!display_name) {
            showMessage('Display name is required', true);
            return;
        }
        if (!username) {
            showMessage('Username is required', true);
            return;
        }
        if (!email) {
            showMessage('Email is required', true);
            return;
        }
        if (!validateEmail(email)) {
            showMessage('Please enter a valid email', true);
            return;
        }
        if (!password) {
            showMessage('Password is required', true);
            return;
        }
        if (password.length < 8) {
            showMessage('Password must be at least 8 characters', true);
            return;
        }
        if (password !== password_confirmation) {
            showMessage('Passwords do not match', true);
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        setButtonLoading(submitBtn, true, 'Creating account...');

        try {
            const { data } = await authApi.register({
                display_name,
                username,
                email,
                password,
                password_confirmation,
            });
            setSession({ token: data.token, user: data.user });
            showMessage('Account created! Redirecting...', false);
            setTimeout(() => {
                window.location.href = '/';
            }, 500);
        } catch (err) {
            const message = err.payload?.message || err.message || 'Registration failed';
            showMessage(message, true);
            setButtonLoading(submitBtn, false);
        }
    });
}

/* ------------------------------------------------------------------ */
/* Forgot password                                                      */
/* ------------------------------------------------------------------ */

function initForgotPassword() {
    const form = document.getElementById('forgot-password-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearMessage();

        const email = form.email.value.trim();
        if (!email) {
            showMessage('Email is required', true);
            return;
        }
        if (!validateEmail(email)) {
            showMessage('Please enter a valid email', true);
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        setButtonLoading(submitBtn, true, 'Sending reset link...');

        try {
            const res = await authApi.forgotPassword(email);
            showMessage(res.message || 'Reset link sent. Check your inbox.', false);
            setButtonLoading(submitBtn, false);
        } catch (err) {
            const message = extractError(err, 'Could not send reset link');
            showMessage(message, true);
            setButtonLoading(submitBtn, false);
        }
    });
}

/* ------------------------------------------------------------------ */
/* Reset password                                                       */
/* ------------------------------------------------------------------ */

function initResetPassword() {
    const form = document.getElementById('reset-password-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearMessage();

        const email = form.email.value.trim();
        const password = form.password.value;
        const password_confirmation = form.password_confirmation.value;
        const token = form.token.value;

        if (!email || !validateEmail(email)) {
            showMessage('A valid email is required', true);
            return;
        }
        if (!password) {
            showMessage('Password is required', true);
            return;
        }
        if (password.length < 8) {
            showMessage('Password must be at least 8 characters', true);
            return;
        }
        if (password !== password_confirmation) {
            showMessage('Passwords do not match', true);
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        setButtonLoading(submitBtn, true, 'Resetting password...');

        try {
            const res = await authApi.resetPassword({ token, email, password, password_confirmation });
            showMessage(res.message || 'Password reset. Redirecting to sign in...', false);
            setTimeout(() => {
                window.location.href = '/login';
            }, 1500);
        } catch (err) {
            const message = extractError(err, 'Could not reset password');
            showMessage(message, true);
            setButtonLoading(submitBtn, false);
        }
    });
}

/* ------------------------------------------------------------------ */
/* Google OAuth                                                        */
/* ------------------------------------------------------------------ */

function initGoogleButtons() {
    document.querySelectorAll('[data-google-login]').forEach(btn => {
        btn.addEventListener('click', async () => {
            btn.disabled = true;
            try {
                const res = await authApi.googleRedirect();
                const url = res.data?.url;
                if (url) {
                    window.location.href = url;
                    return;
                }
                throw new Error('No redirect URL returned');
            } catch (err) {
                showMessage(extractError(err, 'Google sign-in is unavailable right now.'), true);
                btn.disabled = false;
            }
        });
    });
}

/* Google OAuth callback page — reads token payload from URL fragment,
   stores the session, and redirects home. */
function initGoogleCallback() {
    const status = document.getElementById('google-callback-status');
    const setStatus = (msg) => { if (status) status.textContent = msg; };

    const payload = window.location.hash.replace(/^#/, '');
    if (!payload) {
        setStatus('Sign-in link is invalid or expired.');
        setTimeout(() => { window.location.href = '/login'; }, 2000);
        return;
    }

    try {
        const data = JSON.parse(decodeURIComponent(escape(atob(payload))));
        if (!data?.token || !data?.user) throw new Error('Malformed payload');

        setSession({ token: data.token, user: data.user });
        setStatus('Signed in. Redirecting...');
        setTimeout(() => { window.location.href = '/'; }, 800);
    } catch (err) {
        console.error('Google callback failed:', err);
        setStatus('Could not complete sign-in. Redirecting...');
        setTimeout(() => { window.location.href = '/login'; }, 2000);
    }
}

/* ------------------------------------------------------------------ */
/* Boot                                                                */
/* ------------------------------------------------------------------ */

export default async function initAuth() {
    const root = document.getElementById('auth-page');
    if (!root) return;

    const mode = root.dataset.authMode;
    if (mode === 'login') {
        initLogin();
        initGoogleButtons();
    } else if (mode === 'register') {
        initRegister();
        initGoogleButtons();
    } else if (mode === 'forgot') {
        initForgotPassword();
    } else if (mode === 'reset') {
        initResetPassword();
    } else if (mode === 'google-callback') {
        initGoogleCallback();
    }
}
