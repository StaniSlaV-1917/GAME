<script setup>
import { useRoute } from 'vue-router';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { RouterLink, RouterView, useRouter } from 'vue-router';
import { useAuthStore } from './stores/auth';
import { useThemeStore } from './stores/theme';
import { useCartStore } from './stores/cart';
import { useModeStore } from './stores/mode';
import { useNotificationsStore } from './stores/notifications';
import { useChatsStore } from './stores/chats';
import { storeToRefs } from 'pinia';
import api from './api/axios';
import ParticlesBackground from './components/ParticlesBackground.vue';
import SupportChat from './components/SupportChat.vue';
import CursorTrail from './components/CursorTrail.vue';
import { resolveMediaUrl } from './utils/media';
import { useToast } from './composables/useToast';
import hordeSigilUrl from './assets/horde-sigil.png';

const { toasts, remove: removeToast } = useToast();

const authStore = useAuthStore();
const themeStore = useThemeStore();
const modeStore = useModeStore();
const notificationsStore = useNotificationsStore();
const chatsStore = useChatsStore();
const { user, isLoggedIn } = storeToRefs(authStore);
const { badge: notifBadge, unreadCount: notifUnreadCount, peeks: notifPeeks } = storeToRefs(notificationsStore);
const { badge: chatsBadge, totalUnread: chatsUnread } = storeToRefs(chatsStore);
const router = useRouter();
const route = useRoute();

// Линк аватара/имени в шапке.
//   • Если у юзера есть username → публичный профиль /u/:username
//     (там кнопка «Редактировать» ведёт обратно на /profile)
//   • Если username не задан — отправляем сразу в /profile (settings),
//     чтобы юзер заполнил поле username.
const profileLinkTarget = computed(() => {
  if (user.value?.username) {
    return { name: 'user-profile', params: { username: user.value.username } };
  }
  return { name: 'profile' };
});

// Scroll to top on route change
watch(() => route.path, () => {
  window.scrollTo({ top: 0, behavior: 'smooth' });
}, { flush: 'post' });

const scrolled = ref(false);
const mobileMenuOpen = ref(false);

// ── Theme dropdown ──
const themeDropdownOpen = ref(false);
let themeHoverTimer = null;
const openThemeDropdown = () => {
  if (isMobileViewport()) return;     // на мобиле dropdown не открываем
  clearTimeout(themeHoverTimer);
  themeDropdownOpen.value = true;
};
const closeThemeDropdown = () => {
  themeHoverTimer = setTimeout(() => { themeDropdownOpen.value = false; }, 200);
};

/**
 * Определяет, находится ли юзер на мобильном viewport (≤720px).
 * На мобиле клик по теме циклит между темами вместо открытия dropdown'а
 * (который слишком громоздкий на маленьком экране).
 */
const isMobileViewport = () => window.matchMedia('(max-width: 720px)').matches;

/**
 * Обработчик клика по theme-кнопке. На десктопе — toggle dropdown,
 * на мобиле — цикл dark → light → legacy → dark…
 */
const onThemeButtonClick = () => {
  if (isMobileViewport()) {
    themeStore.cycle();
    themeDropdownOpen.value = false;  // на всякий случай если был открыт
  } else {
    themeDropdownOpen.value = !themeDropdownOpen.value;
  }
};

const themeOptions = [
  { value: 'dark',   icon: '☾', label: 'Ночь Кузницы',   desc: 'Тёмная Орда (текущая)' },
  { value: 'light',  icon: '☀', label: 'День Осады',      desc: 'Песок и терракота' },
  { value: 'legacy', icon: '◈', label: 'Древний свод',    desc: 'Старая тёмно-синяя' },
];
const themeIconFor = (value) => themeOptions.find(t => t.value === value)?.icon || '☾';

// ──────────────────────────────────────────────────────────────
// Bell-dropdown — лёгкий превью последних 7 уведомлений.
// Подгружаем fetchAll при первом открытии (кэш живёт пока юзер не
// разлогинится). При новых событиях из poll'а unreadCount обновится
// сам, превью обновится при следующем открытии или при manual reload.
// ──────────────────────────────────────────────────────────────
const notifDropdownOpen = ref(false);
const notifDropdownLoading = computed(() => notificationsStore.loading);
const bellWrapRef = ref(null);

const notifPreview = computed(() => notificationsStore.items.slice(0, 7));

const onBellClick = async () => {
  // Toggle
  if (notifDropdownOpen.value) {
    notifDropdownOpen.value = false;
    notificationsStore.setPeekSuppression(false);
    return;
  }
  notifDropdownOpen.value = true;
  // Открыли dropdown — peek'и больше не нужны (они висят как промокашка
  // когда dropdown закрыт; пока открыт — юзер видит полный список)
  notificationsStore.setPeekSuppression(true);
  // Если ещё ни разу не загружали или прошло много времени — обновим
  if (!notificationsStore.loaded) {
    await notificationsStore.fetchAll();
  } else {
    // Тихо обновим в фоне, чтобы превью было свежим
    notificationsStore.fetchAll();
  }
};

const onNotifItemClick = (n) => {
  notifDropdownOpen.value = false;
  // Открываем /notifications?expand=<id> чтобы страница автораскрыла
  // именно это уведомление
  router.push({ name: 'notifications', query: { expand: n.id } });
};

const handleNotifMarkAll = (ev) => {
  ev?.stopPropagation();
  notificationsStore.markAllRead();
};

// ──────────────────────────────────────────────────────────────
// Peek-карточки — выпрыгивают из bell при новых событиях.
// Каждый peek живёт PEEK_TTL_MS, потом ауто-схлопывается обратно в bell.
// Клик по peek → открыть /notifications?expand=<id>. Клик по ✕ → dismiss.
// Когда юзер открывает dropdown — все peek'и скрываются (suppressPeeks).
// ──────────────────────────────────────────────────────────────
const PEEK_TTL_MS = 5500;
const peekTimers = new Map(); // id → timeoutId

const onPeekClick = (p) => {
  notificationsStore.dismissPeek(p.id);
  router.push({ name: 'notifications', query: { expand: p.id } });
};

const onPeekDismiss = (id) => {
  notificationsStore.dismissPeek(id);
};

// Watcher: для каждого нового peek в очереди стартуем таймер
// автодисмисса. При ухода peek'а из массива (через dismiss или TTL) —
// чистим таймер.
watch(notifPeeks, (curr) => {
  const liveIds = new Set(curr.map((p) => p.id));
  // Чистим таймеры для исчезнувших
  for (const id of peekTimers.keys()) {
    if (!liveIds.has(id)) {
      clearTimeout(peekTimers.get(id));
      peekTimers.delete(id);
    }
  }
  // Стартуем таймеры для новых
  for (const p of curr) {
    if (!peekTimers.has(p.id)) {
      const t = setTimeout(() => {
        notificationsStore.dismissPeek(p.id);
      }, PEEK_TTL_MS);
      peekTimers.set(p.id, t);
    }
  }
}, { deep: true });

// Сигил/заголовок/превью/дата — те же утилиты что на странице,
// дублируем сюда чтобы App.vue был самодостаточен.
const notifSigil = (n) => {
  switch (n.data?.event) {
    case 'comment.created':  return '💬';
    case 'comment.reply':    return '↪';
    case 'follow.created':   return '⚔';
    case 'reaction.created': return n.data?.emoji || '✦';
    default: return '◈';
  }
};
const notifTitle = (n) => {
  const actor = n?.data?.actor?.fullname || n?.data?.actor?.username || 'Кто-то';
  switch (n.data?.event) {
    case 'comment.created': return `${actor} прокомментировал ваш пост`;
    case 'comment.reply':   return `${actor} ответил на ваш комментарий`;
    case 'follow.created':  return `${actor} подписался на вас`;
    case 'reaction.created': return `${actor} отреагировал на ваш пост`;
    default: return 'Новое уведомление';
  }
};
const notifShortPreview = (n) => {
  const text = n?.data?.preview || '';
  if (!text) return '';
  return text.length > 80 ? text.slice(0, 80) + '…' : text;
};
const notifFormatDate = (s) => {
  if (!s) return '';
  const d = new Date(s);
  const diff = (Date.now() - d.getTime()) / 1000;
  if (diff < 60) return 'только что';
  if (diff < 3600) return Math.floor(diff / 60) + ' мин';
  if (diff < 86400) return Math.floor(diff / 3600) + ' ч';
  if (diff < 604800) return Math.floor(diff / 86400) + ' дн';
  return d.toLocaleDateString('ru-RU');
};

// Закрытие дропдауна по клику вне — биндим обработчик на document
// при открытии и снимаем при закрытии (минимальный listener overhead).
const onDocClickForBell = (e) => {
  if (!notifDropdownOpen.value) return;
  const wrap = bellWrapRef.value;
  if (wrap && !wrap.contains(e.target)) {
    notifDropdownOpen.value = false;
  }
};
watch(notifDropdownOpen, (open) => {
  // Toggle peek suppression: пока dropdown открыт — peek'и не нужны
  notificationsStore.setPeekSuppression(open);
  if (open) {
    // Use nextTick-ish: wait one tick чтобы текущий клик не закрыл сразу
    setTimeout(() => document.addEventListener('click', onDocClickForBell), 0);
  } else {
    document.removeEventListener('click', onDocClickForBell);
  }
});

// Закрытие дропдауна при смене маршрута (если юзер ушёл по ссылке)
watch(() => route.path, () => { notifDropdownOpen.value = false; });

// ── Global search (всегда развёрнут в шапке) ──
const searchQuery = ref('');
const searchResults = ref([]);
const searchLoading = ref(false);
const searchInputRef = ref(null);
let allGamesCache = null;
let searchTimer = null;

const doSearch = async (q) => {
  clearTimeout(searchTimer);
  if (!q.trim()) { searchResults.value = []; return; }
  searchTimer = setTimeout(async () => {
    searchLoading.value = true;
    try {
      if (!allGamesCache) {
        const { data } = await api.get('/games');
        allGamesCache = data;
      }
      const lower = q.toLowerCase();
      searchResults.value = allGamesCache
        .filter(g => g.title?.toLowerCase().includes(lower) || g.genre?.toLowerCase().includes(lower))
        .slice(0, 7);
    } catch (e) {
      console.error(e);
    } finally {
      searchLoading.value = false;
    }
  }, 280);
};

watch(searchQuery, (q) => doSearch(q));

const goToGame = (id) => {
  searchQuery.value = '';
  searchResults.value = [];
  router.push({ name: 'game', params: { id } });
};

// Старый orc-cursor (SVG-курсор, заменявший нативный) удалён.
// Теперь нативный курсор ОС остаётся, а поверх — CursorTrail (см. ниже).
// Только определяем isTouch — чтобы не цеплять mouse-only логику на мобиле
// (mobile-menu, hamburger и пр. — там нативный тач-курсор как есть).
const isTouch = ref(false);

const handleLogout = async () => {
  notificationsStore.reset();
  chatsStore.reset();
  await authStore.logout();
  mobileMenuOpen.value = false;
  router.push({ name: 'login' });
};

const onScroll = () => {
  scrolled.value = window.scrollY > 20;
};

