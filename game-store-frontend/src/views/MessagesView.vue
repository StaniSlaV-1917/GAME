<script setup>
/**
 * Phase 4 / Batch D — DM (личные сообщения).
 *
 * Split-layout: слева список чатов (sidebar), справа активный чат.
 * Маршрут /messages — index (sidebar + welcome screen).
 * Маршрут /messages/:roomId — sidebar + конкретный чат справа.
 *
 * Авторизация требуется. Если у юзера нет ни одного чата — показываем
 * welcome screen с инструкцией «Откройте чей-то профиль и нажмите Написать».
 */
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useHead } from '@vueuse/head';
import { storeToRefs } from 'pinia';
import { useAuthStore } from '../stores/auth';
import { useChatsStore } from '../stores/chats';
import { useToast } from '../composables/useToast';
import { resolveMediaUrl } from '../utils/media';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const chatsStore = useChatsStore();
const toast = useToast();

const { user } = storeToRefs(authStore);
const { items: chats, activeRoom, activeMessages, activeLoading, sending } = storeToRefs(chatsStore);

useHead({
  title: 'Личные сообщения — GameStore',
  meta: [{ name: 'robots', content: 'noindex, nofollow' }],
});

const newMessage = ref('');
const messagesEl = ref(null);

const activeRoomId = computed(() => Number(route.params.roomId) || null);

// При смене :roomId — открываем новый чат
watch(activeRoomId, async (id) => {
  if (id) {
    await chatsStore.open(id);
    await scrollToBottom();
  } else {
    chatsStore.close();
  }
}, { immediate: true });

onMounted(async () => {
  await chatsStore.fetchAll();
  if (activeRoomId.value) {
    await scrollToBottom();
  }
});

// ВАЖНО: НЕ вызываем chatsStore.close() в onUnmounted!
// App.vue имеет <RouterView :key="route.fullPath"> — это форсит remount
// MessagesView при каждой смене URL. При навигации между чатами
// (/messages/5 → /messages/8) Vue монтирует НОВЫЙ instance ПЕРЕД unmount
// старого. Если бы старый сделал close() — оно стирало store.activeRoomId
// уже после того как новый его установил → "Чат не выбран" при отправке.
// Полный reset выполняется в handleLogout() в App.vue (через store.reset()).
onUnmounted(() => {
  // no-op
});

// Авто-scroll вниз при новом сообщении
watch(activeMessages, async () => {
  await scrollToBottom();
}, { deep: true, flush: 'post' });

const scrollToBottom = async () => {
  await nextTick();
  if (messagesEl.value) {
    messagesEl.value.scrollTop = messagesEl.value.scrollHeight;
  }
};

const onSend = async () => {
  const body = newMessage.value.trim();
  if (!body || sending.value) return;
  // Optimistic clear — но если send упадёт, восстанавливаем
  newMessage.value = '';
  const result = await chatsStore.send(body);
  if (result?.ok) {
    await scrollToBottom();
  } else {
    // Восстанавливаем черновик и показываем причину
    newMessage.value = body;
    toast.error(result?.error || 'Не удалось отправить сообщение');
  }
};

const onSendKey = (e) => {
  // Ctrl/Cmd + Enter — отправить
  if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
    e.preventDefault();
    onSend();
  }
};

const formatRelative = (s) => {
  if (!s) return '';
  const d = new Date(s);
  const diff = (Date.now() - d.getTime()) / 1000;
  if (diff < 60) return 'только что';
  if (diff < 3600) return Math.floor(diff / 60) + ' мин';
  if (diff < 86400) return Math.floor(diff / 3600) + ' ч';
  if (diff < 604800) return Math.floor(diff / 86400) + ' дн';
  return d.toLocaleDateString('ru-RU');
};

const formatTime = (s) => {
  if (!s) return '';
  return new Date(s).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
};

const counterpartLink = (chat) => {
  if (chat.type === 'direct' && chat.counterpart?.username) {
    return { name: 'user-profile', params: { username: chat.counterpart.username } };
  }
  return null;
};

