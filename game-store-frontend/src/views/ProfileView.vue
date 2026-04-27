<script setup>
import { ref, onMounted, computed } from 'vue';
import { useHead } from '@vueuse/head';
import { RouterLink } from 'vue-router';
import api from '../api/axios';
import { useAuthStore } from '../stores/auth';
import { useToast } from '../composables/useToast';
import { warmupPing } from '../utils/warmup';

const authStore = useAuthStore();
const toast = useToast();
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

const profileForm  = ref({ fullname: '', phone: '' });
const notifyForm   = ref({ notify_login: true, notify_order_created: true, notify_order_status: true });
const savingNotify = ref(false);
const passwordForm = ref({ current_password: '', new_password: '', new_password_confirmation: '' });
const emailChangeForm = ref({ newEmail: '', code: '' });
const emailChangeStep = ref('email'); // 'email' | 'code'
const emailChangeSending = ref(false);

const activeTab = ref('overview');
const loading   = ref({ orders: false, reviews: false });
const error     = ref({ orders: '', reviews: '', profile: '', password: '', emailChange: '' });
const message   = ref({ profile: '', password: '', emailChange: '' });

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
    toast.success('Аватар обновлён!');
  } catch (e) {
    toast.error('Не удалось сохранить аватар. Попробуйте снова.');
    console.error(e);
  } finally {
    savingAvatar.value = false;
  }
};

const tabs = [
  { id: 'overview',  label: 'Обзор',     icon: '◈' },
  { id: 'orders',    label: 'Заказы',    icon: '' },
  { id: 'reviews',   label: 'Отзывы',    icon: '★' },
  { id: 'settings',  label: 'Настройки', icon: '⚙' },
];

const ORDER_STATUS_MAP = {
  created:   { icon: '', label: 'Создан' },
  paid:      { icon: '', label: 'Оплачен' },
  shipped:   { icon: '', label: 'Отправлен' },
  completed: { icon: '', label: 'Выполнен' },
  cancelled: { icon: '', label: 'Отменён' },
};
const orderStatusLabel = (status) => {
  const s = ORDER_STATUS_MAP[status];
  return s ? `${s.icon} ${s.label}` : status;
};

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
  profileForm.value.fullname              = user.value.fullname || '';
  profileForm.value.phone                 = user.value.phone    || '';
  notifyForm.value.notify_login           = user.value.notify_login           ?? true;
  notifyForm.value.notify_order_created   = user.value.notify_order_created   ?? true;
  notifyForm.value.notify_order_status    = user.value.notify_order_status    ?? true;
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
    const msg = data.message || 'Профиль обновлён!';
    message.value.profile = msg;
    toast.success(msg);
    if (data.user) authStore.setUser(data.user);
    else await authStore.fetchUser();
    setTimeout(() => { message.value.profile = ''; }, 3500);
  } catch (e) {
    const d = e.response?.data;
    const msg = (d?.errors ? Object.values(d.errors)[0]?.[0] : null) || d?.message || 'Ошибка обновления профиля.';
    error.value.profile = msg;
    toast.error(msg);
  }
};

const saveNotifications = async () => {
  if (savingNotify.value) return;
  savingNotify.value = true;
  try {
    const { data } = await api.put('/auth/profile', notifyForm.value);
    if (data.user) authStore.setUser(data.user);
    else await authStore.fetchUser();
    // обновляем локальную форму из ответа
    notifyForm.value.notify_login         = data.user?.notify_login         ?? notifyForm.value.notify_login;
    notifyForm.value.notify_order_created = data.user?.notify_order_created ?? notifyForm.value.notify_order_created;
    notifyForm.value.notify_order_status  = data.user?.notify_order_status  ?? notifyForm.value.notify_order_status;
    toast.success('Настройки уведомлений сохранены!');
  } catch (e) {
    toast.error(e.response?.data?.message || 'Ошибка сохранения настроек.');
  } finally {
    savingNotify.value = false;
  }
};

const sendEmailChangeCode = async () => {
  error.value.emailChange = ''; message.value.emailChange = '';
  const newEmail = emailChangeForm.value.newEmail.trim();
  if (!newEmail) { toast.warning('Введите новый email.'); return; }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(newEmail)) { toast.warning('Некорректный формат email.'); return; }
  emailChangeSending.value = true;
  try {
    await authStore.requestEmailChange(newEmail);
    emailChangeStep.value = 'code';
    toast.info('Код подтверждения отправлен на ' + newEmail);
  } catch (e) {
    const msg = e.message || 'Ошибка отправки кода.';
    error.value.emailChange = msg;
    toast.error(msg);
  } finally {
    emailChangeSending.value = false;
  }
};

const confirmEmailCode = async () => {
  error.value.emailChange = ''; message.value.emailChange = '';
  const code = emailChangeForm.value.code.trim();
  if (!code) { toast.warning('Введите код из письма.'); return; }
  emailChangeSending.value = true;
  try {
    await authStore.confirmEmailChange(code);
    message.value.emailChange = 'Email успешно изменён!';
    toast.success('Email успешно изменён!');
    emailChangeStep.value = 'email';
    emailChangeForm.value = { newEmail: '', code: '' };
    setTimeout(() => { message.value.emailChange = ''; }, 3500);
  } catch (e) {
    const msg = e.message || 'Неверный код. Попробуйте снова.';
    error.value.emailChange = msg;
    toast.error(msg);
  } finally {
    emailChangeSending.value = false;
  }
};

const changePassword = async () => {
  error.value.password = ''; message.value.password = '';
  try {
    const { data } = await api.post('/auth/password', passwordForm.value);
    const msg = data.message || 'Пароль изменён!';
    message.value.password = msg;
    toast.success(msg);
    passwordForm.value = { current_password: '', new_password: '', new_password_confirmation: '' };
    setTimeout(() => { message.value.password = ''; }, 3500);
  } catch (e) {
    const msg = e.response?.data?.message || 'Ошибка смены пароля.';
    error.value.password = msg;
    toast.error(msg);
  }
};

onMounted(() => {
  warmupPing(); // edit-формы и аватар-аплоад делают PUT, бэк должен быть тёплым
  loadInitialData();
});
</script>

