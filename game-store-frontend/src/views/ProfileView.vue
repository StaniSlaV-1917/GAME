<script setup>
import { ref, onMounted, computed } from 'vue';
import { useHead } from '@vueuse/head';
import { RouterLink } from 'vue-router';
import api from '../api/axios';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const user = computed(() => authStore.user);

useHead(computed(() => ({
  title: user.value ? `${user.value.fullname || 'Пользователь'} — GameStore` : 'Профиль — GameStore',
  meta: [
    { name: 'description', content: 'Личный кабинет GameStore. Заказы, отзывы, настройки аккаунта.' },
    { name: 'robots', content: 'noindex, nofollow' },
  ],
})));

const orders      = ref([]);
const userReviews = ref([]);

const profileForm  = ref({ fullname: '', email: '' });
const passwordForm = ref({ current_password: '', new_password: '', new_password_confirmation: '' });

const activeTab = ref('overview');
const loading   = ref({ orders: false, reviews: false });
const error     = ref({ orders: '', reviews: '', profile: '', password: '' });
const message   = ref({ profile: '', password: '' });

const avatarPickerOpen = ref(false);
const savingAvatar = ref(false);

const avatarList = [
  'Abomination.png','Archer.png','Archmage.png','Banshee.png','BlackDragon.png',
  'BloodMage.png','BlueDragon.png','CryptFiend.png','CryptLord.png','DarkRanger.png',
  'Destroyer.png','Druidofthe Claw.png','DruidoftheTalon.png','Dryad.png','FarSeer.png',
  'Footman.png','Ghoul.png','GrandTurtle.png','GreenDragonSmall.png','Grunt.png',
  'Huntress.png','IllidanEvil.png','Jaina.png','Kenarius.png','Knight.png',
  'LichKelThuzad.png','Maiev.png','Malfurion.png','Mediv.png','NagaMyrmidon.png',
  'NagaSeaWitch.png','NagaSiren.png','Peon.png','Priestessofthe Moon.png','RedDragon.png',
  'Rexxar.png','Rifleman.png','Roshan.png','Shaman.png','Sorceress.png',
  'SpellBreaker.png','SpiritWalker.png','Tauren.png','Thrall.png','TrollHeadhunter.png',
  'Varimatas.png','archimond.png','doomguard.png','felguard.png','golem.png','pitlord.png',
];

const selectAvatar = async (filename) => {
  if (savingAvatar.value) return;
  savingAvatar.value = true;
  try {
    await api.put('/auth/profile', { avatar: filename });
    await authStore.fetchUser();
    avatarPickerOpen.value = false;
  } catch (e) {
    console.error(e);
  } finally {
    savingAvatar.value = false;
  }
};

const tabs = [
  { id: 'overview',  label: 'Обзор',     icon: '◈' },
  { id: 'orders',    label: 'Заказы',    icon: '📦' },
  { id: 'reviews',   label: 'Отзывы',    icon: '★' },
  { id: 'settings',  label: 'Настройки', icon: '⚙' },
];

const formatDate = (ds) => {
  if (!ds) return '—';
  const d = new Date(ds);
  if (!isNaN(d.getTime())) return d.toLocaleDateString('ru-RU', { day: '2-digit', month: 'short', year: 'numeric' });
  const p = ds.split(/[- :]/);
  if (p.length >= 3) {
    const alt = new Date(+p[0], +p[1]-1, +p[2]);
    if (!isNaN(alt.getTime())) return alt.toLocaleDateString('ru-RU', { day: '2-digit', month: 'short', year: 'numeric' });
  }
  return '—';
};

const totalSpent = computed(() =>
  orders.value.reduce((s, o) => s + Number(o.total || 0), 0).toFixed(0)
);

const loadInitialData = async () => {
  if (!user.value) return;
  profileForm.value.fullname = user.value.fullname || '';
  profileForm.value.email    = user.value.email    || '';
  await Promise.all([loadOrders(), loadUserReviews()]);
};

const loadOrders = async () => {
  loading.value.orders = true;
  error.value.orders   = '';
  try { const { data } = await api.get('/orders'); orders.value = data; }
  catch { error.value.orders = 'Не удалось загрузить заказы.'; }
  finally { loading.value.orders = false; }
};

const loadUserReviews = async () => {
  loading.value.reviews = true;
  error.value.reviews   = '';
  try { const { data } = await api.get('/auth/my-reviews'); userReviews.value = data; }
  catch { error.value.reviews = 'Не удалось загрузить отзывы.'; }
  finally { loading.value.reviews = false; }
};

