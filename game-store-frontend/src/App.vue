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
  <div id="app-wrapper" :class="{ 'theme-light': !themeStore.isDark }">

    <!-- Custom cursor (desktop only) -->
    <template v-if="!isTouch">
      <div class="cursor-dot"  ref="cursorDot"></div>
      <div class="cursor-ring" ref="cursorRing"></div>
    </template>

    <!-- Ambient particles -->
    <ParticlesBackground />

    <!-- ===== HEADER ===== -->
    <header class="main-header" :class="{ scrolled }">
      <!-- Animated gradient border -->
      <div class="header-glow-border"></div>

      <div class="header-content">

        <!-- Logo -->
        <RouterLink to="/" class="logo-link" @click="mobileMenuOpen = false">
          <div class="logo-icon-wrap">
            <img alt="GameStore logo" class="logo-img" src="/images.png" width="40" height="40" />
          </div>
          <span class="logo-text">Game<span class="logo-accent">Store</span></span>
        </RouterLink>

        <!-- Desktop nav -->
        <nav class="main-nav">
          <RouterLink to="/">Главная</RouterLink>
          <RouterLink to="/news">Новости</RouterLink>
          <RouterLink to="/catalog">Каталог</RouterLink>
          <RouterLink to="/about">О магазине</RouterLink>
          <RouterLink v-if="user?.is_admin" to="/admin" class="admin-link">Админка</RouterLink>
        </nav>

        <!-- Global search -->
        <div class="search-wrap" :class="{ open: searchOpen }">
          <Transition name="search-expand">
            <div v-if="searchOpen" class="search-bar-wrap">
              <input
                ref="searchInputRef"
                v-model="searchQuery"
                class="search-input"
                placeholder="Поиск игр..."
                @keydown.escape="closeSearch"
              />
              <button class="search-close-btn" @click="closeSearch" title="Закрыть">✕</button>

              <!-- Dropdown results -->
              <Transition name="dropdown">
                <div v-if="searchQuery && (searchResults.length || searchLoading)" class="search-dropdown">
                  <div v-if="searchLoading" class="search-loading">
                    <span class="search-spinner"></span> Поиск...
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
                    <div v-if="!searchResults.length" class="search-empty">Ничего не найдено</div>
                  </template>
                </div>
              </Transition>
            </div>
          </Transition>

          <button v-if="!searchOpen" class="action-btn search-btn" @click="openSearch" title="Поиск">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          </button>
        </div>

        <!-- User actions -->
        <div class="user-actions">
          <!-- Theme toggle -->
          <button class="action-btn theme-btn" @click="themeStore.toggle()" :title="themeStore.isDark ? 'Светлая тема' : 'Тёмная тема'">
            <span v-if="themeStore.isDark">☀️</span>
            <span v-else>🌙</span>
          </button>

          <RouterLink to="/cart" class="action-btn cart-btn" title="Корзина">
            <span class="cart-icon">🛒</span>
          </RouterLink>

          <template v-if="isLoggedIn && user">
            <RouterLink to="/profile" class="profile-btn">
              <div class="avatar">
                <img v-if="user.avatar" :src="`/avatars/${encodeURIComponent(user.avatar)}`" class="avatar-img" :alt="user.fullname" />
                <span v-else>{{ user.fullname?.[0]?.toUpperCase() ?? '?' }}</span>
              </div>
              <span class="profile-name">{{ user.fullname || 'Профиль' }}</span>
            </RouterLink>
            <button @click="handleLogout" class="logout-btn">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
              Выйти
            </button>
          </template>

          <template v-else>
            <RouterLink to="/login" class="auth-btn ghost">Войти</RouterLink>
            <RouterLink to="/register" class="auth-btn solid">Регистрация</RouterLink>
          </template>

          <!-- Hamburger -->
          <button class="hamburger" @click="mobileMenuOpen = !mobileMenuOpen" :class="{ open: mobileMenuOpen }" aria-label="Меню">
            <span></span><span></span><span></span>
          </button>
        </div>
      </div>

      <!-- Mobile menu -->
      <Transition name="mobile-menu">
        <div class="mobile-menu" v-if="mobileMenuOpen">
          <nav class="mobile-nav">
            <RouterLink to="/" @click="mobileMenuOpen = false">Главная</RouterLink>
            <RouterLink to="/news" @click="mobileMenuOpen = false">Новости</RouterLink>
            <RouterLink to="/catalog" @click="mobileMenuOpen = false">Каталог</RouterLink>
            <RouterLink to="/about" @click="mobileMenuOpen = false">О магазине</RouterLink>
            <RouterLink to="/soviet" @click="mobileMenuOpen = false">☭ СССР</RouterLink>
            <RouterLink to="/cart" @click="mobileMenuOpen = false">Корзина</RouterLink>
            <RouterLink v-if="user?.is_admin" to="/admin" @click="mobileMenuOpen = false">Админка</RouterLink>
          </nav>
          <div class="mobile-auth">
            <template v-if="isLoggedIn && user">
              <RouterLink to="/profile" class="auth-btn solid" @click="mobileMenuOpen = false">{{ user.fullname || 'Профиль' }}</RouterLink>
              <button @click="handleLogout" class="auth-btn ghost">Выйти</button>
            </template>
            <template v-else>
              <RouterLink to="/login" class="auth-btn ghost" @click="mobileMenuOpen = false">Войти</RouterLink>
              <RouterLink to="/register" class="auth-btn solid" @click="mobileMenuOpen = false">Регистрация</RouterLink>
            </template>
          </div>
        </div>
      </Transition>
    </header>

    <!-- Main content -->
    <main class="main-content">
      <RouterView :key="route.fullPath" />
    </main>

    <!-- ===== FOOTER ===== -->
    <footer class="main-footer">
      <!-- Animated top border -->
      <div class="footer-top-border"></div>

      <!-- Background decoration -->
      <div class="footer-bg-glow g1"></div>
      <div class="footer-bg-glow g2"></div>

      <div class="footer-inner">

        <!-- Top grid -->
        <div class="footer-grid">

          <!-- Brand column -->
          <div class="footer-col brand-col">
            <RouterLink to="/" class="footer-logo">
              <img src="/images.png" alt="GameStore logo" class="footer-logo-img" width="34" height="34" loading="lazy" />
              <span class="footer-logo-text">Game<span class="footer-logo-accent">Store</span></span>
            </RouterLink>
            <p class="footer-tagline">Лицензионные ключи для игр по лучшим ценам. Мгновенная доставка, гарантия качества.</p>
            <div class="footer-socials">
              <a href="#" target="_blank" class="social-btn vk" title="ВКонтакте">
                <i class="fa-brands fa-vk"></i>
              </a>
              <a href="#" target="_blank" class="social-btn tg" title="Telegram">
                <i class="fa-brands fa-telegram"></i>
              </a>
              <a href="#" target="_blank" class="social-btn yt" title="YouTube">
                <i class="fa-brands fa-youtube"></i>
              </a>
            </div>
          </div>

          <!-- Genres -->
          <div class="footer-col">
            <h4 class="footer-col-title">
              <span class="col-title-dot"></span>
              Жанры
            </h4>
            <div class="footer-links">
              <RouterLink to="/catalog?genre=Action">Action</RouterLink>
              <RouterLink to="/catalog?genre=RPG">RPG</RouterLink>
              <RouterLink to="/catalog?genre=Strategy">Стратегии</RouterLink>
              <RouterLink to="/catalog?genre=Adventure">Приключения</RouterLink>
              <RouterLink to="/catalog?genre=Sports">Спорт</RouterLink>
              <RouterLink to="/catalog?genre=Horror">Хоррор</RouterLink>
            </div>
          </div>

          <!-- Navigation -->
          <div class="footer-col">
            <h4 class="footer-col-title">
              <span class="col-title-dot"></span>
              Навигация
            </h4>
            <div class="footer-links">
              <RouterLink to="/">Главная</RouterLink>
              <RouterLink to="/news">Новости</RouterLink>
              <RouterLink to="/catalog">Каталог</RouterLink>
              <RouterLink to="/about">О магазине</RouterLink>
              <RouterLink to="/cart">Корзина</RouterLink>
              <RouterLink to="/soviet">☭</RouterLink>
            </div>
          </div>

          <!-- Contacts -->
          <div class="footer-col">
            <h4 class="footer-col-title">
              <span class="col-title-dot"></span>
              Контакты
            </h4>
            <div class="footer-links">
              <a href="mailto:Gamestore.help@yandex.com">
                <span class="link-icon">✉️</span> Gamestore.help@yandex.com
              </a>
              <a href="tel:+79991234567">
                <span class="link-icon">📞</span> +7 (999) 123-45-67
              </a>
            </div>

            <!-- Mini platform badges -->
            <div class="footer-platforms">
              <span class="fp-badge">Steam</span>
              <span class="fp-badge">Epic</span>
              <span class="fp-badge">GOG</span>
              <span class="fp-badge">Battle.net</span>
            </div>
          </div>
        </div>

        <!-- Divider -->
        <div class="footer-divider"></div>

        <!-- Bottom bar -->
        <div class="footer-bottom">
          <p class="footer-copy">© {{ new Date().getFullYear() }} GameStore. Все права защищены.</p>
          <p class="footer-made">Сделано для геймеров</p>
        </div>
      </div>
    </footer>

    <!-- ===== SUPPORT CHAT ===== -->
    <SupportChat />

    <!-- ===== TOAST NOTIFICATIONS ===== -->
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
/* ===== KEYFRAMES ===== */
@keyframes gradientShift {
  0%   { background-position: 0% 50%; }
  50%  { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}
@keyframes glowPulse {
  0%, 100% { opacity: 0.5; }
  50%       { opacity: 1; }
}
@keyframes avatarPop {
  from { transform: scale(0.8); opacity: 0; }
  to   { transform: scale(1); opacity: 1; }
}

/* ===== APP WRAPPER ===== */
#app-wrapper {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background: #020617;
  background-image: radial-gradient(ellipse 80% 50% at 50% -20%, rgba(99,102,241,0.15), transparent);
}
.main-content { flex-grow: 1; }

