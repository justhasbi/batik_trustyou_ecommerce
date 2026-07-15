<script setup>
import { ref, nextTick, onUnmounted } from 'vue'
import client from '@/api/client'

const FALLBACK_MESSAGE = 'Maaf, layanan chat sedang tidak tersedia. Silakan coba lagi nanti.'

const open = ref(false)
const stage = ref('menu')
const mode = ref(null)
const sessionId = ref(null)
const messages = ref([])
const input = ref('')
const typing = ref(false)
const starting = ref(false)
const suggestAdmin = ref(false)
const requireLoginNotice = ref(false)
const scrollEl = ref(null)

let localId = 1
let pollTimer = null
let lastMessageId = 0

function toggle() {
    open.value = !open.value
}

function scrollToBottom() {
    nextTick(() => {
        if (scrollEl.value) scrollEl.value.scrollTop = scrollEl.value.scrollHeight
    })
}

function pushMessage(from, text) {
    messages.value.push({ id: localId++, from, text })
    scrollToBottom()
}

async function startChat(selectedMode) {
    starting.value = true
    try {
        const { data } = await client.post('/chat/start', { mode: selectedMode })
        sessionId.value = data.session_id
        mode.value = data.mode
        stage.value = 'chat'
        if (data.reply) pushMessage(mode.value === 'admin' ? 'admin' : 'bot', data.reply)
        if (mode.value === 'admin') startPolling()
    } catch (e) {
        stage.value = 'chat'
        pushMessage('system', FALLBACK_MESSAGE)
    } finally {
        starting.value = false
    }
}

async function sendMessage() {
    const text = input.value.trim()
    if (!text || !sessionId.value) return
    pushMessage('user', text)
    input.value = ''
    typing.value = true
    try {
        const { data } = await client.post('/chat/message', { session_id: sessionId.value, message: text })
        typing.value = false
        if (mode.value !== 'admin') {
            if (data.reply) pushMessage('bot', data.reply)
            if (data.require_login) requireLoginNotice.value = true
            suggestAdmin.value = !!data.suggest_admin
        }
    } catch (e) {
        typing.value = false
        pushMessage('system', FALLBACK_MESSAGE)
    }
}

async function connectToAdmin() {
    if (!sessionId.value) return
    try {
        const { data } = await client.post('/chat/admin', { session_id: sessionId.value })
        mode.value = data.mode
        suggestAdmin.value = false
        if (data.reply) pushMessage('admin', data.reply)
        startPolling()
    } catch (e) {
        pushMessage('system', 'Maaf, tidak dapat menghubungkan ke admin saat ini.')
    }
}

function startPolling() {
    if (pollTimer) return
    pollTimer = setInterval(async () => {
        try {
            const { data } = await client.get(`/chat/${sessionId.value}/messages`, { params: { after: lastMessageId } })
            for (const m of data.messages || []) {
                if (m.id > lastMessageId) lastMessageId = m.id
                if (m.sender !== 'customer') pushMessage(m.sender === 'admin' ? 'admin' : 'bot', m.message)
            }
        } catch (e) {}
    }, 4000)
}

function stopPolling() {
    if (pollTimer) {
        clearInterval(pollTimer)
        pollTimer = null
    }
}

onUnmounted(stopPolling)
</script>

