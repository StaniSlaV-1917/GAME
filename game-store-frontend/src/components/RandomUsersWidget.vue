<script setup>
/**
 * Виджет «Случайные воины» — sidebar на /feed.
 *
 * Загружает 8 случайных юзеров. Авто-меняет ОДИН элемент каждые 8 сек
 * (slot-machine эффект — намёк на «постоянно идущую ленту»). Кнопка
 * «↻ Обновить» меняет всех сразу.
 *
 * На каждой карточке:
 *   • avatar (или fallback initial)
 *   • fullname + @username + role
 *   • кнопки: «Профиль» → /u/:username, «✉ Написать» → DM
 *
 * Прячется на мобиле (CSS).
 */
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import api from '../api/axios';
import { useAuthStore } from '../stores/auth';
import { useChatsStore } from '../stores/chats';
import { useToast } from '../composables/useToast';

const router = useRouter();
const authStore = useAuthStore();
const chatsStore = useChatsStore();
const toast = useToast();

const users = ref([]);
const loading = ref(false);
const refreshing = ref(false);
const writingFor = ref(null); // username для которого создаётся DM
let rotateTimer = null;

// Загрузить N случайных юзеров (полная замена)
const loadAll = async (limit = 8) => {
  loading.value = true;
  try {
    const { data } = await api.get('/users/random', { params: { limit } });
    users.value = data.data || [];
  } catch (e) {
    console.warn('[RandomUsers] load failed', e);
  } finally {
    loading.value = false;
  }
};

// Подменить один случайный слот новым юзером (slot-machine)
const rotateOne = async () => {
  if (!users.value.length) return;
  try {
    const { data } = await api.get('/users/random', { params: { limit: 1 } });
    const fresh = data.data?.[0];
    if (!fresh) return;
    // Не вставляем дубль — если уже в списке, пропускаем тик
    if (users.value.find((u) => u.id === fresh.id)) return;
    // Заменяем случайный слот
    const idx = Math.floor(Math.random() * users.value.length);
    users.value.splice(idx, 1, fresh);
  } catch (e) {
    // Молча — это фоновое обновление
  }
};

const handleRefresh = async () => {
  refreshing.value = true;
  await loadAll(8);
  refreshing.value = false;
};

const handleWrite = async (user) => {
  if (!authStore.isLoggedIn) {
    router.push({ name: 'login' });
    return;
  }
  if (writingFor.value) return;
  writingFor.value = user.username;
  try {
    const data = await chatsStore.createDmByUsername(user.username);
    router.push({ name: 'messages-room', params: { roomId: data.id } });
  } catch (e) {
    toast.error(e?.response?.data?.message || 'Не удалось открыть чат');
  } finally {
    writingFor.value = null;
  }
};

const roleBadge = (role) => {
  switch (role) {
    case 'admin':   return { label: 'админ',   cls: 'rb-admin' };
    case 'manager': return { label: 'манагер', cls: 'rb-manager' };
    default:        return null;
  }
};

onMounted(async () => {
  await loadAll(8);
  // Постоянно идущая лента: меняем один слот каждые 8 секунд
  rotateTimer = setInterval(() => {
    if (document.visibilityState !== 'visible') return;
    rotateOne();
  }, 8000);
});

onUnmounted(() => {
  if (rotateTimer) clearInterval(rotateTimer);
});
</script>

<template>
  <aside class="random-users-widget">
    <header class="ru-head">
      <div class="ru-eyebrow">⚔ Случайные воины</div>
      <button
        class="ru-refresh"
        :disabled="refreshing"
        @click="handleRefresh"
        title="Обновить"
        type="button"
      >
        <span :class="{ spinning: refreshing }">↻</span>
      </button>
    </header>

    <div v-if="loading && !users.length" class="ru-loading">Зов горна…</div>

    <ul v-else-if="users.length" class="ru-list">
      <li
        v-for="user in users"
        :key="user.id"
        class="ru-card"
      >
        <RouterLink
          :to="{ name: 'user-profile', params: { username: user.username } }"
          class="ru-avatar"
          :title="`@${user.username}`"
        >
          <img
            v-if="user.avatar"
            :src="`/avatars/${encodeURIComponent(user.avatar)}`"
            :alt="user.fullname"
          />
          <span v-else>{{ user.fullname?.[0]?.toUpperCase() ?? '?' }}</span>
        </RouterLink>

        <div class="ru-body">
          <RouterLink
            :to="{ name: 'user-profile', params: { username: user.username } }"
            class="ru-name"
          >
            {{ user.fullname || 'Воин' }}
          </RouterLink>
          <div class="ru-meta">
            <span class="ru-username">@{{ user.username }}</span>
            <span
              v-if="roleBadge(user.role)"
              class="ru-role"
              :class="roleBadge(user.role).cls"
            >{{ roleBadge(user.role).label }}</span>
          </div>
          <div v-if="user.followers_count > 0" class="ru-followers">
            ⚔ {{ user.followers_count }} подписчиков
          </div>
        </div>

        <button
          class="ru-write"
          :disabled="writingFor === user.username"
          @click="handleWrite(user)"
          :title="`Написать @${user.username}`"
          type="button"
        >
          <span v-if="writingFor === user.username">…</span>
          <span v-else>✉</span>
        </button>
      </li>
    </ul>

    <div v-else class="ru-empty">Воинов пока нет.</div>
  </aside>