<template>
  <div class="profile-page">

    <!-- No auth -->
    <div v-if="!user" class="no-auth">
      <div class="no-auth-sigil" aria-hidden="true">
        <svg viewBox="-32 -32 64 64" width="96" height="96">
          <circle r="24" class="na-ring" />
          <g class="na-teeth">
            <line v-for="i in 12" :key="i"
                  x1="0" y1="-26" x2="0" y2="-22"
                  :transform="`rotate(${(i - 1) * 30})`" />
          </g>
          <polygon class="na-gear"
            points="0,-12 3.5,-6 10,-6 7,0 10,6 3.5,6 0,12 -3.5,6 -10,6 -7,0 -10,-6 -3.5,-6" />
          <circle r="4" class="na-core" />
        </svg>
      </div>
      <span class="tribal-eyebrow">
        <span class="eb-spike"></span>
        Врата закрыты
        <span class="eb-spike"></span>
      </span>
      <h2>Требуется войти в оплот</h2>
      <p>Откройте врата, чтобы увидеть свой свиток.</p>
      <RouterLink to="/login" class="btn-primary">Войти в оплот</RouterLink>
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
                {{ user.is_admin ? 'Старейшина' : 'Воин' }}
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
                <div class="stat-icon"></div>
                <p class="stat-num">{{ orders.length }}</p>
                <p class="stat-label">Всего заказов</p>
                <div class="stat-glow"></div>
              </div>
              <div class="stat-card" style="--c:#22c55e">
                <div class="stat-icon"></div>
                <p class="stat-num">{{ totalSpent }} ₽</p>
                <p class="stat-label">Общая сумма</p>
                <div class="stat-glow"></div>
              </div>
              <div class="stat-card" style="--c:#f59e0b">
                <div class="stat-icon"></div>
                <p class="stat-num">{{ userReviews.length }}</p>
                <p class="stat-label">Написано отзывов</p>
                <div class="stat-glow"></div>
              </div>
              <div class="stat-card" style="--c:#a855f7">
                <div class="stat-icon"></div>
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
              <div class="empty-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.25"><path d="M16.5 9.4l-9-5.19M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></div>
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
                  <div class="ofc-status" :class="`status-${order.status}`">{{ orderStatusLabel(order.status) }}</div>
                  <div class="ofc-total">{{ Number(order.total).toFixed(0) }} ₽</div>
                </div>
                <div class="ofc-items">
                  <div v-for="item in order.items" :key="item.id" class="ofc-item">
                    <RouterLink :to="`/games/${item.game.id}`" class="ofc-game-name">
                       {{ item.game.title }}
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
                     {{ review.game.title }}
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

              <!-- Личные данные: имя + телефон -->
              <div class="settings-card">
                <div class="settings-card-header">
                  <div class="settings-card-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
                  <div>
                    <h3>Личные данные</h3>
                    <p>Имя и номер телефона</p>
                  </div>
                </div>
                <form @submit.prevent="saveProfile" class="settings-form">
                  <div class="field">
                    <label>Полное имя</label>
                    <input type="text" v-model="profileForm.fullname" autocomplete="name" placeholder="Ваше имя" />
                  </div>
                  <div class="field">
                    <label>Телефон <span class="field-hint">7XXXXXXXXXX (11 цифр)</span></label>
                    <input type="tel" v-model="profileForm.phone" autocomplete="tel" placeholder="79001234567" />
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

              <!-- Смена email с подтверждением -->
              <div class="settings-card">
                <div class="settings-card-header">
                  <div class="settings-card-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div>
                  <div>
                    <h3>Смена email</h3>
                    <p>Текущий: <strong style="color:#60a5fa">{{ user.email }}</strong></p>
                  </div>
                </div>
                <div class="settings-form">
                  <Transition name="msg">
                    <div v-if="message.emailChange" class="msg-banner success">✓ {{ message.emailChange }}</div>
                    <div v-else-if="error.emailChange" class="msg-banner error">✗ {{ error.emailChange }}</div>
                  </Transition>

                  <!-- Шаг 1: ввод нового email -->
                  <template v-if="emailChangeStep === 'email'">
                    <div class="field">
                      <label>Новый email</label>
                      <input type="email" v-model="emailChangeForm.newEmail" autocomplete="email" placeholder="new@example.com" />
                    </div>
                    <div class="form-footer">
                      <button type="button" class="btn-primary" :disabled="emailChangeSending" @click="sendEmailChangeCode">
                        <span v-if="emailChangeSending" class="btn-spin"></span>
                        {{ emailChangeSending ? 'Отправка...' : 'Получить код' }}
                      </button>
                    </div>
                  </template>

                  <!-- Шаг 2: код подтверждения -->
                  <template v-else>
                    <p class="email-change-hint">Код отправлен на <strong style="color:#60a5fa">{{ emailChangeForm.newEmail }}</strong></p>
                    <div class="field">
                      <label>Код из письма</label>
                      <input type="text" v-model="emailChangeForm.code" inputmode="numeric" autocomplete="one-time-code" placeholder="••••••" maxlength="6" />
                    </div>
                    <div class="form-footer">
                      <button type="button" class="btn-primary" :disabled="emailChangeSending" @click="confirmEmailCode">
                        <span v-if="emailChangeSending" class="btn-spin"></span>
                        {{ emailChangeSending ? 'Проверка...' : 'Подтвердить' }}
                      </button>
                      <button type="button" class="btn-ghost" @click="emailChangeStep = 'email'; error.emailChange = ''">← Назад</button>
                    </div>
                  </template>
                </div>
              </div>

              <!-- Уведомления на email -->
              <div class="settings-card notify-card">
                <div class="settings-card-header">
                  <div class="settings-card-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></div>
                  <div>
                    <h3>Email-уведомления</h3>
                    <p>Выбери, о чём тебе писать на {{ user.email }}</p>
                  </div>
                </div>
                <div class="settings-form">
                  <label class="notify-toggle">
                    <input type="checkbox" v-model="notifyForm.notify_login" @change="saveNotifications" />
                    <span class="toggle-track"><span class="toggle-thumb"></span></span>
                    <span class="toggle-body">
                      <span class="toggle-title">Новый вход в аккаунт</span>
                      <span class="toggle-desc">IP-адрес, браузер и ОС при каждом входе</span>
                    </span>
                  </label>
                  <label class="notify-toggle">
                    <input type="checkbox" v-model="notifyForm.notify_order_created" @change="saveNotifications" />
                    <span class="toggle-track"><span class="toggle-thumb"></span></span>
                    <span class="toggle-body">
                      <span class="toggle-title">Заказ оформлен</span>
                      <span class="toggle-desc">Подтверждение при каждой покупке</span>
                    </span>
                  </label>
                  <label class="notify-toggle">
                    <input type="checkbox" v-model="notifyForm.notify_order_status" @change="saveNotifications" />
                    <span class="toggle-track"><span class="toggle-thumb"></span></span>
                    <span class="toggle-body">
                      <span class="toggle-title">Изменение статуса заказа</span>
                      <span class="toggle-desc">Оплачен, отправлен, выполнен, отменён</span>
                    </span>
                  </label>
                  <div v-if="savingNotify" class="notify-saving">
                    <span class="btn-spin"></span> Сохранение...
                  </div>
                </div>
              </div>

              <!-- Смена пароля -->
              <div class="settings-card">
                <div class="settings-card-header">
                  <div class="settings-card-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
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
                <span class="ap-title-icon"></span>
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
/* ==========================================================
   PROFILE · Ashenforge
   ========================================================== */