onMounted(() => {
  authStore.fetchUser();
  window.addEventListener('scroll', onScroll, { passive: true });
  isTouch.value = window.matchMedia('(pointer: coarse)').matches;
  if (isTouch.value) {
    document.documentElement.classList.add('touch-device');
  }
  // Cursor-trail сам подключает свои листенеры через свой onMounted —
  // здесь ничего не делаем, просто рендерим компонент в template.
});
onUnmounted(() => {
  window.removeEventListener('scroll', onScroll);
  clearTimeout(searchTimer);
  document.removeEventListener('click', onDocClickForBell);
  notificationsStore.stopPolling();
  // Чистим все peek-таймеры
  for (const t of peekTimers.values()) clearTimeout(t);
  peekTimers.clear();
});

// Phase 4 / Batch A — стартуем/останавливаем polling по статусу логина.
// Реактивно: при login → start, при logout → reset уже вызван в handleLogout,
// но на всякий — watch на isLoggedIn для случаев когда логин/логаут
// произошёл из другого места (например, истёк токен и authStore.logout()
// дёрнулся изнутри).
watch(isLoggedIn, (logged) => {
  if (logged) {
    notificationsStore.startPolling();
    chatsStore.startPolling();
  } else {
    notificationsStore.reset();
    chatsStore.reset();
  }
}, { immediate: true });
</script>

<template>
  <div id="app-wrapper">

    <!-- Cursor trail — кованый шлейф искр за нативным курсором.
         Сам компонент Teleport'ится в body и отключается на тач + reduced-motion. -->
    <CursorTrail />

    <!-- Ambient particles (existing; will blend with new theme) -->
    <ParticlesBackground />

    <!-- ===================================================
         HEADER · The Forge Gate
    =================================================== -->
    <header class="main-header" :class="{ scrolled }">
      <!-- Декоративная железная полоса сверху -->
      <div class="header-iron-strip"></div>
      <!-- Тлеющая ember-линия снизу -->
      <div class="header-ember-line"></div>

      <div class="header-content">

        <!-- Logo: sigil + wordmark. Клик → home выбранного режима (shop=/, forum=/feed) -->
        <RouterLink :to="modeStore.homeRoute" class="logo-link" @click="mobileMenuOpen = false" aria-label="GameStore — главная">
          <span class="logo-sigil-wrap">
            <img alt="" class="logo-sigil" :src="hordeSigilUrl" width="44" height="44" />
            <span class="logo-sigil-glow" aria-hidden="true"></span>
          </span>
          <span class="logo-word">
            <span class="logo-word-main">GAME</span><span class="logo-word-accent">STORE</span>
            <span class="logo-word-sub">Оплот воина</span>
          </span>
        </RouterLink>

        <!-- Mode toggle: МАГАЗИН ↔ ФОРУМ -->
        <div class="mode-toggle" role="tablist" aria-label="Режим сайта">
          <button
            class="mode-btn"
            :class="{ active: modeStore.isShop }"
            @click="modeStore.setMode('shop')"
            role="tab"
            :aria-selected="modeStore.isShop"
            type="button"
          >
            <span class="mode-icon" aria-hidden="true">⚔</span>
            <span class="mode-label">Магазин</span>
          </button>
          <button
            class="mode-btn"
            :class="{ active: modeStore.isForum }"
            @click="modeStore.setMode('forum')"
            role="tab"
            :aria-selected="modeStore.isForum"
            type="button"
          >
            <span class="mode-icon" aria-hidden="true">📜</span>
            <span class="mode-label">Форум</span>
          </button>
        </div>

        <!-- Desktop nav — пункты зависят от текущего режима -->
        <nav class="main-nav" aria-label="Главное меню">
          <RouterLink
            v-for="item in modeStore.navItems"
            :key="item.to"
            :to="item.to"
            class="nav-link"
          >
            <span>{{ item.label }}</span>
          </RouterLink>
          <RouterLink v-if="user?.is_admin" to="/admin" class="nav-link admin-link"><span>Совет</span></RouterLink>
        </nav>

        <!-- Global search — "Вестник" (всегда развёрнут, не за иконкой) -->
        <div class="search-wrap is-always-open">
          <div class="search-bar-wrap">
            <span class="search-icon-small" aria-hidden="true">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </span>
            <input
              ref="searchInputRef"
              v-model="searchQuery"
              class="search-input"
              placeholder="Искать игру, жанр, платформу..."
              @keydown.escape="searchQuery = ''"
            />
            <button v-if="searchQuery" class="search-close-btn" @click="searchQuery = ''" title="Очистить">✕</button>

            <!-- Dropdown results -->
            <Transition name="dropdown">
              <div v-if="searchQuery && (searchResults.length || searchLoading)" class="search-dropdown">
                <div v-if="searchLoading" class="search-loading">
                  <span class="search-spinner"></span> Разведка ведётся…
                </div>
                <template v-else>
                  <div
                    v-for="g in searchResults" :key="g.id"
                    class="search-result-item"
                    @click="goToGame(g.id)"
                  >
                    <img :src="resolveMediaUrl(g.image)" :alt="g.title" class="sr-img" loading="lazy" width="44" height="44" />
                    <div class="sr-info">
                      <span class="sr-title">{{ g.title }}</span>
                      <span class="sr-genre">{{ g.genre }}</span>
                    </div>
                    <span class="sr-price">{{ Number(g.price).toFixed(0) }} ₽</span>
                  </div>
                  <div v-if="!searchResults.length" class="search-empty">Разведчики не нашли ничего.</div>
                </template>
              </div>
            </Transition>
          </div>
        </div>

        <!-- User actions -->
        <div class="user-actions">
          <!-- Theme dropdown — три варианта темы -->
          <div
            class="theme-wrap"
            @mouseenter="openThemeDropdown"
            @mouseleave="closeThemeDropdown"
          >
            <button
              class="action-btn theme-btn-labeled"
              @click="onThemeButtonClick"
              :aria-expanded="themeDropdownOpen"
              aria-haspopup="true"
              aria-label="Сменить тему"
            >
              <span class="theme-icon">{{ themeIconFor(themeStore.current) }}</span>
              <span class="theme-label-text">Тема</span>
              <span class="nav-chevron" :class="{ open: themeDropdownOpen }">▾</span>
            </button>
            <Transition name="dropdown">
              <div v-if="themeDropdownOpen" class="theme-dropdown" role="menu">
                <button
                  v-for="t in themeOptions" :key="t.value"
                  class="theme-opt"
                  :class="{ active: themeStore.current === t.value }"
                  @click="themeStore.setTheme(t.value); themeDropdownOpen = false"
                  role="menuitemradio"
                  :aria-checked="themeStore.current === t.value"
                >
                  <span class="theme-opt-icon">{{ t.icon }}</span>
                  <span class="theme-opt-body">
                    <span class="theme-opt-label">{{ t.label }}</span>
                    <span class="theme-opt-desc">{{ t.desc }}</span>
                  </span>
                  <span v-if="themeStore.current === t.value" class="theme-opt-check">●</span>
                </button>
              </div>
            </Transition>
          </div>

          <RouterLink to="/cart" class="action-btn cart-btn" title="Добыча" aria-label="Корзина">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
          </RouterLink>

          <!-- Phase 4/D — DM-инбокс. Только для залогиненных. -->
          <RouterLink
            v-if="isLoggedIn"
            to="/messages"
            class="action-btn chat-btn"
            :class="{ 'has-unread': chatsUnread > 0 }"
            title="Сообщения"
            aria-label="Сообщения"
          >
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <span v-if="chatsUnread > 0" class="bell-badge" aria-hidden="true">{{ chatsBadge }}</span>
          </RouterLink>

          <!-- Bell — уведомления (только для залогиненных).
               Клик → выпадает dropdown с превью последних 7 (как у темы).
               Клик по превью → /notifications?expand=<id> (страница развёрнутая).
               Клик "Все уведомления →" → /notifications. -->
          <div v-if="isLoggedIn" class="bell-wrap" ref="bellWrapRef">
            <button
              class="action-btn bell-btn"
              :class="{ 'has-unread': notifUnreadCount > 0, active: notifDropdownOpen }"
              @click="onBellClick"
              :aria-expanded="notifDropdownOpen"
              aria-haspopup="true"
              aria-label="Уведомления"
              title="Уведомления"
              type="button"
            >
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
              </svg>
              <span v-if="notifUnreadCount > 0" class="bell-badge" aria-hidden="true">{{ notifBadge }}</span>
            </button>

            <Transition name="dropdown">
              <div v-if="notifDropdownOpen" class="notif-dropdown" role="menu">
                <header class="notif-dd-head">
                  <span class="notif-dd-title">Уведомления</span>
                  <button
                    v-if="notifUnreadCount > 0"
                    class="notif-dd-mark"
                    @click="handleNotifMarkAll"
                    type="button"
                  >Прочитать все</button>
                </header>

                <div v-if="notifDropdownLoading && !notifPreview.length" class="notif-dd-loading">
                  Раскручиваем свиток…
                </div>

                <ul v-else-if="notifPreview.length" class="notif-dd-list">
                  <li
                    v-for="n in notifPreview"
                    :key="n.id"
                    class="notif-dd-item"
                    :class="{ unread: !n.read_at }"
                    @click="onNotifItemClick(n)"
                    role="menuitem"
                  >
                    <span class="notif-dd-sigil" aria-hidden="true">{{ notifSigil(n) }}</span>
                    <span class="notif-dd-body">
                      <span class="notif-dd-itemtitle">{{ notifTitle(n) }}</span>
                      <span v-if="notifShortPreview(n)" class="notif-dd-preview">{{ notifShortPreview(n) }}</span>
                      <span class="notif-dd-time">{{ notifFormatDate(n.created_at) }}</span>
                    </span>
                  </li>
                </ul>

                <div v-else class="notif-dd-empty">
                  <span class="notif-dd-empty-sigil" aria-hidden="true">◈</span>
                  <span>Тишина в кузнице</span>
                </div>

                <footer class="notif-dd-foot">
                  <RouterLink to="/notifications" class="notif-dd-all" @click="notifDropdownOpen = false">
                    Все уведомления →
                  </RouterLink>
                </footer>
              </div>
            </Transition>

            <!-- Peek-стек: появляется когда poll детектит новые непрочитанные.
                 Карточка выпрыгивает из колокольчика (transform-origin: top right
                 → scale 0→1), висит ~5с, потом схлопывается обратно в bell.
                 Намёк юзеру: «там что-то новое, нажми меня». -->
            <TransitionGroup
              tag="div"
              name="peek"
              class="peek-stack"
              v-if="!notifDropdownOpen"
            >
              <div
                v-for="(p, i) in notifPeeks"
                :key="p.id"
                class="peek-card"
                :class="{ unread: !p.read_at }"
                :style="{ '--peek-stack-offset': i * 6 + 'px' }"
                @click="onPeekClick(p)"
                role="alert"
              >
                <span class="peek-sigil" aria-hidden="true">{{ notifSigil(p) }}</span>
                <span class="peek-body">
                  <span class="peek-title">{{ notifTitle(p) }}</span>
                  <span v-if="notifShortPreview(p)" class="peek-preview">{{ notifShortPreview(p) }}</span>
                </span>
                <button
                  class="peek-close"
                  @click.stop="onPeekDismiss(p.id)"
                  aria-label="Скрыть"
                  type="button"
                >✕</button>
              </div>
            </TransitionGroup>
          </div>

          <template v-if="isLoggedIn && user">
            <RouterLink :to="profileLinkTarget" class="profile-btn" :title="user.username ? `Публичный профиль @${user.username}` : 'Настройки (задайте username)'">
              <div class="avatar-ring">
                <div class="avatar">
                  <img v-if="user.avatar" :src="`/avatars/${encodeURIComponent(user.avatar)}`" class="avatar-img" :alt="user.fullname" />
                  <span v-else>{{ user.fullname?.[0]?.toUpperCase() ?? '?' }}</span>
                </div>
              </div>
              <span class="profile-name">{{ user.fullname || 'Воин' }}</span>
            </RouterLink>
            <button @click="handleLogout" class="logout-btn" title="Покинуть клан">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
              <span class="logout-label">Уйти</span>
            </button>
          </template>

          <template v-else>
            <RouterLink to="/login" class="auth-btn ghost">Войти</RouterLink>
            <RouterLink to="/register" class="auth-btn solid">
              <span>Присоединиться</span>
            </RouterLink>
          </template>

          <!-- Hamburger -->
          <button class="hamburger" @click="mobileMenuOpen = !mobileMenuOpen" :class="{ open: mobileMenuOpen }" aria-label="Меню">
            <span></span><span></span><span></span>
          </button>
        </div>
      </div>

      <!-- Mobile menu — "свиток" -->
      <Transition name="mobile-menu">
        <div class="mobile-menu" v-if="mobileMenuOpen">
          <!-- Mode toggle на мобиле — внутри меню -->
          <div class="mobile-mode-toggle" role="tablist" aria-label="Режим сайта">
            <button
              class="mode-btn"
              :class="{ active: modeStore.isShop }"
              @click="modeStore.setMode('shop')"
              type="button"
            >
              <span class="mode-icon" aria-hidden="true">⚔</span>
              <span class="mode-label">Магазин</span>
            </button>
            <button
              class="mode-btn"
              :class="{ active: modeStore.isForum }"
              @click="modeStore.setMode('forum')"
              type="button"
            >
              <span class="mode-icon" aria-hidden="true">📜</span>
              <span class="mode-label">Форум</span>
            </button>
          </div>

          <nav class="mobile-nav">
            <RouterLink
              v-for="item in modeStore.navItems"
              :key="item.to"
              :to="item.to"
              @click="mobileMenuOpen = false"
            >{{ item.label }}</RouterLink>
            <RouterLink v-if="modeStore.isShop" to="/cart" @click="mobileMenuOpen = false">Добыча</RouterLink>
            <RouterLink to="/soviet" @click="mobileMenuOpen = false">☭</RouterLink>
            <RouterLink v-if="user?.is_admin" to="/admin" @click="mobileMenuOpen = false">Совет старейшин</RouterLink>
          </nav>
          <div class="mobile-auth">
            <template v-if="isLoggedIn && user">
              <RouterLink to="/profile" class="auth-btn solid" @click="mobileMenuOpen = false">{{ user.fullname || 'Профиль' }}</RouterLink>
              <button @click="handleLogout" class="auth-btn ghost">Покинуть клан</button>
            </template>
            <template v-else>
              <RouterLink to="/login" class="auth-btn ghost" @click="mobileMenuOpen = false">Войти</RouterLink>
              <RouterLink to="/register" class="auth-btn solid" @click="mobileMenuOpen = false">Присоединиться</RouterLink>
            </template>
          </div>
        </div>
      </Transition>
    </header>

    <!-- Main content -->
    <main class="main-content">
      <RouterView :key="route.fullPath" />
    </main>

    <!-- ===================================================
         FOOTER · Wall of Ancestors
    =================================================== -->
    <footer class="main-footer">
      <!-- Декоративная ember-линия сверху -->
      <div class="footer-ember-line"></div>
      <!-- Железная полоса сверху -->
      <div class="footer-iron-strip"></div>
      <!-- Тлеющие угли как фон -->
      <div class="footer-forge-glow"></div>

      <div class="footer-inner">
        <!-- Баннеры Орды слева — декоративно -->
        <div class="footer-banners" aria-hidden="true">
          <span class="footer-banner b1"></span>
          <span class="footer-banner b2"></span>
        </div>

        <div class="footer-grid">
          <!-- Brand column -->
          <div class="footer-col brand-col">
            <RouterLink to="/" class="footer-logo">
              <img :src="hordeSigilUrl" alt="" class="footer-logo-img" width="38" height="38" loading="lazy" />
              <span class="footer-logo-text">
                <span>GAME</span><span class="footer-logo-accent">STORE</span>
              </span>
            </RouterLink>
            <p class="footer-tagline">
              Кузница клана. Тысячи лицензионных ключей для Steam, Epic, GOG и других платформ.
              Мгновенная доставка, честь и верность.
            </p>
            <div class="footer-socials">
              <a href="#" target="_blank" class="social-btn vk" title="ВКонтакте" aria-label="ВКонтакте">
                <i class="fa-brands fa-vk"></i>
              </a>
              <a href="#" target="_blank" class="social-btn tg" title="Telegram" aria-label="Telegram">
                <i class="fa-brands fa-telegram"></i>
              </a>
              <a href="#" target="_blank" class="social-btn yt" title="YouTube" aria-label="YouTube">
                <i class="fa-brands fa-youtube"></i>
              </a>
            </div>
          </div>

          <!-- Магазин — пункты shop-режима -->
          <div class="footer-col">
            <h4 class="footer-col-title">
              <span class="col-title-spike"></span>
              Магазин
            </h4>
            <div class="footer-links">
              <RouterLink to="/">Главная</RouterLink>
              <RouterLink to="/catalog">Оружейная</RouterLink>
              <RouterLink to="/news">Хроники</RouterLink>
              <RouterLink to="/cart">Добыча</RouterLink>
              <RouterLink to="/about">О клане</RouterLink>
            </div>
          </div>

          <!-- Сообщество — пункты forum-режима (ведут на stub-страницы Phase 1) -->
          <div class="footer-col">
            <h4 class="footer-col-title">
              <span class="col-title-spike"></span>
              Сообщество
            </h4>
            <div class="footer-links">
              <RouterLink to="/feed">Лента</RouterLink>
              <RouterLink to="/posts">Посты</RouterLink>
              <RouterLink to="/mods">Моды</RouterLink>
              <RouterLink to="/community">Союзники</RouterLink>
              <RouterLink to="/soviet">☭ СССР</RouterLink>
            </div>
          </div>

          <!-- Contacts -->
          <div class="footer-col">
            <h4 class="footer-col-title">
              <span class="col-title-spike"></span>
              Вестник
            </h4>
            <div class="footer-links">
              <a href="mailto:Gamestore.help@yandex.com">
                <span class="link-icon">✉</span> Gamestore.help@yandex.com
              </a>
              <a href="tel:+79991234567">
                <span class="link-icon">☎</span> +7 (999) 123-45-67
              </a>
            </div>

            <div class="footer-platforms">
              <a href="https://store.steampowered.com/" target="_blank" rel="noopener" class="fp-badge">Steam</a>
              <a href="https://store.epicgames.com/" target="_blank" rel="noopener" class="fp-badge">Epic</a>
              <a href="https://www.gog.com/" target="_blank" rel="noopener" class="fp-badge">GOG</a>
              <a href="https://www.blizzard.com/" target="_blank" rel="noopener" class="fp-badge">Battle.net</a>
              <a href="https://ubisoftconnect.com/" target="_blank" rel="noopener" class="fp-badge">Ubisoft</a>
              <a href="https://www.ea.com/" target="_blank" rel="noopener" class="fp-badge">EA</a>
            </div>
          </div>
        </div>

        <!-- Divider — tribal spike line -->
        <div class="footer-divider"><span class="divider-spike"></span></div>

        <!-- Bottom bar -->
        <div class="footer-bottom">
          <p class="footer-copy">© {{ new Date().getFullYear() }} GameStore. Кузница воина.</p>
          <p class="footer-made">Lok-tar ogar!</p>
        </div>
      </div>
    </footer>

    <!-- Support chat — keep for now -->
    <SupportChat />

    <!-- Toast notifications -->
    <Teleport to="body">
      <div class="toast-container">
        <TransitionGroup name="toast">
          <div
            v-for="toast in toasts"
            :key="toast.id"
            class="toast"
            :class="[`toast-${toast.type}`, { 'toast-visible': toast.visible }]"
            @click="removeToast(toast.id)"
          >
            <span class="toast-icon">
              <template v-if="toast.type === 'success'">✦</template>
              <template v-else-if="toast.type === 'error'">✖</template>
              <template v-else-if="toast.type === 'warning'">⚠</template>
              <template v-else>ℹ</template>
            </span>
            <span class="toast-msg">{{ toast.message }}</span>
            <button class="toast-close" @click.stop="removeToast(toast.id)" aria-label="Закрыть">×</button>
          </div>
        </TransitionGroup>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
