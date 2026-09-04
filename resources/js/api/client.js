const API = window.LANews?.apiBase || '/api/v1';
const TOKEN_KEY = 'laranews.authToken';
const USER_KEY = 'laranews.user';

export function getToken() {
    return localStorage.getItem(TOKEN_KEY);
}

export function getStoredUser() {
    try {
        return JSON.parse(localStorage.getItem(USER_KEY) || 'null');
    } catch (_) {
        return null;
    }
}

export function setSession({ token, user }) {
    if (token) localStorage.setItem(TOKEN_KEY, token);
    if (user) localStorage.setItem(USER_KEY, JSON.stringify(user));
    window.LANews.auth = true;
    window.LANews.authId = user?.id ?? window.LANews.authId;
}

/* Merge updated profile fields into the stored user (keeps token intact). */
export function updateStoredUser(user) {
    if (!user) return;
    const existing = getStoredUser() || {};
    const merged = { ...existing, ...user };
    localStorage.setItem(USER_KEY, JSON.stringify(merged));
    window.LANews.authId = merged.id ?? window.LANews.authId;
}

export function clearSession() {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
    window.LANews.auth = false;
    window.LANews.authId = null;
}

export function isAuthenticated() {
    return window.LANews?.auth === true || Boolean(getToken());
}

async function request(path, { method = 'GET', body, params, formData = false } = {}) {
    const url = new URL(API + path, window.location.origin);

    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                url.searchParams.append(key, value);
            }
        });
    }

    const headers = { Accept: 'application/json' };
    const token = getToken();
    if (token) headers.Authorization = `Bearer ${token}`;

    const config = { method, headers, credentials: 'same-origin' };

    if (body) {
        if (formData) {
            config.body = body;
        } else {
            headers['Content-Type'] = 'application/json';
            config.body = JSON.stringify(body);
        }
    }

    const response = await fetch(url.toString(), config);
    let payload = {};

    try {
        payload = await response.json();
    } catch (_) {
        payload = {};
    }

    if (!response.ok) {
        const error = new Error(payload.message || `Request failed (${response.status})`);
        error.status = response.status;
        error.payload = payload;
        throw error;
    }

    return payload;
}

export const feedApi = {
    feed: (params) => request('/feed', { params }),
    hero: () => request('/feed/hero'),
    trending: (params = {}) => request('/feed/trending', { params }),
    recommended: () => request('/feed/recommended'),
    categories: () => request('/feed/categories'),
    editorsPicks: () => request('/feed/editors-picks'),
    sections: () => request('/feed/sections'),
    activityChart: () => request('/feed/activity-chart'),
    categoryDistribution: (params = {}) => request('/feed/category-distribution', { params }),
};

export const topicsApi = {
    list: (params = {}) => request('/topics', { params }),
    show: (slug) => request(`/topics/${slug}`),
    subscribe: (slug) => request(`/topics/${slug}/subscribe`, { method: 'POST' }),
    unsubscribe: (slug) => request(`/topics/${slug}/subscribe`, { method: 'DELETE' }),
};

export const postsApi = {
    detail: (identifier) => request(`/posts/${identifier}`),
    create: (data, options = {}) => request('/posts', { method: 'POST', body: data, ...options }),
};

export const commentsApi = {
    list: (postId, params) => request(`/posts/${postId}/comments`, { params }),
    show: (postId, commentId) => request(`/posts/${postId}/comments/${commentId}`),
    store: (postId, content) => request(`/posts/${postId}/comments`, { method: 'POST', body: { content } }),
    reply: (commentId, content) => request(`/comments/${commentId}/replies`, { method: 'POST', body: { content } }),
    update: (commentId, content) => request(`/comments/${commentId}`, { method: 'PUT', body: { content } }),
    destroy: (commentId) => request(`/comments/${commentId}`, { method: 'DELETE' }),
    upvote: (commentId) => request(`/comments/${commentId}/vote`, { method: 'POST' }),
    downvote: (commentId) => request(`/comments/${commentId}/vote/down`, { method: 'POST' }),
    removeVote: (commentId) => request(`/comments/${commentId}/vote`, { method: 'DELETE' }),
};

export const bookmarkApi = {
    store: (postId) => request(`/posts/${postId}/bookmark`, { method: 'POST' }),
    destroy: (postId) => request(`/posts/${postId}/bookmark`, { method: 'DELETE' }),
    list: (params = {}) => request('/bookmarks', { params }),
    collections: () => request('/bookmarks/collections'),
};

export const voteApi = {
    upvote: (postId) => request(`/posts/${postId}/vote`, { method: 'POST' }),
    downvote: (postId) => request(`/posts/${postId}/vote/down`, { method: 'POST' }),
    remove: (postId) => request(`/posts/${postId}/vote`, { method: 'DELETE' }),
};

export const usersApi = {
    me: () => request('/user'),
    profile: (username) => request(`/users/${username}`),
    posts: (username, params = {}) => request(`/users/${username}/posts`, { params }),
    comments: (username, params = {}) => request(`/users/${username}/comments`, { params }),
    followers: (username, params = {}) => request(`/users/${username}/followers`, { params }),
    following: (username, params = {}) => request(`/users/${username}/following`, { params }),
    follow: (username) => request(`/users/${username}/follow`, { method: 'POST' }),
    unfollow: (username) => request(`/users/${username}/follow`, { method: 'DELETE' }),
    update: (username, data) => request(`/users/${username}`, { method: 'PUT', body: data }),
    uploadAvatar: (username, file) => {
        const fd = new FormData();
        fd.append('avatar', file);
        return request(`/users/${username}/avatar`, { method: 'POST', body: fd, formData: true });
    },
    removeAvatar: (username) => request(`/users/${username}/avatar`, { method: 'DELETE' }),
};

export const searchApi = {
    all: (q, params = {}) => request('/search', { params: { q, ...params } }),
    posts: (q, params = {}) => request('/search/posts', { params: { q, ...params } }),
    topics: (q, params = {}) => request('/search/topics', { params: { q, ...params } }),
    users: (q, params = {}) => request('/search/users', { params: { q, ...params } }),
};

export const authApi = {
    login: (data) => request('/auth/login', { method: 'POST', body: data }),
    register: (data) => request('/auth/register', { method: 'POST', body: data }),
    logout: () => request('/auth/logout', { method: 'POST' }),
    forgotPassword: (email) => request('/auth/forgot-password', { method: 'POST', body: { email } }),
    resetPassword: (data) => request('/auth/reset-password', { method: 'POST', body: data }),
    googleRedirect: () => request('/auth/google'),
};

export const notificationsApi = {
    list: (params = {}) => request('/notifications', { params }),
    unreadCount: () => request('/notifications/unread-count'),
    readAll: () => request('/notifications/read-all', { method: 'PUT' }),
    read: (id) => request(`/notifications/${id}/read`, { method: 'PUT' }),
    destroy: (id) => request(`/notifications/${id}`, { method: 'DELETE' }),
};

export const settingsApi = {
    show: () => request('/user/settings'),
    update: (data) => request('/user/settings', { method: 'PUT', body: data }),
};

export default {
    feedApi,
    topicsApi,
    postsApi,
    commentsApi,
    bookmarkApi,
    voteApi,
    usersApi,
    searchApi,
    authApi,
    notificationsApi,
    settingsApi,
};