const saveProfile = async () => {
  error.value.profile = ''; message.value.profile = '';
  try {
    const { data } = await api.put('/auth/profile', profileForm.value);
    message.value.profile = data.message || 'Профиль обновлён!';
    await authStore.fetchUser();
    setTimeout(() => { message.value.profile = ''; }, 3500);
  } catch (e) { error.value.profile = e.response?.data?.message || 'Ошибка обновления.'; }
};

const changePassword = async () => {
  error.value.password = ''; message.value.password = '';
  try {
    const { data } = await api.put('/auth/password', passwordForm.value);
    message.value.password = data.message || 'Пароль изменён!';
    passwordForm.value = { current_password: '', new_password: '', new_password_confirmation: '' };
    setTimeout(() => { message.value.password = ''; }, 3500);
  } catch (e) { error.value.password = e.response?.data?.message || 'Ошибка смены пароля.'; }
};

onMounted(loadInitialData);
</script>

<template>
  <div class="profile-page">

    <!-- No auth -->
    <div v-if="!user" class="no-auth">
      <div class="no-auth-icon">🔒</div>
      <h2>Требуется авторизация</h2>
      <p>Войдите в аккаунт, чтобы открыть личный кабинет.</p>
      <RouterLink to="/login" class="btn-primary">Войти в аккаунт</RouterLink>
    </div>

    <template v-else>
      <!-- ===== HERO BANNER ===== -->
      <div class="profile-hero">
        <div class="hero-bg-blur b1"></div>
        <div class="hero-bg-blur b2"></div>
        <div class="hero-grid"></div>

        <div class="hero-inner">
          <!-- Avatar -->
          <div class="avatar-wrap" @click="avatarPickerOpen = true" title="Сменить аватар">
            <div class="avatar-ring"></div>
            <div class="avatar">
              <img v-if="user.avatar" :src="`/avatars/${encodeURIComponent(user.avatar)}`" :alt="user.fullname" class="avatar-img" />
              <span v-else>{{ user.fullname?.[0]?.toUpperCase() ?? '?' }}</span>
            </div>
            <div class="avatar-edit-overlay">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              <span>Сменить</span>
            </div>
          </div>

          <!-- User meta -->
          <div class="hero-meta">
            <div class="hero-name-row">
              <h1 class="hero-name">{{ user.fullname || 'Пользователь' }}</h1>
              <span class="role-badge" :class="user.is_admin ? 'admin' : 'user'">
                {{ user.is_admin ? '⚡ Администратор' : '🎮 Игрок' }}
              </span>
            </div>
            <p class="hero-email">{{ user.email }}</p>
            <p class="hero-since">На сайте с {{ formatDate(user.reg_date) }}</p>
          </div>

          <!-- Quick stats -->
          <div class="hero-stats">
            <div class="hs-item">
              <span class="hs-num">{{ orders.length }}</span>
              <span class="hs-label">Заказов</span>
            </div>
            <div class="hs-sep"></div>
            <div class="hs-item">
              <span class="hs-num">{{ userReviews.length }}</span>
              <span class="hs-label">Отзывов</span>
            </div>
            <div class="hs-sep"></div>
            <div class="hs-item">
              <span class="hs-num">{{ totalSpent }} ₽</span>
              <span class="hs-label">Потрачено</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ===== TAB BAR ===== -->
      <div class="tab-bar-wrap">
        <div class="tab-bar">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            class="tab-btn"
            :class="{ active: activeTab === tab.id }"
            @click="activeTab = tab.id"
          >
            <span class="tab-icon">{{ tab.icon }}</span>
            {{ tab.label }}
          </button>
        </div>
      </div>

      <!-- ===== CONTENT ===== -->
      <div class="profile-body">
        <Transition name="tab-fade" mode="out-in">

          <!-- OVERVIEW -->
          <div v-if="activeTab === 'overview'" key="overview" class="tab-panel">
            <div class="panel-header">
              <h2>Обзор профиля</h2>
              <p>Сводка вашего аккаунта и последняя активность</p>
            </div>

            <div class="stats-grid">
              <div class="stat-card" style="--c:#3b82f6">
                <div class="stat-icon">📦</div>
                <p class="stat-num">{{ orders.length }}</p>
                <p class="stat-label">Всего заказов</p>
                <div class="stat-glow"></div>
              </div>
              <div class="stat-card" style="--c:#22c55e">
                <div class="stat-icon">💰</div>
                <p class="stat-num">{{ totalSpent }} ₽</p>
                <p class="stat-label">Общая сумма</p>
                <div class="stat-glow"></div>
              </div>
              <div class="stat-card" style="--c:#f59e0b">
                <div class="stat-icon">★</div>
                <p class="stat-num">{{ userReviews.length }}</p>
                <p class="stat-label">Написано отзывов</p>
                <div class="stat-glow"></div>
              </div>
              <div class="stat-card" style="--c:#a855f7">
                <div class="stat-icon">📅</div>
                <p class="stat-num small">{{ formatDate(user.reg_date) }}</p>
                <p class="stat-label">Дата регистрации</p>
                <div class="stat-glow"></div>
              </div>
            </div>

            <!-- Recent orders preview -->
            <div class="recent-section" v-if="orders.length">
              <h3 class="recent-title">Последние заказы</h3>
              <div class="orders-list">
                <div v-for="order in orders.slice(0,3)" :key="order.id" class="order-card">
                  <div class="order-card-left">
                    <div class="order-id">#{{ order.id }}</div>
                    <div class="order-date">{{ formatDate(order.order_date) }}</div>
                  </div>
                  <div class="order-games-preview">
                    <span v-for="item in order.items.slice(0,2)" :key="item.id" class="game-chip">
                      {{ item.game.title }}
                    </span>
                    <span v-if="order.items.length > 2" class="game-chip more">+{{ order.items.length - 2 }}</span>
                  </div>
                  <div class="order-total">{{ Number(order.total).toFixed(0) }} ₽</div>
                </div>
              </div>
              <button class="see-all-btn" @click="activeTab = 'orders'">Все заказы →</button>
            </div>
          </div>

          <!-- ORDERS -->
          <div v-else-if="activeTab === 'orders'" key="orders" class="tab-panel">
            <div class="panel-header">
              <h2>Мои заказы</h2>
              <p>История всех ваших покупок</p>
            </div>

            <div v-if="loading.orders" class="loading-state">
              <div class="spinner"></div>
              <p>Загрузка заказов...</p>
            </div>
            <div v-else-if="error.orders" class="error-state">
              <span>⚠️</span> {{ error.orders }}
            </div>
            <div v-else-if="!orders.length" class="empty-state">
              <div class="empty-icon">📦</div>
              <h3>Нет заказов</h3>
              <p>Вы ещё ничего не покупали. Самое время начать!</p>
              <RouterLink to="/catalog" class="btn-primary">Перейти в каталог</RouterLink>
            </div>
            <div v-else class="orders-full-list">
              <div v-for="order in orders" :key="order.id" class="order-full-card">
                <div class="ofc-header">
                  <div class="ofc-id">
                    <span class="ofc-hash">#</span>{{ order.id }}
                  </div>
                  <div class="ofc-date">{{ formatDate(order.order_date) }}</div>
                  <div class="ofc-status">✅ Выполнен</div>
                  <div class="ofc-total">{{ Number(order.total).toFixed(0) }} ₽</div>
                </div>
                <div class="ofc-items">
                  <div v-for="item in order.items" :key="item.id" class="ofc-item">
                    <RouterLink :to="`/games/${item.game.id}`" class="ofc-game-name">
                      🎮 {{ item.game.title }}
                    </RouterLink>
                    <span class="ofc-qty">{{ item.quantity }} шт.</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- REVIEWS -->
          <div v-else-if="activeTab === 'reviews'" key="reviews" class="tab-panel">
            <div class="panel-header">
              <h2>Мои отзывы</h2>
              <p>Ваши оценки и комментарии к играм</p>
            </div>

            <div v-if="loading.reviews" class="loading-state">
              <div class="spinner"></div>
              <p>Загрузка отзывов...</p>
            </div>
            <div v-else-if="error.reviews" class="error-state">
              <span>⚠️</span> {{ error.reviews }}
            </div>
            <div v-else-if="!userReviews.length" class="empty-state">
              <div class="empty-icon">★</div>
              <h3>Нет отзывов</h3>
              <p>Вы ещё не оставили ни одного отзыва. Поделитесь мнением!</p>
              <RouterLink to="/catalog" class="btn-primary">Выбрать игру</RouterLink>
            </div>
            <div v-else class="reviews-grid">
              <div v-for="review in userReviews" :key="review.id" class="review-card">
                <div class="review-top">
                  <RouterLink :to="`/games/${review.game.id}`" class="review-game">
                    🎮 {{ review.game.title }}
                  </RouterLink>
                  <div class="review-date">{{ formatDate(review.created_at) }}</div>
                </div>
                <div class="stars">
                  <span
                    v-for="i in 5" :key="i"
                    class="star"
                    :class="{ filled: i <= review.rating }"
                  >★</span>
                  <span class="rating-num">{{ review.rating }}/5</span>
                </div>
                <p class="review-text">{{ review.body }}</p>
              </div>
            </div>
          </div>

          <!-- SETTINGS -->
          <div v-else-if="activeTab === 'settings'" key="settings" class="tab-panel">
            <div class="panel-header">
              <h2>Настройки</h2>
              <p>Управляйте личными данными и безопасностью</p>
            </div>

            <div class="settings-grid">
              <!-- Profile form -->
              <div class="settings-card">
                <div class="settings-card-header">
                  <div class="settings-card-icon">👤</div>
                  <div>
                    <h3>Личные данные</h3>
                    <p>Имя и адрес электронной почты</p>
                  </div>
                </div>
                <form @submit.prevent="saveProfile" class="settings-form">
                  <div class="field">
                    <label>Полное имя</label>
                    <input type="text" v-model="profileForm.fullname" autocomplete="name" placeholder="Ваше имя" />
                  </div>
                  <div class="field">
                    <label>Email</label>
                    <input type="email" v-model="profileForm.email" autocomplete="email" placeholder="email@example.com" />
                  </div>
                  <div class="form-footer">
                    <button type="submit" class="btn-primary">Сохранить изменения</button>
                    <Transition name="msg">
                      <span v-if="message.profile" class="msg-success">✓ {{ message.profile }}</span>
                      <span v-else-if="error.profile" class="msg-error">✗ {{ error.profile }}</span>
                    </Transition>
                  </div>
                </form>
              </div>

              <!-- Password form -->
              <div class="settings-card">
                <div class="settings-card-header">
                  <div class="settings-card-icon">🔐</div>
                  <div>
                    <h3>Смена пароля</h3>
                    <p>Обновите пароль для безопасности аккаунта</p>
                  </div>
                </div>
                <form @submit.prevent="changePassword" class="settings-form">
                  <div class="field">
                    <label>Текущий пароль</label>
                    <input type="password" v-model="passwordForm.current_password" autocomplete="current-password" placeholder="••••••••" />
                  </div>
                  <div class="field">
                    <label>Новый пароль</label>
                    <input type="password" v-model="passwordForm.new_password" autocomplete="new-password" placeholder="••••••••" />
                  </div>
                  <div class="field">
                    <label>Подтвердите пароль</label>
                    <input type="password" v-model="passwordForm.new_password_confirmation" autocomplete="new-password" placeholder="••••••••" />
                  </div>
                  <div class="form-footer">
                    <button type="submit" class="btn-primary">Изменить пароль</button>
                    <Transition name="msg">
                      <span v-if="message.password" class="msg-success">✓ {{ message.password }}</span>
                      <span v-else-if="error.password" class="msg-error">✗ {{ error.password }}</span>
                    </Transition>
                  </div>
                </form>
              </div>
            </div>
          </div>

        </Transition>
      </div>
    </template>

    <!-- ===== AVATAR PICKER ===== -->
    <Teleport to="body">
      <Transition name="ap-fade">
        <div v-if="avatarPickerOpen" class="ap-backdrop" @click.self="avatarPickerOpen = false">
          <div class="ap-modal">
            <div class="ap-header">
              <div class="ap-title-wrap">
                <span class="ap-title-icon">🎮</span>
                <div>
                  <h3 class="ap-title">Выберите аватар</h3>
                  <p class="ap-sub">Герои Warcraft III · {{ avatarList.length }} вариантов</p>
                </div>
              </div>
              <button class="ap-close" @click="avatarPickerOpen = false">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="ap-grid">
              <button
                v-for="av in avatarList"
                :key="av"
                class="ap-item"
                :class="{ selected: user.avatar === av, saving: savingAvatar }"
                @click="selectAvatar(av)"
                :title="av.replace('.png', '')"
              >
                <img :src="`/avatars/${encodeURIComponent(av)}`" :alt="av.replace('.png', '')" loading="lazy" />
                <div class="ap-item-check">✓</div>
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
/* ===== BASE ===== */
@keyframes gradientShift {
  0%,100% { background-position: 0% 50%; }
  50%      { background-position: 100% 50%; }
}
@keyframes avatarGlow {
  0%,100% { box-shadow: 0 0 30px rgba(99,102,241,0.4), 0 0 60px rgba(99,102,241,0.2); }
  50%      { box-shadow: 0 0 50px rgba(99,102,241,0.6), 0 0 90px rgba(99,102,241,0.3); }
}
@keyframes ringRotate {
  to { transform: rotate(360deg); }
}
@keyframes spinEl {
  to { transform: rotate(360deg); }
}
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}

