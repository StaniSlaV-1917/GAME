<template>
  <main class="home-page">

    <!-- ===================================================
         HERO · "Оплот воина"
    =================================================== -->
    <section class="hero-section">
      <!-- Фон: закатное небо Оргриммара -->
      <div class="hero-sky"></div>

      <!-- Мерцающие звёзды в верхней части неба -->
      <div class="hero-stars" aria-hidden="true">
        <span v-for="n in 14" :key="`star-${n}`" class="hero-star" :style="{ '--i': n }"></span>
      </div>

      <!-- Дрейфующие облака-дымка через небо -->
      <div class="hero-clouds" aria-hidden="true">
        <span class="hero-cloud hero-cloud--1"></span>
        <span class="hero-cloud hero-cloud--2"></span>
        <span class="hero-cloud hero-cloud--3"></span>
      </div>

      <!-- Аврора/полоса свечения на горизонте — пульсирующая -->
      <div class="hero-aurora" aria-hidden="true"></div>

      <!-- Горы-силуэты вдалеке -->
      <div class="hero-mountains">
        <svg viewBox="0 0 1600 200" preserveAspectRatio="none" aria-hidden="true">
          <path d="M0,200 L0,130 L80,80 L160,110 L240,60 L340,95 L430,40 L520,85 L620,50 L720,100 L820,45 L920,90 L1020,55 L1130,95 L1230,60 L1340,100 L1440,70 L1530,105 L1600,85 L1600,200 Z" fill="currentColor"/>
        </svg>
      </div>

      <!-- Дальняя наковальня с ритмичным «ударом молота» каждые ~7 сек.
           На каждом ударе — короткая ember-вспышка позади + лёгкий
           горизонтальный shake фоновых слоёв (через CSS-anim синхронизированы). -->
      <div class="hero-anvil" aria-hidden="true">
        <svg viewBox="0 0 120 60" preserveAspectRatio="xMidYMax meet" class="hero-anvil-svg">
          <path d="M 14 22 L 106 22 L 100 32 L 76 32 L 76 44 L 88 44 L 88 52 L 32 52 L 32 44 L 44 44 L 44 32 L 20 32 Z" fill="currentColor"/>
        </svg>
        <span class="anvil-strike-flash" aria-hidden="true"></span>
      </div>

      <!-- Раскалённый горн снизу -->
      <div class="hero-forge-glow"></div>

      <!-- Тепловое марево над горизонтом — едва заметная дрожь -->
      <div class="hero-shimmer" aria-hidden="true"></div>

      <!-- Летящие угли — увеличены с 12 до 24, разные размеры/скорости -->
      <div class="hero-embers" aria-hidden="true">
        <span v-for="n in 24" :key="n" class="ember-particle" :style="{ '--i': n }"></span>
      </div>

      <!-- Свисающий баннер Орды -->
      <div class="hero-banner-wrap" aria-hidden="true">
        <div class="hero-banner">
          <span class="banner-rope"></span>
          <span class="banner-cloth">
            <span class="banner-sigil">⚔</span>
            <span class="banner-sigil-glow" aria-hidden="true"></span>
          </span>
        </div>
      </div>

      <div class="hero-content">
        <div class="hero-badge">
          <span class="badge-spike"></span>
          Новые поступления в оружейной
          <span class="badge-spike right"></span>
        </div>

        <h1 class="hero-title">
          <span class="hero-title-line">Оплот</span>
          <span class="hero-title-line hero-title-accent">воина</span>
        </h1>
        <p class="hero-tagline">Кузница клана. Тысячи лицензионных ключей для&nbsp;настоящих воинов.</p>
        <p class="hero-subtitle">
          Steam, Epic Games, GOG и другие платформы.
          Мгновенная доставка, честь и&nbsp;верность.
        </p>

        <div class="hero-actions">
          <router-link to="/catalog" class="hero-btn primary">
            <span class="btn-icon">⚔</span>
            <span>В оружейную</span>
            <span class="btn-arrow">→</span>
          </router-link>
          <router-link to="/news" class="hero-btn secondary">
            <span>Хроники</span>
          </router-link>
        </div>

        <!-- Каменные плитки со статистикой -->
        <div class="hero-stats">
          <div class="hstat">
            <span class="hstat-num">500<span class="hstat-plus">+</span></span>
            <span class="hstat-label">Игр в арсенале</span>
          </div>
          <div class="hstat-sep" aria-hidden="true"><span></span></div>
          <div class="hstat">
            <span class="hstat-num">12&nbsp;000<span class="hstat-plus">+</span></span>
            <span class="hstat-label">Воинов в клане</span>
          </div>
          <div class="hstat-sep" aria-hidden="true"><span></span></div>
          <div class="hstat">
            <span class="hstat-num">99.8<span class="hstat-plus">%</span></span>
            <span class="hstat-label">Ключей активировано</span>
          </div>
        </div>
      </div>
    </section>

    <!-- ===================================================
         PLATFORMS · каменная лента с платформами
    =================================================== -->
    <div class="platforms-strip">
      <div class="platforms-edge platforms-edge-l" aria-hidden="true"></div>
      <div class="platforms-edge platforms-edge-r" aria-hidden="true"></div>
      <div class="platforms-track">
        <span
          v-for="(p, idx) in [...platforms, ...platforms]"
          :key="`${p.id}-${idx}`"
          class="platform-item"
        >
          <SvgIcon :icon="p.icon" :size="22" class="platform-icon" />
          <span class="platform-name">{{ p.name }}</span>
        </span>
      </div>
    </div>

    <!-- ===================================================
         GAME CAROUSEL
    =================================================== -->
    <section class="home-section game-carousel-section">
      <GameCarousel />
    </section>

    <div class="page-content-wrapper">

      <!-- ===================================================
           FEATURES · преимущества
      =================================================== -->
      <section class="home-section features-section">
        <div class="section-head">
          <div class="tribal-eyebrow">
            <span class="eyebrow-spike"></span>
            <span class="eyebrow-text">Наши преимущества</span>
            <span class="eyebrow-spike right"></span>
          </div>
          <h2 class="section-title">Почему выбирают нас</h2>
        </div>
        <div class="features-grid">
          <div v-for="(f, i) in features" :key="f.title" class="feature-card" :style="{ '--i': i }">
            <span class="rivet rivet-tl" aria-hidden="true"></span>
            <span class="rivet rivet-tr" aria-hidden="true"></span>
            <span class="rivet rivet-bl" aria-hidden="true"></span>
            <span class="rivet rivet-br" aria-hidden="true"></span>
            <div class="feature-icon-wrap">
              <span class="feature-icon-halo"></span>
              <span class="feature-icon">{{ f.icon }}</span>
            </div>
            <h3 class="feature-title">{{ f.title }}</h3>
            <p class="feature-desc">{{ f.desc }}</p>
          </div>
        </div>
      </section>

      <!-- ===================================================
           HOW · "Путь воина"
      =================================================== -->
      <section class="home-section how-section">
        <div class="section-head">
          <div class="tribal-eyebrow">
            <span class="eyebrow-spike"></span>
            <span class="eyebrow-text">Просто и быстро</span>
            <span class="eyebrow-spike right"></span>
          </div>
          <h2 class="section-title">Путь воина</h2>
        </div>
        <div class="steps-grid">
          <div v-for="(step, i) in steps" :key="i" class="step-card" :style="{ '--i': i }">
            <div class="step-num-wrap">
              <div class="step-num">{{ i + 1 }}</div>
              <div class="step-num-glow"></div>
            </div>
            <div class="step-connector" v-if="i < steps.length - 1" aria-hidden="true">
              <span class="step-connector-line"></span>
              <span class="step-connector-spike"></span>
            </div>
            <div class="step-icon" aria-hidden="true">{{ step.icon }}</div>
            <h3 class="step-title">{{ step.title }}</h3>
            <p class="step-desc">{{ step.desc }}</p>
          </div>
        </div>
      </section>

      <!-- ===================================================
           FAQ · "Свитки мудрецов"
      =================================================== -->
      <section class="home-section faq-section">
        <div class="section-head">
          <div class="tribal-eyebrow">
            <span class="eyebrow-spike"></span>
            <span class="eyebrow-text">Часто задаваемые вопросы</span>
            <span class="eyebrow-spike right"></span>
          </div>
          <h2 class="section-title">Свитки мудрецов</h2>
        </div>
        <div class="faq-container">
          <div
            v-for="item in faqItems"
            :key="item.id"
            class="faq-item"
            :class="{ open: openFaqItem === item.id }"
          >
            <div class="faq-question" @click="toggleFaq(item.id)">
              <span class="faq-question-text">{{ item.question }}</span>
              <span class="faq-icon" aria-hidden="true">
                <span class="faq-icon-inner"></span>
              </span>
            </div>
            <Transition name="faq-slide">
              <div v-if="openFaqItem === item.id" class="faq-answer">
                <p>{{ item.answer }}</p>
              </div>
            </Transition>
          </div>
        </div>
      </section>

    </div>
  </main>
