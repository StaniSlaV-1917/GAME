<script setup>
/**
 * Виджет «Случайные воины» — вертикальная бегущая лента.
 *
 * UX:
 *   • Загружает 16 случайных юзеров (больше для длинной ленты)
 *   • CSS-анимация скролит cards снизу вверх непрерывно (~60 сек на цикл)
 *   • Список рендерится ДВАЖДЫ подряд для seamless loop (когда первый
 *     набор уезжает наверх, второй сразу за ним — нет видимого «прыжка»)
 *   • На hover/focus — анимация ставится на паузу (юзер может кликнуть)
 *   • Каждые 30 сек тихий refresh — подгружаем новых случайных юзеров
 *   • Клик по карточке → /u/:username
 *   • Кнопка «✉» появляется на hover карточки → создаёт DM
 *
 * Скрывается на мобиле (CSS).
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
const writingFor = ref(null);
let refreshTimer = null;

// Дублированный список для seamless loop.
// Если юзеров мало (например 2-3), inflate'им — повторяем их в массиве
// пока не наберётся минимум 16 карточек на ОДНУ копию. Потом дублируем
// весь набор ещё раз (для seamless animation -50% translateY).
// Таким образом с 2 юзерами рендерится 32 карточки и поток выглядит плавно.
const looped = computed(() => {
  if (!users.value.length) return [];
  const MIN_PER_COPY = 16;
  const inflated = [];
  let i = 0;
  while (inflated.length < MIN_PER_COPY) {
    inflated.push(users.value[i % users.value.length]);
    i++;
  }
  return [...inflated, ...inflated];
});

const loadUsers = async () => {
  loading.value = true;
  try {
    const { data } = await api.get('/users/random', { params: { limit: 16 } });
    users.value = data.data || [];
  } catch (e) {
    console.warn('[RandomUsers] load failed', e);
  } finally {
    loading.value = false;
  }
};

const handleWrite = async (user, ev) => {
  ev?.stopPropagation();
  ev?.preventDefault();
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
    case 'admin':   return { label: 'А', cls: 'rb-admin', title: 'Админ' };
    case 'manager': return { label: 'М', cls: 'rb-manager', title: 'Менеджер' };
    default:        return null;
  }
};

onMounted(async () => {
  await loadUsers();
  // Каждые 30 сек — подгружаем новый набор случайных юзеров для разнообразия
  refreshTimer = setInterval(() => {
    if (document.visibilityState !== 'visible') return;
    loadUsers();
  }, 30000);
});

onUnmounted(() => {
  if (refreshTimer) clearInterval(refreshTimer);
});
</script>

<template>
  <aside class="random-users-marquee">
    <header class="rm-head">
      <div class="rm-eyebrow">⚔ Воины кузницы</div>
    </header>

    <div v-if="loading && !users.length" class="rm-loading">Зов горна…</div>

    <div v-else-if="users.length" class="rm-viewport">
      <!-- Внутренний контейнер с бесконечной анимацией.
           Список users рендерится дважды — когда первая копия уходит
           наверх (-50%), вторая сразу за ней → нет видимого reset. -->
      <div class="rm-track">
        <RouterLink
          v-for="(user, idx) in looped"
          :key="`${user.id}-${idx}`"
          :to="{ name: 'user-profile', params: { username: user.username } }"
          class="rm-card"
        >
          <div class="rm-avatar">
            <img
              v-if="user.avatar"
              :src="`/avatars/${encodeURIComponent(user.avatar)}`"
              :alt="user.fullname"
            />
            <span v-else>{{ user.fullname?.[0]?.toUpperCase() ?? '?' }}</span>
            <span
              v-if="roleBadge(user.role)"
              class="rm-role"
              :class="roleBadge(user.role).cls"
              :title="roleBadge(user.role).title"
            >{{ roleBadge(user.role).label }}</span>
          </div>
          <div class="rm-body">
            <span class="rm-name">{{ user.fullname || 'Воин' }}</span>
            <span class="rm-username">@{{ user.username }}</span>
          </div>
          <button
            class="rm-write"
            :disabled="writingFor === user.username"
            @click="handleWrite(user, $event)"
            :title="`Написать @${user.username}`"
            type="button"
          >
            <span v-if="writingFor === user.username">…</span>
            <span v-else>✉</span>
          </button>
        </RouterLink>
      </div>

      <!-- Fade-маски сверху и снизу (плавное исчезновение карточек на краях) -->
      <div class="rm-fade rm-fade-top" aria-hidden="true"></div>
      <div class="rm-fade rm-fade-bot" aria-hidden="true"></div>
    </div>

    <div v-else class="rm-empty">Воинов пока нет.</div>
  </aside>
</template>

<style scoped>
.random-users-marquee {
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-md);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  /* Виджет тянется на 100% высоты родителя (.feed-sidebar который sticky)
     — фактически от хедера до низа viewport'а. Скролл анимация всё равно
     внутри rm-viewport, бесконечная. */
  height: 100%;
}
.rm-head {
  padding: 12px 14px;
  border-bottom: 1px solid var(--iron-dark);
  background: rgba(0,0,0,0.25);
  flex-shrink: 0;
}
.rm-eyebrow {
  font-size: 11px;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: var(--iron-warm);
  text-align: center;
}