.profile-page {
  min-height: 100vh;
  color: #e5e7eb;
  padding-bottom: 80px;
}

/* ===== NO AUTH ===== */
.no-auth {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  text-align: center;
  gap: 16px;
  padding: 40px 24px;
}
.no-auth-icon { font-size: 4rem; }
.no-auth h2 { font-size: 1.8rem; color: #fff; margin: 0; }
.no-auth p  { color: #6b7280; margin: 0; }

/* ===== HERO BANNER ===== */
.profile-hero {
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, #0a0f1e 0%, #0d1424 40%, #0a0e1c 100%);
  border-bottom: 1px solid rgba(255,255,255,0.06);
  padding: 56px 24px 48px;
}

.hero-bg-blur {
  position: absolute;
  border-radius: 50%;
  filter: blur(70px);
  pointer-events: none;
}
.hero-bg-blur.b1 { width: 500px; height: 400px; background: rgba(99,102,241,0.1); top: -120px; left: -100px; }
.hero-bg-blur.b2 { width: 400px; height: 300px; background: rgba(59,130,246,0.07); bottom: -80px; right: -60px; }

.hero-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
  background-size: 50px 50px;
  mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 40%, transparent 100%);
  pointer-events: none;
}

.hero-inner {
  position: relative;
  z-index: 2;
  max-width: 1100px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  gap: 36px;
  flex-wrap: wrap;
  animation: fadeInUp 0.5s ease both;
}

