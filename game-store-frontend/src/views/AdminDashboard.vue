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

// Тон-классы для карточек: ember / brass / bronze — чередуем, чтобы сохранить ритм
const cards = [
  {
    to: '/admin/games',
    title: 'Оружейная',
    desc: 'Ковка клинков: добавляйте, переписывайте, меняйте цены и обложки.',
    tone: 'ember',
    icon: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M12 12h.01"/><path d="M7 12h.01"/><path d="M17 12h.01"/><path d="M12 9v6"/></svg>`,
  },
  {
    to: '/admin/news',
    title: 'Хроники',
    desc: 'Пишите вести, правьте ленту, высекайте истории клана.',
    tone: 'brass',
    icon: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9"/><path d="M8 6h8"/><path d="M8 10h8"/><path d="M8 14h4"/></svg>`,
  },
  {
    to: '/admin/users',
    title: 'Воины',
    desc: 'Учёт рядовых, назначение ролей, редактирование профилей.',
    tone: 'bronze',
    icon: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`,
  },
  {
    to: '/admin/orders',
    title: 'Заказы',
    desc: 'История походов за клинками, статусы, казна оплота.',
    tone: 'ember',
    icon: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>`,
  },
  {
    to: '/admin/reviews',
    title: 'Суды воинов',
    desc: 'Разбирайте отзывы воинов, держите рейтинги клинков в чистоте.',
    tone: 'brass',
    icon: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>`,
  },
  {
    to: '/admin/employees',
    title: 'Совет',
    desc: 'Учётные записи старейшин и посадников — права, роли, доступы.',
    tone: 'bronze',
    icon: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>`,
  },
  {
    to: '/admin/support',
    title: 'Стража поддержки',
    desc: 'Обращения воинов из чата — меняйте статусы, оставляйте заметки.',
    tone: 'ember',
    badge: 'support_new',
    icon: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><line x1="9" y1="10" x2="15" y2="10"/><line x1="12" y1="7" x2="12" y2="13"/></svg>`,
  },
];

const statCards = [
  { key: 'users',   label: 'Воинов',      tone: 'bronze' },
  { key: 'games',   label: 'Клинков',     tone: 'ember' },
  { key: 'orders',  label: 'Походов',     tone: 'brass' },
  { key: 'reviews', label: 'Судов',       tone: 'ember' },
];
</script>

<template>
  <div class="adm-root">
    <!-- Ambient ember glows -->
    <div class="adm-glow g1"></div>
    <div class="adm-glow g2"></div>

    <!-- Header -->
    <div class="adm-header">
      <div class="adm-header-left">
        <span class="tribal-eyebrow">
          <span class="eb-spike"></span>
          Совет старейшин
          <span class="eb-spike"></span>
        </span>
        <h1 class="adm-title">
          Добро пожаловать<span class="adm-comma">,</span>
          <span class="adm-name">{{ authStore.user?.fullname || 'Старейшина' }}</span>
        </h1>
        <p class="adm-sub">Управляйте оплотом — оружейной, воинами, походами и хрониками.</p>
      </div>
      <div class="adm-badge">
        <span class="badge-dot"></span>
        <span>Горн жив</span>
      </div>
    </div>

    <!-- Stats row -->
    <div class="stats-row">
      <div
        v-for="s in statCards" :key="s.key"
        class="stat-card"
        :class="`tone-${s.tone}`"
      >
        <span class="sc-rivet sc-rivet--tl" aria-hidden="true"></span>
        <span class="sc-rivet sc-rivet--tr" aria-hidden="true"></span>
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
        :class="`tone-${c.tone}`"
      >
        <span class="nc-rivet nc-rivet--tl" aria-hidden="true"></span>
        <span class="nc-rivet nc-rivet--tr" aria-hidden="true"></span>
        <span class="nc-rivet nc-rivet--bl" aria-hidden="true"></span>
        <span class="nc-rivet nc-rivet--br" aria-hidden="true"></span>
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
/* ==========================================================
   ADMIN DASHBOARD · Ashenforge — Совет старейшин
   ========================================================== */
.adm-root {
  max-width: 1200px;
  margin: 0 auto;
  padding: 44px 24px 80px;
  position: relative;
  color: var(--text-bone);
  min-height: 100vh;
}

/* ── Ambient ember-glows ── */
.adm-glow {
  position: fixed;
  border-radius: 50%;
  filter: blur(130px);
  pointer-events: none;
  z-index: 0;
}
.g1 {
  width: 560px; height: 560px;
  background: radial-gradient(circle, var(--ember-glow) 0%, transparent 70%);
  top: -140px; right: -120px;
  opacity: 0.22;
}
.g2 {
  width: 440px; height: 440px;
  background: radial-gradient(circle, var(--ember-heart) 0%, transparent 70%);
  bottom: 0; left: -100px;
  opacity: 0.18;
}

/* ── Header ── */
.adm-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 24px;
  margin-bottom: 40px;
  position: relative;
  z-index: 1;
}
.tribal-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-family: var(--font-ui);
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: var(--bronze);
  margin-bottom: 12px;
}
.eb-spike {
  width: 0; height: 0;
  border-left: 4px solid transparent;
  border-right: 4px solid transparent;
  border-top: 6px solid var(--bronze);
  filter: drop-shadow(0 0 3px rgba(199, 154, 94, 0.5));
}
.adm-title {
  font-family: var(--font-display);
  font-size: clamp(1.8rem, 3vw, 2.4rem);
  font-weight: var(--fw-black, 900);
  color: var(--text-bright);
  margin: 0 0 10px;
  line-height: 1.2;
  letter-spacing: 0.3px;
  text-shadow: 0 2px 12px rgba(0, 0, 0, 0.55);
}
.adm-comma { color: var(--text-ash); font-weight: 400; }
.adm-name {
  color: var(--ember-gold);
  text-shadow: 0 0 12px rgba(255, 201, 121, 0.4);
}
.adm-sub {
  font-family: var(--font-body);
  color: var(--text-parchment);
  font-size: 1rem;
  margin: 0;
}

.adm-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  background: linear-gradient(180deg,
    rgba(226, 67, 16, 0.18) 0%,
    rgba(138, 31, 24, 0.15) 100%);
  border: 1px solid var(--ember-heart);
  padding: 7px 16px;
  font-family: var(--font-ui);
  font-size: 0.78rem;
  color: var(--ember-gold);
  font-weight: 700;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  white-space: nowrap;
  box-shadow: var(--inset-iron-top), 0 0 10px rgba(226, 67, 16, 0.25);
  clip-path: var(--clip-forged-sm);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
}
.badge-dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: var(--ember-gold);
  box-shadow: 0 0 10px var(--ember-gold);
  animation: pulse 2s ease-in-out infinite;
}
@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.6; transform: scale(0.85); }
}

/* ── Stats row — 4 каменных тайла с заклёпками ── */
.stats-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 40px;
  position: relative;
  z-index: 1;
}
.stat-card {
  position: relative;
  background: linear-gradient(180deg,
    var(--ash-stone) 0%,
    var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  clip-path: var(--clip-forged-sm);
  padding: 24px 22px 18px;
  overflow: hidden;
  box-shadow: var(--inset-iron-top), var(--shadow-cast);
  transition: transform 0.3s var(--ease-forge), box-shadow 0.3s var(--ease-smoke);
}
.stat-card:hover {
  transform: translateY(-4px);
  box-shadow:
    var(--inset-iron-top),
    var(--shadow-cast),
    0 0 18px var(--tone-glow, rgba(226, 67, 16, 0.25));
}
.stat-card.tone-ember  { --tone-color: var(--ember-flame); --tone-glow: rgba(226, 67, 16, 0.3); }
.stat-card.tone-brass  { --tone-color: var(--brass);       --tone-glow: rgba(255, 201, 121, 0.3); }
.stat-card.tone-bronze { --tone-color: var(--bronze);      --tone-glow: rgba(199, 154, 94, 0.3); }

.sc-rivet {
  position: absolute;
  width: 7px; height: 7px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%,
    var(--brass) 0%,
    var(--bronze) 50%,
    var(--iron-void) 100%);
  z-index: 2;
  box-shadow: inset -1px -1px 1px rgba(0, 0, 0, 0.7);
}
.sc-rivet--tl { top: 10px; left: 10px; }
.sc-rivet--tr { top: 10px; right: 10px; }

.stat-val {
  font-family: var(--font-display);
  font-size: 2.1rem;
  font-weight: var(--fw-black, 900);
  color: var(--text-bright);
  line-height: 1;
  margin-bottom: 6px;
  font-variant-numeric: tabular-nums;
  letter-spacing: 0.3px;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
}
.stat-label {
  font-family: var(--font-ui);
  font-size: 0.76rem;
  color: var(--text-ash);
  font-weight: 700;
  letter-spacing: 1.5px;
  text-transform: uppercase;
}
.stat-bar {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 2px;
  background: linear-gradient(90deg,
    transparent 0%,
    var(--tone-color) 50%,
    transparent 100%);
  opacity: 0.7;
}

/* ── Nav cards — каменные плиты с 4 заклёпками ── */
.nav-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 18px;
  position: relative;
  z-index: 1;
}
.nav-card {
  position: relative;
  display: flex;
  align-items: center;
  gap: 18px;
  padding: 24px 22px;
  background: linear-gradient(180deg,
    var(--ash-ironrust) 0%,
    var(--ash-stone) 50%,
    var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  clip-path: var(--clip-forged-sm);
  text-decoration: none;
  overflow: hidden;
  box-shadow: var(--inset-iron-top), var(--shadow-cast);
  transition: transform 0.3s var(--ease-forge), box-shadow 0.3s var(--ease-smoke), border-color 0.3s var(--ease-smoke);
  cursor: pointer;
}
.nav-card.tone-ember  { --tone-color: var(--ember-flame); --tone-glow: rgba(226, 67, 16, 0.3); }
.nav-card.tone-brass  { --tone-color: var(--brass);       --tone-glow: rgba(255, 201, 121, 0.3); }
.nav-card.tone-bronze { --tone-color: var(--bronze);      --tone-glow: rgba(199, 154, 94, 0.3); }
.nav-card:hover {
  transform: translateY(-5px);
  border-color: var(--tone-color);
  box-shadow:
    var(--inset-iron-top),
    var(--shadow-cast),
    0 0 28px var(--tone-glow);
}
.nav-card:hover .nc-glow { opacity: 1; }
.nav-card:hover .nc-arrow { color: var(--tone-color); transform: translateX(4px); }

.nc-rivet {
  position: absolute;
  width: 7px; height: 7px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%,
    var(--brass) 0%,
    var(--bronze) 50%,
    var(--iron-void) 100%);
  z-index: 2;
  box-shadow: inset -1px -1px 1px rgba(0, 0, 0, 0.7);
}
.nc-rivet--tl { top: 10px; left: 10px; }
.nc-rivet--tr { top: 10px; right: 10px; }
.nc-rivet--bl { bottom: 10px; left: 10px; }
.nc-rivet--br { bottom: 10px; right: 10px; }

.nc-glow {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 0% 50%, var(--tone-glow) 0%, transparent 65%);
  opacity: 0;
  transition: opacity 0.4s var(--ease-smoke);
  pointer-events: none;
}

.nc-icon-wrap {
  width: 54px; height: 54px;
  clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
  background: linear-gradient(180deg,
    var(--ash-forge) 0%,
    var(--ash-bloodrock) 100%);
  border: 1px solid var(--bronze-dark);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  color: var(--tone-color);
  box-shadow: var(--inset-iron-top);
  transition: background 0.3s var(--ease-smoke), border-color 0.3s var(--ease-smoke);
}
.nav-card:hover .nc-icon-wrap {
  border-color: var(--tone-color);
  box-shadow: var(--inset-iron-top), 0 0 12px var(--tone-glow);
}
.nc-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  filter: drop-shadow(0 0 4px var(--tone-glow));
}

.nc-body { flex: 1; min-width: 0; }
.nc-title-row { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; }
.nc-title {
  font-family: var(--font-display);
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--text-bright);
  margin: 0;
  letter-spacing: 0.3px;
}
.nc-badge {
  font-family: var(--font-ui);
  font-size: 0.68rem;
  font-weight: 700;
  padding: 3px 10px;
  background: linear-gradient(180deg, rgba(226, 67, 16, 0.25), rgba(138, 31, 24, 0.15));
  color: var(--ember-gold);
  border: 1px solid var(--ember-heart);
  letter-spacing: 1px;
  text-transform: uppercase;
  animation: pulse-badge 2s var(--ease-smoke) infinite;
  box-shadow: 0 0 8px rgba(226, 67, 16, 0.3);
}
@keyframes pulse-badge {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.7; }
}
.nc-desc {
  font-family: var(--font-body);
  font-size: 0.86rem;
  color: var(--text-parchment);
  line-height: 1.55;
  margin: 0;
}

.nc-arrow {
  color: var(--bronze-dark);
  transition: color 0.3s var(--ease-smoke), transform 0.3s var(--ease-forge);
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