</template>

<script setup>
import { ref } from 'vue';
import { useHead } from '@vueuse/head';
import GameCarousel from '../components/GameCarousel.vue';
import SvgIcon from '@/components/SvgIcon.vue';

useHead({
  title: 'GameStore — Кузница воина',
  meta: [
    { name: 'description', content: 'Магазин лицензионных ключей для игр. Steam, Epic Games, GOG и другие платформы по выгодным ценам.' },
    { property: 'og:type', content: 'website' },
    { property: 'og:title', content: 'GameStore — Кузница воина' },
    { property: 'og:description', content: 'Магазин лицензионных ключей для игр. Steam, Epic Games, GOG и другие платформы по выгодным ценам.' },
    { property: 'og:image', content: '/images.png' },
    { name: 'robots', content: 'index, follow' },
  ],
});

const platforms = ref([
  { id: 1, icon: 'steam',               name: 'Steam' },
  { id: 2, icon: 'epic-games',          name: 'Epic Games' },
  { id: 3, icon: 'gog-com-svgrepo-com', name: 'GOG' },
  { id: 4, icon: 'origin-1',            name: 'Origin' },
  { id: 5, icon: 'ubisoft-logo',        name: 'Ubisoft Connect' },
  { id: 6, icon: 'battle-net',          name: 'Battle.net' },
  { id: 7, icon: 'xbox-9',              name: 'Xbox' },
  { id: 8, icon: 'playstation-4',       name: 'PlayStation' },
]);

const features = ref([
  { icon: '⚡', title: 'Мгновенная доставка', desc: 'Ключ приходит на почту сразу после оплаты — ни ожидания, ни задержек.' },
  { icon: '⚔', title: 'Гарантия качества',   desc: 'Только официальные издатели. Валидность каждого ключа гарантирована честью.' },
  { icon: '☾',  title: 'Поддержка 24/7',      desc: 'Клан всегда на страже. Ответим на вопрос в любое время суток.' },
  { icon: '◈', title: 'Удобная оплата',      desc: 'Visa, MasterCard, МИР и другие способы. Безопасные транзакции.' },
]);

const steps = ref([
  { icon: '⌕', title: 'Найди', desc: 'Через поиск или каталог выбери нужную игру' },
  { icon: '☰', title: 'Забери', desc: 'Оформи заказ за пару кликов' },
  { icon: '◈', title: 'Оплати', desc: 'Выбери удобный способ оплаты' },
  { icon: '⚔', title: 'Играй', desc: 'Ключ мгновенно придёт на твою почту' },
]);