@keyframes spinEl   { to { transform: rotate(360deg); } }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: none; } }
@keyframes naSpin    { to { transform: rotate(360deg); } }
@keyframes naSpinRev { to { transform: rotate(-360deg); } }
@keyframes naPulse {
  0%, 100% { transform: scale(1); opacity: 1; }
  50%      { transform: scale(1.25); opacity: 0.85; }
}
@keyframes avatarSigilSpin { to { transform: rotate(360deg); } }

.profile-page {
  min-height: 100vh;
  color: var(--text-bone);
  padding-bottom: 80px;
}

/* ── tribal-eyebrow (общая) ── */
.tribal-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-family: var(--font-ui);
  font-size: 0.74rem;
  font-weight: 700;
  letter-spacing: 2.8px;
  text-transform: uppercase;
  color: var(--bronze);
}
.eb-spike {
  width: 0; height: 0;
  border-left: 4px solid transparent;
  border-right: 4px solid transparent;
  border-top: 6px solid var(--bronze);
  filter: drop-shadow(0 0 3px rgba(199, 154, 94, 0.5));
}

/* ===== NO AUTH ===== */
.no-auth {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  text-align: center;
  gap: 14px;
  padding: 40px 24px;
}
.no-auth-sigil {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 96px;
  height: 96px;
  margin-bottom: 6px;
}
.no-auth-sigil svg { overflow: visible; }
.no-auth-sigil g {
  transform-origin: 0 0;
  transform-box: view-box;
}
.na-ring {
  fill: none;
  stroke: var(--bronze);
  stroke-width: 1.8;
  opacity: 0.65;
  filter: drop-shadow(0 0 3px rgba(199, 154, 94, 0.5));
}
.na-teeth { animation: naSpin 22s linear infinite; }
.na-teeth line {
  stroke: var(--brass);
  stroke-width: 2.2;
  stroke-linecap: square;
  opacity: 0.75;
}
.na-gear {
  fill: none;
  stroke: var(--ember-flame);
  stroke-width: 1.6;
  animation: naSpinRev 10s linear infinite;
  transform-origin: 0 0;
  transform-box: view-box;
  filter: drop-shadow(0 0 5px rgba(255, 122, 43, 0.6));
}
.na-core {
  fill: var(--ember-gold);
  animation: naPulse 1.8s ease-in-out infinite;
  filter: drop-shadow(0 0 8px rgba(255, 201, 121, 0.8));
}

.no-auth h2 {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: clamp(1.4rem, 3vw, 1.9rem);
  color: var(--text-bright);
  margin: 0;
  letter-spacing: 0.3px;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.55);
}
.no-auth p {
  font-family: var(--font-body);
  color: var(--text-parchment);
  margin: 0;
  font-size: 1rem;
}

/* ===== HERO BANNER — кованый свиток ===== */
.profile-hero {
  position: relative;
  overflow: hidden;
  background: linear-gradient(180deg,
    var(--ash-obsidian) 0%,
    var(--ash-coal) 45%,
    var(--ash-obsidian) 100%);
  border-bottom: 1px solid var(--iron-dark);
  padding: 64px 24px 56px;
  box-shadow: var(--inset-forge);
}

.hero-bg-blur {
  position: absolute;
  border-radius: 50%;
  filter: blur(110px);
  pointer-events: none;
}
.hero-bg-blur.b1 {
  width: 520px; height: 420px;
  background: radial-gradient(circle, var(--ember-glow) 0%, transparent 70%);
  top: -140px; left: -120px;
  opacity: 0.28;
}
.hero-bg-blur.b2 {
  width: 420px; height: 320px;
  background: radial-gradient(circle, var(--ember-heart) 0%, transparent 70%);
  bottom: -90px; right: -60px;
  opacity: 0.22;
}

.hero-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(122, 93, 72, 0.06) 1px, transparent 1px),
    linear-gradient(90deg, rgba(122, 93, 72, 0.06) 1px, transparent 1px);
  background-size: 54px 54px;
  mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 40%, transparent 100%);
  -webkit-mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 40%, transparent 100%);
  pointer-events: none;
}

.hero-inner {
  position: relative;
  z-index: 2;
  max-width: 1140px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  gap: 36px;
  flex-wrap: wrap;
  animation: fadeInUp 0.5s var(--ease-smoke) both;
}

/* ── Аватар как кованая сигила ── */
.avatar-wrap {
  position: relative;
  flex-shrink: 0;
  width: 112px;
  height: 112px;
  cursor: pointer;
}
/* Шестиугольная бронзовая рамка (медленное вращение) */
.avatar-ring {
  position: absolute;
  inset: -6px;
  clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
  background: conic-gradient(
    from 0deg,
    var(--bronze) 0deg,
    var(--brass) 60deg,
    var(--bronze-dark) 120deg,
    var(--bronze) 180deg,
    var(--brass) 240deg,
    var(--bronze-dark) 300deg,
    var(--bronze) 360deg);
  animation: avatarSigilSpin 18s linear infinite;
  filter: drop-shadow(0 0 10px rgba(226, 67, 16, 0.35));
}
.avatar-ring::after {
  content: '';
  position: absolute;
  inset: 3px;
  clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
  background: var(--ash-obsidian);
}

.avatar {
  position: relative;
  z-index: 2;
  width: 100%;
  height: 100%;
  clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
  background: linear-gradient(180deg,
    var(--ash-ironrust) 0%,
    var(--ash-stone) 50%,
    var(--ash-coal) 100%);
  color: var(--text-bright);
  font-family: var(--font-display);
  font-size: 2.8rem;
  font-weight: var(--fw-black, 900);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.7);
  box-shadow: var(--inset-forge), 0 0 24px rgba(226, 67, 16, 0.3);
}
.avatar-img {
  width: 100%; height: 100%;
  object-fit: cover;
  display: block;
  clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
}

.avatar-edit-overlay {
  position: absolute;
  inset: 0;
  z-index: 3;
  clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
  background: rgba(8, 6, 10, 0.75);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  color: var(--ember-gold);
  font-family: var(--font-ui);
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  opacity: 0;
  transition: opacity 0.22s var(--ease-smoke);
  pointer-events: none;
}
.avatar-wrap:hover .avatar-edit-overlay { opacity: 1; }