const isMine = (m) => m.sender_id === user.value?.id;
</script>

<template>
  <div class="messages-view">
    <div class="messages-shell">

      <!-- ─── Sidebar: список чатов ─── -->
      <aside class="chat-sidebar">
        <header class="sidebar-head">
          <h2 class="sidebar-title">Чаты</h2>
          <span class="sidebar-count" v-if="chats.length">{{ chats.length }}</span>
        </header>

        <div v-if="!chats.length" class="sidebar-empty">
          <div class="empty-sigil">⚒</div>
          <p>У тебя пока нет переписок.</p>
          <p class="empty-hint">Открой чей-то <RouterLink to="/feed">профиль из ленты</RouterLink> и нажми «Написать».</p>
        </div>

        <ul v-else class="chat-list">
          <li
            v-for="chat in chats" :key="chat.id"
            class="chat-item"
            :class="{ active: chat.id === activeRoomId, unread: chat.unread_count > 0 }"
            @click="router.push({ name: 'messages-room', params: { roomId: chat.id } })"
          >
            <div class="chat-avatar">
              <img
                v-if="chat.counterpart?.avatar"
                :src="`/avatars/${encodeURIComponent(chat.counterpart.avatar)}`"
                alt=""
              />
              <span v-else>{{ chat.counterpart?.fullname?.[0]?.toUpperCase() ?? '?' }}</span>
            </div>
            <div class="chat-body">
              <div class="chat-row1">
                <span class="chat-name">{{ chat.counterpart?.fullname || chat.name || 'Чат' }}</span>
                <span class="chat-time">{{ formatRelative(chat.last_message_at) }}</span>
              </div>
              <div class="chat-row2">
                <span class="chat-preview">
                  <template v-if="chat.last_message">
                    <span v-if="chat.last_message.sender_id === user?.id" class="own">Вы:</span>
                    {{ chat.last_message.body }}
                  </template>
                  <span v-else class="muted">Чат пуст — начните первым</span>
                </span>
                <span v-if="chat.unread_count > 0" class="unread-badge">{{ chat.unread_count }}</span>
              </div>
            </div>
          </li>
        </ul>
      </aside>

      <!-- ─── Active chat / welcome ─── -->
      <section class="chat-pane">
        <div v-if="!activeRoomId" class="welcome">
          <div class="welcome-sigil">⚔</div>
          <h2>Сообщения</h2>
          <p>Выбери собеседника слева — или начни новый чат с любого профиля кнопкой «Написать».</p>
        </div>

        <template v-else>
          <header class="chat-header">
            <RouterLink
              v-if="counterpartLink(activeRoom || {})"
              :to="counterpartLink(activeRoom)"
              class="header-link"
            >
              <div class="header-avatar">
                <img
                  v-if="activeRoom?.counterpart?.avatar"
                  :src="`/avatars/${encodeURIComponent(activeRoom.counterpart.avatar)}`"
                  alt=""
                />
                <span v-else>{{ activeRoom?.counterpart?.fullname?.[0]?.toUpperCase() ?? '?' }}</span>
              </div>
              <div>
                <div class="header-name">{{ activeRoom?.counterpart?.fullname || activeRoom?.name || 'Чат' }}</div>
                <div v-if="activeRoom?.counterpart?.username" class="header-username">@{{ activeRoom.counterpart.username }}</div>
              </div>
            </RouterLink>
          </header>

          <div ref="messagesEl" class="messages-area">
            <div v-if="activeLoading && !activeMessages.length" class="msg-loading">
              Раскручиваем свиток…
            </div>

            <div v-else-if="!activeMessages.length" class="msg-empty">
              <div>Чат пуст. Брось первое слово в кузницу.</div>
            </div>

            <div v-else class="messages-stream">
              <div
                v-for="m in activeMessages" :key="m.id"
                class="msg-row"
                :class="{ mine: isMine(m), other: !isMine(m) }"
              >
                <div class="msg-bubble">
                  <div class="msg-body">{{ m.body }}</div>
                  <div class="msg-time">{{ formatTime(m.created_at) }}</div>
                </div>
              </div>
            </div>
          </div>

          <footer class="chat-input-bar">
            <textarea
              v-model="newMessage"
              class="chat-input"
              placeholder="Напиши что-нибудь… (Ctrl+Enter — отправить)"
              rows="2"
              maxlength="4000"
              @keydown="onSendKey"
              :disabled="sending"
            ></textarea>
            <button
              class="send-btn"
              :disabled="!newMessage.trim() || sending"
              @click="onSend"
              type="button"
            >
              <span v-if="sending">⌛</span>
              <span v-else>➤</span>
            </button>
          </footer>
        </template>
      </section>

    </div>
  </div>
