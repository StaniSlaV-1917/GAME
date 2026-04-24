<script setup>
import { useRoute } from 'vue-router';
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { RouterLink, RouterView, useRouter } from 'vue-router';
import { useAuthStore } from './stores/auth';
import { useThemeStore } from './stores/theme';
import { useCartStore } from './stores/cart';
import { storeToRefs } from 'pinia';
import api from './api/axios';
import ParticlesBackground from './components/ParticlesBackground.vue';
import SupportChat from './components/SupportChat.vue';
import { resolveMediaUrl } from './utils/media';
import { useToast } from './composables/useToast';
import hordeSigilUrl from './assets/horde-sigil.svg';

const { toasts, remove: removeToast } = useToast();

const authStore = useAuthStore();
const themeStore = useThemeStore();
const { user, isLoggedIn } = storeToRefs(authStore);
const router = useRouter();
const route = useRoute();

// Scroll to top on route change
watch(() => route.path, () => {
  window.scrollTo({ top: 0, behavior: 'smooth' });
}, { flush: 'post' });

const scrolled = ref(false);
const mobileMenuOpen = ref(false);

// ── Global search ──
const searchOpen = ref(false);
const searchQuery = ref('');
const searchResults = ref([]);
const searchLoading = ref(false);
const searchInputRef = ref(null);
let allGamesCache = null;
let searchTimer = null;

const openSearch = async () => {
  searchOpen.value = true;
  await new Promise(r => setTimeout(r, 50));
  searchInputRef.value?.focus();
};

const closeSearch = () => {
  searchOpen.value = false;
  searchQuery.value = '';
  searchResults.value = [];
};

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
  closeSearch();
  router.push({ name: 'game', params: { id } });
};

// ── Custom cursor ──
const cursorDot = ref(null);
const cursorRing = ref(null);
const isTouch = ref(false);

const moveCursor = (e) => {
  const x = e.clientX, y = e.clientY;
  if (cursorDot.value)  cursorDot.value.style.transform  = `translate(${x}px,${y}px)`;
  if (cursorRing.value) cursorRing.value.style.transform = `translate(${x}px,${y}px)`;
};


const handleLogout = async () => {
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
  } else {
    window.addEventListener('mousemove', moveCursor, { passive: true });
  }
});
onUnmounted(() => {
  window.removeEventListener('scroll', onScroll);
  window.removeEventListener('mousemove', moveCursor);
  clearTimeout(searchTimer);
});
</script>

