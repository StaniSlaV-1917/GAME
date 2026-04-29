<script setup>
/**
 * Phase 4 / Batch A — In-app уведомления.
 *
 * Страница /notifications. Показывает 50 последних уведомлений.
 * Сверху — кнопка «Прочитать все». По строке — клик разворачивает
 * её inline в подробный вид: полный текст, родительский коммент,
 * заголовок поста, кнопки «К посту / К профилю / Удалить».
 *
 * При переходе с дропдауна используется ?expand=<id> — страница
 * автоматически разворачивает указанное уведомление и скроллит к
 * нему.
 *
 * Поддерживаемые типы:
 *   • comment.created  — на ваш пост
 *   • comment.reply    — на ваш коммент
 *   • follow.created   — новый подписчик
 *   • reaction.created — реакция на ваш пост
 */
import { onMounted, computed, ref, nextTick, watch } from 'vue';
import { RouterLink, useRouter, useRoute } from 'vue-router';
import { useHead } from '@vueuse/head';
import { useNotificationsStore } from '../stores/notifications';

const notificationsStore = useNotificationsStore();
const router = useRouter();
const route = useRoute();

useHead({
  title: 'Уведомления — GameStore',
});

// Какое уведомление сейчас раскрыто (id) — null если ничего
const expandedId = ref(null);

onMounted(async () => {
  await notificationsStore.fetchAll();
  // Автораскрытие из ?expand=<id>
  const target = route.query.expand;
  if (target) {
    expandedId.value = String(target);
    // Помечаем прочитанным сразу при автораскрытии
    const found = notificationsStore.items.find((n) => n.id === target);
    if (found && !found.read_at) {
      notificationsStore.markRead(target);
    }
    await nextTick();
    scrollToExpanded();
  }
});

// Если query.expand меняется (например юзер пришёл из дропдауна повторно) —
// тоже раскрываем
watch(() => route.query.expand, (target) => {
  if (target) {
    expandedId.value = String(target);
    const found = notificationsStore.items.find((n) => n.id === target);
    if (found && !found.read_at) {
      notificationsStore.markRead(target);
    }
    nextTick(scrollToExpanded);
  }
});

const scrollToExpanded = () => {
  if (!expandedId.value) return;
  const el = document.getElementById(`notif-${expandedId.value}`);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
};

const items = computed(() => notificationsStore.items);
const loading = computed(() => notificationsStore.loading);
const unreadCount = computed(() => notificationsStore.unreadCount);
const hasItems = computed(() => items.value.length > 0);

const formatDate = (s) => {
  if (!s) return '';
  const d = new Date(s);
  const diff = (Date.now() - d.getTime()) / 1000;
  if (diff < 60) return 'только что';
  if (diff < 3600) return Math.floor(diff / 60) + ' мин назад';
  if (diff < 86400) return Math.floor(diff / 3600) + ' ч назад';
  if (diff < 604800) return Math.floor(diff / 86400) + ' дн назад';
  return d.toLocaleDateString('ru-RU');
};

