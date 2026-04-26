
<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useHead } from '@vueuse/head';
import { RouterLink } from 'vue-router';
import api from '../api/axios';

const employees = ref([]);

const roleInfo = {
  manager: {
    label: 'Менеджер',
    color: '#3b82f6',
    help: 'Поможет с заказами, вопросами по активации ключей и возвратами.',
    icon: '',
  },
  admin: {
    label: 'Администратор',
    color: '#8b5cf6',
    help: 'Управляет каталогом игр, новостями и настройками магазина.',
    icon: '',
  },
};

const getRoleInfo = (role) => roleInfo[role] || { label: role || 'Сотрудник', color: '#6b7280', help: 'Специалист нашей команды.', icon: '' };
const getInitials = (name) => name ? name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase() : '?';

useHead({
  title: 'О магазине — GameStore',
  meta: [
    { name: 'description', content: 'GameStore — интернет-магазин лицензионных ключей для игр. Узнайте о нашей миссии, ценностях и гарантиях.' },
    { property: 'og:type', content: 'website' },
    { property: 'og:title', content: 'О магазине — GameStore' },
    { property: 'og:description', content: 'GameStore — интернет-магазин лицензионных ключей для игр. Узнайте о нашей миссии, ценностях и гарантиях.' },
    { property: 'og:image', content: '/images.png' },
    { name: 'robots', content: 'index, follow' },
  ],
});

const stats = [
  { num: '500+',    label: 'Игр в каталоге' },
  { num: '12 000+', label: 'Довольных покупателей' },
  { num: '99.8%',  label: 'Ключей активировано' },
  { num: '24/7',   label: 'Поддержка' },
];

const values = [
  {
    icon: '',
    title: 'Безопасность',
    desc: 'Все транзакции защищены SSL-шифрованием. Ключи поставляются только от проверенных официальных дистрибьюторов.',
    color: '#3b82f6',
  },
  {
    icon: '',
    title: 'Мгновенная доставка',
    desc: 'Ключ приходит на e-mail сразу после оплаты — без ожидания, без задержек, в любое время суток.',
    color: '#f59e0b',
  },
  {
    icon: '',
    title: 'Поддержка 24/7',
    desc: 'Наша команда всегда на связи. Любой вопрос — от активации ключа до возврата — решается быстро.',
    color: '#22c55e',
  },
  {
    icon: '',
    title: 'Честные цены',
    desc: 'Мониторим рынок ежедневно и предлагаем лучшие цены без скрытых комиссий и наценок.',
    color: '#8b5cf6',
  },
  {
    icon: '',
    title: 'Лицензионные ключи',
    desc: 'Только официальные лицензии. Каждый ключ проверяется перед продажей и гарантированно активируется.',
    color: '#f43f5e',
  },
  {
    icon: '',
    title: 'Все платформы',
    desc: 'Steam, Epic Games, GOG, Origin, Ubisoft Connect, Xbox, PlayStation — один магазин для всех платформ.',
    color: '#06b6d4',
  },
];

const steps = [
  { num: '1', title: 'Найдите игру', desc: 'Воспользуйтесь поиском или фильтрами каталога', icon: '' },
  { num: '2', title: 'Добавьте в корзину', desc: 'Оформите заказ за несколько кликов', icon: '' },
  { num: '3', title: 'Оплатите', desc: 'Visa, MasterCard, МИР — безопасная транзакция', icon: '' },
  { num: '4', title: 'Получите ключ', desc: 'Мгновенно на e-mail и в личном кабинете', icon: '' },
];

const setupReveal = () => {
  if (!revealObs) return;
  setTimeout(() => {
    document.querySelectorAll('.about-root .reveal').forEach(el => revealObs.observe(el));
  }, 100);
};

let revealObs = null;
onMounted(async () => {
  revealObs = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) { e.target.classList.add('is-visible'); revealObs.unobserve(e.target); }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
  setupReveal();
  try {
    const { data } = await api.get('/employees');
    employees.value = Array.isArray(data) ? data : (data.data || []);
    setupReveal();
  } catch (e) { console.error(e); }
});
onUnmounted(() => revealObs?.disconnect());
</script>

