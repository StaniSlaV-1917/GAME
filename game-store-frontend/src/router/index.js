// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router';

import HomeView from '../views/HomeView.vue';
import CatalogView from '../views/CatalogView.vue';
import CartView from '../views/CartView.vue';
import LoginView from '../views/LoginView.vue';
import RegisterView from '../views/RegisterView.vue';
import ProfileView from '../views/ProfileView.vue';
import GamePage from '../views/GamePage.vue';
import NotFoundView from '../views/NotFoundView.vue';
import NewsView from '../views/NewsView.vue';

// Админские / менеджерские страницы (lazy)
const AdminDashboard = () => import('../views/AdminDashboard.vue');
const AdminGames = () => import('../views/AdminGames.vue');
const AdminNews = () => import('../views/AdminNews.vue'); // << ДОБАВЛЕНО
const AdminOrders = () => import('../views/AdminOrders.vue');
const AdminUsers = () => import('../views/AdminUsers.vue');
const AdminReviewsPage = () => import('../views/AdminReviewsPage.vue');
const AdminHomeEditor = () => import('../views/AdminHomeEditor.vue');

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
  { path: '/cart', name: 'cart', component: CartView },

  { path: '/login', name: 'login', component: LoginView },
  { path: '/register', name: 'register', component: RegisterView },

  {
    path: '/profile',
    name: 'profile',
    component: ProfileView,
    meta: { requiresAuth: true, roles: ['user', 'manager', 'admin'] }
  },

  // НОВЫЙ маршрут страницы игры
  {
    path: '/games/:id',
    name: 'game',
    component: GamePage,
    props: true
  },

  // --- Админ-панель ---
  {
    path: '/admin',
    name: 'AdminDashboard',
    component: AdminDashboard,
    meta: { requiresAuth: true, roles: ['manager', 'admin'] }
  },
  // << ДОБАВЛЕН МАРШРУТ ДЛЯ НОВОСТЕЙ
  {
    path: '/admin/news',
    name: 'AdminNews',
    component: AdminNews,
    meta: { requiresAuth: true, roles: ['admin'] } 
  },
  {
    path: '/admin/home-editor',
    name: 'AdminHomeEditor',
    component: AdminHomeEditor,
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
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: NotFoundView,
  },
];


const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
