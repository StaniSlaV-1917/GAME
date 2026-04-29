// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth'; // <<< ИМПОРТИРУЕМ ХРАНИЛИЩЕ

import HomeView from '../views/HomeView.vue';
import CatalogView from '../views/CatalogView.vue';
import AboutView from '../views/About.vue'; // <<< ИМПОРТ НОВОГО КОМПОНЕНТА
import SovietView from '../views/SovietView.vue';
import CartView from '../views/CartView.vue';
import LoginView from '../views/LoginView.vue';
import RegisterView from '../views/RegisterView.vue';
import ProfileView from '../views/ProfileView.vue';
import GamePage from '../views/GamePage.vue';
import NotFoundView from '../views/NotFoundView.vue';
import NewsView from '../views/NewsView.vue';

// Forum stubs (Phase 1) — единый компонент для всех заглушек
const ForumStubView = () => import('../views/ForumStubView.vue');

// Админские / менеджерские страницы (lazy)
const AdminDashboard = () => import('../views/AdminDashboard.vue');
const AdminGames = () => import('../views/AdminGames.vue');
const AdminNews = () => import('../views/AdminNews.vue');
const AdminOrders = () => import('../views/AdminOrders.vue');
const AdminUsers = () => import('../views/AdminUsers.vue');
const AdminReviewsPage = () => import('../views/AdminReviewsPage.vue');
const AdminSupport     = () => import('../views/AdminSupport.vue');

const routes = [
  { path: '/', name: 'home', component: HomeView },
  { path: '/news', name: 'news', component: NewsView },
  {
    path: '/news/:id',
    name: 'news-article',
    component: () => import('../views/NewsArticleView.vue'),
    props: true
  },
  { path: '/catalog', name: 'catalog', component: CatalogView },
  { path: '/about', name: 'about', component: AboutView }, // <<< НОВЫЙ МАРШРУТ
  { path: '/soviet', name: 'soviet', component: SovietView },

  // ─── Forum stubs (Phase 1) — заглушки разделов форума,
  //     контент будет в Phase 2-6 по плану v2.0 ───
  {
    path: '/feed',
    name: 'feed',
    component: ForumStubView,
    meta: { stub: { title: 'Лента сообщества', eyebrow: 'Phase 2', icon: '📜' } },
  },
  {
    path: '/posts',
    name: 'posts',
    component: ForumStubView,
    meta: { stub: { title: 'Посты и обсуждения', eyebrow: 'Phase 2', icon: '⚒' } },
  },
  {
    path: '/mods',
    name: 'mods',
    component: ForumStubView,
    meta: { stub: { title: 'Каталог модов', eyebrow: 'Phase 6', icon: '🛡' } },
  },
  {
    path: '/community',
    name: 'community',
    component: ForumStubView,
    meta: { stub: { title: 'Сообщество', eyebrow: 'Phase 3', icon: '🜨' } },
  },

  // <<< ИЗМЕНЕНИЕ ЗДЕСЬ
  { 
    path: '/cart', 
    name: 'cart', 
    component: CartView, 
    meta: { requiresAuth: true } // Требует авторизации
  },

  { path: '/login', name: 'login', component: LoginView },
  { path: '/register', name: 'register', component: RegisterView },

  {
    path: '/profile',
    name: 'profile',
    component: ProfileView,
    meta: { requiresAuth: true, roles: ['user', 'manager', 'admin'] }
  },

  {
    path: '/games/:id',
    name: 'game',
    component: GamePage,
    props: true
  },

  // ── Phase 2 / Forum: публичный профиль /u/:username ──
  {
    path: '/u/:username',
    name: 'user-profile',
    component: () => import('../views/PublicProfileView.vue'),
    props: true,
  },
  // ── Phase 2 / Forum: редактор поста (ВАЖНО: до /post/:id, иначе
  //     :id отматчит "new" как параметр) ──
  {
    path: '/post/new',
    name: 'post-new',
    component: () => import('../views/PostNewView.vue'),
    meta: { requiresAuth: true },
  },
  // Одиночный пост /post/:id — Batch D реализует полноценный view
  {
    path: '/post/:id',
    name: 'post',
    component: () => import('../views/PostView.vue'),
    props: true,
  },

  // --- Админ-панель ---
  {
    path: '/admin',
    name: 'AdminDashboard',
    component: AdminDashboard,
    meta: { requiresAuth: true, roles: ['manager', 'admin'] }
  },
  {
    path: '/admin/news',
    name: 'AdminNews',
    component: AdminNews,
    meta: { requiresAuth: true, roles: ['admin'] } 
  },
  {
    path: '/admin/orders',
    name: 'AdminOrders',
    component: AdminOrders,
    meta: { requiresAuth: true, roles: ['manager', 'admin'] }
  },
  {
    path: '/admin/users',
    name: 'AdminUsers',
    component: AdminUsers,
    meta: { requiresAuth: true, roles: ['manager', 'admin'] }
  },
  {
    path: '/admin/employees',
    name: 'AdminEmployees',
    component: () => import('../views/AdminEmployees.vue'),
    meta: { requiresAuth: true, roles: ['admin'] },
  },
  {
    path: '/admin/games',
    name: 'AdminGames',
    component: AdminGames,
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/reviews',
    name: 'AdminReviewsPage',
    component: AdminReviewsPage,
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/support',
    name: 'AdminSupport',
    component: AdminSupport,
    meta: { requiresAuth: true, roles: ['manager', 'admin'] }
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: NotFoundView,
  },
];


const router = createRouter({
  history: createWebHistory(),
  routes,
});

/**
 * Защита от stale chunks после деплоя.
 *
 * Проблема: SPA с Vite-чанками. У юзера в браузере открыт старый
 * index.html, ссылающийся на старые имена JS-файлов. Деплой создал
 * новые имена (с другими хешами), старые удалены. При попытке lazy-
 * load чанка приложение получает 404 → Firebase rewrite на index.html
 * → MIME error «Expected JavaScript got text/html».
 *
 * Решение: ловим router.onError, и если ошибка содержит признаки
 * dynamic-import-failure — перезагружаем страницу. Юзер получает
 * fresh index.html с актуальными именами чанков.
 */
router.onError((err, to) => {
  const msg = String(err?.message || err || '');
  const isChunkError =
    msg.includes('Failed to fetch dynamically imported module') ||
    msg.includes('Importing a module script failed') ||
    msg.includes('Failed to load module script') ||
    msg.includes('error loading dynamically imported module') ||
    msg.includes('Loading chunk') ||
    msg.includes('Loading CSS chunk');

  if (isChunkError && to?.fullPath) {
    // Сохраняем куда пользователь хотел и перезагружаем
    window.location.href = to.fullPath;
  }
});

// <<< ГЛОБАЛЬНЫЙ НАВИГАЦИОННЫЙ ХУК
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();

  const isLoggedIn = authStore.isLoggedIn;
  const targetRoute = to.name;

  // Если маршрут требует авторизации и пользователь не залогинен
  if (to.meta.requiresAuth && !isLoggedIn) {
    // Перенаправляем на страницу логина, сохраняя путь, куда пользователь хотел попасть
    return next({ 
      name: 'login', 
      query: { redirect: to.fullPath } 
    });
  }

  // Если пользователь залогинен и пытается зайти на /login или /register,
  // перенаправляем его в профиль. Это предотвращает повторный вход.
  if (isLoggedIn && (targetRoute === 'login' || targetRoute === 'register')) {
    return next({ name: 'profile' });
  }
  
  // Во всех остальных случаях просто разрешаем переход
  next();
});

export default router;