<template>
  <div class="about-root">

    <!-- ═══ HERO ═══ -->
    <section class="ab-hero">
      <div class="ab-blob b1"></div>
      <div class="ab-blob b2"></div>
      <div class="ab-blob b3"></div>
      <div class="ab-grid-overlay"></div>

      <div class="ab-hero-inner">
        <div class="ab-badge reveal">О нас</div>
        <h1 class="ab-hero-title reveal">
          Магазин игр,<br>
          <span class="ab-grad">которому доверяют</span>
        </h1>
        <p class="ab-hero-sub reveal">
          GameStore — это команда геймеров, создавших удобный и безопасный способ<br>
          покупки лицензионных ключей по лучшим ценам.
        </p>
      </div>
    </section>

    <!-- ═══ STATS ═══ -->
    <section class="ab-stats reveal">
      <div class="ab-inner">
        <div class="stats-grid">
          <div v-for="s in stats" :key="s.num" class="stat-item">
            <div class="stat-num">{{ s.num }}</div>
            <div class="stat-label">{{ s.label }}</div>
          </div>
        </div>
      </div>
    </section>

    <div class="ab-inner">

      <!-- ═══ MISSION ═══ -->
      <section class="mission-block reveal">
        <div class="mission-text">
          <span class="section-eyebrow">Наша миссия</span>
          <h2 class="section-title">Доступ к играм — простым и мгновенным</h2>
          <p class="section-body">
            Мы верим, что каждый геймер заслуживает честного доступа к любимым играм.
            GameStore создан командой энтузиастов, которые сами прошли через
            неудобные платформы, завышенные цены и сомнительные продавцы — и решили
            сделать лучше.
          </p>
          <p class="section-body">
            Сегодня мы предлагаем сотни лицензионных ключей для всех популярных
            платформ с гарантированной доставкой и поддержкой на каждом шаге.
          </p>
        </div>
        <div class="mission-visual">
          <div class="mv-card">
            <div class="mv-icon"></div>
            <div class="mv-stat">500+</div>
            <div class="mv-label">игр в каталоге</div>
          </div>
          <div class="mv-card accent">
            <div class="mv-icon"></div>
            <div class="mv-stat">~2 сек</div>
            <div class="mv-label">среднее время доставки</div>
          </div>
          <div class="mv-card">
            <div class="mv-icon"></div>
            <div class="mv-stat">4.9</div>
            <div class="mv-label">средний рейтинг</div>
          </div>
          <div class="mv-card">
            <div class="mv-icon">🔒</div>
            <div class="mv-stat">100%</div>
            <div class="mv-label">безопасных сделок</div>
          </div>
        </div>
      </section>

      <!-- ═══ VALUES ═══ -->
      <section class="values-section">
        <div class="section-head reveal">
          <span class="section-eyebrow">Наши ценности</span>
          <h2 class="section-title">Почему нам доверяют</h2>
        </div>
        <div class="values-grid">
          <div
            v-for="v in values" :key="v.title"
            class="value-card reveal"
            :style="{ '--vc': v.color }"
          >
            <div class="vc-icon">{{ v.icon }}</div>
            <h3 class="vc-title">{{ v.title }}</h3>
            <p class="vc-desc">{{ v.desc }}</p>
            <div class="vc-line"></div>
          </div>
        </div>
      </section>

      <!-- ═══ HOW IT WORKS ═══ -->
      <section class="how-section">
        <div class="section-head reveal">
          <span class="section-eyebrow">Как это работает</span>
          <h2 class="section-title">Четыре шага до игры</h2>
        </div>
        <div class="how-grid">
          <div
            v-for="(step, i) in steps" :key="step.num"
            class="how-card reveal"
            :style="{ '--delay': `${i * 0.1}s` }"
          >
            <div class="how-num">{{ step.num }}</div>
            <div class="how-icon">{{ step.icon }}</div>
            <h3 class="how-title">{{ step.title }}</h3>
            <p class="how-desc">{{ step.desc }}</p>
            <div v-if="i < steps.length - 1" class="how-connector"></div>
          </div>
        </div>
      </section>

      <!-- ═══ TEAM ═══ -->
      <section v-if="employees.length" class="team-section">
        <div class="section-head reveal">
          <span class="section-eyebrow">Наша команда</span>
          <h2 class="section-title">Люди, которые вам помогут</h2>
        </div>
        <div class="team-grid">
          <div
            v-for="emp in employees"
            :key="emp.id"
            class="team-card reveal"
            :style="{ '--tc': getRoleInfo(emp.role).color }"
          >
            <div class="tc-avatar" :style="{ background: `linear-gradient(135deg, ${getRoleInfo(emp.role).color}33, ${getRoleInfo(emp.role).color}15)`, borderColor: `${getRoleInfo(emp.role).color}40` }">
              <span class="tc-initials">{{ getInitials(emp.fullname) }}</span>
              <div class="tc-role-icon">{{ getRoleInfo(emp.role).icon }}</div>
            </div>
            <div class="tc-body">
              <div class="tc-role-badge" :style="{ color: getRoleInfo(emp.role).color, background: `${getRoleInfo(emp.role).color}15`, borderColor: `${getRoleInfo(emp.role).color}30` }">
                {{ getRoleInfo(emp.role).label }}
              </div>
              <h3 class="tc-name">{{ emp.fullname }}</h3>
              <p class="tc-help">
                <span class="tc-help-label">Чем могу помочь:</span>
                {{ getRoleInfo(emp.role).help }}
              </p>
              <a v-if="emp.email" :href="`mailto:${emp.email}`" class="tc-contact">
                ✉ {{ emp.email }}
              </a>
            </div>
            <div class="tc-glow"></div>
          </div>
        </div>
      </section>

      <!-- ═══ CTA ═══ -->
      <section class="cta-block reveal">
        <div class="cta-glow"></div>
        <div class="cta-content">
          <h2 class="cta-title">Готовы начать?</h2>
          <p class="cta-sub">Тысячи игр ждут вас — мгновенно, по честной цене.</p>
          <div class="cta-btns">
            <RouterLink to="/catalog" class="cta-btn primary">Перейти в каталог →</RouterLink>
            <RouterLink to="/register" class="cta-btn ghost">Создать аккаунт</RouterLink>
          </div>
        </div>
      </section>

    </div>
  </div>