/* ============================================================
   ASHENFORGE · App Shell
   ============================================================ */

@keyframes emberFloat {
  0%, 100% { opacity: 0.8; transform: translateX(0); }
  50%       { opacity: 1;   transform: translateX(3px); }
}
@keyframes shimmer {
  0%   { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}
@keyframes avatarForge {
  from { transform: scale(0.7) rotate(-8deg); opacity: 0; }
  to   { transform: scale(1) rotate(0); opacity: 1; }
}

/* ==========================================================
   APP WRAPPER
   ========================================================== */
#app-wrapper {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  position: relative;
  z-index: 1;
}
.main-content { flex-grow: 1; position: relative; z-index: 1; }

/* ==========================================================
   HEADER
   ========================================================== */
.main-header {
  position: sticky;
  top: 0;
  z-index: var(--z-header);
  background: linear-gradient(180deg,
    rgba(8, 6, 10, 0.72) 0%,
    rgba(18, 16, 13, 0.65) 100%);
  backdrop-filter: blur(20px) saturate(140%);
  -webkit-backdrop-filter: blur(20px) saturate(140%);
  border-bottom: 1px solid var(--iron-dark);
  /* Здесь раньше был overflow-x: hidden — убрал, потому что по CSS-спеке
     при overflow-x ≠ visible вторая ось становится auto, что превращает
     theme-dropdown и search-dropdown (position: absolute, top: 100%)
     в скроллируемые элементы внутри шапки. Дропдауны должны выпадать
     поверх контента сайта, а не висеть в скролле хедера.
     Защита от переполнения ширины обеспечивается компакт-правилами
     ниже + min-width: 0 и overflow: hidden на .main-nav. */
  transition:
    background var(--dur-med) var(--ease-smoke),
    box-shadow var(--dur-med) var(--ease-smoke);
}
.main-header.scrolled {
  background: linear-gradient(180deg,
    rgba(8, 6, 10, 0.92) 0%,
    rgba(18, 16, 13, 0.88) 100%);
  box-shadow:
    var(--shadow-cast),
    0 1px 0 rgba(199, 154, 94, 0.08),
    var(--inset-forge);
}

