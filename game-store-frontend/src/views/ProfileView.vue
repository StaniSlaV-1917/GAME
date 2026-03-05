<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../api/axios';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const user = computed(() => authStore.user);

const orders = ref([]);
const userReviews = ref([]);

const profileForm = ref({ fullname: '', email: '' });
const passwordForm = ref({ current_password: '', new_password: '', new_password_confirmation: '' });

const activeTab = ref('overview');

const loading = ref({ orders: false, reviews: false });
const error = ref({ orders: '', reviews: '', profile: '', password: '' });
const message = ref({ profile: '', password: '' });

const loadInitialData = async () => {
  if (!user.value) return;
  profileForm.value.fullname = user.value.fullname || '';
  profileForm.value.email = user.value.email || '';
  await Promise.all([loadOrders(), loadUserReviews()]);
};

const loadOrders = async () => {
  loading.value.orders = true;
  error.value.orders = '';
  try {
    const { data } = await api.get('/orders');
    orders.value = data;
  } catch (e) {
    error.value.orders = 'Ошибка загрузки заказов.';
  } finally {
    loading.value.orders = false;
  }
};

const loadUserReviews = async () => {
  loading.value.reviews = true;
  error.value.reviews = '';
  try {
    const { data } = await api.get('/auth/my-reviews');
    userReviews.value = data;
  } catch (e) {
    error.value.reviews = 'Ошибка загрузки отзывов.';
  } finally {
    loading.value.reviews = false;
  }
};

const saveProfile = async () => {
  error.value.profile = '';
  message.value.profile = '';
  try {
    const { data } = await api.put('/auth/profile', profileForm.value);
    message.value.profile = data.message || 'Профиль успешно обновлен!';
    await authStore.fetchUser(); // Обновляем данные пользователя в сторе
    setTimeout(() => message.value.profile = '', 3000);
  } catch (e) {
    error.value.profile = e.response?.data?.message || 'Ошибка обновления профиля.';
  }
};

const changePassword = async () => {
  error.value.password = '';
  message.value.password = '';
  try {
    const { data } = await api.put('/auth/password', passwordForm.value);
    message.value.password = data.message || 'Пароль успешно изменен!';
    passwordForm.value = { current_password: '', new_password: '', new_password_confirmation: '' };
    setTimeout(() => message.value.password = '', 3000);
  } catch (e) {
    error.value.password = e.response?.data?.message || 'Ошибка смены пароля.';
  }
};

onMounted(loadInitialData);

</script>