</template>

<style scoped>
.random-users-widget {
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-md);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  overflow: hidden;
}
.ru-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 14px;
  border-bottom: 1px solid var(--iron-dark);
  background: rgba(0,0,0,0.25);
}
.ru-eyebrow {
  font-size: 11px;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: var(--iron-warm);
}
.ru-refresh {
  width: 28px;
  height: 28px;
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-sm);
  background: rgba(0,0,0,0.3);
  color: var(--text-muted);
  cursor: pointer;
  font-size: 14px;
  font-family: inherit;
  transition: all var(--dur-fast);
}
.ru-refresh:hover:not(:disabled) {
  border-color: var(--iron-warm);
  color: var(--iron-warm);
}
.ru-refresh:disabled { opacity: 0.5; cursor: not-allowed; }
.ru-refresh .spinning {
  display: inline-block;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.ru-loading, .ru-empty {
  padding: 20px 14px;
  text-align: center;
  color: var(--text-muted);
  font-style: italic;
  font-size: 13px;
}

.ru-list {
  list-style: none;
  margin: 0;
  padding: 4px 0;
}
.ru-card {
  display: grid;
  grid-template-columns: 36px 1fr 32px;
  gap: 10px;
  align-items: center;
  padding: 10px 14px;
  border-bottom: 1px solid rgba(60, 50, 40, 0.4);
  transition: all 0.4s var(--ease-smoke);
}
.ru-card:last-child { border-bottom: none; }
.ru-card:hover {
  background: rgba(226, 67, 16, 0.06);
}

/* Slot-machine fade-in эффект для ротируемых элементов */
.ru-card {
  animation: slot-in 0.5s ease-out;
}
@keyframes slot-in {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}

.ru-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 1px solid var(--iron-dark);
  background: rgba(0,0,0,0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-bright);
  font-weight: 600;
  overflow: hidden;
  text-decoration: none;
  transition: border-color var(--dur-fast);
}
.ru-avatar:hover { border-color: var(--iron-warm); }
.ru-avatar img { width: 100%; height: 100%; object-fit: cover; }

.ru-body {
  min-width: 0;
}
.ru-name {
  display: block;
  font-size: 13px;
  color: var(--text-bright);
  text-decoration: none;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.ru-name:hover { color: var(--iron-warm); }
.ru-meta {
  display: flex;
  gap: 6px;
  align-items: center;
  font-size: 11px;
  color: var(--text-muted);
}
.ru-username { letter-spacing: 0.02em; }
.ru-role {
  padding: 1px 5px;
  border-radius: 8px;
  font-size: 9px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}
.ru-role.rb-admin {
  background: rgba(226, 67, 16, 0.2);
  color: #ffba78;
  border: 1px solid rgba(226, 67, 16, 0.4);
}
.ru-role.rb-manager {
  background: rgba(108, 191, 108, 0.15);
  color: #8edb8e;
  border: 1px solid rgba(108, 191, 108, 0.3);
}
.ru-followers {
  font-size: 10px;
  color: var(--text-muted);
  margin-top: 2px;
  letter-spacing: 0.05em;
}

.ru-write {
  width: 32px;
  height: 32px;
  border: 1px solid var(--iron-warm);
  border-radius: var(--r-sm);
  background: linear-gradient(180deg, rgba(226, 67, 16, 0.2) 0%, rgba(0,0,0,0) 100%);
  color: var(--text-bright);
  cursor: pointer;
  font-size: 14px;
  font-family: inherit;
  transition: all var(--dur-fast);
}
.ru-write:hover:not(:disabled) {
  box-shadow: 0 0 10px rgba(226, 67, 16, 0.4);
  transform: translateY(-1px);
}
.ru-write:disabled { opacity: 0.4; cursor: not-allowed; }
</style>