</template>

<style scoped>
/* ─── Reveal ─── */
.reveal { opacity: 0; transform: translateY(28px); transition: opacity 0.65s ease, transform 0.65s ease; }
.reveal.is-visible { opacity: 1; transform: none; }

.about-root { color: #e5e7eb; min-height: 100vh; }

/* ─── Hero ─── */
.ab-hero {
  position: relative;
  overflow: hidden;
  padding: 100px 24px 90px;
  text-align: center;
}
.ab-blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  pointer-events: none;
  z-index: 0;
}
.b1 { width: 500px; height: 500px; background: rgba(59,130,246,0.12); top: -120px; left: -100px; animation: blobFloat 8s ease-in-out infinite; }
.b2 { width: 400px; height: 400px; background: rgba(139,92,246,0.1); bottom: -80px; right: -80px; animation: blobFloat 11s ease-in-out infinite reverse; }
.b3 { width: 300px; height: 300px; background: rgba(6,182,212,0.07); top: 50%; left: 50%; transform: translate(-50%,-50%); animation: blobFloat 9s ease-in-out infinite 2s; }
@keyframes blobFloat {
  0%, 100% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(20px, -20px) scale(1.06); }
}
.ab-grid-overlay {
  position: absolute; inset: 0; z-index: 0;
  background-image:
    linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
  background-size: 60px 60px;
  mask-image: radial-gradient(ellipse at center, black 30%, transparent 80%);
}
.ab-hero-inner { position: relative; z-index: 1; max-width: 760px; margin: 0 auto; }