const faqItems = ref([
  { id: 1, question: 'Как я получу купленную игру?', answer: 'Сразу после оплаты ключ будет отправлен на твою электронную почту. Также он будет доступен в личном кабинете в разделе «Мои покупки».' },
  { id: 2, question: 'Принимаете ли вы карты всех банков?', answer: 'Мы принимаем Visa, MasterCard и МИР большинства банков. При проблемах с оплатой свяжись с нашей поддержкой.' },
  { id: 3, question: 'Что делать, если ключ не работает?', answer: 'Свяжись с поддержкой, предоставь номер заказа и скриншот ошибки. Мы оперативно заменим ключ или вернём деньги.' },
  { id: 4, question: 'Могу ли я вернуть игру?', answer: 'Цифровые ключи не подлежат возврату после активации. Пожалуйста, внимательно изучи системные требования и описание до покупки.' },
  { id: 5, question: 'На какой платформе активировать ключ?', answer: 'Платформа (Steam, Epic Games, GOG и т.д.) всегда указана на странице товара. Убедись, что у тебя есть аккаунт в соответствующем сервисе.' },
]);

const openFaqItem = ref(null);
const toggleFaq = (id) => { openFaqItem.value = openFaqItem.value === id ? null : id; };
</script>

<style scoped>
/* ============================================================
   ASHENFORGE · HomeView
   ============================================================ */

@keyframes emberRise {
  0%   { opacity: 0; transform: translate(var(--x, 0), 0) scale(0.6); }
  15%  { opacity: 1; }
  100% { opacity: 0; transform: translate(calc(var(--x, 0) + 40px), -180px) scale(1.2); }
}

@keyframes bannerDrop {
  /* ВАЖНО: в keyframe transform должен включать translateX(-50%) — иначе
     анимация затирает базовое центрирование .hero-banner-wrap и знамя
     уезжает вправо на половину своей ширины. */
  from { transform: translate(-50%, -24px); opacity: 0; }
  to   { transform: translate(-50%, 0);     opacity: 1; }
}

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(30px); }
  to   { opacity: 1; transform: translateY(0); }
}

@keyframes tickerScroll {
  from { transform: translateX(0); }
  to   { transform: translateX(-50%); }
}

.home-page {
  color: var(--text-bone);
  width: 100%;
  overflow-x: hidden;
  position: relative;
}

/* ==========================================================
   HERO
   ========================================================== */
.hero-section {
  position: relative;
  min-height: 92vh;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 160px 24px 120px;
  overflow: hidden;
  isolation: isolate;
}

/* Небо заката */
.hero-sky {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse 120% 80% at 50% 100%, rgba(226, 67, 16, 0.25) 0%, transparent 55%),
    radial-gradient(ellipse 90% 55% at 50% 20%, rgba(138, 31, 24, 0.22) 0%, transparent 60%),
    linear-gradient(180deg,
      #0a0806 0%,
      #14100c 25%,
      #2a1612 55%,
      #5a1412 80%,
      #8a1f18 100%);
  z-index: -2;
}
.hero-sky::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image:
    radial-gradient(2px 2px at 20% 30%, rgba(255, 201, 121, 0.55), transparent),
    radial-gradient(1.5px 1.5px at 70% 15%, rgba(255, 201, 121, 0.5), transparent),
    radial-gradient(1px 1px at 45% 25%, rgba(199, 154, 94, 0.6), transparent),
    radial-gradient(1px 1px at 85% 35%, rgba(199, 154, 94, 0.5), transparent),
    radial-gradient(1.5px 1.5px at 15% 20%, rgba(212, 181, 106, 0.6), transparent),
    radial-gradient(1px 1px at 90% 10%, rgba(199, 154, 94, 0.55), transparent);
  background-size: 100% 100%;
  opacity: 0.7;
}

/* Silhouette гор */
.hero-mountains {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 28vh;
  min-height: 220px;
  color: #0a0704;
  z-index: -1;
  filter: drop-shadow(0 -4px 8px rgba(138, 31, 24, 0.3));
}
.hero-mountains svg { display: block; width: 100%; height: 100%; }

/* Раскалённый горн снизу */
.hero-forge-glow {
  position: absolute;
  bottom: -80px; left: 50%;
  transform: translateX(-50%);
  width: 120%; height: 340px;
  background: radial-gradient(ellipse 50% 80% at 50% 100%,
    rgba(255, 122, 43, 0.55) 0%,
    rgba(226, 67, 16, 0.35) 25%,
    rgba(138, 31, 24, 0.18) 50%,
    transparent 75%);
  filter: blur(16px);
  z-index: -1;
  animation: emberPulse 4s ease-in-out infinite;
  pointer-events: none;
}

/* ── Мерцающие звёзды в верхней части неба ── */
.hero-stars {
  position: absolute;
  inset: 0 0 50% 0;       /* только верхняя половина */
  pointer-events: none;
  z-index: -2;
  overflow: hidden;
}
.hero-star {
  position: absolute;
  /* Распределение по экрану: x = (i * 7%) mod 100, y по высоте — pseudo-random */
  left: calc((var(--i) * 7.3%) - (var(--i) * 100% * 0));
  top: calc((var(--i) * 6.7%) + 4%);
  width: 2px;
  height: 2px;
  border-radius: 50%;
  background: var(--text-bone);
  box-shadow: 0 0 4px rgba(248, 232, 200, 0.85);
  opacity: 0;
  animation: starTwinkle 4.5s ease-in-out infinite;
  animation-delay: calc(var(--i) * -0.32s);
}
@keyframes starTwinkle {
  0%, 100% { opacity: 0.2; transform: scale(0.7); }
  50%      { opacity: 0.95; transform: scale(1.1); }
}
/* Каждая 5-я звезда чуть ярче и крупнее — для разнообразия */
.hero-star:nth-child(5n) {
  width: 3px; height: 3px;
  box-shadow: 0 0 8px rgba(255, 201, 121, 0.7);
  background: var(--ember-gold);
}