<template>
  <main class="profile-page-container">
    <div v-if="!user" class="auth-required-notice">
      <h1>Доступ запрещен</h1>
      <p>Для доступа к этой странице необходимо авторизоваться.</p>
      <RouterLink to="/login" class="go-to-login-btn">Войти</RouterLink>
    </div>

    <div v-else class="profile-layout">
      <aside class="profile-sidebar">
        <div class="user-card">
          <div class="user-avatar">{{ user.fullname?.charAt(0).toUpperCase() || 'U' }}</div>
          <h2 class="user-name">{{ user.fullname }}</h2>
          <p class="user-email">{{ user.email }}</p>
        </div>
        <nav class="sidebar-nav">
          <a href="#" @click.prevent="activeTab = 'overview'" :class="{ active: activeTab === 'overview' }">Обзор</a>
          <a href="#" @click.prevent="activeTab = 'orders'" :class="{ active: activeTab === 'orders' }">Мои заказы</a>
          <a href="#" @click.prevent="activeTab = 'reviews'" :class="{ active: activeTab === 'reviews' }">Мои отзывы</a>
          <a href="#" @click.prevent="activeTab = 'settings'" :class="{ active: activeTab === 'settings' }">Настройки</a>
        </nav>
      </aside>

      <div class="profile-content">
        <!-- Overview -->
        <section v-show="activeTab === 'overview'" class="content-section">
          <h1 class="section-title">Обзор профиля</h1>
          <div class="stats-grid">
            <div class="stat-card">
              <p class="stat-label">Всего заказов</p>
              <p class="stat-value">{{ orders.length }}</p>
            </div>
            <div class="stat-card">
              <p class="stat-label">Написано отзывов</p>
              <p class="stat-value">{{ userReviews.length }}</p>
            </div>
             <div class="stat-card">
              <p class="stat-label">Дата регистрации</p>
              <p class="stat-value small">{{ new Date(user.created_at).toLocaleDateString('ru-RU') }}</p>
            </div>
          </div>
        </section>

        <!-- Orders -->
        <section v-show="activeTab === 'orders'" class="content-section">
          <h1 class="section-title">Мои заказы</h1>
          <div v-if="loading.orders">Загрузка...</div>
          <div v-else-if="error.orders" class="error-box">{{ error.orders }}</div>
          <div v-else-if="!orders.length" class="empty-state">У вас пока нет заказов.</div>
          <div v-else class="orders-list">
            <div v-for="order in orders" :key="order.id" class="order-item-card">
              <div class="order-header">
                <h3>Заказ #{{ order.id }}</h3>
                <p class="order-date">{{ new Date(order.created_at).toLocaleDateString('ru-RU') }}</p>
                <p class="order-total">{{ Number(order.total).toFixed(0) }} ₽</p>
              </div>
              <ul class="order-games-list">
                <li v-for="item in order.items" :key="item.id">
                  <RouterLink :to="`/game/${item.game.id}`">{{ item.game.title }}</RouterLink>
                  <span>{{ item.quantity }} шт.</span>
                </li>
              </ul>
            </div>
          </div>
        </section>

        <!-- Reviews -->
        <section v-show="activeTab === 'reviews'" class="content-section">
           <h1 class="section-title">Мои отзывы</h1>
            <div v-if="loading.reviews">Загрузка...</div>
            <div v-else-if="error.reviews" class="error-box">{{ error.reviews }}</div>
            <div v-else-if="!userReviews.length" class="empty-state">Вы еще не оставили ни одного отзыва.</div>
            <div v-else class="reviews-list">
                <div v-for="review in userReviews" :key="review.id" class="review-item-card">
                    <div class="review-game-title">
                        <RouterLink :to="`/game/${review.game.id}`">{{ review.game.title }}</RouterLink>
                    </div>
                    <div class="review-rating">Оценка: {{ '★'.repeat(review.rating) }}{{ '☆'.repeat(5 - review.rating) }}</div>
                    <p class="review-body">{{ review.body }}</p>
                    <div class="review-date">{{ new Date(review.created_at).toLocaleDateString('ru-RU') }}</div>
                </div>
            </div>
        </section>

        <!-- Settings -->
        <section v-show="activeTab === 'settings'" class="content-section">
          <h1 class="section-title">Настройки</h1>
          <div class="form-container">
            <h2>Личные данные</h2>
            <form @submit.prevent="saveProfile">
              <div class="form-group">
                <label for="fullname">Полное имя</label>
                <input id="fullname" type="text" v-model="profileForm.fullname" autocomplete="name">
              </div>
              <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" v-model="profileForm.email" autocomplete="email">
              </div>
              <button type="submit" class="submit-btn">Сохранить изменения</button>
              <p v-if="message.profile" class="success-message">{{ message.profile }}</p>
              <p v-if="error.profile" class="error-message">{{ error.profile }}</p>
            </form>
          </div>
          <div class="form-container">
            <h2>Смена пароля</h2>
            <form @submit.prevent="changePassword">
               <div class="form-group">
                <label for="current_password">Текущий пароль</label>
                <input id="current_password" type="password" v-model="passwordForm.current_password" autocomplete="current-password">
              </div>
              <div class="form-group">
                <label for="new_password">Новый пароль</label>
                <input id="new_password" type="password" v-model="passwordForm.new_password" autocomplete="new-password">
              </div>
              <div class="form-group">
                <label for="new_password_confirmation">Подтвердите пароль</label>
                <input id="new_password_confirmation" type="password" v-model="passwordForm.new_password_confirmation" autocomplete="new-password">
              </div>
              <button type="submit" class="submit-btn">Изменить пароль</button>
              <p v-if="message.password" class="success-message">{{ message.password }}</p>
              <p v-if="error.password" class="error-message">{{ error.password }}</p>
            </form>
          </div>
        </section>
      </div>
    </div>
  </main>
</template>