.ab-badge {
  display: inline-block;
  background: rgba(59,130,246,0.15);
  border: 1px solid rgba(59,130,246,0.35);
  color: #60a5fa;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 2.5px;
  text-transform: uppercase;
  padding: 6px 18px;
  border-radius: 999px;
  margin-bottom: 28px;
}
.ab-hero-title {
  font-size: clamp(2.2rem, 5vw, 3.8rem);
  font-weight: 800;
  color: #fff;
  line-height: 1.15;
  margin: 0 0 20px;
}
.ab-grad {
  background: linear-gradient(135deg, #60a5fa, #818cf8, #34d399);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.ab-hero-sub {
  font-size: 1.1rem;
  color: #94a3b8;
  line-height: 1.7;
  margin: 0;
}

/* ─── Stats ─── */
.ab-stats {
  background: rgba(15,23,42,0.7);
  border-top: 1px solid rgba(255,255,255,0.06);
  border-bottom: 1px solid rgba(255,255,255,0.06);
  backdrop-filter: blur(12px);
  padding: 40px 24px;
  margin-bottom: 0;
}
.ab-inner { max-width: 1100px; margin: 0 auto; padding: 0 24px; }
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0;
}
.stat-item {
  text-align: center;
  padding: 16px 24px;
  border-right: 1px solid rgba(255,255,255,0.06);
}
.stat-item:last-child { border-right: none; }
.stat-num {
  font-size: clamp(1.8rem, 3vw, 2.4rem);
  font-weight: 800;
  color: #fff;
  line-height: 1;
  margin-bottom: 6px;
  background: linear-gradient(135deg, #60a5fa, #a78bfa);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.stat-label { font-size: 0.85rem; color: #6b7280; }

/* ─── Shared sections ─── */
.ab-inner > section { padding: 72px 0; }
.section-head { margin-bottom: 48px; }
.section-eyebrow {
  display: block;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 2.5px;
  text-transform: uppercase;
  color: #3b82f6;
  margin-bottom: 12px;
}
.section-title {
  font-size: clamp(1.6rem, 3vw, 2.2rem);
  font-weight: 800;
  color: #fff;
  margin: 0;
}

/* ─── Mission ─── */
.mission-block {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 60px;
  align-items: center;
}
.mission-text { }
.section-body {
  font-size: 1rem;
  line-height: 1.85;
  color: #94a3b8;
  margin: 0 0 16px;
}
.section-body:last-child { margin-bottom: 0; }

.mission-visual {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
.mv-card {
  background: rgba(15,23,42,0.8);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 16px;
  padding: 22px 18px;
  text-align: center;
  backdrop-filter: blur(12px);
  transition: transform 0.3s, border-color 0.3s;
}
.mv-card:hover { transform: translateY(-4px); border-color: rgba(99,102,241,0.4); }
.mv-card.accent {
  background: rgba(59,130,246,0.1);
  border-color: rgba(59,130,246,0.25);
  grid-column: span 2;
}
.mv-icon { font-size: 1.6rem; margin-bottom: 8px; }
.mv-stat { font-size: 1.6rem; font-weight: 800; color: #fff; }
.mv-label { font-size: 0.78rem; color: #6b7280; margin-top: 4px; }

/* ─── Values ─── */
.values-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}
.value-card {
  background: rgba(15,23,42,0.75);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 18px;
  padding: 28px 24px;
  position: relative;
  overflow: hidden;
  backdrop-filter: blur(12px);
  transition: transform 0.3s, border-color 0.3s, box-shadow 0.3s;
}
.value-card:hover {
  transform: translateY(-6px);
  border-color: var(--vc);
  box-shadow: 0 16px 40px rgba(0,0,0,0.4), 0 0 30px rgba(0,0,0,0.2);
}
.vc-line {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 2px;
  background: var(--vc);
  opacity: 0;
  transition: opacity 0.3s;
}
.value-card:hover .vc-line { opacity: 0.7; }
.vc-icon { font-size: 2rem; margin-bottom: 14px; }
.vc-title { font-size: 1.05rem; font-weight: 700; color: #f1f5f9; margin: 0 0 10px; }
.vc-desc { font-size: 0.88rem; color: #6b7280; line-height: 1.65; margin: 0; }

/* ─── How it works ─── */
.how-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0;
  position: relative;
}
.how-card {
  position: relative;
  padding: 32px 24px;
  background: rgba(15,23,42,0.6);
  border: 1px solid rgba(255,255,255,0.06);
  border-radius: 18px;
  text-align: center;
  margin: 0 6px;
  transition: transform 0.3s, border-color 0.3s;
  transition-delay: var(--delay, 0s);
}
.how-card:hover { transform: translateY(-6px); border-color: rgba(99,102,241,0.4); }
.how-connector {
  position: absolute;
  top: 44px;
  right: -18px;
  width: 30px;
  height: 2px;
  background: rgba(99,102,241,0.3);
  z-index: 1;
}
.how-num {
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 2px;
  color: #3b82f6;
  margin-bottom: 14px;
}
.how-icon { font-size: 2rem; margin-bottom: 12px; }
.how-title { font-size: 1rem; font-weight: 700; color: #f1f5f9; margin: 0 0 8px; }
.how-desc { font-size: 0.82rem; color: #6b7280; line-height: 1.6; margin: 0; }

/* ─── Team ─── */
.team-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}
.team-card {
  position: relative;
  display: flex;
  gap: 20px;
  align-items: flex-start;
  background: rgba(15,23,42,0.75);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 20px;
  padding: 28px;
  overflow: hidden;
  backdrop-filter: blur(14px);
  transition: transform 0.3s, border-color 0.3s, box-shadow 0.3s;
}
.team-card:hover {
  transform: translateY(-6px);
  border-color: var(--tc);
  box-shadow: 0 20px 40px rgba(0,0,0,0.4);
}
.team-card:hover .tc-glow { opacity: 1; }
.tc-glow {
  position: absolute; inset: 0;
  background: radial-gradient(circle at 0% 0%, var(--tc) 0%, transparent 60%);
  opacity: 0;
  transition: opacity 0.4s;
  pointer-events: none;
  mix-blend-mode: screen;
  filter: blur(20px);
}
.tc-avatar {
  width: 64px; height: 64px;
  border-radius: 18px;
  border: 1px solid;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  position: relative;
}
.tc-initials {
  font-size: 1.3rem;
  font-weight: 800;
  color: #fff;
  letter-spacing: 1px;
}
.tc-role-icon {
  position: absolute;
  bottom: -6px; right: -6px;
  width: 22px; height: 22px;
  background: rgba(10,15,30,0.9);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.75rem;
  border: 1px solid rgba(255,255,255,0.1);
}
.tc-body { flex: 1; min-width: 0; }
.tc-role-badge {
  display: inline-block;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  padding: 3px 10px;
  border-radius: 999px;
  border: 1px solid;
  margin-bottom: 8px;
}
.tc-name {
  font-size: 1.05rem;
  font-weight: 700;
  color: #f1f5f9;
  margin: 0 0 10px;
}
.tc-help {
  font-size: 0.85rem;
  color: #94a3b8;
  line-height: 1.6;
  margin: 0 0 14px;
}
.tc-help-label {
  display: block;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: uppercase;
  color: #6b7280;
  margin-bottom: 4px;
}
.tc-contact {
  font-size: 0.82rem;
  color: #60a5fa;
  text-decoration: none;
  border-bottom: 1px solid rgba(96,165,250,0.25);
  transition: color 0.2s, border-color 0.2s;
  word-break: break-all;
}
.tc-contact:hover { color: #93c5fd; border-color: #93c5fd; }

/* ─── CTA ─── */
.cta-block {
  position: relative;
  border-radius: 24px;
  overflow: hidden;
  background: rgba(15,23,42,0.8);
  border: 1px solid rgba(99,102,241,0.2);
  padding: 64px 48px;
  text-align: center;
  backdrop-filter: blur(16px);
}
.cta-glow {
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at center, rgba(99,102,241,0.12) 0%, transparent 70%);
  pointer-events: none;
}
.cta-content { position: relative; z-index: 1; }
.cta-title { font-size: clamp(1.8rem, 3vw, 2.6rem); font-weight: 800; color: #fff; margin: 0 0 12px; }
.cta-sub { font-size: 1.05rem; color: #94a3b8; margin: 0 0 32px; }
.cta-btns { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
.cta-btn {
  padding: 14px 32px;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 700;
  text-decoration: none;
  transition: all 0.25s;
}
.cta-btn.primary {
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: #fff;
  box-shadow: 0 6px 24px rgba(59,130,246,0.35);
}
.cta-btn.primary:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(59,130,246,0.5); }
.cta-btn.ghost {
  border: 1px solid rgba(255,255,255,0.15);
  color: #d1d5db;
  background: rgba(255,255,255,0.05);
}
.cta-btn.ghost:hover { border-color: rgba(255,255,255,0.35); color: #fff; background: rgba(255,255,255,0.1); }

/* ─── Responsive ─── */
@media (max-width: 1024px) {
  .mission-block { grid-template-columns: 1fr; gap: 40px; }
  .values-grid { grid-template-columns: repeat(2, 1fr); }
  .how-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
  .how-connector { display: none; }
  .how-card { margin: 0; }
}
@media (max-width: 640px) {
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
  .stat-item { border-right: none; border-bottom: 1px solid rgba(255,255,255,0.06); }
  .stat-item:nth-child(odd) { border-right: 1px solid rgba(255,255,255,0.06); }
  .stat-item:last-child, .stat-item:nth-last-child(2):nth-child(odd) { border-bottom: none; }
  .values-grid { grid-template-columns: 1fr; }
  .how-grid { grid-template-columns: 1fr; }
  .cta-block { padding: 40px 24px; }
  .ab-hero { padding: 70px 24px 60px; }
}
</style>
