<script setup>
import { ref, onMounted } from 'vue';
import { RouterLink, RouterView, useRouter } from 'vue-router';
import { useAuthStore } from './stores/auth';
import { storeToRefs } from 'pinia';
import api from './api/axios';

const authStore = useAuthStore();
const { user, isLoggedIn } = storeToRefs(authStore);
const router = useRouter();

const popularGames = ref([]);

const loadPopularGames = async () => {
  try {
    const { data } = await api.get('/games?is_hit=true&limit=4');
    popularGames.value = data;
  } catch (error) {
    console.error("Не удалось загрузить популярные игры:", error);
  }
};

const handleLogout = async () => {
  await authStore.logout();
  router.push({ name: 'login' });
};

onMounted(() => {
  loadPopularGames();
});

</script>

<template>
  <div id="app-wrapper">
    <header class="main-header">
      <div class="header-content">
        <RouterLink to="/" class="logo-link">
          <img alt="GameStore logo" class="logo-img" src="/images.png" />
          <span class="logo-text">GameStore</span>
        </RouterLink>

        <nav class="main-nav">
          <RouterLink to="/">Главная</RouterLink>
          <RouterLink to="/news">Новости</RouterLink>
          <RouterLink to="/catalog">Каталог</RouterLink>
          <RouterLink v-if="user?.is_admin" to="/admin" class="admin-link">Админка</RouterLink>
        </nav>

        <div class="user-actions">
          <RouterLink to="/cart" class="action-icon-link">
             <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
          </RouterLink>

          <template v-if="isLoggedIn && user">
            <RouterLink to="/profile" class="action-icon-link profile-link">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
              <span>{{ user.fullname }}</span>
            </RouterLink>
            <button @click="handleLogout" class="logout-btn">Выйти</button>
          </template>
          
          <template v-else>
            <RouterLink to="/login" class="auth-link login">Войти</RouterLink>
            <RouterLink to="/register" class="auth-link register">Регистрация</RouterLink>
          </template>
        </div>
      </div>
    </header>

    <main class="main-content">
      <RouterView />
    </main>

    <footer class="main-footer">
      <div class="footer-main-content">
        <div class="footer-grid">
          <div class="footer-col footer-about">
            <h3 class="footer-col-title">О магазине</h3>
            <p>GameStore — онлайн-магазин цифровых ключей. Мы заботимся о том, чтобы ваши покупки были безопасными, а поддержка — доступной и понятной.</p>
          </div>

          <div class="footer-col footer-popular-games">
            <h3 class="footer-col-title">Популярные игры</h3>
            <div v-if="popularGames.length" class="footer-links">
              <RouterLink v-for="game in popularGames" :key="game.id" :to="`/games/${game.id}`">
                {{ game.title }}
              </RouterLink>
            </div>
             <p v-else>Загрузка...</p>
          </div>

          <div class="footer-col footer-navigation">
             <h3 class="footer-col-title">Навигация</h3>
             <div class="footer-links">
                <RouterLink to="/">Главная</RouterLink>
                <RouterLink to="/news">Новости</RouterLink>
                <RouterLink to="/catalog">Каталог</RouterLink>
                <RouterLink to="/cart">Корзина</RouterLink>
             </div>
          </div>

          <div class="footer-col footer-contacts-socials">
            <div class="footer-contacts">
              <h3 class="footer-col-title">Связаться с нами</h3>
              <div class="footer-links">
                <a href="mailto:support@gamestore.com">support@gamestore.com</a>
                <a href="tel:+79991234567">+7 (999) 123-45-67</a>
              </div>
            </div>
            <div class="footer-socials">
              <h3 class="footer-col-title">Мы в соцсетях</h3>
              <div class="social-links">
                <a href="#" target="_blank" class="social-link social-link-vk">
                  <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/f3/VK_Compact_Logo_%282021-present%29.svg/1200px-VK_Compact_Logo_%282021-present%29.svg.png" alt="VK">
                </a>
                <a href="#" target="_blank" class="social-link social-link-telegram">
                  <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/82/Telegram_logo.svg/2048px-Telegram_logo.svg.png" alt="Telegram">
                </a>
                <a href="#" target="_blank" class="social-link social-link-youtube">
                  <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/0/09/YouTube_full-color_icon_%282017%29.svg/1280px-YouTube_full-color_icon_%282017%29.svg.png" alt="YouTube">
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="footer-bottom-bar">
        <p>&copy; {{ new Date().getFullYear() }} GameStore. Все права защищены.</p>
      </div>
    </footer>
  </div>