/* ── Дрейфующие облака-дымка через небо ── */
.hero-clouds {
  position: absolute;
  inset: 0 0 40% 0;       /* верхние 60% */
  pointer-events: none;
  z-index: -2;
  overflow: hidden;
}
.hero-cloud {
  position: absolute;
  width: clamp(280px, 40vw, 540px);
  height: clamp(60px, 8vw, 100px);
  background: linear-gradient(90deg,
    transparent 0%,
    rgba(122, 93, 72, 0.18) 30%,
    rgba(58, 42, 34, 0.22) 50%,
    rgba(122, 93, 72, 0.18) 70%,
    transparent 100%);
  filter: blur(28px);
  border-radius: 50%;
  opacity: 0.6;
  animation: cloudDrift 65s linear infinite;
}
.hero-cloud--1 { top: 18%; animation-duration: 68s; animation-delay: 0s; }
.hero-cloud--2 { top: 32%; animation-duration: 92s; animation-delay: -22s; opacity: 0.45; }
.hero-cloud--3 { top: 8%;  animation-duration: 78s; animation-delay: -48s; opacity: 0.55; }
@keyframes cloudDrift {
  0%   { transform: translateX(-30vw); }
  100% { transform: translateX(130vw); }
}

/* ── Аврора/полоса свечения на горизонте ── */
.hero-aurora {
  position: absolute;
  bottom: 22%;
  left: -10%;
  right: -10%;
  height: 4px;
  background: linear-gradient(90deg,
    transparent 0%,
    rgba(226, 67, 16, 0.55) 35%,
    rgba(255, 122, 43, 0.7) 50%,
    rgba(226, 67, 16, 0.55) 65%,
    transparent 100%);
  filter: blur(12px);
  z-index: -1;
  animation: auroraPulse 6s ease-in-out infinite;
}
@keyframes auroraPulse {
  0%, 100% { opacity: 0.55; transform: scaleY(1); }
  50%      { opacity: 1;    transform: scaleY(1.4); }
}

/* ── Дальняя наковальня с ритмичным "ударом" ── */
.hero-anvil {
  position: absolute;
  bottom: 18%;
  left: 50%;
  transform: translateX(-50%);
  width: clamp(80px, 12vw, 130px);
  z-index: -1;
  pointer-events: none;
  filter: drop-shadow(0 0 12px rgba(226, 67, 16, 0.4));
}
.hero-anvil-svg {
  width: 100%;
  display: block;
  color: rgba(8, 6, 10, 0.92);
  animation: anvilStrike 7s ease-in-out infinite;
}
@keyframes anvilStrike {
  /* Шейк-эффект: каждые 7 сек короткий «удар» — наковальня дрожит +
     ember-flash позади. Большую часть времени — статика. */
  0%, 70%, 100% { transform: translate(0, 0); }
  72%           { transform: translate(0, 1.5px) scale(1.005); }
  74%           { transform: translate(-0.5px, 0); }
  76%           { transform: translate(0, 0); }
}
.anvil-strike-flash {
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 200%;
  height: 80%;
  background: radial-gradient(ellipse at center bottom,
    rgba(255, 201, 121, 0.7) 0%,
    rgba(255, 122, 43, 0.45) 25%,
    transparent 65%);
  filter: blur(12px);
  opacity: 0;
  animation: anvilFlash 7s ease-in-out infinite;
}
@keyframes anvilFlash {
  0%, 70%, 100% { opacity: 0;    transform: translateX(-50%) scale(0.6); }
  72%           { opacity: 0.85; transform: translateX(-50%) scale(1.2); }
  78%           { opacity: 0.4;  transform: translateX(-50%) scale(1.5); }
  85%           { opacity: 0;    transform: translateX(-50%) scale(1.7); }
}

/* ── Тепловое марево над горизонтом (едва заметное) ── */
.hero-shimmer {
  position: absolute;
  bottom: 20%;
  left: 0;
  right: 0;
  height: 18%;
  background: repeating-linear-gradient(
    180deg,
    rgba(255, 122, 43, 0.04) 0,
    rgba(255, 122, 43, 0.04) 2px,
    transparent 2px,
    transparent 5px
  );
  filter: blur(2px);
  pointer-events: none;
  z-index: -1;
  animation: shimmerWave 3s ease-in-out infinite;
}
@keyframes shimmerWave {
  0%, 100% { transform: translateY(0)    skewY(0deg); }
  50%      { transform: translateY(-2px) skewY(0.3deg); }
}

/* Летящие угли — увеличено разнообразие через nth-child */
.hero-embers {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: -1;
  overflow: hidden;
}
.ember-particle {
  position: absolute;
  bottom: 10%;
  /* Распределение: var(--i) от 1 до 24, x = i * 4% — равномерно по ширине */
  left: calc((var(--i) * 4%) + 2%);
  width: 4px; height: 4px;
  border-radius: 50%;
  background: radial-gradient(circle, var(--ember-gold), var(--ember-flame));
  box-shadow: 0 0 8px rgba(255, 122, 43, 0.8);
  animation: emberRise 7s ease-out infinite;
  animation-delay: calc(var(--i) * -0.3s);
  --x: calc((var(--i) * 3px) - 36px);
  opacity: 0;
}
/* Разнообразие частиц: каждая 3-я мельче и быстрее, каждая 4-я крупнее */
.ember-particle:nth-child(3n)   { width: 2px; height: 2px; animation-duration: 5s; }
.ember-particle:nth-child(4n)   { width: 6px; height: 6px; box-shadow: 0 0 14px rgba(255, 167, 88, 0.9); }
.ember-particle:nth-child(5n+1) { animation-duration: 9s; }   /* медленные плавающие */