/* Декоративная железная полоса сверху */
.header-iron-strip {
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background:
    linear-gradient(180deg, var(--bronze-dark) 0%, var(--iron-void) 100%);
  box-shadow:
    inset 0 1px 0 rgba(199, 154, 94, 0.35),
    inset 0 -1px 0 rgba(0, 0, 0, 0.5);
}
/* Тлеющая ember-линия снизу */
.header-ember-line {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 1px;
  background: linear-gradient(90deg,
    transparent 0%,
    rgba(138, 31, 24, 0.4) 15%,
    rgba(226, 67, 16, 0.6) 50%,
    rgba(138, 31, 24, 0.4) 85%,
    transparent 100%);
  box-shadow: 0 0 14px rgba(226, 67, 16, 0.35);
  animation: emberPulse 4s ease-in-out infinite;
}

.header-content {
  display: flex;
  align-items: center;
  max-width: var(--page-max);
  margin: 0 auto;
  padding: 0 var(--sp-6);
  height: var(--header-h);
  gap: var(--sp-2);
  /* Гарантируем что контент никогда не выходит за viewport, даже если
     наши compact-правила не сработали по какой-то причине (стейл кэш,
     неучтённый шрифт). user-actions с margin-left:auto всё равно
     прижимается к правому краю, остальные flex-children сжимаются. */
  min-width: 0;
  width: 100%;
  box-sizing: border-box;
}

/* ==========================================================
   LOGO
   ========================================================== */
.logo-link {
  display: flex;
  align-items: center;
  gap: var(--sp-3);
  text-decoration: none;
  flex-shrink: 0;
  color: inherit;
  padding: var(--sp-1) var(--sp-2);
  margin-left: calc(-1 * var(--sp-2));
  border-radius: var(--r-sm);
  transition: filter var(--dur-med) var(--ease-smoke);
}
.logo-link:hover { filter: brightness(1.1); }
.logo-link:hover .logo-sigil-glow { opacity: 1; }
.logo-link:hover .logo-sigil { filter: drop-shadow(0 0 8px rgba(255, 122, 43, 0.6)); }

.logo-sigil-wrap {
  position: relative;
  display: inline-flex;
  width: 42px; height: 42px;
  align-items: center;
  justify-content: center;
}
.logo-sigil {
  position: relative;
  z-index: 2;
  width: 42px; height: 42px;
  display: block;
  filter: drop-shadow(0 0 4px rgba(226, 67, 16, 0.3));
  transition: filter var(--dur-med) var(--ease-smoke);
}
.logo-sigil-glow {
  position: absolute;
  inset: -6px;
  background: radial-gradient(circle, rgba(255, 122, 43, 0.5) 0%, transparent 70%);
  opacity: 0.55;
  pointer-events: none;
  transition: opacity var(--dur-med) var(--ease-smoke);
  animation: emberFlicker 3.5s ease-in-out infinite;
}

.logo-word {
  display: flex;
  flex-direction: column;
  line-height: 1;
  gap: 2px;
}
.logo-word-main,
.logo-word-accent {
  font-family: var(--font-display);
  font-weight: var(--fw-black);
  font-size: 1.3rem;
  letter-spacing: var(--ls-wider);
}
.logo-word-main { color: var(--text-bright); }
.logo-word-accent {
  background: var(--grad-ember-text);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  filter: drop-shadow(0 0 8px rgba(226, 67, 16, 0.4));
}
.logo-word-sub {
  font-family: var(--font-tribal);
  font-size: 0.62rem;
  color: var(--bronze);
  letter-spacing: var(--ls-widest);
  text-transform: uppercase;
  margin-top: 1px;
}

/* ==========================================================
   NAV
   ========================================================== */
/* ==========================================================
   MODE TOGGLE — сегментированный pill «Магазин ↔ Форум»
   ========================================================== */
.mode-toggle {
  display: inline-flex;
  align-items: stretch;
  margin-left: var(--sp-3);
  padding: 3px;
  border: 1px solid var(--iron-mid);
  border-radius: var(--r-pill);
  background: linear-gradient(180deg, var(--ash-obsidian) 0%, var(--ash-coal) 100%);
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.6), var(--inset-iron-top);
  flex-shrink: 0;
}
.mode-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border: 1px solid transparent;
  border-radius: var(--r-pill);
  background: transparent;
  color: var(--text-ash);
  font-family: var(--font-display);
  font-size: 0.74rem;
  font-weight: var(--fw-bold);
  letter-spacing: 0.08em;
  text-transform: uppercase;
  cursor: pointer;
  transition: all var(--dur-fast) var(--ease-smoke);
  white-space: nowrap;
}
.mode-btn:hover {
  color: var(--text-bone);
}
.mode-btn.active {
  background: linear-gradient(180deg, var(--ember-blood) 0%, var(--ember-deep) 100%);
  border-color: var(--ember-heart);
  color: var(--text-bright);
  box-shadow:
    var(--inset-iron-top),
    inset 0 -1px 2px rgba(0, 0, 0, 0.4),
    var(--glow-ember-soft);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
}
.mode-icon {
  font-size: 0.92rem;
  line-height: 1;
  filter: drop-shadow(0 0 2px rgba(0, 0, 0, 0.5));
}
.mode-btn.active .mode-icon {
  filter: drop-shadow(0 0 4px rgba(255, 201, 121, 0.7));
}

/* Mobile mode toggle — внутри гамбургер-меню */
.mobile-mode-toggle {
  display: flex;
  gap: 6px;
  margin: 0 0 16px;
  padding: 4px;
  border: 1px solid var(--iron-mid);
  border-radius: var(--r-pill);
  background: linear-gradient(180deg, var(--ash-obsidian) 0%, var(--ash-coal) 100%);
}
.mobile-mode-toggle .mode-btn {
  flex: 1;
  justify-content: center;
  padding: 9px 14px;
  font-size: 0.78rem;
}

.main-nav {
  /* Уплотнённый отступ от лого — на 1280–1440 с 5 пунктами + поиском
     32px было перебором. */
  margin-left: var(--sp-5);
  display: flex;
  align-items: center;
  gap: 0;
  flex: 0 1 auto;
  min-width: 0;          /* позволяет flexbox'у фактически сжимать содержимое */
  overflow: hidden;      /* содержимое внутри не будет торчать наружу */
}
.nav-link {
  position: relative;
  font-family: var(--font-display);
  font-size: 0.82rem;
  font-weight: var(--fw-semibold);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--text-parchment);
  /* Уплотнённый padding — на дефолте у нас 5 пунктов nav + поиск + 4 кнопки.
     Если оставить 10px 16px, на 1366×768 при 100% зума всё рядом не помещается. */
  padding: 10px 12px;
  text-decoration: none;
  white-space: nowrap;
  border: 1px solid transparent;
  border-radius: var(--r-xs);
  transition: all var(--dur-fast) var(--ease-smoke);
}
.nav-link span { position: relative; z-index: 2; }

/* Фоновое свечение при hover */
.nav-link::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at center, rgba(194, 40, 26, 0.25) 0%, transparent 70%);
  opacity: 0;
  transition: opacity var(--dur-fast) var(--ease-smoke);
  border-radius: var(--r-xs);
}
/* Нижняя ember-полоса у активного */
.nav-link::after {
  content: '';
  position: absolute;
  bottom: 4px;
  left: 50%;
  transform: translateX(-50%) scaleX(0);
  width: 60%;
  height: 2px;
  background: var(--grad-ember);
  box-shadow: 0 0 8px rgba(226, 67, 16, 0.6);
  transition: transform var(--dur-med) var(--ease-forge);
}

.nav-link:hover {
  color: var(--text-bright);
  border-color: var(--iron-dark);
}
.nav-link:hover::before { opacity: 1; }
.nav-link:hover::after { transform: translateX(-50%) scaleX(0.5); }

.nav-link.router-link-exact-active {
  color: var(--text-bright);
  background: linear-gradient(180deg,
    rgba(90, 20, 18, 0.35) 0%,
    rgba(58, 42, 34, 0.3) 100%);
  border-color: var(--ember-deep);
  box-shadow:
    var(--inset-forge),
    0 0 12px rgba(194, 40, 26, 0.25);
}
.nav-link.router-link-exact-active::after { transform: translateX(-50%) scaleX(1); }

.nav-link.admin-link {
  color: var(--brass);
}
.nav-link.admin-link:hover {
  color: var(--gold-faint);
  border-color: var(--bronze-dark);
}
.nav-link.admin-link.router-link-exact-active {
  color: var(--gold-faint);
  border-color: var(--bronze);
  background: linear-gradient(180deg,
    rgba(160, 115, 72, 0.25) 0%,
    rgba(90, 70, 58, 0.2) 100%);
  box-shadow: var(--glow-brass);
}
.nav-link.admin-link.router-link-exact-active::after {
  background: var(--grad-bronze);
  box-shadow: var(--glow-brass);
}

/* ==========================================================
   NAV DROPDOWNS
   ========================================================== */
.nav-link-wrap {
  position: relative;
  display: inline-flex;
}
.nav-link.has-dropdown {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.nav-chevron {
  font-size: 0.65rem;
  color: var(--brass);
  transition: transform var(--dur-fast) var(--ease-smoke), color var(--dur-fast);
  margin-top: 1px;
}
.nav-chevron.open { transform: rotate(-180deg); }
.nav-link.has-dropdown:hover .nav-chevron { color: var(--ember-spark); }

.nav-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  left: -8px;
  min-width: 360px;
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  box-shadow:
    var(--shadow-lift),
    var(--inset-iron-top),
    var(--inset-forge);
  padding: 18px;
  z-index: var(--z-overlay);
  backdrop-filter: blur(16px);
  clip-path: var(--clip-forged-sm);
}
/* Бронзовая полоса сверху dropdown — имитация литого края */
.nav-dropdown::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 2px;
  background: linear-gradient(90deg,
    transparent 0%,
    var(--bronze) 30%,
    var(--brass) 50%,
    var(--bronze) 70%,
    transparent 100%);
  box-shadow: 0 0 8px rgba(199, 154, 94, 0.4);
}

.dropdown-head {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 2px 8px 12px;
  border-bottom: 1px dashed var(--iron-dark);
  margin-bottom: 12px;
}
.dropdown-eyebrow {
  font-family: var(--font-tribal);
  font-size: 0.72rem;
  color: var(--brass);
  letter-spacing: var(--ls-widest);
  text-transform: uppercase;
}

.dropdown-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 4px;
}
.dropdown-col {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  text-decoration: none;
  color: var(--text-parchment);
  font-family: var(--font-ui);
  font-size: 0.9rem;
  font-weight: var(--fw-medium);
  border-radius: var(--r-xs);
  border-left: 2px solid transparent;
  transition: all var(--dur-fast) var(--ease-smoke);
  position: relative;
}
.dropdown-item:hover,
.dropdown-item.router-link-active {
  color: var(--text-bright);
  background: rgba(194, 40, 26, 0.12);
  border-left-color: var(--ember-heart);
}
.dropdown-item:hover .dd-icon { color: var(--ember-spark); filter: drop-shadow(0 0 6px rgba(255, 122, 43, 0.6)); }