/* ── Hero meta ── */
.hero-meta { flex: 1; min-width: 220px; }
.hero-name-row {
  display: flex;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
  margin-bottom: 8px;
}
.hero-name {
  font-family: var(--font-display);
  font-size: clamp(1.7rem, 3.2vw, 2.2rem);
  font-weight: var(--fw-black, 900);
  color: var(--text-bright);
  margin: 0;
  letter-spacing: 0.3px;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.55);
}

.role-badge {
  font-family: var(--font-ui);
  font-size: 0.7rem;
  font-weight: 700;
  padding: 5px 14px;
  letter-spacing: 2.2px;
  text-transform: uppercase;
  border: 1px solid currentColor;
  background: rgba(8, 6, 10, 0.55);
}
.role-badge.user  { color: var(--bronze);      box-shadow: 0 0 10px rgba(199, 154, 94, 0.25); }
.role-badge.admin { color: var(--ember-glow);  box-shadow: 0 0 12px rgba(255, 122, 43, 0.3); }

.hero-email {
  font-family: var(--font-ui);
  font-size: 0.92rem;
  color: var(--text-parchment);
  margin: 0 0 4px;
}
.hero-since {
  font-family: var(--font-ui);
  font-size: 0.8rem;
  color: var(--text-ash);
  margin: 0;
  letter-spacing: 0.3px;
}

/* ── Hero stats ── */
.hero-stats {
  display: flex;
  align-items: center;
  gap: 22px;
  padding: 18px 28px;
  background: linear-gradient(180deg,
    var(--ash-stone) 0%,
    var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  clip-path: var(--clip-forged-sm);
  box-shadow: var(--inset-iron-top), var(--shadow-cast);
  flex-shrink: 0;
}
.hs-item { display: flex; flex-direction: column; align-items: center; gap: 4px; }
.hs-num {
  font-family: var(--font-display);
  font-size: 1.5rem;
  font-weight: var(--fw-black, 900);
  color: var(--ember-gold);
  line-height: 1;
  text-shadow: 0 0 10px rgba(255, 201, 121, 0.35);
}
.hs-label {
  font-family: var(--font-ui);
  font-size: 0.7rem;
  color: var(--text-ash);
  text-transform: uppercase;
  letter-spacing: 1.5px;
}
.hs-sep {
  width: 1px;
  height: 36px;
  background: linear-gradient(180deg,
    transparent 0%,
    var(--bronze) 50%,
    transparent 100%);
  opacity: 0.4;
}

/* ===== TAB BAR ===== */
.tab-bar-wrap {
  position: sticky;
  top: 68px;
  z-index: 50;
  background: linear-gradient(180deg,
    rgba(18, 16, 13, 0.92) 0%,
    rgba(8, 6, 10, 0.88) 100%);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--iron-dark);
  box-shadow: 0 2px 0 var(--bronze-dark) inset;
}
.tab-bar {
  max-width: 1140px;
  margin: 0 auto;
  padding: 0 24px;
  display: flex;
  gap: 2px;
  position: relative;
}
.tab-bar::after {
  content: '';
  position: absolute;
  left: 24px; right: 24px;
  bottom: 0;
  height: 1px;
  background: linear-gradient(90deg,
    transparent 0%,
    var(--bronze-dark) 20%,
    var(--bronze-dark) 80%,
    transparent 100%);
  opacity: 0.4;
}
.tab-btn {
  position: relative;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 17px 22px;
  font-family: var(--font-ui);
  font-size: 0.82rem;
  font-weight: 700;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  color: var(--text-ash);
  background: none;
  border: none;
  cursor: pointer;
  transition: color 0.2s var(--ease-smoke);
  white-space: nowrap;
}
.tab-btn:hover { color: var(--text-parchment); }
.tab-btn.active { color: var(--ember-gold); }
.tab-btn.active::after {
  content: '';
  position: absolute;
  left: 14%; right: 14%;
  bottom: 0;
  height: 3px;
  background: linear-gradient(90deg,
    transparent 0%,
    var(--ember-glow) 20%,
    var(--ember-flame) 50%,
    var(--ember-glow) 80%,
    transparent 100%);
  box-shadow: 0 0 12px rgba(226, 67, 16, 0.55);
  z-index: 1;
}
.tab-icon {
  font-size: 1rem;
  color: var(--bronze);
}
.tab-btn.active .tab-icon {
  color: var(--ember-gold);
  filter: drop-shadow(0 0 6px rgba(255, 201, 121, 0.55));
}

/* ===== BODY ===== */
.profile-body {
  max-width: 1140px;
  margin: 0 auto;
  padding: 44px 24px 0;
}

/* Tab transition */
.tab-fade-enter-active, .tab-fade-leave-active { transition: all 0.28s var(--ease-smoke); }
.tab-fade-enter-from { opacity: 0; transform: translateY(14px); }
.tab-fade-leave-to   { opacity: 0; transform: translateY(-10px); }

/* ===== PANEL HEADER ===== */
.panel-header { margin-bottom: 36px; }
.panel-header h2 {
  font-family: var(--font-display);
  font-size: clamp(1.6rem, 3vw, 2rem);
  font-weight: var(--fw-black, 900);
  color: var(--text-bright);
  margin: 0 0 6px;
  letter-spacing: 0.3px;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.45);
}
.panel-header p {
  font-family: var(--font-body);
  font-size: 0.95rem;
  color: var(--text-parchment);
  margin: 0;
}