/* Свисающий баннер */
.hero-banner-wrap {
  position: absolute;
  top: 0; left: 50%;
  transform: translateX(-50%);
  z-index: 1;
  animation: bannerDrop 0.8s var(--ease-forge) 0.2s both;
  pointer-events: none;
}
.hero-banner {
  display: flex;
  flex-direction: column;
  align-items: center;
}
.banner-rope {
  width: 2px;
  height: 30px;
  background: linear-gradient(180deg, var(--iron-void) 0%, var(--iron-mid) 100%);
}
.banner-cloth {
  position: relative;
  width: 80px;
  height: 110px;
  background: var(--grad-ember-banner);
  clip-path: polygon(0 0, 100% 0, 100% 85%, 50% 100%, 0 85%);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow:
    inset 0 3px 0 rgba(255, 201, 121, 0.2),
    inset 0 -3px 0 rgba(0, 0, 0, 0.35),
    0 8px 16px rgba(0, 0, 0, 0.5);
  animation: bannerSway 5s ease-in-out infinite;
  transform-origin: top center;
}
.banner-sigil {
  position: relative;
  z-index: 1;
  font-family: var(--font-display);
  font-size: 1.4rem;
  color: var(--ember-gold);
  text-shadow: 0 0 12px rgba(255, 201, 121, 0.9);
  margin-top: -16px;
}

/* Пульсирующий halo позади сигила — будто символ светится изнутри */
.banner-sigil-glow {
  position: absolute;
  top: 38%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: radial-gradient(circle,
    rgba(255, 201, 121, 0.65) 0%,
    rgba(255, 122, 43, 0.4) 40%,
    transparent 70%);
  filter: blur(4px);
  animation: bannerSigilGlow 3.5s ease-in-out infinite;
  pointer-events: none;
}
@keyframes bannerSigilGlow {
  0%, 100% { opacity: 0.5; transform: translate(-50%, -50%) scale(0.85); }
  50%      { opacity: 1;   transform: translate(-50%, -50%) scale(1.2); }
}

/* Hero content */
.hero-content {
  position: relative;
  z-index: 3;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--sp-5);
  animation: fadeUp 0.8s var(--ease-forge) both;
  max-width: 780px;
}

/* Badge */
.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-family: var(--font-display);
  font-size: 0.78rem;
  font-weight: var(--fw-semibold);
  color: var(--brass);
  background: linear-gradient(180deg, rgba(42, 10, 8, 0.7) 0%, rgba(18, 16, 13, 0.6) 100%);
  border: 1px solid var(--iron-mid);
  padding: 8px 20px;
  text-transform: uppercase;
  letter-spacing: var(--ls-wider);
  box-shadow:
    inset 0 1px 0 rgba(199, 154, 94, 0.15),
    0 0 18px rgba(194, 40, 26, 0.25);
  backdrop-filter: blur(8px);
  clip-path: polygon(10px 0, calc(100% - 10px) 0, 100% 50%, calc(100% - 10px) 100%, 10px 100%, 0 50%);
}
.badge-spike {
  width: 0; height: 0;
  border-left: 4px solid transparent;
  border-right: 4px solid transparent;
  border-top: 6px solid var(--ember-heart);
  filter: drop-shadow(0 0 4px rgba(194, 40, 26, 0.6));
}
.badge-spike.right { transform: rotate(180deg); }

/* Title */
.hero-title {
  font-family: var(--font-display);
  font-weight: var(--fw-black);
  font-size: var(--fs-display);
  line-height: 0.95;
  margin: 0;
  letter-spacing: var(--ls-tight);
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.hero-title-line {
  color: var(--text-bright);
  text-shadow:
    0 2px 0 rgba(0, 0, 0, 0.7),
    0 4px 14px rgba(0, 0, 0, 0.8),
    0 0 40px rgba(194, 40, 26, 0.15);
}
.hero-title-accent {
  background: var(--grad-ember-text);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  filter: drop-shadow(0 0 24px rgba(226, 67, 16, 0.5));
}

/* Tagline — Spectral italic */
.hero-tagline {
  font-family: var(--font-body);
  font-style: italic;
  font-size: 1.25rem;
  color: var(--text-bone);
  margin: 0;
  max-width: 620px;
  line-height: 1.55;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
}

.hero-subtitle {
  font-family: var(--font-body);
  font-size: 1rem;
  color: var(--text-parchment);
  max-width: 560px;
  line-height: 1.7;
  margin: 0;
}

/* Actions */
.hero-actions {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
  justify-content: center;
  margin-top: 6px;
}
.hero-btn {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 14px 28px;
  font-family: var(--font-display);
  font-size: 0.95rem;
  font-weight: var(--fw-bold);
  text-transform: uppercase;
  letter-spacing: var(--ls-wide);
  text-decoration: none;
  border-radius: var(--r-xs);
  border: 1px solid transparent;
  transition: all var(--dur-med) var(--ease-smoke);
  position: relative;
  overflow: hidden;
}
.btn-icon { font-size: 1.1rem; }
.btn-arrow { transition: transform var(--dur-fast); }

.hero-btn.primary {
  background: var(--grad-ember);
  color: var(--text-bright);
  border-color: var(--ember-heart);
  box-shadow:
    var(--glow-ember-soft),
    var(--inset-iron-top),
    inset 0 -3px 4px rgba(0, 0, 0, 0.35);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.7);
}
.hero-btn.primary::before {
  content: '';
  position: absolute;
  top: 0; left: -120%;
  width: 50%; height: 100%;
  background: linear-gradient(90deg,
    transparent,
    rgba(255, 201, 121, 0.4),
    transparent);
  transform: skewX(-20deg);
  transition: left 0.7s var(--ease-smoke);
}
.hero-btn.primary:hover {
  filter: brightness(1.1) saturate(1.1);
  box-shadow: var(--glow-ember-strong), var(--inset-iron-top), inset 0 -3px 4px rgba(0, 0, 0, 0.35);
  transform: translateY(-2px);
}
.hero-btn.primary:hover::before { left: 120%; }
.hero-btn.primary:hover .btn-arrow { transform: translateX(4px); }

.hero-btn.secondary {
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  color: var(--text-parchment);
  border-color: var(--iron-mid);
  box-shadow: var(--inset-iron-top);
}
.hero-btn.secondary:hover {
  color: var(--text-bright);
  border-color: var(--iron-warm);
  background: linear-gradient(180deg, var(--ash-ironrust) 0%, var(--ash-stone) 100%);
  box-shadow: var(--glow-ember-soft), var(--inset-iron-top);
  transform: translateY(-1px);
}