.dd-icon {
  color: var(--brass);
  font-size: 1rem;
  flex-shrink: 0;
  width: 20px;
  text-align: center;
  transition: all var(--dur-fast) var(--ease-smoke);
}
.dd-label { flex: 1; }

.dropdown-foot {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px dashed var(--iron-dark);
}
.dd-see-all {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 12px;
  color: var(--ember-gold);
  font-family: var(--font-display);
  font-size: 0.82rem;
  font-weight: var(--fw-semibold);
  text-transform: uppercase;
  letter-spacing: var(--ls-wide);
  text-decoration: none;
  border-radius: var(--r-xs);
  transition: all var(--dur-fast) var(--ease-smoke);
}
.dd-see-all:hover {
  background: rgba(255, 122, 43, 0.1);
  color: var(--ember-spark);
}
.dd-see-all span {
  transition: transform var(--dur-fast);
}
.dd-see-all:hover span { transform: translateX(4px); }

/* Admin dropdown — narrower + single col */
.admin-dropdown {
  min-width: 260px;
}

/* Transitions */
.dropdown-enter-active,
.dropdown-leave-active {
  transition:
    opacity var(--dur-fast) var(--ease-smoke),
    transform var(--dur-med) var(--ease-forge);
}
.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px) scale(0.96);
}

/* ==========================================================
   USER ACTIONS
   ========================================================== */
.user-actions {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: var(--sp-2);
  flex-shrink: 0;
}

.action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px; height: 40px;
  border-radius: var(--r-sm);
  border: 1px solid var(--iron-dark);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  text-decoration: none;
  color: var(--text-parchment);
  cursor: pointer;
  transition: all var(--dur-fast) var(--ease-smoke);
  position: relative;
  overflow: hidden;
}
.action-btn::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at bottom, rgba(226, 67, 16, 0.35) 0%, transparent 70%);
  opacity: 0;
  transition: opacity var(--dur-fast);
  pointer-events: none;
}
.action-btn:hover {
  color: var(--text-bright);
  border-color: var(--iron-warm);
  box-shadow: var(--glow-ember-soft), var(--inset-iron-top);
  transform: translateY(-1px);
}
.action-btn:hover::before { opacity: 1; }

/* ──────────────────────────────────────────────────────────────
   BELL BUTTON + DROPDOWN — уведомления.
   .has-unread пульсирует мягко, и угольно-красный бейдж в углу.
   Дропдаун — кованый свиток шириной ~360px, шрифт-сетка как у темы.
   ────────────────────────────────────────────────────────────── */
.bell-wrap {
  position: relative;
  flex-shrink: 0;
}
.bell-btn {
  position: relative;
}
.bell-btn.active {
  border-color: var(--iron-warm);
  background: linear-gradient(180deg, var(--ash-coal) 0%, var(--ash-stone) 100%);
}
.bell-btn.has-unread {
  border-color: var(--iron-warm);
  box-shadow: 0 0 0 1px rgba(226, 67, 16, 0.18) inset;
}
.bell-btn.has-unread svg {
  color: var(--text-bright);
  filter: drop-shadow(0 0 4px rgba(226, 67, 16, 0.55));
  animation: bell-tap 2.4s ease-in-out infinite;
}
@keyframes bell-tap {
  0%, 92%, 100%   { transform: rotate(0); }
  94%             { transform: rotate(-9deg); }
  96%             { transform: rotate(7deg); }
  98%             { transform: rotate(-4deg); }
}
.bell-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  border-radius: 9px;
  background: linear-gradient(180deg, #b8341a 0%, #7a1f0c 100%);
  color: #fff5d6;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.3px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 200, 140, 0.35);
  box-shadow: 0 0 8px rgba(226, 67, 16, 0.55), 0 1px 2px rgba(0,0,0,0.4);
  pointer-events: none;
}
@media (prefers-reduced-motion: reduce) {
  .bell-btn.has-unread svg { animation: none; }
}

/* ──────────────────────────────────────────────────────────────
   NOTIF DROPDOWN — кованый свиток с превью последних уведомлений.
   ────────────────────────────────────────────────────────────── */
.notif-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 360px;
  max-width: calc(100vw - 24px);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-md);
  box-shadow: 0 12px 32px rgba(0,0,0,0.55), var(--inset-iron-top);
  z-index: 1000;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  max-height: 70vh;
}
.notif-dd-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 14px;
  border-bottom: 1px solid var(--iron-dark);
  background: rgba(0,0,0,0.25);
}
.notif-dd-title {
  font-size: 13px;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: var(--iron-warm);
}
.notif-dd-mark {
  background: none;
  border: none;
  padding: 4px 6px;
  font-size: 11px;
  color: var(--text-muted);
  cursor: pointer;
  letter-spacing: 0.05em;
  transition: color var(--dur-fast);
  font-family: inherit;
}
.notif-dd-mark:hover { color: var(--text-bright); }

.notif-dd-loading {
  padding: 24px 14px;
  text-align: center;
  color: var(--text-muted);
  font-size: 12px;
  font-style: italic;
}