/* ===== STAT CARDS — каменные плиты с заклёпками ===== */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 16px;
  margin-bottom: 44px;
}
.stat-card {
  position: relative;
  padding: 30px 24px;
  background: linear-gradient(180deg,
    var(--ash-stone) 0%,
    var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  clip-path: var(--clip-forged-sm);
  box-shadow: var(--inset-iron-top), var(--shadow-cast);
  overflow: hidden;
  transition: transform 0.3s var(--ease-forge), box-shadow 0.3s var(--ease-smoke);
  animation: fadeInUp 0.45s var(--ease-smoke) both;
}
/* Четыре заклёпки по углам через background-image */
.stat-card::before {
  content: '';
  position: absolute;
  inset: 10px;
  pointer-events: none;
  background-image:
    radial-gradient(circle 4px at 0 0,       var(--brass) 2px, var(--bronze) 3px, transparent 4px),
    radial-gradient(circle 4px at 100% 0,    var(--brass) 2px, var(--bronze) 3px, transparent 4px),
    radial-gradient(circle 4px at 0 100%,    var(--brass) 2px, var(--bronze) 3px, transparent 4px),
    radial-gradient(circle 4px at 100% 100%, var(--brass) 2px, var(--bronze) 3px, transparent 4px);
  z-index: 2;
}
.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--inset-iron-top), var(--shadow-cast), 0 0 22px rgba(226, 67, 16, 0.28);
}
.stat-card:hover .stat-glow { opacity: 1; }
.stat-icon {
  font-size: 1.4rem;
  margin-bottom: 12px;
  color: var(--bronze);
  filter: drop-shadow(0 0 4px rgba(199, 154, 94, 0.4));
}
.stat-num {
  font-family: var(--font-display);
  font-size: 2rem;
  font-weight: var(--fw-black, 900);
  color: var(--ember-gold);
  margin: 0 0 6px;
  line-height: 1;
  letter-spacing: 0.3px;
  text-shadow: 0 0 12px rgba(255, 201, 121, 0.35);
}
.stat-num.small {
  font-size: 1.25rem;
  color: var(--text-bright);
  text-shadow: 0 2px 6px rgba(0, 0, 0, 0.5);
}
.stat-label {
  font-family: var(--font-ui);
  font-size: 0.74rem;
  color: var(--text-ash);
  text-transform: uppercase;
  letter-spacing: 1.5px;
  margin: 0;
}
.stat-glow {
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse 70% 55% at 50% 100%,
    rgba(226, 67, 16, 0.22) 0%,
    transparent 70%);
  opacity: 0;
  transition: opacity 0.3s var(--ease-smoke);
  pointer-events: none;
}

/* ===== RECENT ORDERS ===== */
.recent-title {
  font-family: var(--font-display);
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--text-bright);
  margin: 0 0 18px;
  letter-spacing: 0.3px;
}
.orders-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 18px; }
.order-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
  background: linear-gradient(180deg,
    var(--ash-stone) 0%,
    var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  box-shadow: var(--inset-iron-top);
  transition: border-color 0.2s var(--ease-smoke), box-shadow 0.2s var(--ease-smoke);
  flex-wrap: wrap;
}
.order-card:hover {
  border-color: var(--bronze-dark);
  box-shadow: var(--inset-iron-top), 0 0 14px rgba(199, 154, 94, 0.25);
}
.order-card-left { display: flex; flex-direction: column; gap: 3px; min-width: 72px; }
.order-id {
  font-family: var(--font-display);
  font-size: 1rem;
  font-weight: 700;
  color: var(--text-bright);
}
.order-date {
  font-family: var(--font-ui);
  font-size: 0.75rem;
  color: var(--text-ash);
}
.order-games-preview { display: flex; gap: 8px; flex-wrap: wrap; flex: 1; }
.game-chip {
  font-family: var(--font-ui);
  font-size: 0.76rem;
  padding: 5px 11px;
  background: rgba(8, 6, 10, 0.55);
  border: 1px solid var(--iron-dark);
  color: var(--text-parchment);
  white-space: nowrap;
  letter-spacing: 0.4px;
}
.game-chip.more { color: var(--text-ash); }
.order-total {
  font-family: var(--font-display);
  font-size: 1rem;
  font-weight: 700;
  color: var(--ember-gold);
  margin-left: auto;
  white-space: nowrap;
  text-shadow: 0 0 8px rgba(255, 201, 121, 0.25);
}

.see-all-btn {
  background: none;
  border: none;
  color: var(--ember-spark);
  font-family: var(--font-ui);
  font-size: 0.84rem;
  font-weight: 700;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  cursor: pointer;
  padding: 0;
  transition: color 0.2s var(--ease-smoke);
}
.see-all-btn:hover { color: var(--ember-gold); }

/* ===== FULL ORDERS LIST ===== */
.orders-full-list { display: flex; flex-direction: column; gap: 14px; }
.order-full-card {
  background: linear-gradient(180deg,
    var(--ash-ironrust) 0%,
    var(--ash-stone) 45%,
    var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  overflow: hidden;
  box-shadow: var(--inset-iron-top), var(--shadow-cast);
  transition: border-color 0.2s var(--ease-smoke), box-shadow 0.2s var(--ease-smoke);
}
.order-full-card:hover {
  border-color: var(--bronze-dark);
  box-shadow: var(--inset-iron-top), var(--shadow-cast), 0 0 16px rgba(226, 67, 16, 0.2);
}
.ofc-header {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 18px 22px;
  background: linear-gradient(180deg,
    var(--ash-bloodrock) 0%,
    var(--ash-ironrust) 100%);
  border-bottom: 1px solid var(--iron-dark);
  flex-wrap: wrap;
}
.ofc-id {
  font-family: var(--font-display);
  font-size: 1.08rem;
  font-weight: var(--fw-black, 900);
  color: var(--text-bright);
  letter-spacing: 0.3px;
}
.ofc-hash { color: var(--bronze); margin-right: 2px; opacity: 0.7; }
.ofc-date {
  font-family: var(--font-ui);
  font-size: 0.82rem;
  color: var(--text-ash);
  flex: 1;
}
.ofc-status {
  font-family: var(--font-ui);
  font-size: 0.76rem;
  font-weight: 700;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  padding: 4px 11px;
  border: 1px solid currentColor;
  background: rgba(8, 6, 10, 0.4);
}
.ofc-status.status-created   { color: var(--bronze); }
.ofc-status.status-paid      { color: var(--ember-spark); }
.ofc-status.status-shipped   { color: var(--ember-glow); }
.ofc-status.status-completed { color: var(--ember-gold); }
.ofc-status.status-cancelled { color: #ffb4a8; }
.ofc-total {
  font-family: var(--font-display);
  font-size: 1.08rem;
  font-weight: 700;
  color: var(--ember-gold);
  margin-left: auto;
  text-shadow: 0 0 8px rgba(255, 201, 121, 0.3);
}
.ofc-items { padding: 14px 22px; display: flex; flex-direction: column; gap: 8px; }
.ofc-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 3px 0;
}
.ofc-game-name {
  font-family: var(--font-body);
  font-size: 0.95rem;
  color: var(--text-parchment);
  text-decoration: none;
  transition: color 0.2s var(--ease-smoke);
}
.ofc-game-name:hover { color: var(--ember-gold); }
.ofc-qty {
  font-family: var(--font-ui);
  font-size: 0.78rem;
  color: var(--text-ash);
  letter-spacing: 0.5px;
}

/* ===== REVIEWS ===== */
.reviews-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 18px;
}
.review-card {
  position: relative;
  padding: 24px;
  background: linear-gradient(180deg,
    var(--ash-stone) 0%,
    var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  clip-path: var(--clip-forged-sm);
  display: flex;
  flex-direction: column;
  gap: 14px;
  box-shadow: var(--inset-iron-top), var(--shadow-cast);
  transition: transform 0.3s var(--ease-forge), box-shadow 0.3s var(--ease-smoke);
  animation: fadeInUp 0.4s var(--ease-smoke) both;
}
.review-card::before {
  content: '';
  position: absolute;
  inset: 10px;
  pointer-events: none;
  background-image:
    radial-gradient(circle 3.5px at 0 0,       var(--brass) 1.8px, var(--bronze) 2.8px, transparent 3.6px),
    radial-gradient(circle 3.5px at 100% 0,    var(--brass) 1.8px, var(--bronze) 2.8px, transparent 3.6px);
  z-index: 2;
}
.review-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--inset-iron-top), var(--shadow-cast), 0 0 20px rgba(255, 201, 121, 0.22);
}
.review-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 10px;
}
.review-game {
  font-family: var(--font-display);
  font-size: 1rem;
  font-weight: 700;
  color: var(--text-bright);
  text-decoration: none;
  transition: color 0.2s var(--ease-smoke);
  letter-spacing: 0.3px;
}
.review-game:hover { color: var(--ember-gold); }
.review-date {
  font-family: var(--font-ui);
  font-size: 0.74rem;
  color: var(--text-ash);
  white-space: nowrap;
}
.stars { display: flex; align-items: center; gap: 3px; }
.star {
  font-size: 1.12rem;
  color: var(--iron-warm);
  transition: color 0.15s var(--ease-smoke);
}
.star.filled {
  color: var(--ember-gold);
  text-shadow: 0 0 6px rgba(255, 201, 121, 0.55);
}
.rating-num {
  font-family: var(--font-ui);
  font-size: 0.78rem;
  color: var(--text-parchment);
  margin-left: 8px;
  letter-spacing: 0.5px;
}
.review-text {
  font-family: var(--font-body);
  font-size: 0.92rem;
  color: var(--text-parchment);
  line-height: 1.7;
  margin: 0;
  flex: 1;
}