/* Hero stats */
.hero-stats {
  display: flex;
  align-items: stretch;
  gap: 0;
  padding: 4px;
  background: linear-gradient(180deg, rgba(42, 10, 8, 0.7) 0%, rgba(18, 16, 13, 0.7) 100%);
  border: 1px solid var(--iron-mid);
  box-shadow:
    inset 0 1px 0 rgba(199, 154, 94, 0.15),
    0 6px 20px rgba(0, 0, 0, 0.5);
  flex-wrap: wrap;
  justify-content: center;
  margin-top: 20px;
  clip-path: var(--clip-forged-sm);
  position: relative;
}
.hstat {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: 14px 26px;
}
.hstat-num {
  font-family: var(--font-display);
  font-weight: var(--fw-black);
  font-size: 1.65rem;
  color: var(--text-bright);
  line-height: 1;
  letter-spacing: var(--ls-tight);
  text-shadow: 0 0 16px rgba(226, 67, 16, 0.3);
}
.hstat-plus {
  background: var(--grad-ember-text);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  margin-left: 1px;
}
.hstat-label {
  font-family: var(--font-display);
  font-size: 0.7rem;
  color: var(--text-ash);
  text-transform: uppercase;
  letter-spacing: var(--ls-wider);
}
.hstat-sep {
  display: flex;
  align-items: center;
  padding: 0 2px;
}
.hstat-sep span {
  width: 1px; height: 40px;
  background: linear-gradient(180deg, transparent, var(--iron-mid) 30%, var(--iron-mid) 70%, transparent);
}

/* ==========================================================
   PLATFORMS STRIP · каменная лента
   ========================================================== */
.platforms-strip {
  position: relative;
  width: 100%;
  overflow: hidden;
  background: linear-gradient(180deg,
    var(--ash-obsidian) 0%,
    var(--ash-stone) 50%,
    var(--ash-obsidian) 100%);
  border-top: 1px solid var(--iron-dark);
  border-bottom: 1px solid var(--iron-dark);
  box-shadow:
    inset 0 2px 4px rgba(0, 0, 0, 0.5),
    inset 0 -2px 4px rgba(0, 0, 0, 0.5);
  padding: 16px 0;
}
/* Края — затемнение */
.platforms-edge {
  position: absolute;
  top: 0; bottom: 0;
  width: 100px;
  pointer-events: none;
  z-index: 2;
}
.platforms-edge-l {
  left: 0;
  background: linear-gradient(90deg, var(--ash-obsidian) 0%, transparent 100%);
}
.platforms-edge-r {
  right: 0;
  background: linear-gradient(-90deg, var(--ash-obsidian) 0%, transparent 100%);
}

.platforms-track {
  display: flex;
  align-items: center;
  width: max-content;
  animation: tickerScroll 32s linear infinite;
}
.platform-item {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  white-space: nowrap;
  padding: 0 34px;
  position: relative;
  cursor: default;
  transition: all var(--dur-med) var(--ease-smoke);
}
.platform-item::after {
  content: '';
  position: absolute;
  right: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 1px;
  height: 24px;
  background: linear-gradient(180deg, transparent, var(--iron-mid) 50%, transparent);
}
.platform-item:last-child::after { display: none; }

.platform-icon {
  mix-blend-mode: screen;
  opacity: 0.55;
  transition: opacity var(--dur-med) var(--ease-smoke), filter var(--dur-med);
  flex-shrink: 0;
}
.platform-item:hover .platform-icon {
  opacity: 1;
  filter: drop-shadow(0 0 8px rgba(255, 122, 43, 0.6));
}

.platform-name {
  font-family: var(--font-display);
  font-size: 0.85rem;
  font-weight: var(--fw-semibold);
  color: var(--text-ash);
  letter-spacing: var(--ls-wide);
  text-transform: uppercase;
  transition: color var(--dur-med);
}
.platform-item:hover .platform-name { color: var(--brass); }

/* ==========================================================
   CAROUSEL SECTION
   ========================================================== */
.game-carousel-section {
  position: relative;
  padding: 80px 0 60px;
}

/* ==========================================================
   SHARED SECTION STYLES
   ========================================================== */
.page-content-wrapper {
  max-width: var(--content-max);
  margin: 0 auto 80px;
  padding: 0 24px;
}
.home-section { margin-bottom: 100px; }

.section-head {
  margin-bottom: 48px;
  text-align: center;
}

.tribal-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 16px;
}
.eyebrow-spike {
  width: 0; height: 0;
  border-top: 6px solid transparent;
  border-bottom: 6px solid transparent;
  border-right: 10px solid var(--ember-heart);
  filter: drop-shadow(0 0 4px rgba(194, 40, 26, 0.5));
}
.eyebrow-spike.right {
  border-right: none;
  border-left: 10px solid var(--ember-heart);
}
.eyebrow-text {
  font-family: var(--font-tribal);
  font-size: 0.78rem;
  color: var(--brass);
  letter-spacing: var(--ls-widest);
  text-transform: uppercase;
}

.section-title {
  font-family: var(--font-display);
  font-size: clamp(1.8rem, 3.6vw, 2.6rem);
  font-weight: var(--fw-bold);
  color: var(--text-bright);
  margin: 0;
  letter-spacing: var(--ls-wide);
  text-shadow: 0 2px 0 rgba(0, 0, 0, 0.6), 0 0 30px rgba(226, 67, 16, 0.2);
}

/* ==========================================================
   FEATURES
   ========================================================== */
.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 20px;
}
.feature-card {
  position: relative;
  padding: 32px 28px 28px;
  background:
    linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  clip-path: var(--clip-forged-sm);
  box-shadow:
    inset 0 0 0 1px var(--iron-mid),
    inset 0 0 0 3px var(--iron-void),
    var(--shadow-cast);
  transition: all var(--dur-med) var(--ease-smoke);
  animation: fadeUp 0.6s var(--ease-forge) both;
  animation-delay: calc(var(--i, 0) * 0.1s);
}
.feature-card:hover {
  transform: translateY(-6px);
  box-shadow:
    inset 0 0 0 1px var(--bronze-dark),
    inset 0 0 0 3px var(--iron-void),
    inset 0 0 40px rgba(226, 67, 16, 0.08),
    var(--shadow-lift);
}