</template>

<style scoped>
/* Layout: split */
.messages-view {
  min-height: calc(100vh - 73px);
  padding: 24px 16px;
}
.messages-shell {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 320px 1fr;
  gap: 16px;
  height: calc(100vh - 73px - 48px);
  min-height: 500px;
}
@media (max-width: 800px) {
  .messages-shell {
    grid-template-columns: 1fr;
    height: auto;
  }
}

/* Sidebar */
.chat-sidebar {
  display: flex;
  flex-direction: column;
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-md);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  overflow: hidden;
}
.sidebar-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 18px;
  border-bottom: 1px solid var(--iron-dark);
  background: rgba(0,0,0,0.25);
}
.sidebar-title {
  margin: 0;
  font-size: 14px;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: var(--iron-warm);
}
.sidebar-count {
  font-size: 11px;
  color: var(--text-muted);
}
.sidebar-empty {
  padding: 32px 16px;
  text-align: center;
  color: var(--text-muted);
}
.empty-sigil {
  font-size: 32px;
  color: var(--iron-warm);
  opacity: 0.5;
  margin-bottom: 8px;
}
.empty-hint {
  font-size: 12px;
  margin-top: 12px;
  line-height: 1.5;
}
.empty-hint a {
  color: var(--iron-warm);
  text-decoration: none;
  border-bottom: 1px dashed;
}

/* Chat list */
.chat-list {
  list-style: none;
  margin: 0;
  padding: 4px 0;
  overflow-y: auto;
  flex: 1;
}
.chat-item {
  display: flex;
  gap: 10px;
  padding: 10px 14px;
  cursor: pointer;
  transition: background var(--dur-fast);
  border-left: 2px solid transparent;
}
.chat-item:hover {
  background: rgba(226, 67, 16, 0.08);
}
.chat-item.active {
  background: rgba(226, 67, 16, 0.12);
  border-left-color: var(--iron-warm);
}
.chat-item.unread {
  border-left-color: #e24310;
}

.chat-avatar {
  flex-shrink: 0;
  width: 38px;
  height: 38px;
  border-radius: 50%;
  border: 1px solid var(--iron-dark);
  background: rgba(0,0,0,0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-bright);
  font-weight: 600;
  overflow: hidden;
}
.chat-avatar img { width: 100%; height: 100%; object-fit: cover; }