/* ===== SETTINGS — кованые формы ===== */
.settings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
  gap: 22px;
}
.settings-card {
  position: relative;
  padding: 30px 30px 26px;
  background: linear-gradient(180deg,
    var(--ash-ironrust) 0%,
    var(--ash-stone) 45%,
    var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  clip-path: var(--clip-forged-sm);
  box-shadow: var(--inset-iron-top), var(--shadow-cast);
  animation: fadeInUp 0.45s var(--ease-smoke) both;
}
.settings-card::before {
  content: '';
  position: absolute;
  inset: 12px;
  pointer-events: none;
  background-image:
    radial-gradient(circle 4px at 0 0,       var(--brass) 2px, var(--bronze) 3px, transparent 4px),
    radial-gradient(circle 4px at 100% 0,    var(--brass) 2px, var(--bronze) 3px, transparent 4px),
    radial-gradient(circle 4px at 0 100%,    var(--brass) 2px, var(--bronze) 3px, transparent 4px),
    radial-gradient(circle 4px at 100% 100%, var(--brass) 2px, var(--bronze) 3px, transparent 4px);
  z-index: 2;
}
.settings-card-header {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  margin-bottom: 22px;
  padding-bottom: 18px;
  border-bottom: 1px dashed var(--iron-dark);
}
.settings-card-icon {
  width: 46px;
  height: 46px;
  clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
  background: linear-gradient(180deg,
    var(--ash-forge) 0%,
    var(--ash-bloodrock) 100%);
  border: 1px solid var(--bronze-dark);
  color: var(--ember-gold);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: var(--inset-iron-top), 0 0 10px rgba(226, 67, 16, 0.3);
}
.settings-card-icon svg { filter: drop-shadow(0 0 3px rgba(255, 201, 121, 0.5)); }
.settings-card-header h3 {
  font-family: var(--font-display);
  font-size: 1.08rem;
  font-weight: 700;
  color: var(--text-bright);
  margin: 0 0 4px;
  letter-spacing: 0.3px;
}
.settings-card-header p {
  font-family: var(--font-body);
  font-size: 0.86rem;
  color: var(--text-parchment);
  margin: 0;
}

.settings-form { display: flex; flex-direction: column; gap: 16px; }

.field { display: flex; flex-direction: column; gap: 7px; }
.field label {
  font-family: var(--font-ui);
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--bronze);
  text-transform: uppercase;
  letter-spacing: 1.5px;
}
.field input {
  width: 100%;
  box-sizing: border-box;
  padding: 12px 15px;
  background: linear-gradient(180deg,
    rgba(8, 6, 10, 0.75) 0%,
    rgba(18, 16, 13, 0.85) 100%);
  border: 1px solid var(--iron-mid);
  color: var(--text-bone);
  font-family: var(--font-body);
  font-size: 0.96rem;
  box-shadow: var(--inset-iron-top);
  transition: border-color 0.2s var(--ease-smoke), box-shadow 0.2s var(--ease-smoke);
  outline: none;
}
.field input::placeholder { color: var(--text-void); }
.field input:focus {
  border-color: var(--ember-flame);
  box-shadow:
    var(--inset-iron-top),
    0 0 0 3px rgba(226, 67, 16, 0.14),
    0 0 12px rgba(255, 122, 43, 0.22);
}

.form-footer {
  display: flex;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
  margin-top: 6px;
}

/* ===== SHARED COMPONENTS — кованая кнопка и т.п. ===== */
.btn-primary {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 26px;
  border: 1px solid var(--ember-heart);
  background: var(--grad-ember);
  color: var(--text-bright);
  font-family: var(--font-display);
  font-size: 0.92rem;
  font-weight: 700;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  text-decoration: none;
  cursor: pointer;
  overflow: hidden;
  box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.35), var(--glow-ember);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.65);
  transition: transform 0.18s var(--ease-forge), box-shadow 0.2s var(--ease-smoke);
  clip-path: var(--clip-forged-sm);
  white-space: nowrap;
}
.btn-primary::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg,
    transparent 0%, rgba(255, 201, 121, 0.4) 50%, transparent 100%);
  transform: translateX(-120%);
  transition: transform 0.6s var(--ease-smoke);
  pointer-events: none;
}
.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.35), var(--glow-ember-strong);
}
.btn-primary:hover::after { transform: translateX(120%); }
.btn-primary:disabled {
  background: var(--ash-stone);
  border-color: var(--iron-mid);
  color: var(--text-smoke);
  box-shadow: var(--inset-iron-top);
  cursor: not-allowed;
  text-shadow: none;
}