/* Rivets on feature card */
.feature-card .rivet {
  position: absolute;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%, var(--brass) 0%, var(--bronze) 45%, var(--iron-void) 100%);
  box-shadow:
    inset -1px -1px 2px rgba(0, 0, 0, 0.7),
    inset 1px 1px 1px rgba(255, 201, 121, 0.35);
  pointer-events: none;
}
.feature-card .rivet-tl { top: 10px; left: 10px; }
.feature-card .rivet-tr { top: 10px; right: 10px; }
.feature-card .rivet-bl { bottom: 10px; left: 10px; }
.feature-card .rivet-br { bottom: 10px; right: 10px; }

.feature-icon-wrap {
  position: relative;
  width: 56px; height: 56px;
  margin-bottom: 20px;
  display: grid;
  place-items: center;
}
.feature-icon-halo {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(255, 122, 43, 0.5) 0%, rgba(194, 40, 26, 0.25) 50%, transparent 75%);
  filter: blur(4px);
  animation: emberFlicker 4s ease-in-out infinite;
}
.feature-card:hover .feature-icon-halo {
  background: radial-gradient(circle, rgba(255, 122, 43, 0.75) 0%, rgba(226, 67, 16, 0.4) 50%, transparent 75%);
}
.feature-icon {
  position: relative;
  z-index: 1;
  font-size: 1.75rem;
  color: var(--ember-gold);
  filter: drop-shadow(0 0 8px rgba(255, 122, 43, 0.8));
  line-height: 1;
}

.feature-title {
  font-family: var(--font-display);
  font-size: 1.15rem;
  font-weight: var(--fw-bold);
  color: var(--text-bright);
  margin: 0 0 10px;
  letter-spacing: var(--ls-wide);
}
.feature-desc {
  font-family: var(--font-body);
  font-size: 0.92rem;
  color: var(--text-parchment);
  line-height: 1.7;
  margin: 0;
}

/* ==========================================================
   STEPS · "Путь воина"
   ========================================================== */
.steps-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 0;
  position: relative;
}
.step-card {
  text-align: center;
  padding: 32px 24px 28px;
  position: relative;
  animation: fadeUp 0.6s var(--ease-forge) both;
  animation-delay: calc(var(--i, 0) * 0.12s);
}

.step-num-wrap {
  position: relative;
  display: inline-block;
  margin: 0 auto 18px;
}
.step-num {
  position: relative;
  width: 58px; height: 58px;
  display: grid;
  place-items: center;
  font-family: var(--font-display);
  font-size: 1.4rem;
  font-weight: var(--fw-black);
  color: var(--text-bright);
  background: var(--grad-ember);
  clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0 75%, 0 25%);
  box-shadow:
    inset 0 1px 0 rgba(255, 201, 121, 0.35),
    inset 0 -2px 4px rgba(0, 0, 0, 0.4);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.7);
  z-index: 2;
}
.step-num-glow {
  position: absolute;
  inset: -10px;
  background: radial-gradient(circle, rgba(255, 122, 43, 0.45) 0%, transparent 70%);
  filter: blur(10px);
  z-index: 1;
  animation: emberPulse 3s ease-in-out infinite;
}

/* Connector — огненная линия + шип */
.step-connector {
  position: absolute;
  top: 60px;
  right: -16px;
  display: flex;
  align-items: center;
  gap: 4px;
  z-index: 1;
}
.step-connector-line {
  width: 36px;
  height: 2px;
  background: linear-gradient(90deg, var(--ember-heart), transparent);
  box-shadow: 0 0 6px rgba(194, 40, 26, 0.5);
}
.step-connector-spike {
  width: 0; height: 0;
  border-left: 6px solid var(--ember-heart);
  border-top: 4px solid transparent;
  border-bottom: 4px solid transparent;
  filter: drop-shadow(0 0 4px rgba(194, 40, 26, 0.6));
}

.step-icon {
  font-size: 1.75rem;
  color: var(--brass);
  margin-bottom: 10px;
  filter: drop-shadow(0 0 6px rgba(199, 154, 94, 0.4));
}
.step-title {
  font-family: var(--font-display);
  font-size: 1.15rem;
  font-weight: var(--fw-bold);
  color: var(--text-bright);
  margin: 0 0 8px;
  letter-spacing: var(--ls-wide);
  text-transform: uppercase;
}
.step-desc {
  font-family: var(--font-body);
  font-size: 0.9rem;
  color: var(--text-ash);
  line-height: 1.6;
  margin: 0;
}

/* ==========================================================
   FAQ · "Свитки мудрецов"
   ========================================================== */