.notif-dd-list {
  list-style: none;
  margin: 0;
  padding: 4px 0;
  overflow-y: auto;
  flex: 1;
}
.notif-dd-item {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  padding: 10px 14px;
  cursor: pointer;
  transition: background var(--dur-fast);
  border-left: 2px solid transparent;
}
.notif-dd-item:hover {
  background: rgba(226, 67, 16, 0.08);
}
.notif-dd-item.unread {
  border-left-color: #e24310;
  background: rgba(226, 67, 16, 0.04);
}
.notif-dd-sigil {
  flex-shrink: 0;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: 1px solid var(--iron-dark);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  background: rgba(0,0,0,0.3);
  color: var(--iron-warm);
}
.notif-dd-item.unread .notif-dd-sigil {
  border-color: var(--iron-warm);
}
.notif-dd-body {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.notif-dd-itemtitle {
  font-size: 12px;
  color: var(--text-bright);
  line-height: 1.4;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.notif-dd-preview {
  font-size: 11px;
  color: var(--text-muted);
  line-height: 1.4;
  font-style: italic;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.notif-dd-time {
  font-size: 10px;
  color: var(--text-muted);
  letter-spacing: 0.05em;
  text-transform: lowercase;
  margin-top: 2px;
}

.notif-dd-empty {
  padding: 32px 14px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  color: var(--text-muted);
  font-size: 12px;
}
.notif-dd-empty-sigil {
  font-size: 24px;
  color: var(--iron-warm);
  opacity: 0.6;
}

.notif-dd-foot {
  border-top: 1px solid var(--iron-dark);
  background: rgba(0,0,0,0.2);
}
.notif-dd-all {
  display: block;
  padding: 11px 14px;
  text-align: center;
  text-decoration: none;
  color: var(--iron-warm);
  font-size: 12px;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  transition: all var(--dur-fast);
}
.notif-dd-all:hover {
  background: rgba(226, 67, 16, 0.1);
  color: var(--text-bright);
}

/* На мобиле dropdown растягивается под viewport */
@media (max-width: 600px) {
  .notif-dropdown {
    position: fixed;
    top: 65px;
    right: 8px;
    left: 8px;
    width: auto;
    max-width: none;
  }
}

/* ──────────────────────────────────────────────────────────────
   PEEK STACK — карточки выпрыгивающие из колокольчика при новых
   событиях. Стэк под bell (top: 100%+6px, right: 0). Каждая карточка
   смещена вниз через --peek-stack-offset (эффект колоды).
   transform-origin: top right — точка от/до которой scale: масштабируется
   к/от bell-кнопки (она в правом верхнем углу карточки). Чувствуется
   будто карточка вылазит из колокольчика и потом туда же ныряет.
   ────────────────────────────────────────────────────────────── */
.peek-stack {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 320px;
  max-width: calc(100vw - 24px);
  display: flex;
  flex-direction: column;
  gap: 8px;
  pointer-events: none;
  z-index: 999;
}
.peek-card {
  position: relative;
  pointer-events: auto;
  display: flex;
  gap: 10px;
  align-items: flex-start;
  padding: 12px 14px;
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  border: 1px solid var(--iron-warm);
  border-radius: var(--r-sm);
  box-shadow:
    0 8px 24px rgba(0,0,0,0.55),
    0 0 14px rgba(226, 67, 16, 0.25),
    var(--inset-iron-top);
  cursor: pointer;
  transform-origin: top right;
  margin-top: var(--peek-stack-offset, 0);
  transition: transform 0.18s var(--ease-smoke), box-shadow 0.18s var(--ease-smoke);
}
.peek-card:hover {
  transform: translateX(-2px) scale(1.02);
  box-shadow:
    0 10px 28px rgba(0,0,0,0.6),
    0 0 18px rgba(226, 67, 16, 0.4),
    var(--inset-iron-top);
}
.peek-card.unread {
  border-color: rgba(226, 67, 16, 0.7);
  box-shadow:
    0 8px 24px rgba(0,0,0,0.55),
    0 0 18px rgba(226, 67, 16, 0.45),
    var(--inset-iron-top);
}

.peek-sigil {
  flex-shrink: 0;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 1px solid var(--iron-warm);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 15px;
  background: rgba(0,0,0,0.35);
  color: var(--iron-warm);
  filter: drop-shadow(0 0 4px rgba(226, 67, 16, 0.45));
}
.peek-body {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.peek-title {
  font-size: 12px;
  color: var(--text-bright);
  line-height: 1.4;
  font-weight: 500;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}
.peek-preview {
  font-size: 11px;
  color: var(--text-muted);
  line-height: 1.4;
  font-style: italic;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}
.peek-close {
  flex-shrink: 0;
  width: 22px;
  height: 22px;
  padding: 0;
  border: none;
  background: transparent;
  color: var(--text-muted);
  font-size: 12px;
  cursor: pointer;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all var(--dur-fast);
  font-family: inherit;
}
.peek-close:hover {
  color: var(--text-bright);
  background: rgba(226, 67, 16, 0.15);
}

/* Анимация появления — выпрыгиваем из колокольчика
   (transform-origin: top right совпадает с правым верхним углом — там bell).
   От scale(0.05) с translate(чуть к bell) до scale(1) с лёгким overshoot. */
.peek-enter-active {
  animation: peek-emerge 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.peek-leave-active {
  animation: peek-retract 0.42s cubic-bezier(0.55, 0, 0.7, 0);
  position: absolute; /* leave из flow чтобы остальные не прыгали */
  right: 0;
  width: 100%;
}
@keyframes peek-emerge {
  0% {
    transform: scale(0.05) translate(18px, -14px);
    opacity: 0;
    filter: blur(4px);
  }
  60% {
    transform: scale(1.06) translate(0, 4px);
    opacity: 1;
    filter: blur(0);
  }
  100% {
    transform: scale(1) translate(0, 0);
    opacity: 1;
  }
}
@keyframes peek-retract {
  0% {
    transform: scale(1) translate(0, 0);
    opacity: 1;
  }
  35% {
    transform: scale(0.85) translate(4px, -4px);
    opacity: 0.85;
  }
  100% {
    transform: scale(0.04) translate(20px, -16px);
    opacity: 0;
    filter: blur(3px);
  }
}

/* Когда стек переставляется (один peek ушёл — соседи поднимаются плавно) */
.peek-move {
  transition: transform 0.32s var(--ease-smoke);
}

@media (prefers-reduced-motion: reduce) {
  .peek-enter-active,
  .peek-leave-active {
    animation: none;
    transition: opacity 0.2s;
  }
  .peek-enter-from,
  .peek-leave-to { opacity: 0; }
}

/* Мобильный peek — растягиваем на всю ширину под bell */
@media (max-width: 600px) {
  .peek-stack {
    position: fixed;
    top: 60px;
    right: 8px;
    left: 8px;
    width: auto;
    max-width: none;
  }
}

/* ==========================================================
   THEME DROPDOWN — кнопка-вывеска с тремя вариантами темы
   ========================================================== */
.theme-wrap {
  position: relative;
  flex-shrink: 0;
}
.theme-btn-labeled {
  width: auto;
  min-width: 0;
  height: 40px;
  padding: 0 11px;
  gap: 6px;
  font-family: var(--font-ui);
  font-size: 0.78rem;
  font-weight: var(--fw-semibold);
  letter-spacing: 0.05em;
}
.theme-btn-labeled .theme-icon {
  font-size: 1.05rem;
  line-height: 1;
  color: var(--brass);
  filter: drop-shadow(0 0 4px rgba(199, 154, 94, 0.5));
  transition: color var(--dur-fast), filter var(--dur-fast);
}
.theme-btn-labeled:hover .theme-icon {
  color: var(--ember-gold);
  filter: drop-shadow(0 0 8px rgba(255, 201, 121, 0.8));
}
.theme-btn-labeled .theme-label-text {
  color: var(--text-parchment);
  text-transform: uppercase;
}
.theme-btn-labeled:hover .theme-label-text { color: var(--text-bright); }
.theme-btn-labeled .nav-chevron {
  font-size: 0.7rem;
  color: var(--brass);
  transition: transform var(--dur-fast) var(--ease-smoke);
}
.theme-btn-labeled .nav-chevron.open { transform: rotate(-180deg); }

.theme-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  min-width: 280px;
  padding: 8px;
  background: linear-gradient(180deg,
    rgba(28, 24, 22, 0.98) 0%,
    rgba(18, 16, 14, 0.98) 100%);
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-md);
  box-shadow:
    var(--shadow-cast),
    0 0 0 1px rgba(199, 154, 94, 0.06),
    var(--inset-iron-top);
  backdrop-filter: blur(14px) saturate(140%);
  -webkit-backdrop-filter: blur(14px) saturate(140%);
  z-index: var(--z-dropdown, 50);
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.theme-dropdown::before {
  content: '';
  position: absolute;
  top: -6px;
  right: 22px;
  width: 10px;
  height: 10px;
  background: rgba(28, 24, 22, 0.98);
  border-left: 1px solid var(--iron-dark);
  border-top: 1px solid var(--iron-dark);
  transform: rotate(45deg);
}
.theme-opt {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 10px 12px;
  background: transparent;
  border: 1px solid transparent;
  border-radius: var(--r-sm);
  color: var(--text-parchment);
  font-family: var(--font-ui);
  font-size: 0.86rem;
  cursor: pointer;
  text-align: left;
  transition: all var(--dur-fast) var(--ease-smoke);
}
.theme-opt:hover {
  background: linear-gradient(180deg,
    rgba(226, 67, 16, 0.08) 0%,
    rgba(138, 31, 24, 0.06) 100%);
  border-color: var(--iron-warm);
  color: var(--text-bright);
}
.theme-opt.active {
  background: linear-gradient(180deg,
    rgba(226, 67, 16, 0.14) 0%,
    rgba(138, 31, 24, 0.1) 100%);
  border-color: rgba(226, 67, 16, 0.45);
  box-shadow: inset 0 0 12px rgba(226, 67, 16, 0.18);
}
.theme-opt-icon {
  flex-shrink: 0;
  width: 32px; height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  color: var(--brass);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-sm);
  filter: drop-shadow(0 0 4px rgba(199, 154, 94, 0.4));
}
.theme-opt.active .theme-opt-icon {
  color: var(--ember-gold);
  border-color: rgba(255, 122, 43, 0.5);
  filter: drop-shadow(0 0 8px rgba(255, 201, 121, 0.8));
}
.theme-opt-body {
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex: 1;
  min-width: 0;
}
.theme-opt-label {
  font-family: var(--font-display);
  font-size: 0.92rem;
  font-weight: var(--fw-bold);
  letter-spacing: 0.02em;
  color: inherit;
}
.theme-opt-desc {
  font-size: 0.74rem;
  color: var(--text-muted);
  letter-spacing: 0.02em;
}
.theme-opt-check {
  flex-shrink: 0;
  font-size: 0.8rem;
  color: var(--ember-bright);
  text-shadow: 0 0 8px rgba(255, 122, 43, 0.8);
}

/* ==========================================================
   PROFILE BUTTON
   ========================================================== */
.profile-btn {
  display: flex;
  align-items: center;
  gap: var(--sp-2);
  padding: 4px 14px 4px 4px;
  border-radius: var(--r-pill);
  border: 1px solid var(--iron-dark);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  text-decoration: none;
  color: var(--text-parchment);
  cursor: pointer;
  transition: all var(--dur-fast) var(--ease-smoke);
}
.profile-btn:hover {
  color: var(--text-bright);
  border-color: var(--bronze-dark);
  box-shadow: var(--glow-brass), var(--inset-iron-top);
}

.avatar-ring {
  width: 32px; height: 32px;
  border-radius: 50%;
  padding: 2px;
  background: var(--grad-bronze);
  box-shadow:
    inset 0 -1px 2px rgba(0, 0, 0, 0.5),
    0 0 0 1px var(--iron-void);
  flex-shrink: 0;
}
.avatar {
  width: 100%; height: 100%;
  border-radius: 50%;
  background: var(--grad-ember);
  color: var(--text-bright);
  font-family: var(--font-display);
  font-size: 0.85rem;
  font-weight: var(--fw-bold);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  animation: avatarForge 0.4s var(--ease-forge);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.7);
}
.avatar-img {
  width: 100%; height: 100%;
  object-fit: cover;
  border-radius: 50%;
  display: block;
}
.profile-name {
  font-family: var(--font-ui);
  font-size: 0.82rem;
  font-weight: var(--fw-semibold);
  max-width: 92px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* ==========================================================
   LOGOUT — компактная иконка-кнопка по дефолту, без подписи.
   ========================================================== */
.logout-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 10px;
  border-radius: var(--r-sm);
  border: 1px solid var(--iron-dark);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  color: var(--text-ash);
  font-family: var(--font-ui);
  font-size: 0.78rem;
  font-weight: var(--fw-medium);
  cursor: pointer;
  transition: all var(--dur-fast) var(--ease-smoke);
  white-space: nowrap;
}
/* Подпись «Уйти» по дефолту скрыта — иконка дверки понятна без слов,
   и иначе на 1366×768 при 100% зума хедер не помещает всё в строку.
   На очень широких мониторах (≥1440px) возвращаем подпись + добавляем
   немного дыхания остальным элементам. */
.logout-label { display: none; }
/* «Премиум»-стили возвращают подписи и больший padding только на действительно
   широких мониторах (≥1600px). Между 1381–1599 действует компакт по дефолту,
   иначе на 1440-laptop'ах хедер переполнялся. */
@media (min-width: 1600px) {
  .logout-label { display: inline; }
  .logout-btn { padding: 8px 14px; font-size: 0.82rem; }
  .search-input { width: 280px; padding: 10px 40px 10px 36px; font-size: 0.9rem; }
  .nav-link { padding: 10px 14px; font-size: 0.84rem; }
  .profile-name { max-width: 110px; font-size: 0.84rem; }
}
.logout-btn:hover {
  color: var(--ember-spark);
  border-color: var(--ember-deep);
  background: linear-gradient(180deg, var(--ash-bloodrock) 0%, var(--ash-stone) 100%);
  box-shadow: var(--glow-ember-soft);
}

/* ==========================================================
   AUTH BUTTONS
   ========================================================== */
.auth-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 20px;
  border-radius: var(--r-sm);
  font-family: var(--font-display);
  font-size: 0.82rem;
  font-weight: var(--fw-bold);
  text-transform: uppercase;
  letter-spacing: var(--ls-wide);
  text-decoration: none;
  cursor: pointer;
  white-space: nowrap;
  transition: all var(--dur-fast) var(--ease-smoke);
  border: 1px solid transparent;
  position: relative;
  overflow: hidden;
}
.auth-btn.ghost {
  color: var(--text-parchment);
  background: transparent;
  border-color: var(--iron-mid);
}
.auth-btn.ghost:hover {
  color: var(--text-bright);
  border-color: var(--iron-warm);
  background: var(--ash-stone);
  box-shadow: var(--inset-iron-top);
}
.auth-btn.solid {
  color: var(--text-bright);
  background: var(--grad-ember);
  border-color: var(--ember-heart);
  box-shadow:
    var(--glow-ember-soft),
    var(--inset-iron-top),
    inset 0 -2px 4px rgba(0, 0, 0, 0.35);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.65);
}
.auth-btn.solid::before {
  content: '';
  position: absolute;
  top: 0; left: -100%;
  width: 100%; height: 100%;
  background: linear-gradient(90deg,
    transparent 0%,
    rgba(255, 201, 121, 0.35) 50%,
    transparent 100%);
  transition: left 0.6s var(--ease-smoke);
}
.auth-btn.solid:hover {
  filter: brightness(1.15);
  box-shadow: var(--glow-ember-strong), var(--inset-iron-top);
  transform: translateY(-1px);
}
.auth-btn.solid:hover::before { left: 100%; }

/* ==========================================================
   HAMBURGER
   ========================================================== */
.hamburger {
  display: none;
  flex-direction: column;
  justify-content: center;
  gap: 5px;
  width: 40px; height: 40px;
  border-radius: var(--r-sm);
  border: 1px solid var(--iron-dark);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  cursor: pointer;
  padding: 0 10px;
  transition: all var(--dur-fast) var(--ease-smoke);
}
.hamburger span {
  display: block;
  width: 100%; height: 2px;
  background: var(--brass);
  border-radius: 2px;
  transition: all var(--dur-med) ease;
  transform-origin: center;
  box-shadow: 0 0 4px rgba(199, 154, 94, 0.5);
}
.hamburger:hover {
  border-color: var(--iron-warm);
  box-shadow: var(--glow-brass);
}
.hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); background: var(--ember-flame); }
.hamburger.open span:nth-child(2) { opacity: 0; transform: scaleX(0); }
.hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); background: var(--ember-flame); }

/* ==========================================================
   MOBILE MENU
   ========================================================== */
