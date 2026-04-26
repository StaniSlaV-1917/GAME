<script setup>
import { ref, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import api from '../api/axios';

const authStore = useAuthStore();
const stats = ref({ users: 0, games: 0, orders: 0, reviews: 0, revenue: 0, support_new: 0, support_total: 0 });
const statsLoaded = ref(false);

const animateCount = (target, key) => {
  const duration = 1200;
  const start = performance.now();
  const step = (now) => {
    const t = Math.min((now - start) / duration, 1);
    const eased = 1 - Math.pow(1 - t, 3);
    stats.value[key] = Math.round(target * eased);
    if (t < 1) requestAnimationFrame(step);
  };
  requestAnimationFrame(step);
};

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/stats');
    statsLoaded.value = true;
    Object.keys(data).forEach(k => animateCount(data[k], k));
  } catch (e) { console.error(e); }
});

const cards = [
  {
    to: '/admin/games',
    title: 'Игры',
    desc: 'Добавляйте и редактируйте игры, цены, обложки и описания.',
    color: '#3b82f6',
    glow: 'rgba(59,130,246,0.25)',
    icon: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M12 12h.01"/><path d="M7 12h.01"/><path d="M17 12h.01"/><path d="M12 9v6"/></svg>`,
  },
  {
    to: '/admin/news',
    title: 'Новости',
    desc: 'Создавайте статьи и управляйте новостной лентой магазина.',
    color: '#8b5cf6',
    glow: 'rgba(139,92,246,0.25)',
    icon: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9"/><path d="M8 6h8"/><path d="M8 10h8"/><path d="M8 14h4"/></svg>`,
  },
  {
    to: '/admin/users',
    title: 'Пользователи',
    desc: 'Управляйте аккаунтами, назначайте роли и редактируйте данные.',
    color: '#22c55e',
    glow: 'rgba(34,197,94,0.25)',
    icon: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`,
  },
  {
    to: '/admin/orders',
    title: 'Заказы',
    desc: 'История покупок, статусы заказов и выручка магазина.',
    color: '#f59e0b',
    glow: 'rgba(245,158,11,0.25)',
    icon: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>`,
  },
  {
    to: '/admin/reviews',
    title: 'Отзывы',
    desc: 'Модерируйте пользовательские отзывы и рейтинги игр.',
    color: '#06b6d4',
    glow: 'rgba(6,182,212,0.25)',
    icon: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>`,
  },
  {
    to: '/admin/employees',
    title: 'Сотрудники',
    desc: 'Учётные записи менеджеров и права доступа к панели.',
    color: '#f43f5e',
    glow: 'rgba(244,63,94,0.25)',
    icon: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>`,
  },
  {
    to: '/admin/support',
    title: 'Поддержка',
    desc: 'Обращения пользователей из чата — меняйте статусы и оставляйте заметки.',
    color: '#06b6d4',
    glow: 'rgba(6,182,212,0.25)',
    badge: 'support_new',
    icon: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><line x1="9" y1="10" x2="15" y2="10"/><line x1="12" y1="7" x2="12" y2="13"/></svg>`,
  },
];

const statCards = [
  { key: 'users',   label: 'Пользователей', color: '#22c55e', icon: '👤' },
  { key: 'games',   label: 'Игр в каталоге', color: '#3b82f6', icon: '🎮' },
  { key: 'orders',  label: 'Заказов',        color: '#f59e0b', icon: '📦' },
  { key: 'reviews', label: 'Отзывов',        color: '#8b5cf6', icon: '⭐' },
];
</script>

<template>
  <div class="adm-root">
    <!-- Ambient glows -->
    <div class="adm-glow g1"></div>
    <div class="adm-glow g2"></div>

    <!-- Header -->
    <div class="adm-header">
      <div>
        <p class="adm-eyebrow">Панель управления</p>
        <h1 class="adm-title">
          Добро пожаловать<span class="adm-comma">,</span>
          <span class="adm-name">{{ authStore.user?.fullname || 'Администратор' }}</span>
        </h1>
        <p class="adm-sub">Управляйте магазином — игры, пользователи, заказы и контент.</p>
      </div>
      <div class="adm-badge">
        <span class="badge-dot"></span>
        <span>Онлайн</span>
      </div>
    </div>

    <!-- Stats row -->
    <div class="stats-row">
      <div
        v-for="s in statCards" :key="s.key"
        class="stat-card"
        :style="{ '--sc': s.color }"
      >
        <div class="stat-icon">{{ s.icon }}</div>
        <div class="stat-val">{{ stats[s.key].toLocaleString('ru-RU') }}</div>
        <div class="stat-label">{{ s.label }}</div>
        <div class="stat-bar"></div>
      </div>
    </div>

    <!-- Nav cards -->
    <div class="nav-grid">
      <RouterLink
        v-for="c in cards" :key="c.to"
        :to="c.to"
        class="nav-card"
        :style="{ '--cc': c.color, '--cg': c.glow }"
      >
        <div class="nc-icon-wrap">
          <span class="nc-icon" v-html="c.icon"></span>
        </div>
        <div class="nc-body">
          <div class="nc-title-row">
            <h2 class="nc-title">{{ c.title }}</h2>
            <span v-if="c.badge && stats[c.badge] > 0" class="nc-badge">{{ stats[c.badge] }} новых</span>
          </div>
          <p class="nc-desc">{{ c.desc }}</p>
        </div>
        <div class="nc-arrow">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
        <div class="nc-glow"></div>
      </RouterLink>
    </div>
  </div>
</template>

<style scoped>
.adm-root {
  max-width: 1200px;
  margin: 0 auto;
  padding: 40px 24px 80px;
  position: relative;
  color: #e5e7eb;
  min-height: 100vh;
}

/* Ambient glows */
.adm-glow {
  position: fixed;
  border-radius: 50%;
  filter: blur(120px);
  pointer-events: none;
  z-index: 0;
}
.g1 { width: 500px; height: 500px; background: rgba(59,130,246,0.07); top: -100px; right: -100px; }
.g2 { width: 400px; height: 400px; background: rgba(139,92,246,0.06); bottom: 0; left: -80px; }

/* Header */
.adm-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 36px;
  position: relative;
  z-index: 1;
}
.adm-eyebrow {
  font-size: 0.78rem;
  font-weight: 600;
  color: #3b82f6;
  letter-spacing: 2px;
  text-transform: uppercase;
  margin: 0 0 8px;
}
.adm-title {
  font-size: clamp(1.6rem, 3vw, 2.2rem);
  font-weight: 800;
  color: #fff;
  margin: 0 0 8px;
  line-height: 1.2;
}
.adm-comma { color: #6b7280; }
.adm-name { color: #60a5fa; }
.adm-sub { color: #6b7280; font-size: 0.95rem; margin: 0; }

.adm-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(34,197,94,0.1);
  border: 1px solid rgba(34,197,94,0.25);
  border-radius: 999px;
  padding: 6px 14px;
  font-size: 0.82rem;
  color: #4ade80;
  font-weight: 600;
  white-space: nowrap;
}
.badge-dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: #4ade80;
  box-shadow: 0 0 8px #4ade80;
  animation: pulse 2s ease-in-out infinite;
}
@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(0.8); }
}

/* Stats */
.stats-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 36px;
  position: relative;
  z-index: 1;
}
.stat-card {
  background: rgba(15,23,42,0.8);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 16px;
  padding: 20px 22px;
  position: relative;
  overflow: hidden;
  backdrop-filter: blur(12px);
  transition: border-color 0.3s, transform 0.3s;
}
.stat-card:hover {
  border-color: var(--sc);
  transform: translateY(-4px);
}
.stat-icon { font-size: 1.5rem; margin-bottom: 10px; }
.stat-val {
  font-size: 2rem;
  font-weight: 800;
  color: #fff;
  line-height: 1;
  margin-bottom: 4px;
  font-variant-numeric: tabular-nums;
}
.stat-label { font-size: 0.8rem; color: #6b7280; font-weight: 500; }
.stat-bar {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 2px;
  background: var(--sc);
  opacity: 0.6;
  border-radius: 0 0 16px 16px;
}

/* Nav cards grid */
.nav-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  position: relative;
  z-index: 1;
}
.nav-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: rgba(15,23,42,0.75);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 16px;
  text-decoration: none;
  position: relative;
  overflow: hidden;
  backdrop-filter: blur(12px);
  transition: border-color 0.3s, transform 0.3s, box-shadow 0.3s;
  cursor: pointer;
}
.nav-card:hover {
  border-color: var(--cc);
  transform: translateY(-5px);
  box-shadow: 0 16px 40px rgba(0,0,0,0.4), 0 0 0 1px var(--cc);
}
.nav-card:hover .nc-glow { opacity: 1; }
.nav-card:hover .nc-arrow { color: var(--cc); transform: translateX(4px); }

.nc-glow {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 0% 50%, var(--cg) 0%, transparent 65%);
  opacity: 0;
  transition: opacity 0.4s;
  pointer-events: none;
}

.nc-icon-wrap {
  width: 52px; height: 52px;
  border-radius: 12px;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.08);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  color: var(--cc);
  transition: background 0.3s, border-color 0.3s;
}
.nav-card:hover .nc-icon-wrap {
  background: var(--cg);
  border-color: var(--cc);
}
.nc-icon { display: flex; align-items: center; justify-content: center; }

.nc-body { flex: 1; min-width: 0; }
.nc-title-row { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
.nc-title { font-size: 1rem; font-weight: 700; color: #f1f5f9; margin: 0; }
.nc-badge {
  font-size: 0.7rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 999px;
  background: rgba(239,68,68,0.2);
  color: #f87171;
  border: 1px solid rgba(239,68,68,0.35);
  animation: pulse-badge 2s ease infinite;
}
@keyframes pulse-badge {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.65; }
}
.nc-desc { font-size: 0.8rem; color: #6b7280; line-height: 1.5; margin: 0; }

.nc-arrow {
  color: #374151;
  transition: color 0.3s, transform 0.3s;
  flex-shrink: 0;
}

/* Responsive */
@media (max-width: 1024px) {
  .stats-row { grid-template-columns: repeat(2, 1fr); }
  .nav-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
  .adm-header { flex-direction: column; gap: 16px; }
  .stats-row { grid-template-columns: repeat(2, 1fr); }
  .nav-grid { grid-template-columns: 1fr; }
}
</style>