/* ===================================================
   HEADER
=================================================== */
.main-header {
  position: sticky;
  top: 0;
  z-index: 1000;
  /* Liquid glass base */
  background: rgba(2, 6, 23, 0.45);
  backdrop-filter: blur(24px) saturate(180%) brightness(1.05);
  -webkit-backdrop-filter: blur(24px) saturate(180%) brightness(1.05);
  border-bottom: 1px solid rgba(255, 255, 255, 0.07);
  transition: background 0.35s ease, box-shadow 0.35s ease;
}

/* More opaque + shadow when page is scrolled */
.main-header.scrolled {
  background: rgba(2, 6, 23, 0.72);
  box-shadow:
    0 1px 0 rgba(255,255,255,0.06),
    0 8px 32px rgba(0, 0, 0, 0.5),
    0 2px 8px rgba(0, 0, 0, 0.3);
}

/* Animated gradient bottom border */
.header-glow-border {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg,
    transparent 0%,
    rgba(59,130,246,0.6) 25%,
    rgba(99,102,241,0.8) 50%,
    rgba(59,130,246,0.6) 75%,
    transparent 100%);
  background-size: 200% 100%;
  animation: gradientShift 4s ease infinite;
}

.header-content {
  display: flex;
  align-items: center;
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 24px;
  height: 68px;
  gap: 8px;
}