.msg-success {
  font-family: var(--font-ui);
  font-size: 0.85rem;
  color: var(--ember-gold);
  font-weight: 600;
  letter-spacing: 0.5px;
}
.msg-error {
  font-family: var(--font-ui);
  font-size: 0.85rem;
  color: #ffb4a8;
  font-weight: 600;
  letter-spacing: 0.5px;
}
.msg-enter-active, .msg-leave-active { transition: all 0.25s var(--ease-smoke); }
.msg-enter-from, .msg-leave-to { opacity: 0; transform: translateX(-8px); }

.field-hint {
  font-weight: 400;
  font-size: 0.72rem;
  color: var(--text-ash);
  text-transform: none;
  letter-spacing: 0;
  margin-left: 6px;
}

.msg-banner {
  padding: 11px 14px;
  font-family: var(--font-body);
  font-size: 0.88rem;
  font-weight: 500;
  box-shadow: var(--inset-iron-top);
}
.msg-banner.success {
  background: linear-gradient(180deg, rgba(226, 67, 16, 0.18), rgba(138, 31, 24, 0.1));
  border: 1px solid rgba(255, 167, 88, 0.4);
  color: var(--ember-gold);
}
.msg-banner.error {
  background: linear-gradient(180deg, rgba(138, 31, 24, 0.25), rgba(90, 20, 18, 0.35));
  border: 1px solid rgba(194, 40, 26, 0.45);
  color: #ffb4a8;
}

.email-change-hint {
  font-family: var(--font-body);
  font-size: 0.88rem;
  color: var(--text-parchment);
  margin: 0;
  line-height: 1.55;
}

.btn-ghost {
  background: transparent;
  border: 1px solid var(--bronze-dark);
  color: var(--text-parchment);
  padding: 11px 20px;
  font-family: var(--font-ui);
  font-size: 0.82rem;
  font-weight: 700;
  letter-spacing: 1.3px;
  text-transform: uppercase;
  cursor: pointer;
  box-shadow: var(--inset-iron-top);
  transition: all 0.2s var(--ease-smoke);
}
.btn-ghost:hover {
  border-color: var(--bronze);
  color: var(--text-bright);
  background: rgba(122, 93, 72, 0.12);
}

.btn-spin {
  display: inline-block;
  width: 13px;
  height: 13px;
  border: 2px solid rgba(255, 248, 234, 0.3);
  border-top-color: var(--text-bright);
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
  margin-right: 6px;
  vertical-align: middle;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Notification toggles — кованые рычаги ── */
.notify-card .settings-form { gap: 8px; }

.notify-toggle {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 16px;
  background: linear-gradient(180deg,
    rgba(8, 6, 10, 0.55) 0%,
    rgba(18, 16, 13, 0.65) 100%);
  border: 1px solid var(--iron-dark);
  cursor: pointer;
  box-shadow: var(--inset-iron-top);
  transition: border-color 0.2s var(--ease-smoke), box-shadow 0.2s var(--ease-smoke);
  position: relative;
}
.notify-toggle:hover {
  border-color: var(--bronze-dark);
  box-shadow: var(--inset-iron-top), 0 0 10px rgba(199, 154, 94, 0.2);
}
.notify-toggle input[type="checkbox"] { display: none; }

.toggle-track {
  flex-shrink: 0;
  width: 46px;
  height: 24px;
  background: var(--iron-void);
  position: relative;
  transition: background 0.25s var(--ease-smoke), border-color 0.25s var(--ease-smoke);
  border: 1px solid var(--iron-dark);
  box-shadow: var(--inset-iron-top);
}
.notify-toggle input:checked ~ .toggle-track {
  background: var(--grad-ember);
  border-color: var(--ember-heart);
  box-shadow:
    var(--inset-iron-top),
    inset 0 -2px 3px rgba(0, 0, 0, 0.35),
    0 0 10px rgba(226, 67, 16, 0.4);
}
.toggle-thumb {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 18px;
  height: 18px;
  background: radial-gradient(circle at 30% 30%,
    var(--iron-warm) 0%,
    var(--iron-dark) 100%);
  border: 1px solid var(--iron-void);
  transition: transform 0.25s var(--ease-forge), background 0.25s var(--ease-smoke);
}
.notify-toggle input:checked ~ .toggle-track .toggle-thumb {
  transform: translateX(20px);
  background: radial-gradient(circle at 30% 30%,
    var(--ember-gold) 0%,
    var(--brass) 100%);
  border-color: var(--bronze);
}

.toggle-body { display: flex; flex-direction: column; gap: 3px; flex: 1; }
.toggle-title {
  font-family: var(--font-ui);
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--text-bone);
  letter-spacing: 0.3px;
}
.toggle-desc {
  font-family: var(--font-body);
  font-size: 0.8rem;
  color: var(--text-ash);
}
.notify-toggle input:checked ~ .toggle-track ~ .toggle-body .toggle-title {
  color: var(--ember-gold);
}

.notify-saving {
  display: flex;
  align-items: center;
  gap: 8px;
  font-family: var(--font-ui);
  font-size: 0.8rem;
  color: var(--text-ash);
  padding: 8px 0 0 4px;
  letter-spacing: 0.5px;
}

/* States */
.loading-state {
  text-align: center;
  padding: 64px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 18px;
  color: var(--text-ash);
  font-family: var(--font-body);
  font-size: 0.95rem;
}
.spinner {
  width: 38px;
  height: 38px;
  border: 3px solid var(--iron-dark);
  border-top-color: var(--ember-flame);
  border-radius: 50%;
  animation: spinEl 0.9s linear infinite;
  filter: drop-shadow(0 0 6px rgba(226, 67, 16, 0.4));
}
.error-state {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 16px 20px;
  background: linear-gradient(180deg, rgba(138, 31, 24, 0.25), rgba(90, 20, 18, 0.35));
  border: 1px solid rgba(194, 40, 26, 0.45);
  color: #ffb4a8;
  font-family: var(--font-body);
  font-size: 0.9rem;
  box-shadow: var(--inset-iron-top);
}
.empty-state {
  text-align: center;
  padding: 64px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
}
.empty-icon {
  font-size: 3rem;
  color: var(--bronze);
  filter: drop-shadow(0 0 8px rgba(199, 154, 94, 0.35));
}
.empty-state h3 {
  font-family: var(--font-display);
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--text-bright);
  margin: 0;
  letter-spacing: 0.3px;
}
.empty-state p {
  font-family: var(--font-body);
  color: var(--text-parchment);
  margin: 0;
  font-size: 0.95rem;
}

/* ===== AVATAR PICKER — модалка-свиток ===== */
.ap-fade-enter-active, .ap-fade-leave-active { transition: opacity 0.25s var(--ease-smoke); }
.ap-fade-enter-from, .ap-fade-leave-to { opacity: 0; }