</template>

<style scoped>
/* --- ОБНОВЛЕННЫЙ ГЛОБАЛЬНЫЙ СТИЛЬ --- */
#app-wrapper {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background-color: #030712; /* Глубокий темный фон */
  /* Эффект туманности вверху */
  background-image: radial-gradient(ellipse 80% 50% at 50% -20%, rgba(120, 113, 192, 0.2), transparent);
}

.main-content { flex-grow: 1; }

/* --- ОБНОВЛЕННЫЙ ХЕДЕР --- */
.main-header {
  position: sticky;
  top: 0;
  z-index: 1000;
  /* Эффект стекла */
  background: rgba(17, 24, 39, 0.7);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}
.header-content { display: flex; align-items: center; max-width: 1400px; margin: 0 auto; padding: 0 24px; height: 70px; }

.logo-link { display: flex; align-items: center; gap: 12px; text-decoration: none; }
.logo-img { height: 38px; width: 38px; border-radius: 8px; }
.logo-text { font-size: 1.6rem; font-weight: 700; color: #fff; }

/* Навигация */
.main-nav { margin-left: 48px; display: flex; gap: 16px; }
.main-nav a {
  font-size: 1rem;
  font-weight: 500;
  color: #9ca3af;
  padding: 8px 14px;
  border-radius: 8px;
  text-decoration: none;
  transition: color 0.2s, background-color 0.2s;
}
.main-nav a:hover { color: #fff; background-color: rgba(255, 255, 255, 0.1); }
/* Яркий акцент для активной ссылки */
.main-nav a.router-link-exact-active { color: #fff; background: linear-gradient(90deg, #3b82f6, #6366f1); }
.main-nav a.admin-link.router-link-exact-active { background: linear-gradient(90deg, #c026d3, #a21caf); }

/* Кнопки пользователя */
.user-actions { margin-left: auto; display: flex; align-items: center; gap: 16px; }
.action-icon-link { color: #9ca3af; transition: color 0.2s; padding: 6px; display: flex; align-items: center; border-radius: 8px; text-decoration: none; }
.action-icon-link:hover { color: #fff; background-color: rgba(255, 255, 255, 0.1); }
.profile-link span { margin-left: 8px; font-weight: 500; font-size: 0.95rem; }

.logout-btn {
  background: none;
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #9ca3af;
  padding: 8px 14px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}
.logout-btn:hover { background-color: rgba(255, 255, 255, 0.1); color: #fff; border-color: rgba(255, 255, 255, 0.3); }

.auth-link { font-size: 0.95rem; font-weight: 600; padding: 9px 18px; border-radius: 8px; text-decoration: none; transition: all 0.2s; }
.auth-link.login { color: #d1d5db; }
.auth-link.login:hover { background-color: rgba(255, 255, 255, 0.1); }
.auth-link.register { background: linear-gradient(90deg, #3b82f6, #6366f1); color: #fff; }
.auth-link.register:hover { filter: brightness(1.1); }

/* --- ОБНОВЛЕННЫЙ ФУТЕР --- */
.main-footer {
  background: rgba(17, 24, 39, 0.7);
  color: #9ca3af;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}

.footer-main-content { max-width: 1400px; margin: 0 auto; padding: 48px 24px; }
.footer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 32px; }
.footer-col-title { font-size: 1.1rem; font-weight: 600; color: #e5e7eb; margin-bottom: 16px; }
.footer-col p { line-height: 1.7; margin: 0; }
.footer-links { display: flex; flex-direction: column; gap: 10px; }
.footer-links a { color: #9ca3af; text-decoration: none; transition: color 0.2s; }
.footer-links a:hover { color: #60a5fa; } /* Ярче при наведении */

.footer-socials { margin-top: 32px; }
.social-links { display: flex; gap: 16px; }
.social-link {
  display: flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 50%;
  background-color: rgba(255, 255, 255, 0.05);
  transition: background-color 0.2s;
}
.social-link:hover { background-color: rgba(255, 255, 255, 0.15); }
.social-link img { width: 24px; height: 24px; opacity: 0.8; }

.footer-bottom-bar {
  background: rgba(0, 0, 0, 0.2);
  padding: 16px 24px; text-align: center; font-size: 0.9rem;
}
.footer-bottom-bar p { margin: 0; }

@media (max-width: 768px) {
  .main-nav { display: none; }
  .header-content { padding: 0 16px; }
  .logo-text { display: none; }
}
</style>