.chat-body {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.chat-row1 {
  display: flex;
  justify-content: space-between;
  gap: 8px;
}
.chat-name {
  font-size: 13px;
  color: var(--text-bright);
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.chat-time {
  font-size: 10px;
  color: var(--text-muted);
  white-space: nowrap;
}
.chat-row2 {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
}
.chat-preview {
  flex: 1;
  font-size: 12px;
  color: var(--text-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.chat-preview .own { color: var(--iron-warm); margin-right: 4px; }
.chat-preview .muted { font-style: italic; opacity: 0.7; }
.unread-badge {
  flex-shrink: 0;
  min-width: 18px;
  height: 18px;
  padding: 0 6px;
  border-radius: 9px;
  background: linear-gradient(180deg, #b8341a 0%, #7a1f0c 100%);
  color: #fff5d6;
  font-size: 10px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Right pane */
.chat-pane {
  display: flex;
  flex-direction: column;
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-md);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  overflow: hidden;
}
.welcome {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 32px;
  color: var(--text-muted);
}
.welcome-sigil {
  font-size: 64px;
  color: var(--iron-warm);
  opacity: 0.4;
  margin-bottom: 16px;
}
.welcome h2 {
  color: var(--text-bright);
  margin: 0 0 12px;
}
.welcome p {
  max-width: 400px;
  margin: 0;
  line-height: 1.5;
}

/* Active chat header */
.chat-header {
  padding: 14px 18px;
  border-bottom: 1px solid var(--iron-dark);
  background: rgba(0,0,0,0.25);
}
.header-link {
  display: flex;
  align-items: center;
  gap: 12px;
  color: inherit;
  text-decoration: none;
}
.header-link:hover { color: var(--iron-warm); }
.header-avatar {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 1px solid var(--iron-dark);
  background: rgba(0,0,0,0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-bright);
  font-weight: 600;
  overflow: hidden;
}
.header-avatar img { width: 100%; height: 100%; object-fit: cover; }
.header-name {
  font-size: 15px;
  color: var(--text-bright);
}
.header-username {
  font-size: 11px;
  color: var(--text-muted);
}

/* Messages area */
.messages-area {
  flex: 1;
  overflow-y: auto;
  padding: 16px 18px;
  background: rgba(0,0,0,0.15);
}
.msg-loading, .msg-empty {
  text-align: center;
  padding: 32px;
  color: var(--text-muted);
  font-style: italic;
}
.messages-stream {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.msg-row {
  display: flex;
}
.msg-row.mine { justify-content: flex-end; }
.msg-row.other { justify-content: flex-start; }

.msg-bubble {
  max-width: 70%;
  padding: 8px 12px;
  border-radius: 12px;
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  border: 1px solid var(--iron-dark);
  position: relative;
}
.msg-row.mine .msg-bubble {
  background: linear-gradient(180deg, rgba(226, 67, 16, 0.2) 0%, rgba(226, 67, 16, 0.05) 100%);
  border-color: var(--iron-warm);
  border-bottom-right-radius: 3px;
}
.msg-row.other .msg-bubble {
  border-bottom-left-radius: 3px;
}

.msg-body {
  font-size: 14px;
  color: var(--text-bright);
  line-height: 1.5;
  white-space: pre-wrap;
  word-wrap: break-word;
}
.msg-time {
  font-size: 10px;
  color: var(--text-muted);
  margin-top: 2px;
  text-align: right;
  letter-spacing: 0.05em;
}

/* Input bar */
.chat-input-bar {
  display: flex;
  gap: 8px;
  padding: 12px;
  border-top: 1px solid var(--iron-dark);
  background: rgba(0,0,0,0.3);
  align-items: flex-end;
}
.chat-input {
  flex: 1;
  resize: none;
  padding: 10px 12px;
  background: linear-gradient(180deg, rgba(8, 6, 10, 0.6), rgba(18, 16, 13, 0.7));
  border: 1px solid var(--iron-mid);
  border-radius: var(--r-sm);
  color: var(--text-bone);
  font-family: inherit;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s;
}
.chat-input:focus { border-color: var(--ember-flame); }
.chat-input:disabled { opacity: 0.5; }

.send-btn {
  flex-shrink: 0;
  width: 44px;
  height: 44px;
  border: 1px solid var(--iron-warm);
  border-radius: var(--r-sm);
  background: linear-gradient(180deg, rgba(226, 67, 16, 0.2) 0%, rgba(0,0,0,0) 100%);
  color: var(--text-bright);
  font-size: 18px;
  cursor: pointer;
  font-family: inherit;
  transition: all var(--dur-fast);
}
.send-btn:hover:not(:disabled) {
  box-shadow: 0 0 12px rgba(226, 67, 16, 0.45);
  transform: translateY(-1px);
}
.send-btn:disabled { opacity: 0.4; cursor: not-allowed; }
</style>