.ap-backdrop {
  position: fixed; inset: 0;
  background: rgba(0, 0, 0, 0.82);
  backdrop-filter: blur(8px);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.ap-modal {
  position: relative;
  background: linear-gradient(180deg,
    var(--ash-ironrust) 0%,
    var(--ash-stone) 40%,
    var(--ash-coal) 100%);
  border: 1px solid var(--bronze-dark);
  clip-path: var(--clip-forged-md);
  width: 100%;
  max-width: 700px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow:
    inset 0 0 0 1px var(--iron-mid),
    var(--shadow-deep),
    var(--inset-forge);
  animation: slideUp 0.28s var(--ease-forge);
}
.ap-modal::before {
  content: '';
  position: absolute;
  inset: 12px;
  pointer-events: none;
  background-image:
    radial-gradient(circle 5px at 0 0,       var(--brass) 2.5px, var(--bronze) 3.8px, transparent 5px),
    radial-gradient(circle 5px at 100% 0,    var(--brass) 2.5px, var(--bronze) 3.8px, transparent 5px),
    radial-gradient(circle 5px at 0 100%,    var(--brass) 2.5px, var(--bronze) 3.8px, transparent 5px),
    radial-gradient(circle 5px at 100% 100%, var(--brass) 2.5px, var(--bronze) 3.8px, transparent 5px);
  z-index: 3;
}
@keyframes slideUp {
  from { opacity: 0; transform: translateY(24px); }
  to   { opacity: 1; transform: none; }
}

.ap-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 22px 26px;
  border-bottom: 1px dashed var(--iron-dark);
  flex-shrink: 0;
  position: relative;
  z-index: 2;
}
.ap-title-wrap { display: flex; align-items: center; gap: 12px; }
.ap-title-icon {
  font-size: 1.4rem;
  color: var(--ember-gold);
  filter: drop-shadow(0 0 6px rgba(255, 201, 121, 0.5));
}
.ap-title {
  font-family: var(--font-display);
  font-size: 1.18rem;
  font-weight: 700;
  color: var(--text-bright);
  margin: 0 0 4px;
  letter-spacing: 0.3px;
}
.ap-sub {
  font-family: var(--font-ui);
  font-size: 0.75rem;
  color: var(--text-ash);
  margin: 0;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}
.ap-close {
  width: 36px;
  height: 36px;
  border: 1px solid var(--iron-dark);
  background: rgba(8, 6, 10, 0.55);
  color: var(--text-parchment);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s var(--ease-smoke);
  flex-shrink: 0;
  box-shadow: var(--inset-iron-top);
}
.ap-close:hover {
  background: rgba(138, 31, 24, 0.35);
  color: var(--ember-gold);
  border-color: var(--ember-heart);
}

.ap-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(92px, 1fr));
  gap: 12px;
  padding: 22px 26px 26px;
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: var(--bronze-dark) transparent;
  position: relative;
  z-index: 2;
}
.ap-grid::-webkit-scrollbar { width: 6px; }
.ap-grid::-webkit-scrollbar-thumb { background: var(--bronze-dark); }

.ap-item {
  position: relative;
  height: 92px;
  background: linear-gradient(180deg,
    var(--ash-coal) 0%,
    var(--ash-obsidian) 100%);
  border: 2px solid var(--iron-dark);
  overflow: hidden;
  cursor: pointer;
  padding: 6px;
  box-sizing: border-box;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: var(--inset-iron-top);
  transition: border-color 0.18s var(--ease-smoke), transform 0.18s var(--ease-forge), box-shadow 0.18s var(--ease-smoke);
}
.ap-item:hover {
  border-color: var(--bronze);
  transform: translateY(-2px) scale(1.05);
  box-shadow: var(--inset-iron-top), 0 0 12px rgba(199, 154, 94, 0.35);
}
.ap-item.selected {
  border-color: var(--ember-flame);
  box-shadow: var(--inset-iron-top), 0 0 0 3px rgba(226, 67, 16, 0.35), 0 0 14px rgba(255, 122, 43, 0.4);
}
.ap-item.saving { pointer-events: none; opacity: 0.7; }
.ap-item img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
  transition: transform 0.2s var(--ease-forge);
}
.ap-item:hover img { transform: scale(1.08); }
.ap-item-check {
  position: absolute;
  inset: 0;
  background: rgba(226, 67, 16, 0.72);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-bright);
  font-size: 1.6rem;
  font-weight: 800;
  opacity: 0;
  transition: opacity 0.18s var(--ease-smoke);
}
.ap-item.selected .ap-item-check { opacity: 1; }

/* ===== RESPONSIVE ===== */
@media (max-width: 1100px) {
  .stats-grid { gap: 16px; }
  .reviews-grid { gap: 18px; }
}
@media (max-width: 900px) {
  .hero-stats { gap: 18px; }
  .hs-num { font-size: 1.4rem; }
  .hs-label { font-size: 0.7rem; }
}
@media (max-width: 768px) {
  .hero-inner { flex-direction: column; text-align: center; align-items: center; gap: 18px; }
  .hero-name-row { justify-content: center; flex-wrap: wrap; }
  .hero-stats { width: 100%; justify-content: center; flex-wrap: wrap; gap: 12px 18px; }
  .tab-bar { overflow-x: auto; scrollbar-width: none; flex-wrap: nowrap; }
  .tab-bar::-webkit-scrollbar { display: none; }
  .tab-btn { padding: 15px 14px; font-size: 0.76rem; letter-spacing: 1px; flex-shrink: 0; }
  .stats-grid { grid-template-columns: 1fr 1fr; }
  .reviews-grid { grid-template-columns: 1fr; }
  .settings-grid { grid-template-columns: 1fr; }
  .hs-sep { display: none; }
}

@media (max-width: 600px) {
  .hero-section { padding: 60px 18px 40px; }
  .hero-name { font-size: clamp(1.6rem, 6vw, 2.2rem); }
  .stats-grid { gap: 12px; }
  .stat-card { padding: 18px 16px; }
}

@media (max-width: 480px) {
  .stats-grid { grid-template-columns: 1fr; }
  .ofc-header { gap: 10px; flex-wrap: wrap; }
  .hero-section { padding: 50px 14px 32px; }
  .avatar-frame { width: 100px; height: 100px; }
  .tab-btn { padding: 13px 12px; font-size: 0.72rem; }
  .order-full-card { padding: 16px; }
}

@media (max-width: 380px) {
  .hero-name { font-size: 1.4rem; }
  .hero-tagline { font-size: 0.85rem; }
}
</style>
