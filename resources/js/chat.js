import { getToken, getStoredUser } from './api/client.js';

const API = window.LANews?.apiBase || '/api/v1';

async function apiFetch(path, opts = {}) {
    const headers = { 'Accept': 'application/json' };
    const token = getToken();
    if (token) headers['Authorization'] = 'Bearer ' + token;
    const res = await fetch(API + path, { headers, ...opts });
    return res.json();
}

function escapeHtml(s) { return String(s ?? '').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;'); }

function formatTime(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleDateString(undefined, { hour: '2-digit', minute: '2-digit' });
}

function renderConversations(convs, currentConvId) {
    const el = document.getElementById('chat-conversations');
    if (!el) return;
    if (!convs || convs.length === 0) {
        el.innerHTML = '<p class="text-on-surface-variant text-sm">No conversations yet.</p>';
        return;
    }
    el.innerHTML = convs.map(c => {
        const other = c.other_user;
        const last = c.last_message;
        const active = currentConvId === c.id ? 'bg-surface-container-high' : '';
        return `
            <div class="conversation-item p-3 rounded-lg cursor-pointer hover:bg-surface-container transition-colors ${active}" data-conv-id="${c.id}">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-surface-container-high flex items-center justify-center text-on-surface-variant font-bold">
                        ${escapeHtml((other.display_name || other.username || '?').charAt(0).toUpperCase())}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-label text-label text-on-surface truncate">${escapeHtml(other.display_name || other.username)}</p>
                        ${last ? `<p class="font-metadata text-metadata text-on-surface-variant truncate text-xs">${escapeHtml(last.body)}</p>` : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');

    // Attach click events
    el.querySelectorAll('.conversation-item').forEach(div => {
        div.addEventListener('click', () => {
            const id = parseInt(div.dataset.convId);
            loadConversation(id);
        });
    });
}

function renderMessages(msgs, currentUserId) {
    const el = document.getElementById('chat-messages');
    if (!el) return;
    if (!msgs || msgs.length === 0) {
        el.innerHTML = '<p class="text-on-surface-variant text-sm text-center">No messages yet.</p>';
        return;
    }
    el.innerHTML = msgs.map(m => {
        const isMine = m.sender_id === currentUserId;
        const align = isMine ? 'justify-end' : 'justify-start';
        const bg = isMine ? 'bg-primary text-on-primary' : 'bg-surface-container text-on-surface';
        return `
            <div class="flex ${align} mb-2">
                <div class="max-w-[70%] px-4 py-2 rounded-lg ${bg}">
                    <p class="font-body-md text-body-md">${escapeHtml(m.body)}</p>
                    <p class="font-metadata text-metadata text-xs opacity-70 mt-1">${formatTime(m.created_at)}</p>
                </div>
            </div>
        `;
    }).join('');
    el.scrollTop = el.scrollHeight;
}

let currentConvId = null;
let pollInterval = null;

async function loadConversations() {
    try {
        const data = await apiFetch('/chat/conversations');
        renderConversations(data.data || [], currentConvId);
    } catch (e) {
        console.error('Failed load conversations', e);
    }
}

async function loadConversation(id) {
    currentConvId = id;
    try {
        const data = await apiFetch('/chat/conversations/' + id + '/messages');
        const user = getStoredUser();
        renderMessages(data.data || [], user?.id);
        document.getElementById('chat-no-conversation').style.display = 'none';
        document.getElementById('chat-send-form').style.display = 'flex';
        // Highlight conv
        renderConversations(null, id);
        await loadConversations(); // refresh list
    } catch (e) {
        console.error('Failed load messages', e);
    }
}

async function sendMessage(body) {
    if (!currentConvId) return;
    try {
        await apiFetch('/chat/send', {
            method: 'POST',
            body: JSON.stringify({ body }),
            headers: { 'Content-Type': 'application/json' }
        });
        document.getElementById('chat-input').value = '';
        await loadConversation(currentConvId);
        await loadConversations();
    } catch (e) {
        console.error('Send failed', e);
    }
}

export default function initChatPage() {
    const root = document.getElementById('chat-page');
    if (!root) return;

    const form = document.getElementById('chat-send-form');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = document.getElementById('chat-input');
        const body = input.value.trim();
        if (body) await sendMessage(body);
    });

    // Load initial conversations
    loadConversations();

    // Polling every 5 seconds
    if (pollInterval) clearInterval(pollInterval);
    pollInterval = setInterval(() => {
        loadConversations();
        if (currentConvId) loadConversation(currentConvId);
    }, 5000);
}