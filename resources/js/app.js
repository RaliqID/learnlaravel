/**
 * LaraNews frontend entry point.
 * Loads page-specific controllers; keeps global wiring (bootstrap) minimal.
 */

import './bootstrap';
import './auth-state.js';
import initHomepage from './home.js';
import initPostPage from './post.js';
import initProfilePage from './profile.js';
import initAuth from './auth.js';
import initTopicsPage from './topics.js';
import initTopicPage from './topic.js';
import initSearchPage from './search.js';
import initNotificationsPage from './notifications.js';
import initSettingsPage from './settings.js';
import initCreatePostPage from './create-post.js';
import initChatPage from './chat.js';
import initDiscoveryPage from './discovery.js';

if (document.getElementById('homepage-root')) initHomepage().catch(() => {});
if (document.getElementById('post-page')) initPostPage().catch(() => {});
if (document.getElementById('profile-page')) initProfilePage().catch(() => {});
if (document.getElementById('auth-page')) initAuth().catch(() => {});
if (document.getElementById('topics-page')) initTopicsPage().catch(() => {});
if (document.getElementById('topic-page')) initTopicPage().catch(() => {});
if (document.getElementById('search-page')) initSearchPage().catch(() => {});
if (document.getElementById('notifications-page')) initNotificationsPage().catch(() => {});
if (document.getElementById('settings-page')) initSettingsPage().catch(() => {});
if (document.getElementById('create-post-page')) initCreatePostPage().catch(() => {});
if (document.getElementById('chat-page')) initChatPage().catch(() => {});
if (document.getElementById('discovery-page')) initDiscoveryPage().catch(() => {});