<template>
  <div id="app-wrapper">

    <!-- Custom cursor (desktop only) — ember theme -->
    <template v-if="!isTouch">
      <div class="cursor-dot"  ref="cursorDot"></div>
      <div class="cursor-ring" ref="cursorRing"></div>
    </template>

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

        <!-- Logo: sigil + wordmark -->
        <RouterLink to="/" class="logo-link" @click="mobileMenuOpen = false" aria-label="GameStore — на главную">
          <span class="logo-sigil-wrap">
            <img alt="" class="logo-sigil" :src="hordeSigilUrl" width="44" height="44" />
            <span class="logo-sigil-glow" aria-hidden="true"></span>
          </span>
          <span class="logo-word">
            <span class="logo-word-main">GAME</span><span class="logo-word-accent">STORE</span>
            <span class="logo-word-sub">Оплот воина</span>
          </span>
        </RouterLink>

        <!-- Desktop nav -->
        <nav class="main-nav" aria-label="Главное меню">
          <RouterLink to="/" class="nav-link"><span>Главная</span></RouterLink>
          <RouterLink to="/news" class="nav-link"><span>Хроники</span></RouterLink>
          <RouterLink to="/catalog" class="nav-link"><span>Оружейная</span></RouterLink>
          <RouterLink to="/about" class="nav-link"><span>О клане</span></RouterLink>
          <RouterLink v-if="user?.is_admin" to="/admin" class="nav-link admin-link"><span>Совет</span></RouterLink>
        </nav>

        <!-- Global search — "Вестник" -->
        <div class="search-wrap" :class="{ open: searchOpen }">
          <Transition name="search-expand">
            <div v-if="searchOpen" class="search-bar-wrap">
              <span class="search-icon-small" aria-hidden="true">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              </span>
              <input
                ref="searchInputRef"
                v-model="searchQuery"
                class="search-input"
                placeholder="Искать игру, жанр, платформу..."
                @keydown.escape="closeSearch"
              />
              <button class="search-close-btn" @click="closeSearch" title="Закрыть">✕</button>

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
          </Transition>

          <button v-if="!searchOpen" class="action-btn search-btn" @click="openSearch" title="Поиск" aria-label="Открыть поиск">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          </button>
        </div>

        <!-- User actions -->
        <div class="user-actions">
          <!-- Theme toggle — torch / sun -->
          <button class="action-btn theme-btn" @click="themeStore.toggle()" :title="themeStore.isDark ? 'День Осады' : 'Ночь Кузницы'" aria-label="Сменить тему">
            <span v-if="themeStore.isDark" class="theme-icon">☀</span>
            <span v-else class="theme-icon">☾</span>
          </button>

          <RouterLink to="/cart" class="action-btn cart-btn" title="Добыча" aria-label="Корзина">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
          </RouterLink>

          <template v-if="isLoggedIn && user">
            <RouterLink to="/profile" class="profile-btn">
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
          <nav class="mobile-nav">
            <RouterLink to="/" @click="mobileMenuOpen = false">Главная</RouterLink>
            <RouterLink to="/news" @click="mobileMenuOpen = false">Хроники</RouterLink>
            <RouterLink to="/catalog" @click="mobileMenuOpen = false">Оружейная</RouterLink>
            <RouterLink to="/about" @click="mobileMenuOpen = false">О клане</RouterLink>
            <RouterLink to="/soviet" @click="mobileMenuOpen = false">☭ СССР</RouterLink>
            <RouterLink to="/cart" @click="mobileMenuOpen = false">Добыча</RouterLink>
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

          <!-- Genres -->
          <div class="footer-col">
            <h4 class="footer-col-title">
              <span class="col-title-spike"></span>
              Школы боя
            </h4>
            <div class="footer-links">
              <RouterLink to="/catalog?genre=Action">Action</RouterLink>
              <RouterLink to="/catalog?genre=RPG">RPG</RouterLink>
              <RouterLink to="/catalog?genre=Strategy">Стратегии</RouterLink>
              <RouterLink to="/catalog?genre=Adventure">Приключения</RouterLink>
              <RouterLink to="/catalog?genre=Sports">Спорт</RouterLink>
              <RouterLink to="/catalog?genre=Horror">Ужас</RouterLink>
            </div>
          </div>

          <!-- Navigation -->
          <div class="footer-col">
            <h4 class="footer-col-title">
              <span class="col-title-spike"></span>
              Путь
            </h4>
            <div class="footer-links">
              <RouterLink to="/">Главная</RouterLink>
              <RouterLink to="/news">Хроники</RouterLink>
              <RouterLink to="/catalog">Оружейная</RouterLink>
              <RouterLink to="/about">О клане</RouterLink>
              <RouterLink to="/cart">Добыча</RouterLink>
              <RouterLink to="/soviet">☭</RouterLink>
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
              <span class="fp-badge">Steam</span>
              <span class="fp-badge">Epic</span>
              <span class="fp-badge">GOG</span>
              <span class="fp-badge">Battle.net</span>
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
.main-nav {
  margin-left: var(--sp-8);
  display: flex;
  align-items: center;
  gap: 2px;
  flex: 1;
}
.nav-link {
  position: relative;
  font-family: var(--font-display);
  font-size: 0.82rem;
  font-weight: var(--fw-semibold);
  text-transform: uppercase;
  letter-spacing: var(--ls-wide);
  color: var(--text-parchment);
  padding: 10px 16px;
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

.theme-btn .theme-icon {
  font-size: 1.05rem;
  color: var(--brass);
  filter: drop-shadow(0 0 4px rgba(199, 154, 94, 0.5));
}
.theme-btn:hover .theme-icon {
  color: var(--ember-gold);
  filter: drop-shadow(0 0 8px rgba(255, 201, 121, 0.8));
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
  font-size: 0.86rem;
  font-weight: var(--fw-semibold);
  max-width: 120px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* ==========================================================
   LOGOUT
   ========================================================== */
.logout-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  border-radius: var(--r-sm);
  border: 1px solid var(--iron-dark);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  color: var(--text-ash);
  font-family: var(--font-ui);
  font-size: 0.82rem;
  font-weight: var(--fw-medium);
  cursor: pointer;
  transition: all var(--dur-fast) var(--ease-smoke);
  white-space: nowrap;
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
  transition: all var(--dur-fast) var(--ease-smoke);
}
.fp-badge:hover {
  color: var(--brass);
  border-color: var(--bronze-dark);
  box-shadow: var(--glow-brass);
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
  width: 280px;
  padding: 10px 40px 10px 36px;
  border-radius: var(--r-sm);
  border: 1px solid var(--iron-mid);
  background: linear-gradient(180deg, var(--ash-obsidian) 0%, var(--ash-coal) 100%);
  color: var(--text-bone);
  font-family: var(--font-ui);
  font-size: 0.9rem;
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

/* ==========================================================
   CURSOR
   ========================================================== */
.cursor-dot,
.cursor-ring {
  position: fixed;
  top: 0; left: 0;
  pointer-events: none;
  z-index: var(--z-cursor);
  border-radius: 50%;
  will-change: transform;
}
.cursor-dot {
  width: 7px; height: 7px;
  margin: -3.5px 0 0 -3.5px;
  background: var(--ember-glow);
  box-shadow:
    0 0 10px rgba(255, 122, 43, 0.9),
    0 0 20px rgba(226, 67, 16, 0.6);
  transition: transform 0.04s linear;
}
.cursor-ring {
  width: 36px; height: 36px;
  margin: -18px 0 0 -18px;
  border: 1.5px solid rgba(255, 122, 43, 0.55);
  transition: transform 0.18s ease-out;
  mix-blend-mode: screen;
}

/* ==========================================================
   RESPONSIVE
   ========================================================== */
@media (max-width: 1100px) {
  .footer-grid { grid-template-columns: 1fr 1fr; }
  .brand-col { grid-column: 1 / -1; flex-direction: row; flex-wrap: wrap; align-items: flex-start; gap: 24px; }
  .footer-tagline { flex: 1; min-width: 220px; }
  .main-nav { margin-left: var(--sp-5); }
  .nav-link { padding: 10px 12px; }
}

@media (max-width: 980px) {
  .logo-word-sub { display: none; }
  .search-input { width: 220px; }
}

@media (max-width: 900px) {
  .main-nav { display: none; }
  .hamburger { display: flex; }
  .logout-label, .profile-name { display: none; }
  .footer-banners { display: none; }
}

@media (max-width: 640px) {
  .footer-grid { grid-template-columns: 1fr; }
  .brand-col { flex-direction: column; }
  .footer-bottom { flex-direction: column; text-align: center; }
  .auth-btn.ghost { display: none; }
  .search-input { width: 180px; }
  .search-dropdown { width: 280px; }
}

@media (max-width: 400px) {
  .header-content { padding: 0 var(--sp-4); }
  .logo-word { display: none; }
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

/* Скрыть системный курсор */
html:not(.touch-device) * { cursor: none !important; }
</style>