.mobile-menu {
  border-top: 1px solid var(--iron-dark);
  background: linear-gradient(180deg,
    rgba(8, 6, 10, 0.98) 0%,
    rgba(18, 16, 13, 0.98) 100%);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  padding: 20px 24px 24px;
  box-shadow: var(--shadow-cast);
}
.mobile-nav {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-bottom: 20px;
}
.mobile-nav a {
  padding: 12px 16px;
  border-radius: var(--r-sm);
  border-left: 3px solid transparent;
  text-decoration: none;
  color: var(--text-parchment);
  font-family: var(--font-display);
  font-size: 0.95rem;
  font-weight: var(--fw-semibold);
  text-transform: uppercase;
  letter-spacing: var(--ls-wide);
  transition: all var(--dur-fast) var(--ease-smoke);
}
.mobile-nav a:hover,
.mobile-nav a.router-link-exact-active {
  color: var(--text-bright);
  background: var(--ash-stone);
  border-left-color: var(--ember-heart);
  box-shadow: inset 0 0 16px rgba(194, 40, 26, 0.08);
}
.mobile-auth { display: flex; gap: 10px; }
.mobile-auth .auth-btn { flex: 1; }

.mobile-menu-enter-active,
.mobile-menu-leave-active {
  transition: all var(--dur-med) var(--ease-smoke);
  overflow: hidden;
}
.mobile-menu-enter-from,
.mobile-menu-leave-to {
  opacity: 0;
  transform: translateY(-16px);
}

/* ==========================================================
   FOOTER
   ========================================================== */
.main-footer {
  position: relative;
  background:
    linear-gradient(180deg,
      var(--ash-obsidian) 0%,
      rgba(42, 10, 8, 0.4) 100%),
    var(--ash-void);
  color: var(--text-ash);
  overflow: hidden;
  padding-top: var(--sp-16);
}

/* Ember line at top */
.footer-ember-line {
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 1px;
  background: linear-gradient(90deg,
    transparent 0%,
    rgba(138, 31, 24, 0.4) 15%,
    rgba(226, 67, 16, 0.7) 50%,
    rgba(138, 31, 24, 0.4) 85%,
    transparent 100%);
  box-shadow: 0 0 14px rgba(226, 67, 16, 0.35);
  animation: emberPulse 5s ease-in-out infinite;
}
/* Iron strip — deeper */
.footer-iron-strip {
  position: absolute;
  top: 1px; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(180deg, var(--iron-void) 0%, var(--ash-void) 100%);
  box-shadow: inset 0 -1px 0 rgba(199, 154, 94, 0.1);
}

/* Тлеющий фон снизу футера */
.footer-forge-glow {
  position: absolute;
  bottom: -120px; left: 0; right: 0;
  height: 340px;
  background: var(--grad-forge);
  opacity: 0.4;
  pointer-events: none;
  filter: blur(32px);
}

.footer-inner {
  position: relative;
  max-width: var(--page-max);
  margin: 0 auto;
  padding: 0 var(--sp-6);
  z-index: 1;
}

/* Декоративные висящие баннеры */
.footer-banners {
  position: absolute;
  top: -8px;
  left: 8%;
  display: flex;
  gap: 12px;
  pointer-events: none;
  opacity: 0.35;
}
.footer-banner {
  display: block;
  width: 22px;
  height: 90px;
  background: var(--grad-ember-banner);
  clip-path: polygon(0 0, 100% 0, 100% 85%, 50% 100%, 0 85%);
  box-shadow: var(--inset-iron-top);
  animation: bannerSway 6s ease-in-out infinite;
}
.footer-banner.b2 { animation-delay: -3s; height: 110px; opacity: 0.7; }

/* Grid */
.footer-grid {
  display: grid;
  grid-template-columns: 1.7fr 1fr 1fr 1.2fr;
  gap: 40px 48px;
}

/* Brand column */
.brand-col { display: flex; flex-direction: column; gap: var(--sp-5); }

.footer-logo {
  display: inline-flex;
  align-items: center;
  gap: var(--sp-3);
  text-decoration: none;
}
.footer-logo-img {
  width: 38px; height: 38px;
  filter: drop-shadow(0 0 4px rgba(226, 67, 16, 0.3));
}
.footer-logo-text {
  font-family: var(--font-display);
  font-weight: var(--fw-black);
  font-size: 1.35rem;
  letter-spacing: var(--ls-wider);
  color: var(--text-bright);
}
.footer-logo-accent {
  background: var(--grad-ember-text);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  filter: drop-shadow(0 0 6px rgba(226, 67, 16, 0.4));
}

.footer-tagline {
  font-family: var(--font-body);
  font-style: italic;
  font-size: 0.92rem;
  color: var(--text-ash);
  line-height: 1.75;
  margin: 0;
  max-width: 320px;
}

/* Socials */
.footer-socials { display: flex; gap: 10px; }
.social-btn {
  width: 42px; height: 42px;
  border-radius: var(--r-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
  text-decoration: none;
  color: var(--text-ash);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  border: 1px solid var(--iron-dark);
  transition: all var(--dur-fast) var(--ease-smoke);
}
.social-btn:hover {
  color: var(--text-bright);
  border-color: var(--ember-deep);
  background: linear-gradient(180deg, var(--ash-bloodrock) 0%, var(--ash-stone) 100%);
  transform: translateY(-3px);
  box-shadow: var(--glow-ember-soft), var(--inset-iron-top);
}
.social-btn.vk:hover  { box-shadow: var(--glow-brass), var(--inset-iron-top); border-color: var(--bronze-dark); }
.social-btn.tg:hover  { box-shadow: var(--glow-ember-soft), var(--inset-iron-top); }
.social-btn.yt:hover  { box-shadow: var(--glow-blood), var(--inset-iron-top); border-color: var(--ember-heart); }

/* Column titles */
.footer-col-title {
  font-family: var(--font-display);
  font-size: 0.82rem;
  font-weight: var(--fw-bold);
  text-transform: uppercase;
  letter-spacing: var(--ls-widest);
  color: var(--text-bone);
  margin: 0 0 var(--sp-5);
  display: flex;
  align-items: center;
  gap: 10px;
}
.col-title-spike {
  display: inline-block;
  width: 0; height: 0;
  border-left: 7px solid transparent;
  border-right: 7px solid transparent;
  border-top: 10px solid var(--ember-heart);
  filter: drop-shadow(0 0 4px rgba(194, 40, 26, 0.5));
  flex-shrink: 0;
}

/* Links */
.footer-links {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.footer-links a {
  font-family: var(--font-ui);
  font-size: 0.9rem;
  color: var(--text-ash);
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 3px 0;
  transition: all var(--dur-fast) var(--ease-smoke);
  border-left: 2px solid transparent;
  padding-left: 8px;
  margin-left: -8px;
}
.footer-links a:hover {
  color: var(--ember-spark);
  border-left-color: var(--ember-flame);
  transform: translateX(3px);
}
.link-icon {
  color: var(--brass);
  font-size: 0.95rem;
  flex-shrink: 0;
}

/* Platform badges */
.footer-platforms {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: var(--sp-5);
}
.fp-badge {
  font-family: var(--font-display);
  font-size: 0.68rem;
  font-weight: var(--fw-semibold);
  letter-spacing: var(--ls-wide);
  text-transform: uppercase;
  color: var(--text-ash);
  background: var(--ash-coal);
  border: 1px solid var(--iron-dark);
  padding: 4px 10px;
  border-radius: var(--r-xs);
  text-decoration: none;
  cursor: pointer;
  transition: all var(--dur-fast) var(--ease-smoke);
}
.fp-badge:hover {
  color: var(--ember-gold);
  border-color: var(--ember-heart);
  background: linear-gradient(180deg, var(--ash-bloodrock), var(--ash-coal));
  box-shadow: var(--glow-ember-soft);
  transform: translateY(-1px);
}

/* Divider */
.footer-divider {
  margin: var(--sp-12) 0 0;
  height: 1px;
  background: linear-gradient(90deg,
    transparent,
    var(--iron-dark) 15%,
    var(--iron-mid) 50%,
    var(--iron-dark) 85%,
    transparent);
  position: relative;
}
.divider-spike {
  position: absolute;
  top: -7px;
  left: 50%;
  transform: translateX(-50%);
  width: 0; height: 0;
  border-left: 7px solid transparent;
  border-right: 7px solid transparent;
  border-bottom: 8px solid var(--ember-heart);
  filter: drop-shadow(0 0 6px rgba(194, 40, 26, 0.6));
}

/* Bottom bar */
.footer-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--sp-5) 0 var(--sp-6);
  flex-wrap: wrap;
  gap: 8px;
}
.footer-copy, .footer-made {
  font-family: var(--font-body);
  font-size: 0.85rem;
  margin: 0;
  color: var(--text-smoke);
}
.footer-made {
  font-family: var(--font-tribal);
  color: var(--brass);
  letter-spacing: var(--ls-wide);
  font-size: 0.9rem;
}

/* ==========================================================
   SEARCH
   ========================================================== */
.search-wrap {
  position: relative;
  display: flex;
  align-items: center;
  flex: 0 1 240px;       /* может сжиматься от 240 до 0 при недостатке места */
  min-width: 0;
}

.search-bar-wrap {
  display: flex;
  align-items: center;
  gap: 6px;
  position: relative;
}

.search-icon-small {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--bronze);
  pointer-events: none;
  display: flex;
  z-index: 2;
}
.search-input {
  width: 240px;
  padding: 9px 38px 9px 34px;
  border-radius: var(--r-sm);
  border: 1px solid var(--iron-mid);
  background: linear-gradient(180deg, var(--ash-obsidian) 0%, var(--ash-coal) 100%);
  color: var(--text-bone);
  font-family: var(--font-ui);
  font-size: 0.88rem;
  outline: none;
  transition: all var(--dur-fast) var(--ease-smoke);
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.4);
}
.search-input::placeholder { color: var(--text-smoke); font-style: italic; }
.search-input:focus {
  border-color: var(--ember-heart);
  box-shadow:
    inset 0 2px 4px rgba(0, 0, 0, 0.4),
    0 0 0 3px rgba(194, 40, 26, 0.2),
    var(--glow-ember-soft);
}

.search-close-btn {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: var(--text-ash);
  cursor: pointer;
  font-size: 0.85rem;
  padding: 4px 6px;
  border-radius: var(--r-xs);
  transition: color var(--dur-fast);
  z-index: 2;
}
.search-close-btn:hover { color: var(--ember-flame); }

/* Dropdown */
.search-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  left: 0;
  width: 380px;
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  backdrop-filter: blur(16px);
  border: 1px solid var(--iron-mid);
  border-radius: var(--r-sm);
  box-shadow:
    var(--shadow-lift),
    var(--inset-iron-top),
    var(--inset-forge);
  overflow: hidden;
  z-index: var(--z-overlay);
}
.search-loading {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 16px 18px;
  color: var(--text-ash);
  font-family: var(--font-body);
  font-style: italic;
  font-size: 0.88rem;
}
.search-spinner {
  display: inline-block;
  width: 14px; height: 14px;
  border: 2px solid var(--iron-dark);
  border-top-color: var(--ember-flame);
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.search-result-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 16px;
  cursor: pointer;
  transition: background var(--dur-fast) var(--ease-smoke);
  border-bottom: 1px solid var(--iron-dark);
  position: relative;
}
.search-result-item:last-child { border-bottom: none; }
.search-result-item::before {
  content: '';
  position: absolute;
  left: 0; top: 0; bottom: 0;
  width: 3px;
  background: var(--ember-heart);
  transform: scaleY(0);
  transform-origin: center;
  transition: transform var(--dur-fast) var(--ease-smoke);
}
.search-result-item:hover {
  background: var(--ash-bloodrock);
}
.search-result-item:hover::before { transform: scaleY(1); }