/* --- Logo --- */
.logo-link {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  flex-shrink: 0;
}
.logo-icon-wrap {
  position: relative;
  width: 38px;
  height: 38px;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 0 0 1px rgba(255,255,255,0.1), 0 4px 12px rgba(99,102,241,0.25);
  transition: box-shadow 0.25s;
}
.logo-link:hover .logo-icon-wrap {
  box-shadow: 0 0 0 1px rgba(99,102,241,0.4), 0 4px 20px rgba(99,102,241,0.45);
}
.logo-img { width: 100%; height: 100%; object-fit: cover; display: block; }
.logo-text { font-size: 1.45rem; font-weight: 800; color: #fff; letter-spacing: -0.3px; }
.logo-accent { color: #60a5fa; }

/* --- Desktop nav --- */
.main-nav {
  margin-left: 32px;
  display: flex;
  align-items: center;
  gap: 4px;
  flex: 1;
}
.main-nav a {
  position: relative;
  font-size: 0.9rem;
  font-weight: 500;
  color: #9ca3af;
  padding: 7px 13px;
  border-radius: 8px;
  text-decoration: none;
  white-space: nowrap;
  transition: color 0.2s, background 0.2s;
}
.main-nav a::after {
  content: '';
  position: absolute;
  bottom: 3px;
  left: 50%;
  transform: translateX(-50%) scaleX(0);
  width: 60%;
  height: 2px;
  border-radius: 1px;
  background: #3b82f6;
  transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.main-nav a:hover { color: #e5e7eb; background: rgba(255,255,255,0.06); }
.main-nav a:hover::after { transform: translateX(-50%) scaleX(1); }

.main-nav a.router-link-exact-active {
  color: #fff;
  background: linear-gradient(135deg, rgba(59,130,246,0.2), rgba(99,102,241,0.2));
  border: 1px solid rgba(99,102,241,0.3);
  box-shadow: 0 0 12px rgba(99,102,241,0.15), inset 0 1px 0 rgba(255,255,255,0.08);
}
.main-nav a.router-link-exact-active::after { transform: translateX(-50%) scaleX(1); }

.main-nav a.admin-link.router-link-exact-active {
  background: linear-gradient(135deg, rgba(192,38,211,0.2), rgba(162,28,175,0.2));
  border-color: rgba(192,38,211,0.35);
  box-shadow: 0 0 12px rgba(192,38,211,0.2), inset 0 1px 0 rgba(255,255,255,0.08);
}
.main-nav a.admin-link.router-link-exact-active::after { background: #c026d3; }

.main-nav a.soviet-link { color: #f87171; }
.main-nav a.soviet-link:hover { color: #fca5a5; background: rgba(204,0,0,0.1); }
.main-nav a.soviet-link::after { background: #cc0000; }
.main-nav a.soviet-link.router-link-exact-active {
  color: #fca5a5;
  background: linear-gradient(135deg, rgba(139,0,0,0.25), rgba(204,0,0,0.2));
  border-color: rgba(204,0,0,0.4);
  box-shadow: 0 0 12px rgba(204,0,0,0.2);
}

/* --- User actions --- */
.user-actions { margin-left: auto; display: flex; align-items: center; gap: 10px; flex-shrink: 0; }

.action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  border: 1px solid rgba(255,255,255,0.08);
  background: rgba(255,255,255,0.04);
  text-decoration: none;
  color: #9ca3af;
  transition: all 0.2s;
  cursor: pointer;
}
.action-btn:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.16); color: #fff; }
.cart-icon { font-size: 1.15rem; line-height: 1; }

.profile-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 5px 12px 5px 5px;
  border-radius: 999px;
  border: 1px solid rgba(255,255,255,0.1);
  background: rgba(255,255,255,0.04);
  text-decoration: none;
  color: #d1d5db;
  transition: all 0.2s;
  cursor: pointer;
}
.profile-btn:hover { background: rgba(255,255,255,0.09); border-color: rgba(255,255,255,0.18); color: #fff; }

.avatar {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: #fff;
  font-size: 0.8rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  overflow: hidden;
  animation: avatarPop 0.3s ease;
}
.avatar-img {
  width: 100%; height: 100%;
  object-fit: cover;
  border-radius: 50%;
  display: block;
}
.profile-name { font-size: 0.88rem; font-weight: 500; max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.logout-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  border-radius: 8px;
  border: 1px solid rgba(255,255,255,0.1);
  background: rgba(255,255,255,0.03);
  color: #9ca3af;
  font-size: 0.88rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}
.logout-btn:hover { background: rgba(239,68,68,0.1); border-color: rgba(239,68,68,0.3); color: #fca5a5; }

.auth-btn {
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 0.88rem;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.2s;
  white-space: nowrap;
  cursor: pointer;
  border: none;
}
.auth-btn.ghost {
  color: #d1d5db;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.1);
}
.auth-btn.ghost:hover { background: rgba(255,255,255,0.1); color: #fff; }
.auth-btn.solid {
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: #fff;
  box-shadow: 0 4px 14px rgba(99,102,241,0.35);
}
.auth-btn.solid:hover { filter: brightness(1.1); box-shadow: 0 6px 20px rgba(99,102,241,0.5); transform: translateY(-1px); }

/* --- Hamburger --- */
.hamburger {
  display: none;
  flex-direction: column;
  justify-content: center;
  gap: 5px;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  border: 1px solid rgba(255,255,255,0.1);
  background: rgba(255,255,255,0.04);
  cursor: pointer;
  padding: 0 10px;
  transition: all 0.2s;
}
.hamburger span {
  display: block;
  width: 100%;
  height: 2px;
  background: #9ca3af;
  border-radius: 2px;
  transition: all 0.3s ease;
  transform-origin: center;
}
.hamburger:hover { background: rgba(255,255,255,0.08); }
.hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
.hamburger.open span:nth-child(2) { opacity: 0; transform: scaleX(0); }
.hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

/* --- Mobile menu --- */
.mobile-menu {
  border-top: 1px solid rgba(255,255,255,0.06);
  background: rgba(2,6,23,0.95);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  padding: 16px 24px 20px;
}
.mobile-nav {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-bottom: 16px;
}
.mobile-nav a {
  padding: 12px 14px;
  border-radius: 10px;
  text-decoration: none;
  color: #9ca3af;
  font-size: 0.95rem;
  font-weight: 500;
  transition: all 0.2s;
}
.mobile-nav a:hover, .mobile-nav a.router-link-exact-active {
  color: #fff;
  background: rgba(255,255,255,0.07);
}
.mobile-auth { display: flex; gap: 10px; }
.mobile-auth .auth-btn { flex: 1; text-align: center; }

.mobile-menu-enter-active, .mobile-menu-leave-active { transition: all 0.28s ease; overflow: hidden; }
.mobile-menu-enter-from, .mobile-menu-leave-to { opacity: 0; transform: translateY(-10px); }

/* ===================================================
   FOOTER
=================================================== */
.main-footer {
  position: relative;
  background: rgba(2, 6, 23, 0.92);
  backdrop-filter: blur(20px) saturate(160%);
  -webkit-backdrop-filter: blur(20px) saturate(160%);
  color: #6b7280;
  overflow: hidden;
}

/* Animated top gradient border */
.footer-top-border {
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 1px;
  background: linear-gradient(90deg,
    transparent 0%,
    rgba(59,130,246,0.5) 20%,
    rgba(99,102,241,0.8) 50%,
    rgba(59,130,246,0.5) 80%,
    transparent 100%);
  background-size: 200% 100%;
  animation: gradientShift 5s ease infinite;
}

/* Subtle background glows */
.footer-bg-glow {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  pointer-events: none;
}
.footer-bg-glow.g1 {
  width: 400px; height: 300px;
  background: rgba(59,130,246,0.06);
  top: -80px; left: -80px;
  animation: glowPulse 6s ease infinite;
}
.footer-bg-glow.g2 {
  width: 350px; height: 250px;
  background: rgba(99,102,241,0.05);
  bottom: 0; right: 0;
  animation: glowPulse 8s ease infinite reverse;
}

.footer-inner {
  position: relative;
  max-width: 1400px;
  margin: 0 auto;
  padding: 56px 24px 0;
  z-index: 1;
}

/* Footer grid */
.footer-grid {
  display: grid;
  grid-template-columns: 1.6fr 1fr 1fr 1fr;
  gap: 40px 32px;
}

/* Brand column */
.brand-col { display: flex; flex-direction: column; gap: 16px; }

.footer-logo {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
}
.footer-logo-img {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  box-shadow: 0 0 12px rgba(99,102,241,0.25);
}
.footer-logo-text { font-size: 1.3rem; font-weight: 800; color: #e5e7eb; }
.footer-logo-accent { color: #60a5fa; }

.footer-tagline {
  font-size: 0.88rem;
  color: #4b5563;
  line-height: 1.7;
  margin: 0;
  max-width: 280px;
}

/* Social buttons */
.footer-socials { display: flex; gap: 10px; margin-top: 4px; }
.social-btn {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  text-decoration: none;
  color: #4b5563;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.07);
  transition: all 0.25s;
}
.social-btn:hover { transform: translateY(-3px); }
.social-btn.vk:hover  { color: #fff; background: rgba(0,119,255,0.2); border-color: rgba(0,119,255,0.4); box-shadow: 0 6px 20px rgba(0,119,255,0.25); }
.social-btn.tg:hover  { color: #fff; background: rgba(0,136,204,0.2); border-color: rgba(0,136,204,0.4); box-shadow: 0 6px 20px rgba(0,136,204,0.25); }
.social-btn.yt:hover  { color: #fff; background: rgba(255,0,0,0.2);   border-color: rgba(255,0,0,0.4);   box-shadow: 0 6px 20px rgba(255,0,0,0.2); }

/* Column titles */
.footer-col-title {
  font-size: 0.78rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 2.5px;
  color: #e5e7eb;
  margin: 0 0 18px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.col-title-dot {
  display: inline-block;
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  box-shadow: 0 0 8px rgba(59,130,246,0.5);
  animation: glowPulse 2s ease infinite;
  flex-shrink: 0;
}

/* Footer links */
.footer-links {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.footer-links a {
  font-size: 0.88rem;
  color: #4b5563;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: color 0.2s, transform 0.2s;
  padding: 2px 0;
}
.footer-links a:hover { color: #93c5fd; transform: translateX(4px); }
.link-icon { font-size: 0.9rem; }
.footer-loading { font-size: 0.85rem; color: #374151; margin: 0; }

/* Platform badges */
.footer-platforms {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 20px;
}
.fp-badge {
  font-size: 0.7rem;
  font-weight: 600;
  letter-spacing: 0.5px;
  color: #374151;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.06);
  padding: 3px 10px;
  border-radius: 999px;
  transition: all 0.2s;
}
.fp-badge:hover { color: #9ca3af; border-color: rgba(255,255,255,0.12); }

/* Divider */
.footer-divider {
  margin: 40px 0 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.07) 20%, rgba(255,255,255,0.07) 80%, transparent);
}

/* Bottom bar */
.footer-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 18px 0 24px;
  flex-wrap: wrap;
  gap: 8px;
}
.footer-copy, .footer-made {
  font-size: 0.82rem;
  margin: 0;
  color: #374151;
}
.footer-made { color: #1f2937; }

/* ===== RESPONSIVE ===== */
@media (max-width: 1100px) {
  .footer-grid { grid-template-columns: 1fr 1fr; }
  .brand-col { grid-column: 1 / -1; flex-direction: row; flex-wrap: wrap; align-items: flex-start; gap: 24px; }
  .footer-tagline { flex: 1; min-width: 200px; }
}

@media (max-width: 900px) {
  .main-nav { display: none; }
  .hamburger { display: flex; }
  .logout-btn span, .profile-name { display: none; }
}

@media (max-width: 640px) {
  .footer-grid { grid-template-columns: 1fr; }
  .brand-col { flex-direction: column; }
  .footer-bottom { flex-direction: column; text-align: center; }
  .auth-btn:not(.solid):not(.ghost) { display: none; }
}

@media (max-width: 400px) {
  .header-content { padding: 0 16px; }
  .logo-text { display: none; }
}

/* ===================================================
   CUSTOM CURSOR
=================================================== */
.cursor-dot,
.cursor-ring {
  position: fixed;
  top: 0; left: 0;
  pointer-events: none;
  z-index: 99999;
  border-radius: 50%;
  will-change: transform;
}
.cursor-dot {
  width: 7px; height: 7px;
  margin: -3.5px 0 0 -3.5px;
  background: #60a5fa;
  box-shadow: 0 0 10px rgba(96,165,250,0.9), 0 0 20px rgba(96,165,250,0.5);
  transition: transform 0.04s linear;
}
.cursor-ring {
  width: 34px; height: 34px;
  margin: -17px 0 0 -17px;
  border: 1.5px solid rgba(96,165,250,0.55);
  transition: transform 0.18s ease-out;
  mix-blend-mode: screen;
}

/* ===================================================
   GLOBAL SEARCH
=================================================== */
.search-wrap {
  position: relative;
  display: flex;
  align-items: center;
}
.search-btn { font-size: 1rem; }

.search-bar-wrap {
  display: flex;
  align-items: center;
  gap: 6px;
  position: relative;
}

.search-input {
  width: 240px;
  padding: 9px 14px;
  border-radius: 10px;
  border: 1px solid rgba(99,102,241,0.45);
  background: rgba(15,23,42,0.85);
  color: #e5e7eb;
  font-size: 0.9rem;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.search-input::placeholder { color: #4b5563; }
.search-input:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99,102,241,0.2);
}

.search-close-btn {
  background: none;
  border: none;
  color: #6b7280;
  cursor: pointer;
  font-size: 0.85rem;
  padding: 4px 6px;
  border-radius: 6px;
  transition: color 0.2s;
  flex-shrink: 0;
}
.search-close-btn:hover { color: #e5e7eb; }

/* Dropdown */
.search-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  left: 0;
  width: 340px;
  background: rgba(10, 15, 30, 0.97);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(99,102,241,0.35);
  border-radius: 14px;
  box-shadow: 0 16px 48px rgba(0,0,0,0.6), 0 0 0 1px rgba(255,255,255,0.04);
  overflow: hidden;
  z-index: 500;
}
.search-loading {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 14px 16px;
  color: #6b7280;
  font-size: 0.88rem;
}
.search-spinner {
  display: inline-block;
  width: 14px; height: 14px;
  border: 2px solid rgba(99,102,241,0.3);
  border-top-color: #6366f1;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.search-result-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  cursor: pointer;
  transition: background 0.15s;
  border-bottom: 1px solid rgba(255,255,255,0.04);
}
.search-result-item:last-child { border-bottom: none; }
.search-result-item:hover { background: rgba(99,102,241,0.12); }
.sr-img {
  width: 44px; height: 44px;
  border-radius: 8px;
  object-fit: cover;
  flex-shrink: 0;
  background: #0a0f1e;
}
.sr-info { flex: 1; min-width: 0; }
.sr-title { display: block; font-size: 0.88rem; font-weight: 600; color: #e5e7eb; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sr-genre { display: block; font-size: 0.75rem; color: #6b7280; margin-top: 2px; }
.sr-price { font-size: 0.88rem; font-weight: 700; color: #4ade80; white-space: nowrap; flex-shrink: 0; }
.search-empty { padding: 16px; text-align: center; color: #4b5563; font-size: 0.88rem; }

/* Transitions */
.search-expand-enter-active, .search-expand-leave-active { transition: all 0.22s ease; }
.search-expand-enter-from, .search-expand-leave-to { opacity: 0; transform: scaleX(0.85); transform-origin: right; }

.dropdown-enter-active, .dropdown-leave-active { transition: all 0.18s ease; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-6px); }

/* ===================================================
   LIGHT THEME OVERRIDES  (non-scoped rules below in separate <style>)
=================================================== */

/* ===== TOAST NOTIFICATIONS ===== */
/* NOTE: .toast-container is inside <Teleport to="body"> so these need :deep or
   global styles. Using :deep here for scoped context. */
</style>

<!-- Toast styles are global because Teleport renders outside the scoped root -->
<style>
/* ===== GLOBAL TOAST STYLES ===== */
.toast-container {
  position: fixed;
  bottom: 28px;
  right: 24px;
  z-index: 99999;
  display: flex;
  flex-direction: column;
  gap: 10px;
  pointer-events: none;
  max-width: 360px;
  width: calc(100vw - 32px);
}

.toast {
  pointer-events: all;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 13px 14px 13px 16px;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(15, 23, 42, 0.94);
  backdrop-filter: blur(14px);
  box-shadow: 0 8px 32px rgba(0,0,0,0.45), inset 0 1px 0 rgba(255,255,255,0.05);
  color: #e5e7eb;
  font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  font-size: 0.9rem;
  line-height: 1.45;
  cursor: pointer;
  opacity: 0;
  transform: translateX(20px);
  transition: opacity 0.28s ease, transform 0.28s ease;
  word-break: break-word;
}
.toast.toast-visible {
  opacity: 1;
  transform: translateX(0);
}

.toast-success { border-color: rgba(74, 222, 128, 0.3); }
.toast-success .toast-icon { color: #4ade80; }

.toast-error { border-color: rgba(248, 113, 113, 0.35); background: rgba(20, 10, 10, 0.95); }
.toast-error .toast-icon { color: #f87171; }

.toast-warning { border-color: rgba(251, 191, 36, 0.3); }
.toast-warning .toast-icon { color: #fbbf24; }

.toast-info { border-color: rgba(59, 130, 246, 0.35); }
.toast-info .toast-icon { color: #60a5fa; }

.toast-icon {
  font-size: 1rem;
  flex-shrink: 0;
  margin-top: 1px;
}
.toast-msg { flex: 1; }
.toast-close {
  background: none;
  border: none;
  color: #6b7280;
  font-size: 1.1rem;
  line-height: 1;
  padding: 0 0 0 4px;
  cursor: pointer;
  flex-shrink: 0;
  transition: color 0.15s;
}
.toast-close:hover { color: #e5e7eb; }

/* TransitionGroup animations (uses CSS classes, not .toast-visible) */
.toast-enter-active { transition: opacity 0.28s ease, transform 0.28s ease; }
.toast-leave-active { transition: opacity 0.25s ease, transform 0.25s ease; }
.toast-enter-from   { opacity: 0; transform: translateX(20px); }
.toast-leave-to     { opacity: 0; transform: translateX(20px); }

@media (max-width: 480px) {
  .toast-container { bottom: 14px; right: 8px; left: 8px; width: auto; max-width: none; }
}
</style>

<!-- Global non-scoped styles -->
<style>
/* Hide system cursor when custom cursor is active */
html:not(.touch-device) * { cursor: none !important; }
</style>