<style scoped>
.profile-page-container { max-width: 1200px; margin: 32px auto; padding: 0 24px; color: #e5e7eb; }

.auth-required-notice { text-align: center; padding: 40px; background: #111827; border-radius: 12px; }
.auth-required-notice h1 { font-size: 1.5rem; color: #fff; }
.auth-required-notice p { color: #9ca3af; margin: 8px 0 16px; }
.go-to-login-btn { background: #3b82f6; color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; transition: background .2s; }
.go-to-login-btn:hover { background: #2563eb; }

.profile-layout { display: grid; grid-template-columns: 260px 1fr; gap: 32px; align-items: flex-start; }

/* Sidebar */
.profile-sidebar { position: sticky; top: 92px; }
.user-card { background: #111827; border-radius: 12px; padding: 24px; text-align: center; margin-bottom: 16px; border: 1px solid #1f2937;}
.user-avatar { width: 80px; height: 80px; border-radius: 50%; background: #3b82f6; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 600; margin: 0 auto 16px; }
.user-name { font-size: 1.25rem; font-weight: 700; color: #fff; margin: 0; }
.user-email { font-size: 0.9rem; color: #9ca3af; margin-top: 4px; }

.sidebar-nav { display: flex; flex-direction: column; background: #111827; border-radius: 12px; padding: 8px; border: 1px solid #1f2937;}
.sidebar-nav a { color: #d1d5db; text-decoration: none; padding: 12px 16px; border-radius: 8px; font-weight: 500; transition: all .2s; }
.sidebar-nav a:hover { background: #1f2937; color: #fff; }
.sidebar-nav a.active { background: #3b82f6; color: #fff; }

/* Content */
.profile-content { min-width: 0; }
.content-section { background: #111827; border-radius: 12px; padding: 24px; border: 1px solid #1f2937;}
.section-title { font-size: 1.8rem; font-weight: 700; color: #fff; margin: 0 0 24px; }

/* Overview */
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
.stat-card { background: #1f2937; padding: 20px; border-radius: 10px; }
.stat-label { margin: 0 0 8px; color: #9ca3af; font-size: 0.9rem; }
.stat-value { margin: 0; font-size: 2.25rem; font-weight: 700; color: #fff; }
.stat-value.small { font-size: 1.5rem; }

/* Orders */
.empty-state, .error-box { text-align: center; color: #9ca3af; padding: 40px; }
.error-box { background: #3a1a1a; border: 1px solid #ef4444; color: #fecaca; border-radius: 10px; }
.orders-list { display: flex; flex-direction: column; gap: 16px; }
.order-item-card { background: #1f2937; border-radius: 10px; padding: 16px; transition: background .2s; }
.order-item-card:hover { background: #28344e; }
.order-header { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px; }
.order-header h3 { margin: 0; font-size: 1.1rem; color: #fff; }
.order-header p { margin: 0; color: #9ca3af; }
.order-games-list { list-style: none; padding-left: 16px; margin: 0; border-left: 2px solid #3b82f6; display: flex; flex-direction: column; gap: 8px; }
.order-games-list li { display: flex; justify-content: space-between; }
.order-games-list li a { color: #d1d5db; text-decoration: none; }
.order-games-list li a:hover { text-decoration: underline; }
.order-games-list li span { color: #9ca3af; }

/* Reviews */
.reviews-list { display: flex; flex-direction: column; gap: 16px; }
.review-item-card { background: #1f2937; padding: 16px; border-radius: 10px; }
.review-game-title a { color: #fff; font-weight: 600; text-decoration: none; font-size: 1.1rem; }
.review-game-title a:hover { color: #3b82f6; }
.review-rating { color: #facc15; margin: 8px 0; }
.review-body { color: #d1d5db; margin: 8px 0; }
.review-date { font-size: 0.8rem; color: #6b7280; text-align: right; }

/* Settings */
.form-container { background: #1f2937; border-radius: 10px; padding: 24px; margin-top: 16px; }
.form-container h2 { font-size: 1.25rem; margin: 0 0 16px; color: #fff; }
.form-group { margin-bottom: 16px; }
.form-group label { display: block; margin-bottom: 6px; color: #9ca3af; font-size: 0.9rem; }
.form-group input { width: 100%; box-sizing: border-box; background: #111827; border: 1px solid #374151; color: #e5e7eb; padding: 10px 12px; border-radius: 8px; font-size: 1rem; transition: all .2s; }
.form-group input:focus { border-color: #3b82f6; outline: none; box-shadow: 0 0 0 3px rgba(59,130,246,0.2); }
.submit-btn { background: #3b82f6; color: #fff; border: none; padding: 12px 20px; border-radius: 8px; font-size: 1rem; cursor: pointer; transition: background .2s; }
.submit-btn:hover { background: #2563eb; }
.success-message { color: #4ade80; margin-top: 10px; }
.error-message { color: #f87171; margin-top: 10px; }

@media (max-width: 768px) {
    .profile-layout { grid-template-columns: 1fr; }
    .profile-sidebar { position: static; }
}

</style>