const formatDateFull = (s) => {
  if (!s) return '';
  const d = new Date(s);
  return d.toLocaleString('ru-RU', {
    day: 'numeric', month: 'long', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
};

const titleFor = (n) => {
  const actor = n?.data?.actor?.fullname || n?.data?.actor?.username || 'Кто-то';
  switch (n.data?.event) {
    case 'comment.created': return `${actor} прокомментировал ваш пост`;
    case 'comment.reply':   return `${actor} ответил на ваш комментарий`;
    case 'follow.created':  return `${actor} подписался на вас`;
    case 'reaction.created': return `${actor} отреагировал на ваш пост ${n.data?.emoji || '⚔'}`;
    default: return 'Новое уведомление';
  }
};

/** Короткое превью (для свёрнутого вида). */
const shortPreview = (n) => {
  const text = n.data?.preview || '';
  if (!text) return '';
  return text.length > 110 ? text.slice(0, 110) + '…' : text;
};

const sigilFor = (n) => {
  switch (n.data?.event) {
    case 'comment.created':  return '💬';
    case 'comment.reply':    return '↪';
    case 'follow.created':   return '⚔';
    case 'reaction.created': return n.data?.emoji || '✦';
    default: return '◈';
  }
};

const isExpanded = (n) => expandedId.value === n.id;

/**
 * Клик по строке → toggle expand. Если разворачиваем впервые и
 * уведомление не прочитано — помечаем прочитанным.
 */
const handleRowClick = async (n) => {
  if (isExpanded(n)) {
    expandedId.value = null;
    return;
  }
  expandedId.value = n.id;
  if (!n.read_at) {
    await notificationsStore.markRead(n.id);
  }
};

const handleDelete = async (n, ev) => {
  ev?.stopPropagation();
  await notificationsStore.remove(n.id);
  if (expandedId.value === n.id) expandedId.value = null;
};

const handleMarkAll = async () => {
  await notificationsStore.markAllRead();
};

/** Куда ведёт «К посту». */
const postLink = (n) => {
  return n.data?.post_id
    ? { name: 'post', params: { id: n.data.post_id } }
    : null;
};

/** Куда ведёт «К профилю автора». */
const authorLink = (n) => {
  return n.data?.actor?.username
    ? { name: 'user-profile', params: { username: n.data.actor.username } }
    : null;
};

/**
 * Развёрнутое описание — что именно произошло.
 * Используется как «summary line» под заголовком в expanded view.
 */
const expandedSummary = (n) => {
  const e = n.data?.event;
  const actor = n?.data?.actor?.fullname || n?.data?.actor?.username || 'Кто-то';
  switch (e) {
    case 'comment.created':
      return `${actor} оставил новый комментарий под вашим постом${n.data?.post_title ? ` «${n.data.post_title}»` : ''}.`;
    case 'comment.reply':
      return `${actor} ответил на ваш комментарий в обсуждении${n.data?.post_title ? ` «${n.data.post_title}»` : ''}.`;
    case 'follow.created':
      return `${actor} теперь следит за вашими хрониками и будет видеть ваши посты в своей ленте.`;
    case 'reaction.created':
      return `${actor} поставил реакцию ${n.data?.emoji || '⚔'} на ваш пост${n.data?.post_title ? ` «${n.data.post_title}»` : ''}.`;
    default:
      return 'Подробности недоступны.';
  }
};
</script>

<template>
  <div class="notifications-view">
    <div class="notif-page-shell">

      <header class="notif-hero">
        <div class="notif-hero-text">
          <div class="notif-hero-eyebrow">⚔ Хроники</div>
          <h1 class="notif-hero-title">Уведомления</h1>
          <p class="notif-hero-sub">
            <span v-if="unreadCount > 0">
              Непрочитанных: <strong class="hl">{{ unreadCount }}</strong>
            </span>
            <span v-else>Всё прочитано. Тишина в кузнице.</span>
          </p>
        </div>
        <button
          v-if="unreadCount > 0"
          class="notif-mark-all"
          @click="handleMarkAll"
          type="button"
        >
          Прочитать все
        </button>
      </header>

      <div v-if="loading && !hasItems" class="notif-loading">
        Раскручиваем свиток…
      </div>

      <div v-else-if="!hasItems" class="notif-empty">
        <div class="empty-sigil" aria-hidden="true">◈</div>
        <h3>Свиток пуст</h3>
        <p>Когда кто-то отзовётся на ваши посты, ответит в обсуждении или подпишется — здесь зажгутся искры.</p>
        <div class="empty-actions">
          <RouterLink to="/feed" class="empty-btn">К ленте</RouterLink>
          <RouterLink to="/post/new" class="empty-btn ghost">Написать пост</RouterLink>
        </div>
      </div>

      <ul v-else class="notif-list">
        <li
          v-for="n in items"
          :key="n.id"
          :id="`notif-${n.id}`"
          class="notif-row"
          :class="{ unread: !n.read_at, expanded: isExpanded(n) }"
        >
          <!-- Свёрнутая часть: всегда видна, по клику toggle expand -->
          <div class="notif-collapsed" @click="handleRowClick(n)">
            <div class="notif-sigil" aria-hidden="true">{{ sigilFor(n) }}</div>

            <div class="notif-body">
              <div class="notif-title">{{ titleFor(n) }}</div>
              <div v-if="shortPreview(n)" class="notif-preview">{{ shortPreview(n) }}</div>
              <div class="notif-meta">{{ formatDate(n.created_at) }}</div>
            </div>

            <div class="notif-chevron" :class="{ rotated: isExpanded(n) }" aria-hidden="true">▾</div>
          </div>

          <!-- Развёрнутая часть -->
          <Transition name="notif-expand">
            <div v-if="isExpanded(n)" class="notif-expanded">
              <div class="notif-sep" aria-hidden="true"></div>

              <div class="notif-summary">{{ expandedSummary(n) }}</div>

              <!-- Полный preview / контекст -->
              <div v-if="n.data?.preview" class="notif-quote">
                <div class="quote-label">{{
                  n.data.event === 'comment.created' || n.data.event === 'comment.reply'
                    ? 'Содержание:'
                    : (n.data.event === 'reaction.created' ? 'Пост:' : '')
                }}</div>
                <blockquote class="quote-body">{{ n.data.preview }}</blockquote>
              </div>

              <!-- Родительский коммент (для reply) -->
              <div v-if="n.data?.event === 'comment.reply' && n.data?.parent_preview" class="notif-quote">
                <div class="quote-label">Ваш комментарий:</div>
                <blockquote class="quote-body parent">{{ n.data.parent_preview }}</blockquote>
              </div>

              <!-- Полная дата -->
              <div class="notif-fulltime">⏳ {{ formatDateFull(n.created_at) }}</div>

              <!-- Кнопки действий -->
              <div class="notif-cta">
                <RouterLink
                  v-if="postLink(n)"
                  :to="postLink(n)"
                  class="cta-btn primary"
                >
                  К посту →
                </RouterLink>
                <RouterLink
                  v-if="authorLink(n)"
                  :to="authorLink(n)"
                  class="cta-btn"
                >
                  Профиль автора
                </RouterLink>
                <button
                  class="cta-btn ghost danger"
                  @click="(e) => handleDelete(n, e)"
                  type="button"
                >
                  Удалить
                </button>
              </div>
            </div>
          </Transition>
        </li>
      </ul>

    </div>
  </div>
</template>

<style scoped>
/* ─────────────────────────────────────────────────────────────
   NOTIFICATIONS PAGE — Ashenforge стиль:
   тлеющий пергаментный свиток, иконы-сигилы у каждой строки,
   мягкое ember-свечение для непрочитанных, разворачивание inline.
   ───────────────────────────────────────────────────────────── */
.notifications-view {
  min-height: calc(100vh - 73px);
  padding: 32px 16px 64px;
}
.notif-page-shell {
  max-width: 760px;
  margin: 0 auto;
}

/* Hero */
.notif-hero {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 24px;
  padding: 24px 24px 22px;
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-md);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  box-shadow: var(--inset-iron-top), 0 4px 14px rgba(0,0,0,0.4);
  margin-bottom: 18px;
  position: relative;
  overflow: hidden;
}
.notif-hero::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at top right, rgba(226, 67, 16, 0.15) 0%, transparent 60%);
  pointer-events: none;
}
.notif-hero-text { position: relative; z-index: 1; }
.notif-hero-eyebrow {
  font-size: 11px;
  letter-spacing: 0.25em;
  text-transform: uppercase;
  color: var(--iron-warm, #c98851);
  margin-bottom: 4px;
}
.notif-hero-title {
  font-size: 28px;
  margin: 0 0 4px;
  color: var(--text-bright);
  font-family: var(--font-display, inherit);
  letter-spacing: 0.02em;
}
.notif-hero-sub {
  margin: 0;
  color: var(--text-muted);
  font-size: 14px;
}
.notif-hero-sub .hl {
  color: var(--text-bright);
  text-shadow: 0 0 6px rgba(226, 67, 16, 0.55);
}
.notif-mark-all {
  position: relative;
  z-index: 1;
  padding: 10px 18px;
  border: 1px solid var(--iron-warm);
  border-radius: var(--r-sm);
  background: linear-gradient(180deg, rgba(226, 67, 16, 0.12) 0%, rgba(0,0,0,0.0) 100%);
  color: var(--text-bright);
  font-size: 13px;
  letter-spacing: 0.05em;
  cursor: pointer;
  transition: all var(--dur-fast) var(--ease-smoke);
  white-space: nowrap;
  font-family: inherit;
}
.notif-mark-all:hover {
  border-color: #e24310;
  box-shadow: 0 0 12px rgba(226, 67, 16, 0.45);
  transform: translateY(-1px);
}

/* Loading */
.notif-loading {
  text-align: center;
  padding: 48px 16px;
  color: var(--text-muted);
  font-style: italic;
}

/* Empty */
.notif-empty {
  text-align: center;
  padding: 64px 24px;
  border: 1px dashed var(--iron-dark);
  border-radius: var(--r-md);
  background: rgba(0,0,0,0.18);
}
.empty-sigil {
  font-size: 48px;
  color: var(--iron-warm);
  margin-bottom: 12px;
  filter: drop-shadow(0 0 12px rgba(226, 67, 16, 0.35));
}
.notif-empty h3 {
  margin: 0 0 8px;
  color: var(--text-bright);
}
.notif-empty p {
  margin: 0 auto 20px;
  max-width: 440px;
  color: var(--text-muted);
  line-height: 1.5;
}
.empty-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
  flex-wrap: wrap;
}
.empty-btn {
  padding: 10px 22px;
  border: 1px solid var(--iron-warm);
  border-radius: var(--r-sm);
  background: linear-gradient(180deg, rgba(226, 67, 16, 0.18) 0%, rgba(0,0,0,0) 100%);
  color: var(--text-bright);
  text-decoration: none;
  font-size: 13px;
  letter-spacing: 0.05em;
  transition: all var(--dur-fast) var(--ease-smoke);
}
.empty-btn:hover { box-shadow: 0 0 10px rgba(226, 67, 16, 0.4); }
.empty-btn.ghost {
  background: transparent;
  border-color: var(--iron-dark);
  color: var(--text-parchment);
}