/* Avatar */
.avatar-wrap {
  position: relative;
  flex-shrink: 0;
  width: 100px;
  height: 100px;
  cursor: pointer;
}
.avatar-ring {
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  border: 2px solid transparent;
  background: linear-gradient(135deg, #3b82f6, #6366f1, #8b5cf6, #3b82f6) border-box;
  -webkit-mask: linear-gradient(#fff 0 0) padding-box, linear-gradient(#fff 0 0);
  -webkit-mask-composite: destination-out;
  mask-composite: exclude;
  animation: ringRotate 4s linear infinite;
}
.avatar {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: linear-gradient(135deg, #1e3a8a, #3b82f6, #6366f1);
  color: #fff;
  font-size: 2.6rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: avatarGlow 3s ease-in-out infinite;
  box-shadow: 0 0 30px rgba(99,102,241,0.4);
  overflow: hidden;
}
.avatar-img {
  width: 100%; height: 100%;
  object-fit: cover;
  border-radius: 50%;
  display: block;
}
.avatar-edit-overlay {
  position: absolute; inset: 0;
  border-radius: 50%;
  background: rgba(0,0,0,0.6);
  backdrop-filter: blur(2px);
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  gap: 4px;
  color: #fff; font-size: 0.7rem; font-weight: 700;
  opacity: 0;
  transition: opacity 0.22s;
  pointer-events: none;
}
.avatar-wrap:hover .avatar-edit-overlay { opacity: 1; }

/* Hero meta */
.hero-meta { flex: 1; min-width: 200px; }
.hero-name-row { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 6px; }
.hero-name { font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 800; color: #fff; margin: 0; letter-spacing: -0.5px; }

.role-badge {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 4px 12px;
  border-radius: 999px;
  letter-spacing: 0.5px;
}
.role-badge.user  { background: rgba(59,130,246,0.15); color: #93c5fd; border: 1px solid rgba(59,130,246,0.25); }
.role-badge.admin { background: rgba(192,38,211,0.15); color: #e879f9; border: 1px solid rgba(192,38,211,0.3); }

.hero-email { font-size: 0.9rem; color: #6b7280; margin: 0 0 4px; }
.hero-since { font-size: 0.82rem; color: #374151; margin: 0; }

/* Hero stats */
.hero-stats {
  display: flex;
  align-items: center;
  gap: 24px;
  padding: 18px 28px;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 14px;
  backdrop-filter: blur(12px);
  flex-shrink: 0;
}
.hs-item { display: flex; flex-direction: column; align-items: center; gap: 2px; }
.hs-num   { font-size: 1.4rem; font-weight: 800; color: #fff; line-height: 1; }
.hs-label { font-size: 0.7rem; color: #4b5563; text-transform: uppercase; letter-spacing: 1px; }
.hs-sep   { width: 1px; height: 32px; background: rgba(255,255,255,0.08); }

/* ===== TAB BAR ===== */
.tab-bar-wrap {
  position: sticky;
  top: 68px;
  z-index: 50;
  background: rgba(2,6,23,0.8);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(255,255,255,0.07);
}
.tab-bar {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 24px;
  display: flex;
  gap: 4px;
}
.tab-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 16px 20px;
  font-size: 0.9rem;
  font-weight: 600;
  color: #6b7280;
  background: none;
  border: none;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  transition: all 0.2s;
  white-space: nowrap;
}
.tab-btn:hover { color: #d1d5db; }
.tab-btn.active {
  color: #fff;
  border-bottom-color: #3b82f6;
}
.tab-icon { font-size: 1rem; }

/* ===== BODY ===== */
.profile-body {
  max-width: 1100px;
  margin: 0 auto;
  padding: 40px 24px 0;
}

/* Tab transition */
.tab-fade-enter-active, .tab-fade-leave-active { transition: all 0.22s ease; }
.tab-fade-enter-from { opacity: 0; transform: translateY(12px); }
.tab-fade-leave-to   { opacity: 0; transform: translateY(-8px); }

/* ===== PANEL HEADER ===== */
.panel-header { margin-bottom: 32px; }
.panel-header h2 { font-size: 1.6rem; font-weight: 800; color: #fff; margin: 0 0 6px; }
.panel-header p  { font-size: 0.9rem; color: #4b5563; margin: 0; }

/* ===== STAT CARDS ===== */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 16px;
  margin-bottom: 40px;
}
.stat-card {
  position: relative;
  padding: 28px 24px;
  border-radius: 16px;
  background: rgba(17,24,39,0.8);
  border: 1px solid rgba(255,255,255,0.07);
  backdrop-filter: blur(12px);
  overflow: hidden;
  transition: transform 0.25s, border-color 0.25s;
  animation: fadeInUp 0.4s ease both;
}
.stat-card:hover { transform: translateY(-4px); border-color: color-mix(in srgb, var(--c) 30%, transparent); }
.stat-card:hover .stat-glow { opacity: 1; }
.stat-icon { font-size: 1.4rem; margin-bottom: 12px; }
.stat-num  { font-size: 2rem; font-weight: 900; color: var(--c, #fff); margin: 0 0 4px; line-height: 1; }
.stat-num.small { font-size: 1.3rem; }
.stat-label { font-size: 0.8rem; color: #4b5563; text-transform: uppercase; letter-spacing: 1px; margin: 0; }
.stat-glow {
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse 60% 50% at 50% 0%, color-mix(in srgb, var(--c) 12%, transparent), transparent);
  opacity: 0;
  transition: opacity 0.3s;
  pointer-events: none;
}

/* ===== RECENT ORDERS ===== */
.recent-section { }
.recent-title { font-size: 1.1rem; font-weight: 700; color: #e5e7eb; margin: 0 0 16px; }
.orders-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; }
.order-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
  border-radius: 12px;
  background: rgba(17,24,39,0.7);
  border: 1px solid rgba(255,255,255,0.06);
  transition: border-color 0.2s, background 0.2s;
  flex-wrap: wrap;
}
.order-card:hover { border-color: rgba(59,130,246,0.25); background: rgba(17,24,39,0.9); }
.order-card-left { display: flex; flex-direction: column; gap: 3px; min-width: 60px; }
.order-id { font-size: 1rem; font-weight: 700; color: #fff; }
.order-date { font-size: 0.75rem; color: #4b5563; }
.order-games-preview { display: flex; gap: 8px; flex-wrap: wrap; flex: 1; }
.game-chip {
  font-size: 0.78rem;
  padding: 4px 10px;
  border-radius: 999px;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.08);
  color: #9ca3af;
  white-space: nowrap;
}
.game-chip.more { color: #4b5563; }
.order-total { font-size: 1rem; font-weight: 700; color: #4ade80; margin-left: auto; white-space: nowrap; }

.see-all-btn {
  background: none;
  border: none;
  color: #3b82f6;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
  transition: color 0.2s;
}
.see-all-btn:hover { color: #60a5fa; }

/* ===== FULL ORDERS LIST ===== */
.orders-full-list { display: flex; flex-direction: column; gap: 14px; }
.order-full-card {
  border-radius: 14px;
  background: rgba(17,24,39,0.7);
  border: 1px solid rgba(255,255,255,0.07);
  overflow: hidden;
  transition: border-color 0.2s;
}
.order-full-card:hover { border-color: rgba(59,130,246,0.25); }
.ofc-header {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
  background: rgba(255,255,255,0.02);
  border-bottom: 1px solid rgba(255,255,255,0.05);
  flex-wrap: wrap;
}
.ofc-id { font-size: 1.05rem; font-weight: 800; color: #fff; }
.ofc-hash { color: #374151; margin-right: 1px; }
.ofc-date { font-size: 0.82rem; color: #4b5563; flex: 1; }
.ofc-status { font-size: 0.8rem; color: #4ade80; }
.ofc-total { font-size: 1.05rem; font-weight: 700; color: #4ade80; margin-left: auto; }
.ofc-items { padding: 12px 20px; display: flex; flex-direction: column; gap: 8px; }
.ofc-item { display: flex; justify-content: space-between; align-items: center; }
.ofc-game-name {
  font-size: 0.9rem;
  color: #d1d5db;
  text-decoration: none;
  transition: color 0.2s;
}
.ofc-game-name:hover { color: #60a5fa; }
.ofc-qty { font-size: 0.8rem; color: #4b5563; }

/* ===== REVIEWS ===== */
.reviews-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
.review-card {
  padding: 22px;
  border-radius: 14px;
  background: rgba(17,24,39,0.7);
  border: 1px solid rgba(255,255,255,0.07);
  display: flex;
  flex-direction: column;
  gap: 12px;
  transition: border-color 0.2s, transform 0.2s;
  animation: fadeInUp 0.4s ease both;
}
.review-card:hover { border-color: rgba(250,204,21,0.25); transform: translateY(-3px); }
.review-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; }
.review-game { font-size: 0.95rem; font-weight: 700; color: #fff; text-decoration: none; transition: color 0.2s; }
.review-game:hover { color: #60a5fa; }
.review-date { font-size: 0.75rem; color: #374151; white-space: nowrap; }
.stars { display: flex; align-items: center; gap: 3px; }
.star { font-size: 1.1rem; color: #374151; transition: color 0.15s; }
.star.filled { color: #facc15; }
.rating-num { font-size: 0.78rem; color: #6b7280; margin-left: 6px; }
.review-text { font-size: 0.88rem; color: #9ca3af; line-height: 1.65; margin: 0; flex: 1; }

/* ===== SETTINGS ===== */
.settings-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 20px; }
.settings-card {
  padding: 28px;
  border-radius: 16px;
  background: rgba(17,24,39,0.8);
  border: 1px solid rgba(255,255,255,0.08);
  backdrop-filter: blur(12px);
  animation: fadeInUp 0.4s ease both;
}
.settings-card-header {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  margin-bottom: 24px;
  padding-bottom: 20px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}
.settings-card-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(59,130,246,0.12);
  border: 1px solid rgba(59,130,246,0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  flex-shrink: 0;
}
.settings-card-header h3 { font-size: 1rem; font-weight: 700; color: #fff; margin: 0 0 4px; }
.settings-card-header p  { font-size: 0.82rem; color: #4b5563; margin: 0; }

.settings-form { display: flex; flex-direction: column; gap: 16px; }

.field { display: flex; flex-direction: column; gap: 6px; }
.field label { font-size: 0.82rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.8px; }
.field input {
  width: 100%;
  box-sizing: border-box;
  padding: 11px 14px;
  border-radius: 10px;
  background: rgba(2,6,23,0.6);
  border: 1px solid rgba(255,255,255,0.1);
  color: #e5e7eb;
  font-size: 0.95rem;
  transition: border-color 0.2s, box-shadow 0.2s;
  outline: none;
}
.field input::placeholder { color: #374151; }
.field input:focus {
  border-color: rgba(59,130,246,0.5);
  box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
}

.form-footer {
  display: flex;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
  margin-top: 4px;
}

/* ===== SHARED COMPONENTS ===== */
.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 11px 24px;
  border-radius: 10px;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: #fff;
  font-size: 0.9rem;
  font-weight: 700;
  text-decoration: none;
  border: none;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(99,102,241,0.35);
  transition: all 0.2s;
  white-space: nowrap;
}
.btn-primary:hover { filter: brightness(1.1); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(99,102,241,0.45); }

.msg-success { font-size: 0.85rem; color: #4ade80; font-weight: 500; }
.msg-error   { font-size: 0.85rem; color: #f87171; font-weight: 500; }
.msg-enter-active, .msg-leave-active { transition: all 0.25s ease; }
.msg-enter-from, .msg-leave-to { opacity: 0; transform: translateX(-8px); }

/* States */
.loading-state {
  text-align: center;
  padding: 60px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  color: #4b5563;
}
.spinner {
  width: 36px;
  height: 36px;
  border: 3px solid rgba(255,255,255,0.08);
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spinEl 0.8s linear infinite;
}
.error-state {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 16px 20px;
  border-radius: 10px;
  background: rgba(239,68,68,0.08);
  border: 1px solid rgba(239,68,68,0.2);
  color: #fca5a5;
  font-size: 0.9rem;
}
.empty-state {
  text-align: center;
  padding: 60px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}
.empty-icon { font-size: 3rem; }
.empty-state h3 { font-size: 1.2rem; color: #fff; margin: 0; }
.empty-state p  { color: #4b5563; margin: 0; font-size: 0.9rem; }

/* ===== AVATAR PICKER ===== */
.ap-fade-enter-active, .ap-fade-leave-active { transition: opacity 0.2s ease; }
.ap-fade-enter-from, .ap-fade-leave-to { opacity: 0; }

.ap-backdrop {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.8);
  backdrop-filter: blur(8px);
  z-index: 9999;
  display: flex; align-items: center; justify-content: center;
  padding: 20px;
}
.ap-modal {
  background: rgba(10,15,30,0.98);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 20px;
  width: 100%; max-width: 680px;
  max-height: 90vh;
  display: flex; flex-direction: column;
  box-shadow: 0 40px 100px rgba(0,0,0,0.7);
  animation: slideUp 0.22s ease;
}
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: none; } }

.ap-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid rgba(255,255,255,0.07);
  flex-shrink: 0;
}
.ap-title-wrap { display: flex; align-items: center; gap: 12px; }
.ap-title-icon { font-size: 1.5rem; }
.ap-title { font-size: 1.1rem; font-weight: 800; color: #fff; margin: 0 0 2px; }
.ap-sub { font-size: 0.78rem; color: #4b5563; margin: 0; }
.ap-close {
  width: 34px; height: 34px; border-radius: 8px; border: none;
  background: rgba(255,255,255,0.05); color: #6b7280;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.2s; flex-shrink: 0;
}
.ap-close:hover { background: rgba(255,255,255,0.1); color: #e5e7eb; }

.ap-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(88px, 1fr));
  gap: 12px;
  padding: 20px 24px 24px;
  overflow-y: auto;
  scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.1) transparent;
}
.ap-grid::-webkit-scrollbar { width: 4px; }
.ap-grid::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }

.ap-item {
  position: relative;
  height: 88px;
  border-radius: 12px;
  border: 2px solid rgba(255,255,255,0.07);
  background: rgba(255,255,255,0.03);
  overflow: hidden;
  cursor: pointer;
  padding: 6px;
  box-sizing: border-box;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: border-color 0.18s, transform 0.18s, box-shadow 0.18s;
}
.ap-item:hover {
  border-color: rgba(99,102,241,0.5);
  transform: scale(1.06);
  box-shadow: 0 8px 20px rgba(0,0,0,0.4);
}
.ap-item.selected {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99,102,241,0.3);
}
.ap-item.saving { pointer-events: none; opacity: 0.7; }
.ap-item img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
  border-radius: 6px;
  transition: transform 0.18s;
}
.ap-item:hover img { transform: scale(1.1); }
.ap-item-check {
  position: absolute; inset: 0;
  background: rgba(99,102,241,0.75);
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: 1.5rem; font-weight: 800;
  opacity: 0; transition: opacity 0.15s;
  border-radius: 10px;
}
.ap-item.selected .ap-item-check { opacity: 1; }

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
  .hero-inner { flex-direction: column; text-align: center; align-items: center; }
  .hero-name-row { justify-content: center; }
  .hero-stats { width: 100%; justify-content: center; }
  .tab-bar { overflow-x: auto; scrollbar-width: none; }
  .tab-bar::-webkit-scrollbar { display: none; }
  .tab-btn { padding: 14px 14px; font-size: 0.82rem; }
  .stats-grid { grid-template-columns: 1fr 1fr; }
  .reviews-grid { grid-template-columns: 1fr; }
  .settings-grid { grid-template-columns: 1fr; }
}

@media (max-width: 480px) {
  .stats-grid { grid-template-columns: 1fr; }
  .ofc-header { gap: 10px; }
  .hs-sep { display: none; }
}
</style>