.rm-loading, .rm-empty {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-muted);
  font-style: italic;
  font-size: 13px;
}

/* ── MARQUEE viewport ── */
.rm-viewport {
  position: relative;
  flex: 1;
  overflow: hidden;
  /* Прячем scrollbar на всякий */
  scrollbar-width: none;
}
.rm-viewport::-webkit-scrollbar { display: none; }

.rm-track {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 8px 10px;
  /* Анимация скролит трек на -50% (ровно высота одной копии списка),
     создавая иллюзию бесконечного потока — вторая копия как раз
     встаёт на место первой. */
  animation: marquee-scroll 60s linear infinite;
  will-change: transform;
}
@keyframes marquee-scroll {
  from { transform: translateY(0); }
  to   { transform: translateY(-50%); }
}

/* На hover паузим — юзер может прочитать/кликнуть */
.rm-viewport:hover .rm-track,
.rm-viewport:focus-within .rm-track {
  animation-play-state: paused;
}

/* Снижение анимации для prefers-reduced-motion */
@media (prefers-reduced-motion: reduce) {
  .rm-track {
    animation: none;
  }
}

/* Fade-маски сверху и снизу (плавное затухание у краёв) */
.rm-fade {
  position: absolute;
  left: 0;
  right: 0;
  height: 36px;
  pointer-events: none;
  z-index: 2;
}
.rm-fade-top {
  top: 0;
  background: linear-gradient(180deg, var(--ash-coal) 0%, transparent 100%);
}
.rm-fade-bot {
  bottom: 0;
  background: linear-gradient(0deg, var(--ash-coal) 0%, transparent 100%);
}

/* ── Карточка юзера ── */
.rm-card {
  display: grid;
  grid-template-columns: 38px 1fr 30px;
  gap: 10px;
  align-items: center;
  padding: 8px 10px;
  border: 1px solid rgba(60, 50, 40, 0.5);
  border-radius: var(--r-sm);
  background: rgba(0,0,0,0.18);
  text-decoration: none;
  color: inherit;
  transition: all var(--dur-fast);
  flex-shrink: 0;
}
.rm-card:hover {
  background: rgba(226, 67, 16, 0.08);
  border-color: var(--iron-warm);
  transform: translateX(2px);
}
.rm-card:hover .rm-write {
  opacity: 1;
  transform: scale(1);
}

.rm-avatar {
  position: relative;
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
.rm-avatar img { width: 100%; height: 100%; object-fit: cover; }
.rm-role {
  position: absolute;
  bottom: -2px;
  right: -2px;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  font-size: 8px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--ash-coal);
  z-index: 1;
}
.rm-role.rb-admin {
  background: linear-gradient(180deg, #e24310 0%, #7a1f0c 100%);
  color: #fff5d6;
}
.rm-role.rb-manager {
  background: linear-gradient(180deg, #6cbf6c 0%, #3e7d3e 100%);
  color: #f0fff0;
}

.rm-body {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 1px;
}
.rm-name {
  font-size: 12px;
  color: var(--text-bright);
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.rm-username {
  font-size: 10px;
  color: var(--text-muted);
  letter-spacing: 0.02em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.rm-write {
  width: 30px;
  height: 30px;
  border: 1px solid var(--iron-warm);
  border-radius: var(--r-sm);
  background: linear-gradient(180deg, rgba(226, 67, 16, 0.25) 0%, rgba(0,0,0,0) 100%);
  color: var(--text-bright);
  cursor: pointer;
  font-size: 13px;
  font-family: inherit;
  /* Скрыта по дефолту, показывается на hover карточки */
  opacity: 0;
  transform: scale(0.85);
  transition: all var(--dur-fast);
}
.rm-write:hover:not(:disabled) {
  box-shadow: 0 0 10px rgba(226, 67, 16, 0.5);
}
.rm-write:disabled { opacity: 0.4; cursor: not-allowed; }

/* На touch-устройствах кнопка всегда видна (нет hover) */
@media (hover: none) {
  .rm-write { opacity: 1; transform: scale(1); }
}
</style>