/* List */
.notif-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.notif-row {
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-sm);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  transition: all var(--dur-fast) var(--ease-smoke);
  position: relative;
  overflow: hidden;
}
.notif-row:hover {
  border-color: var(--iron-warm);
  box-shadow: var(--glow-ember-soft);
}
.notif-row.unread {
  border-color: rgba(226, 67, 16, 0.45);
  box-shadow: 0 0 0 1px rgba(226, 67, 16, 0.18) inset;
}
.notif-row.unread::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 3px;
  background: linear-gradient(180deg, #e24310 0%, #7a1f0c 100%);
}
.notif-row.expanded {
  border-color: var(--iron-warm);
  box-shadow: var(--glow-ember-soft);
}

/* Свёрнутая часть — всегда видима */
.notif-collapsed {
  display: flex;
  gap: 14px;
  align-items: center;
  padding: 14px 16px;
  cursor: pointer;
  user-select: none;
}
.notif-sigil {
  flex-shrink: 0;
  width: 38px;
  height: 38px;
  border-radius: 50%;
  border: 1px solid var(--iron-dark);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  background: rgba(0,0,0,0.3);
  color: var(--iron-warm);
}
.notif-row.unread .notif-sigil {
  border-color: var(--iron-warm);
  filter: drop-shadow(0 0 6px rgba(226, 67, 16, 0.4));
}