<template>
    <div class="chat-widget">
        <Transition name="chat-panel">
            <div v-if="open" class="chat-panel card">
                <div class="chat-panel__header">
                    <span class="mono chat-panel__title">
                        {{ mode === 'admin' ? 'Chat dengan Admin' : mode === 'bot' ? 'Asisten Batik TrustYou' : 'Chat' }}
                    </span>
                    <button type="button" class="chat-panel__close" aria-label="Tutup chat" @click="toggle">×</button>
                </div>

                <div v-if="stage === 'menu'" class="chat-panel__menu">
                    <p class="muted">Pilih layanan yang Anda butuhkan.</p>
                    <button type="button" class="btn btn--block" :disabled="starting" @click="startChat('bot')">
                        Chat dengan Bot
                    </button>
                    <button type="button" class="btn btn--ghost btn--block" :disabled="starting" @click="startChat('admin')">
                        Chat dengan Admin
                    </button>
                </div>

                <template v-else>
                    <div ref="scrollEl" class="chat-panel__messages">
                        <div v-for="m in messages" :key="m.id" class="chat-msg" :class="`chat-msg--${m.from}`">
                            <p>{{ m.text }}</p>
                        </div>
                        <div v-if="typing" class="chat-msg chat-msg--bot chat-msg--typing">
                            <span></span><span></span><span></span>
                        </div>
                        <div v-if="requireLoginNotice" class="chat-panel__notice muted">
                            Silakan <RouterLink to="/login" @click="toggle">masuk</RouterLink> untuk melihat status pesanan Anda.
                        </div>
                        <button v-if="suggestAdmin" type="button" class="btn btn--ghost btn--sm" @click="connectToAdmin">
                            Hubungkan ke admin
                        </button>
                    </div>

                    <form class="chat-panel__input" @submit.prevent="sendMessage">
                        <input
                            v-model="input"
                            type="text"
                            class="input"
                            placeholder="Tulis pesan…"
                            autocomplete="off"
                        />
                        <button type="submit" class="btn btn--sm" :disabled="!input.trim()">Kirim</button>
                    </form>
                </template>
            </div>
        </Transition>

        <button type="button" class="chat-fab" :aria-expanded="open" aria-label="Buka chat" @click="toggle">
            <span v-if="!open">💬</span>
            <span v-else>×</span>
        </button>
    </div>
</template>

<style scoped>
.chat-widget {
    position: fixed;
    right: 24px;
    bottom: 24px;
    z-index: 50;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 12px;
}

.chat-fab {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: var(--indigo);
    color: #fff;
    border: none;
    font-size: 22px;
    cursor: pointer;
    box-shadow: 0 8px 20px rgba(46, 42, 94, 0.35);
    transition: background-color 0.15s ease;
}

.chat-fab:hover {
    background: var(--indigo-hover);
}

.chat-panel {
    width: min(340px, calc(100vw - 48px));
    height: min(460px, calc(100vh - 140px));
    display: flex;
    flex-direction: column;
    box-shadow: 0 12px 32px rgba(23, 23, 28, 0.16);
    overflow: hidden;
}

.chat-panel__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border-bottom: 1px solid var(--line);
}

.chat-panel__title {
    font-size: 12px;
    letter-spacing: 0.04em;
}

.chat-panel__close {
    background: none;
    border: none;
    font-size: 18px;
    line-height: 1;
    color: var(--muted);
    cursor: pointer;
}

.chat-panel__menu {
    padding: 20px 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.chat-panel__messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.chat-msg p {
    margin: 0;
    max-width: 85%;
    padding: 9px 12px;
    border-radius: var(--radius);
    font-size: 13.5px;
    line-height: 1.45;
}

.chat-msg--user {
    align-self: flex-end;
}

.chat-msg--user p {
    background: var(--indigo);
    color: #fff;
    margin-left: auto;
}

.chat-msg--bot p,
.chat-msg--admin p {
    background: var(--indigo-tint);
    color: var(--ink);
}

.chat-msg--system p {
    background: transparent;
    border: 1px dashed var(--line-2);
    color: var(--muted);
}

.chat-msg--typing p {
    display: none;
}

.chat-msg--typing {
    display: flex;
    gap: 4px;
    padding: 9px 12px;
    background: var(--indigo-tint);
    border-radius: var(--radius);
    width: fit-content;
}

.chat-msg--typing span {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--muted);
    animation: blink 1s infinite ease-in-out;
}

.chat-msg--typing span:nth-child(2) {
    animation-delay: 0.15s;
}

.chat-msg--typing span:nth-child(3) {
    animation-delay: 0.3s;
}

@keyframes blink {
    0%,
    80%,
    100% {
        opacity: 0.25;
    }
    40% {
        opacity: 1;
    }
}

.chat-panel__notice {
    font-size: 13px;
}

.chat-panel__input {
    display: flex;
    gap: 8px;
    padding: 12px;
    border-top: 1px solid var(--line);
}

.chat-panel__input .input {
    flex: 1;
}

.chat-panel-enter-active,
.chat-panel-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}

.chat-panel-enter-from,
.chat-panel-leave-to {
    opacity: 0;
    transform: translateY(8px);
}
</style>