.sr-img {
  width: 46px; height: 46px;
  border-radius: var(--r-xs);
  object-fit: cover;
  flex-shrink: 0;
  background: var(--ash-obsidian);
  border: 1px solid var(--iron-dark);
}
.sr-info { flex: 1; min-width: 0; }
.sr-title {
  display: block;
  font-family: var(--font-display);
  font-size: 0.92rem;
  font-weight: var(--fw-semibold);
  color: var(--text-bright);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.sr-genre {
  display: block;
  font-family: var(--font-body);
  font-size: 0.78rem;
  color: var(--text-ash);
  margin-top: 2px;
  font-style: italic;
}
.sr-price {
  font-family: var(--font-display);
  font-size: 0.92rem;
  font-weight: var(--fw-bold);
  color: var(--ember-gold);
  white-space: nowrap;
  flex-shrink: 0;
  text-shadow: 0 0 8px rgba(255, 201, 121, 0.3);
}
.search-empty {
  padding: 18px;
  text-align: center;
  color: var(--text-smoke);
  font-family: var(--font-body);
  font-style: italic;
  font-size: 0.88rem;
}

/* Transitions */
.search-expand-enter-active,
.search-expand-leave-active { transition: all var(--dur-med) var(--ease-smoke); }
.search-expand-enter-from,
.search-expand-leave-to { opacity: 0; transform: scaleX(0.85); transform-origin: right; }

.dropdown-enter-active,
.dropdown-leave-active { transition: all var(--dur-fast) var(--ease-smoke); }
.dropdown-enter-from,
.dropdown-leave-to { opacity: 0; transform: translateY(-8px); }

/* CURSOR — старый orc-hand SVG-курсор удалён.
   Теперь нативный курсор ОС остаётся видимым, поверх — CursorTrail.vue
   (искры за курсором + бурст на клике, theme-aware). */

/* ==========================================================
   RESPONSIVE — header / footer / global app shell
   Цель: на любой ширине от 320px до desktop UI остаётся читаемым,
   ничто не вылезает за край, ничто не прилипает к краям.
   ========================================================== */
@media (max-width: 1599px) {
  /* Compact-зона покрывает ВСЁ от 0 до 1599 — стыкуется ровно с min-width: 1600
     ниже, без «no man's land». На 1594-мониторе раньше попадало между правилами,
     дефолты возвращали жирные подписи и profile-name → Logout уезжал за край.
     Правило ловит ВСЕ ноуты (1366/1440/1536/1600 эффективные) и любые
     промежуточные ширины окна. Премиум-стили — только от 1600px. */
  .header-content { padding: 0 var(--sp-4); gap: 6px; }
  .main-nav { margin-left: var(--sp-3); }
  .nav-link { padding: 9px 10px; font-size: 0.78rem; }
  .logo-word-sub { display: none; }
  .search-input { width: 200px; padding: 9px 34px 9px 32px; }
  /* Mode-toggle: подписи прячутся, остаются только иконки ⚔ / 📜 */
  .mode-btn .mode-label { display: none; }
  .mode-btn { padding: 6px 9px; }
  .mode-toggle { margin-left: var(--sp-2); padding: 2px; }
  /* Профиль-имя и подпись Темы тоже прячутся в этой зоне:
     это закрывает 110% зум на 1366×768 (viewport ~1242) — там оба
     элемента пока показывались и наезжали на Logout. */
  .profile-name { display: none; }
  .profile-btn { padding: 4px 4px; }
  .theme-btn-labeled .theme-label-text { display: none; }
  .theme-btn-labeled { padding: 0 9px; gap: 5px; }
}

@media (max-width: 1240px) {
  /* Дальнейшее уплотнение для очень тесных ширин (1100-1240).
     profile-name / theme-label / mode-label уже скрыты с 1500. */
  .search-input { width: 170px; padding: 9px 30px 9px 28px; }
  .nav-link { padding: 8px 8px; font-size: 0.74rem; letter-spacing: 0.04em; }
  .main-nav { gap: 0; }
  .mode-btn { padding: 5px 7px; }
  .mode-icon { font-size: 0.85rem; }
}

@media (max-width: 1100px) {
  .footer-grid { grid-template-columns: 1fr 1fr; }
  .brand-col { grid-column: 1 / -1; flex-direction: row; flex-wrap: wrap; align-items: flex-start; gap: 24px; }
  .footer-tagline { flex: 1; min-width: 220px; }
  .main-nav { margin-left: var(--sp-2); }
  .search-wrap { flex: 0 1 180px; }
  /* Theme-label / profile-name уже скрыты с 1500 */
}

@media (max-width: 980px) {
  /* logo-word-sub уже скрыт с 1280px */
  .search-wrap { flex: 1 1 180px; min-width: 0; }
  .search-input { width: 100%; }
}

@media (max-width: 900px) {
  .main-nav { display: none; }
  .hamburger { display: flex; }
  /* .logout-label / .profile-name уже скрыты выше — здесь только nav→hamburger. */
  .footer-banners { display: none; }
  .header-content { gap: 8px; }
  /* Mode-toggle на десктопе исчезает — на мобиле он внутри hamburger-меню */
  .mode-toggle { display: none; }
}

@media (max-width: 720px) {
  .header-content { padding: 0 var(--sp-4); }
  .logo-word-main, .logo-word-accent { font-size: 1.1rem; }
  .logo-sigil-wrap, .logo-sigil { width: 36px; height: 36px; }
  /* Кнопки действий компактнее */
  .action-btn { width: 36px; height: 36px; }
  .theme-btn-labeled { width: 36px; padding: 0; gap: 0; }
  .theme-btn-labeled .nav-chevron { display: none; }
  /* На мобиле прячем поиск в шапке полностью — он наезжал на кнопки темы.
     Использовать поиск с телефона можно через каталог (там фильтры). */
  .search-wrap { display: none; }
}

@media (max-width: 640px) {
  .footer-grid { grid-template-columns: 1fr; gap: 28px; }
  .brand-col { flex-direction: column; }
  .footer-bottom { flex-direction: column; text-align: center; gap: 10px; }
  .footer-inner { padding-left: 18px; padding-right: 18px; }
  .footer-platforms { justify-content: flex-start; }
  .auth-btn.ghost { display: none; }
  .search-dropdown { width: 100%; max-width: 320px; left: auto; right: 0; }
  .theme-dropdown { right: -8px; min-width: 240px; }
}

@media (max-width: 480px) {
  .header-content { padding: 0 var(--sp-3); height: 60px; gap: 6px; }
  .logo-word { display: none; }
  .auth-btn.solid { padding: 8px 12px; font-size: 0.75rem; }
  .auth-btn.solid span { display: inline; }
  .toast-container { right: 12px; left: 12px; bottom: 16px; }
  .toast { max-width: 100%; }
}

@media (max-width: 380px) {
  /* Iphone SE и подобные — крайние компромиссы */
  .header-content { padding: 0 8px; gap: 4px; }
  .logo-sigil-wrap, .logo-sigil { width: 32px; height: 32px; }
  .action-btn { width: 32px; height: 32px; }
  .theme-btn-labeled { width: 32px; }
  /* Поиск всё-таки прячем за иконку, иначе кнопкам нет места */
  .search-wrap { display: none; }
}
</style>

<!-- ============================================================
     GLOBAL STYLES — toast (teleported to body, needs global)
============================================================ -->
<style>
.toast-container {
  position: fixed;
  bottom: 28px;
  right: 24px;
  z-index: var(--z-toast);
  display: flex;
  flex-direction: column;
  gap: 10px;
  pointer-events: none;
  max-width: 380px;
  width: calc(100vw - 32px);
}

.toast {
  pointer-events: all;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 14px 16px 14px 18px;
  border-radius: var(--r-sm);
  border: 1px solid var(--iron-mid);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  backdrop-filter: blur(14px);
  box-shadow:
    var(--shadow-cast),
    var(--inset-iron-top);
  color: var(--text-bone);
  font-family: var(--font-ui);
  font-size: 0.92rem;
  line-height: 1.55;
  cursor: pointer;
  opacity: 0;
  transform: translateX(20px);
  transition:
    opacity var(--dur-med) var(--ease-smoke),
    transform var(--dur-med) var(--ease-smoke);
  word-break: break-word;
  position: relative;
  overflow: hidden;
}
/* Левая акцентная полоса */
.toast::before {
  content: '';
  position: absolute;
  top: 0; left: 0; bottom: 0;
  width: 3px;
  background: var(--bronze);
}
.toast.toast-visible {
  opacity: 1;
  transform: translateX(0);
}

.toast-success { border-color: rgba(127, 166, 61, 0.5); }
.toast-success::before { background: var(--orc-green); box-shadow: 0 0 12px rgba(127, 166, 61, 0.5); }
.toast-success .toast-icon { color: var(--orc-emerald); }

.toast-error { border-color: var(--ember-heart); background: linear-gradient(180deg, var(--ash-bloodrock) 0%, var(--ash-stone) 100%); }
.toast-error::before { background: var(--ember-heart); box-shadow: 0 0 14px rgba(194, 40, 26, 0.6); }
.toast-error .toast-icon { color: var(--ember-flame); }

.toast-warning { border-color: rgba(226, 132, 44, 0.5); }
.toast-warning::before { background: var(--warn-ember); box-shadow: 0 0 12px rgba(226, 132, 44, 0.5); }
.toast-warning .toast-icon { color: var(--warn-gold); }

.toast-info { border-color: rgba(199, 154, 94, 0.45); }
.toast-info::before { background: var(--brass); box-shadow: 0 0 10px rgba(199, 154, 94, 0.4); }
.toast-info .toast-icon { color: var(--brass); }

.toast-icon {
  font-size: 1.05rem;
  flex-shrink: 0;
  margin-top: 1px;
}
.toast-msg { flex: 1; color: var(--text-bone); }
.toast-close {
  background: none;
  border: none;
  color: var(--text-smoke);
  font-size: 1.2rem;
  line-height: 1;
  padding: 0 0 0 4px;
  cursor: pointer;
  flex-shrink: 0;
  transition: color var(--dur-fast);
}
.toast-close:hover { color: var(--ember-flame); }

.toast-enter-active { transition: opacity var(--dur-med) var(--ease-smoke), transform var(--dur-med) var(--ease-smoke); }
.toast-leave-active { transition: opacity var(--dur-fast) var(--ease-smoke), transform var(--dur-fast) var(--ease-smoke); }
.toast-enter-from   { opacity: 0; transform: translateX(20px); }
.toast-leave-to     { opacity: 0; transform: translateX(20px); }

@media (max-width: 480px) {
  .toast-container { bottom: 14px; right: 8px; left: 8px; width: auto; max-width: none; }
}

/* Системный курсор больше не скрываем — он остаётся виден, а
   CursorTrail.vue лишь добавляет искры/частицы поверх него. */
</style>