.notif-body {
  flex: 1;
  min-width: 0;
}
.notif-title {
  font-size: 14px;
  color: var(--text-bright);
  margin-bottom: 2px;
  font-weight: 500;
}
.notif-preview {
  font-size: 13px;
  color: var(--text-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 4px;
  font-style: italic;
}
.notif-meta {
  font-size: 11px;
  color: var(--text-muted);
  letter-spacing: 0.05em;
  text-transform: lowercase;
}

.notif-chevron {
  flex-shrink: 0;
  font-size: 16px;
  color: var(--text-muted);
  transition: transform var(--dur-fast);
}
.notif-chevron.rotated { transform: rotate(180deg); color: var(--iron-warm); }

/* Развёрнутая часть */
.notif-expand-enter-active,
.notif-expand-leave-active {
  transition: max-height 0.32s var(--ease-smoke), opacity 0.22s ease;
  overflow: hidden;
}
.notif-expand-enter-from,
.notif-expand-leave-to {
  max-height: 0;
  opacity: 0;
}
.notif-expand-enter-to,
.notif-expand-leave-from {
  max-height: 600px;
  opacity: 1;
}

.notif-expanded {
  padding: 0 16px 16px;
}
.notif-sep {
  height: 1px;
  background: linear-gradient(90deg, transparent 0%, var(--iron-dark) 50%, transparent 100%);
  margin: 0 0 14px;
}
.notif-summary {
  font-size: 14px;
  line-height: 1.5;
  color: var(--text-parchment);
  margin-bottom: 14px;
}
.notif-quote {
  margin-bottom: 14px;
}
.quote-label {
  font-size: 10px;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: var(--iron-warm);
  margin-bottom: 6px;
}
.quote-body {
  margin: 0;
  padding: 12px 14px;
  border-left: 2px solid var(--iron-warm);
  background: rgba(0,0,0,0.25);
  border-radius: 0 var(--r-sm) var(--r-sm) 0;
  font-size: 13px;
  line-height: 1.6;
  color: var(--text-bright);
  font-style: italic;
  white-space: pre-wrap;
  word-break: break-word;
}
.quote-body.parent {
  border-left-color: var(--iron-dark);
  opacity: 0.85;
  font-style: normal;
}
.notif-fulltime {
  font-size: 11px;
  color: var(--text-muted);
  letter-spacing: 0.05em;
  margin-bottom: 14px;
}
.notif-cta {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}
.cta-btn {
  padding: 9px 16px;
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-sm);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  color: var(--text-parchment);
  text-decoration: none;
  font-size: 12px;
  letter-spacing: 0.05em;
  cursor: pointer;
  transition: all var(--dur-fast) var(--ease-smoke);
  font-family: inherit;
}
.cta-btn:hover {
  border-color: var(--iron-warm);
  color: var(--text-bright);
  transform: translateY(-1px);
}
.cta-btn.primary {
  border-color: var(--iron-warm);
  background: linear-gradient(180deg, rgba(226, 67, 16, 0.18) 0%, rgba(0,0,0,0) 100%);
  color: var(--text-bright);
}
.cta-btn.primary:hover {
  box-shadow: 0 0 10px rgba(226, 67, 16, 0.4);
}
.cta-btn.ghost {
  background: transparent;
}
.cta-btn.danger:hover {
  border-color: #b8341a;
  color: #ffb494;
}

@media (max-width: 600px) {
  .notif-hero {
    flex-direction: column;
    align-items: stretch;
    gap: 14px;
  }
  .notif-mark-all { width: 100%; }
  .notif-collapsed { padding: 12px; gap: 10px; }
  .notif-sigil { width: 32px; height: 32px; font-size: 16px; }
  .notif-title { font-size: 13px; }
  .notif-expanded { padding: 0 12px 12px; }
  .cta-btn { flex: 1; min-width: 110px; text-align: center; }
}
</style>