.faq-container {
  max-width: var(--narrow-max);
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.faq-item {
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  box-shadow:
    inset 0 1px 0 rgba(199, 154, 94, 0.12),
    var(--shadow-subtle);
  overflow: hidden;
  transition: all var(--dur-med) var(--ease-smoke);
  clip-path: var(--clip-forged-sm);
}
.faq-item.open {
  border-color: var(--ember-deep);
  box-shadow:
    inset 0 1px 0 rgba(255, 201, 121, 0.2),
    var(--inset-forge-hot),
    var(--shadow-cast),
    0 0 20px rgba(194, 40, 26, 0.18);
}

.faq-question {
  padding: 20px 26px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  gap: 16px;
  user-select: none;
  transition: background var(--dur-fast);
}
.faq-question:hover {
  background: rgba(255, 122, 43, 0.04);
}
.faq-question-text {
  font-family: var(--font-display);
  font-weight: var(--fw-semibold);
  font-size: 1rem;
  color: var(--text-bright);
  letter-spacing: var(--ls-wide);
}

.faq-icon {
  position: relative;
  width: 22px; height: 22px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  border: 1px solid var(--iron-mid);
  background: var(--ash-coal);
  transition: all var(--dur-med) var(--ease-forge);
  clip-path: polygon(50% 0, 100% 50%, 50% 100%, 0 50%);
}
.faq-icon-inner {
  position: relative;
  width: 10px; height: 10px;
}
.faq-icon-inner::before,
.faq-icon-inner::after {
  content: '';
  position: absolute;
  background: var(--brass);
  transition: all var(--dur-med) var(--ease-forge);
}
.faq-icon-inner::before {
  top: 50%; left: 0;
  width: 100%; height: 2px;
  transform: translateY(-50%);
}
.faq-icon-inner::after {
  top: 0; left: 50%;
  width: 2px; height: 100%;
  transform: translateX(-50%);
}
.faq-item.open .faq-icon {
  background: var(--grad-ember);
  border-color: var(--ember-heart);
  transform: rotate(180deg);
  box-shadow: var(--glow-ember-soft);
}
.faq-item.open .faq-icon-inner::before { background: var(--text-bright); }
.faq-item.open .faq-icon-inner::after { transform: translateX(-50%) scaleY(0); }

.faq-answer {
  color: var(--text-parchment);
  line-height: 1.75;
  overflow: hidden;
  border-top: 1px dashed var(--iron-dark);
}
.faq-answer p {
  margin: 0;
  padding: 18px 26px 22px;
  font-family: var(--font-body);
  font-size: 0.95rem;
}

.faq-slide-enter-active,
.faq-slide-leave-active {
  transition: max-height var(--dur-med) var(--ease-smoke), opacity var(--dur-fast) var(--ease-smoke);
  max-height: 260px;
}
.faq-slide-enter-from,
.faq-slide-leave-to { max-height: 0; opacity: 0; }

/* ==========================================================
   RESPONSIVE — подробная сетка под все размеры
   ========================================================== */
@media (max-width: 1100px) {
  .hero-section { padding: 150px 22px 100px; }
  .hero-stats { gap: 22px; }
}
@media (max-width: 980px) {
  .hero-section { min-height: 80vh; padding: 140px 22px 90px; }
  .hero-title { font-size: clamp(2.4rem, 9vw, 4.5rem); }
  .hero-tagline { font-size: 1.1rem; }
  .hero-subtitle { font-size: 0.96rem; }
  .platform-item { padding: 0 22px; }
  .hero-stats { flex-wrap: wrap; justify-content: center; gap: 18px 0; }
  .hstat-sep { display: none; }
}
@media (max-width: 768px) {
  .hero-section { padding: 120px 20px 80px; min-height: 72vh; }
  .hero-stats { gap: 0; }
  .hstat { padding: 12px 18px; }
  .steps-grid { grid-template-columns: 1fr 1fr; gap: 18px; }
  .step-connector { display: none; }
  .hero-actions { flex-direction: column; align-items: stretch; width: 100%; max-width: 320px; }
  .hero-btn { justify-content: center; padding: 14px 22px; font-size: 0.92rem; }
  .hero-badge { font-size: 0.74rem; padding: 6px 14px; }
  /* scale применяем к .banner-cloth (внутри banner), а не к .hero-banner —
     иначе перекрывается выравнивание родителя */
  .banner-cloth { transform: scale(0.85); transform-origin: top center; }
  .features-grid { grid-template-columns: 1fr 1fr; gap: 18px; }
}
@media (max-width: 600px) {
  .hero-section { padding: 105px 18px 70px; min-height: auto; }
  .hero-title { font-size: clamp(2.2rem, 11vw, 3.6rem); }
  .hero-tagline { font-size: 0.98rem; }
  .hero-subtitle { font-size: 0.88rem; line-height: 1.55; }
  .hstat-num { font-size: 1.6rem; }
  .hstat-label { font-size: 0.7rem; }
}
@media (max-width: 480px) {
  .hero-section { padding: 96px 14px 60px; }
  .steps-grid { grid-template-columns: 1fr; }
  .features-grid { grid-template-columns: 1fr; }
  .hero-title { font-size: clamp(2.2rem, 12vw, 3.5rem); }
  .hero-banner-wrap { display: none; }
  .hero-stats { padding: 14px 8px; flex-direction: column; gap: 14px; }
  .hstat { padding: 6px 0; }
}
@media (max-width: 380px) {
  .hero-section { padding: 88px 10px 50px; }
  .hero-title { font-size: clamp(2rem, 13vw, 3rem); }
  .hero-actions { max-width: 100%; }
  .hero-btn { padding: 12px 14px; font-size: 0.84rem; gap: 6px; }
}

/* ──────────────────────────────────────────────────────────────
   Тематические оверрайды для hero-фона переехали в themes.css.
   Раньше тут стояли :global([data-theme="..."]) .xxx правила, но
   у Vue/Vite scoped-CSS компилятора в этой комбинации БАГ:
   `:global(SEL) DESCENDANT` минифицировался в одиночный SEL без
   descendant'а, и в продакшен прилетало `[data-theme="light"]
   { display: none }` — что прятало <html> целиком (белый экран при
   переключении на Light). Глобальные оверрайды живут в themes.css,
   где никаких scoped-преобразований нет.
   ────────────────────────────────────────────────────────────── */

/* Reduced-motion: глушим все долгие анимации фона, оставляем только
   мягкое статичное свечение для атмосферы */
@media (prefers-reduced-motion: reduce) {
  .hero-star,
  .hero-cloud,
  .hero-aurora,
  .hero-anvil-svg,
  .anvil-strike-flash,
  .hero-shimmer,
  .ember-particle,
  .banner-sigil-glow,
  .hero-forge-glow {
    animation: none !important;
  }
  .hero-star { opacity: 0.6; }
  .ember-particle { opacity: 0; } /* убираем, чтоб не торчали статично */
  .anvil-strike-flash { display: none; }
}
</style>
