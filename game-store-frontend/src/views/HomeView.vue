<template>
  <main class="home-page">

    <!-- ===== HERO ===== -->
    <section class="hero-section">
      <!-- Animated background blobs -->
      <div class="blob blob-1"></div>
      <div class="blob blob-2"></div>
      <div class="blob blob-3"></div>
      <!-- Orbit rings -->
      <div class="particles-container">
        <div class="particle-orbit orbit-1"></div>
        <div class="particle-orbit orbit-2"></div>
        <div class="particle-orbit orbit-3"></div>
      </div>
      <!-- Grid overlay -->
      <div class="hero-grid-overlay"></div>

      <div class="hero-content">
        <div class="hero-badge">Новинки уже в каталоге</div>
        <h1 class="hero-title">
          Новые горизонты<br>
          <span class="hero-title-accent">в мире игр</span>
        </h1>
        <p class="hero-subtitle">
          Тысячи лицензионных ключей для Steam, Epic Games, GOG и других платформ.
          Мгновенная доставка, честные цены, поддержка 24/7.
        </p>
        <div class="hero-actions">
          <router-link to="/catalog" class="hero-btn primary">
            Перейти в каталог
            <span class="btn-arrow">→</span>
          </router-link>
          <router-link to="/news" class="hero-btn secondary">Новости</router-link>
        </div>

        <!-- Hero stats -->
        <div class="hero-stats">
          <div class="hstat">
            <span class="hstat-num">500+</span>
            <span class="hstat-label">Игр в каталоге</span>
          </div>
          <div class="hstat-sep"></div>
          <div class="hstat">
            <span class="hstat-num">12 000+</span>
            <span class="hstat-label">Довольных покупателей</span>
          </div>
          <div class="hstat-sep"></div>
          <div class="hstat">
            <span class="hstat-num">99.8%</span>
            <span class="hstat-label">Ключей активировано</span>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== PLATFORMS TICKER ===== -->
    <div class="platforms-strip">
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

    <!-- ===== CAROUSEL ===== -->
    <section class="home-section game-carousel-section">
      <GameCarousel />
    </section>

    <div class="page-content-wrapper">

      <!-- ===== FEATURES ===== -->
      <section class="home-section features-section">
        <div class="section-head">
          <span class="section-eyebrow">Наши преимущества</span>
          <h2 class="section-title">Почему выбирают GameStore?</h2>
        </div>
        <div class="features-grid">
          <div v-for="f in features" :key="f.title" class="feature-card">
            <div class="feature-icon-wrap" :style="{ '--fc': f.color }">
              <span class="feature-icon">{{ f.icon }}</span>
            </div>
            <h3 class="feature-title">{{ f.title }}</h3>
            <p class="feature-desc">{{ f.desc }}</p>
          </div>
        </div>
      </section>

      <!-- ===== HOW IT WORKS ===== -->
      <section class="home-section how-section">
        <div class="section-head">
          <span class="section-eyebrow">Просто и быстро</span>
          <h2 class="section-title">Как это работает?</h2>
        </div>
        <div class="steps-grid">
          <div v-for="(step, i) in steps" :key="i" class="step-card">
            <div class="step-num">{{ i + 1 }}</div>
            <div class="step-connector" v-if="i < steps.length - 1"></div>
            <div class="step-icon">{{ step.icon }}</div>
            <h3 class="step-title">{{ step.title }}</h3>
            <p class="step-desc">{{ step.desc }}</p>
          </div>
        </div>
      </section>

      <!-- ===== FAQ ===== -->
      <section class="home-section faq-section">
        <div class="section-head">
          <span class="section-eyebrow">Часто задаваемые вопросы</span>
          <h2 class="section-title">Помощь и поддержка</h2>
        </div>
        <div class="faq-container">
          <div v-for="item in faqItems" :key="item.id" class="faq-item" :class="{ open: openFaqItem === item.id }">
            <div class="faq-question" @click="toggleFaq(item.id)">
              <span>{{ item.question }}</span>
              <span class="faq-icon">
                <span class="faq-icon-bar h"></span>
                <span class="faq-icon-bar v"></span>
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
  title: 'GameStore — Купить ключи для игр',
  meta: [
    { name: 'description', content: 'Магазин лицензионных ключей для игр. Steam, Epic Games, GOG и другие платформы по выгодным ценам.' },
    { property: 'og:type', content: 'website' },
    { property: 'og:title', content: 'GameStore — Купить ключи для игр' },
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
  { icon: '→', title: 'Мгновенная доставка', desc: 'Ключ приходит на почту сразу после оплаты — без ожидания и задержек.', color: '#f59e0b' },
  { icon: '✓', title: 'Гарантия качества', desc: 'Только официальные издатели. Гарантируем валидность каждого ключа.', color: '#3b82f6' },
  { icon: '✦', title: 'Поддержка 24/7', desc: 'Наша команда всегда готова помочь с любым вопросом в кратчайшие сроки.', color: '#22c55e' },
  { icon: '💳', title: 'Удобная оплата', desc: 'Visa, MasterCard, МИР и другие способы. Безопасные транзакции.', color: '#a855f7' },
]);

const steps = ref([
  { icon: '', title: 'Найдите игру', desc: 'Используйте поиск или каталог для выбора нужной игры' },
  { icon: '', title: 'Добавьте в корзину', desc: 'Оформите заказ за несколько кликов' },
  { icon: '', title: 'Оплатите', desc: 'Выберите удобный способ оплаты' },
  { icon: '', title: 'Играйте!', desc: 'Ключ мгновенно придёт на вашу почту' },
]);

const faqItems = ref([
  { id: 1, question: 'Как я получу купленную игру?', answer: 'Сразу после оплаты ключ будет отправлен на вашу электронную почту. Также он будет доступен в личном кабинете в разделе «Мои покупки».' },
  { id: 2, question: 'Принимаете ли вы карты всех банков?', answer: 'Мы принимаем Visa, MasterCard и МИР большинства банков. При проблемах с оплатой свяжитесь с нашей поддержкой.' },
  { id: 3, question: 'Что делать, если ключ не работает?', answer: 'Свяжитесь с поддержкой, предоставьте номер заказа и скриншот ошибки. Мы оперативно заменим ключ или вернём деньги.' },
  { id: 4, question: 'Могу ли я вернуть игру?', answer: 'Цифровые ключи не подлежат возврату после активации. Пожалуйста, внимательно изучите системные требования и описание до покупки.' },
  { id: 5, question: 'На какой платформе активировать ключ?', answer: 'Платформа (Steam, Epic Games, GOG и т.д.) всегда указана на странице товара. Убедитесь, что у вас есть аккаунт в соответствующем сервисе.' },
]);

const openFaqItem = ref(null);
const toggleFaq = (id) => { openFaqItem.value = openFaqItem.value === id ? null : id; };
</script>

<style scoped>
/* ===== BASE ===== */
@keyframes rotate-cw  { to { transform: rotate(360deg); } }
@keyframes rotate-ccw { to { transform: rotate(-360deg); } }
@keyframes blobFloat {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33%       { transform: translate(40px, -30px) scale(1.05); }
  66%       { transform: translate(-20px, 20px) scale(0.95); }
}
@keyframes tickerScroll {
  from { transform: translateX(0); }
  to   { transform: translateX(-50%); }
}
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(28px); }
  to   { opacity: 1; transform: translateY(0); }
}

.home-page {
  color: #e5e7eb;
  width: 100%;
  background: #020617;
  overflow-x: hidden;
}

/* ===== HERO ===== */
.hero-section {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 120px 24px 100px;
  overflow: hidden;
  background: radial-gradient(ellipse 80% 60% at 50% -10%, rgba(59,130,246,0.15), transparent 70%);
}

/* Animated blobs */
.blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  pointer-events: none;
  animation: blobFloat 12s ease-in-out infinite;
}
.blob-1 { width: 500px; height: 500px; background: rgba(99,102,241,0.12); top: -100px; left: -100px; animation-duration: 14s; }
.blob-2 { width: 400px; height: 400px; background: rgba(59,130,246,0.10); bottom: 0; right: -100px; animation-duration: 18s; animation-delay: -5s; }
.blob-3 { width: 300px; height: 300px; background: rgba(34,197,94,0.07); top: 50%; left: 60%; animation-duration: 20s; animation-delay: -9s; }

/* Grid overlay */
.hero-grid-overlay {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
  background-size: 60px 60px;
  mask-image: radial-gradient(ellipse 70% 70% at 50% 50%, black, transparent);
  pointer-events: none;
}

/* Orbits */
.particles-container { position: absolute; inset: 0; pointer-events: none; }
.particle-orbit {
  position: absolute;
  top: 10%;
  left: 20%;
  border-radius: 50%;
  border: 1px solid rgba(255,255,255,0.05);
}
.orbit-1 { width: 44vw; height: 44vw; min-width: 280px; max-width: 480px; animation: rotate-cw 28s linear infinite; }
.orbit-2 { width: 68vw; height: 68vw; min-width: 380px; max-width: 820px; animation: rotate-ccw 45s linear infinite; }
.orbit-3 { width: 95vw; height: 95vw; min-width: 480px; max-width: 1200px; animation: rotate-cw 70s linear infinite; border-color: rgba(255,255,255,0.03); }

/* Hero content */
.hero-content {
  position: relative;
  z-index: 10;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 24px;
  animation: fadeInUp 0.7s ease both;
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  color: #93c5fd;
  background: rgba(59,130,246,0.1);
  border: 1px solid rgba(59,130,246,0.25);
  padding: 6px 18px;
  border-radius: 999px;
  letter-spacing: 0.5px;
}

.hero-title {
  font-size: clamp(2.8rem, 6vw, 5.2rem);
  font-weight: 900;
  color: #fff;
  line-height: 1.1;
  margin: 0;
  letter-spacing: -1px;
}
.hero-title-accent {
  background: linear-gradient(90deg, #3b82f6, #818cf8, #6366f1);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero-subtitle {
  font-size: 1.15rem;
  color: #9ca3af;
  max-width: 580px;
  line-height: 1.75;
  margin: 0;
}

.hero-actions { display: flex; gap: 14px; flex-wrap: wrap; justify-content: center; }

.hero-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 14px 32px;
  border-radius: 10px;
  font-size: 1rem;
  font-weight: 700;
  text-decoration: none;
  transition: all 0.25s ease;
}
.hero-btn.primary {
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: #fff;
  box-shadow: 0 8px 30px rgba(99,102,241,0.35);
}
.hero-btn.primary:hover { transform: translateY(-3px); box-shadow: 0 14px 40px rgba(99,102,241,0.5); filter: brightness(1.1); }
.hero-btn.secondary {
  background: rgba(255,255,255,0.06);
  color: #d1d5db;
  border: 1px solid rgba(255,255,255,0.12);
}
.hero-btn.secondary:hover { background: rgba(255,255,255,0.1); color: #fff; border-color: rgba(255,255,255,0.2); }
.btn-arrow { transition: transform 0.2s; }
.hero-btn.primary:hover .btn-arrow { transform: translateX(4px); }

/* Hero stats */
.hero-stats {
  display: flex;
  align-items: center;
  gap: 28px;
  padding: 18px 36px;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 14px;
  backdrop-filter: blur(12px);
  flex-wrap: wrap;
  justify-content: center;
}
.hstat { display: flex; flex-direction: column; align-items: center; gap: 2px; }
.hstat-num { font-size: 1.5rem; font-weight: 800; color: #fff; line-height: 1; }
.hstat-label { font-size: 0.72rem; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; }
.hstat-sep { width: 1px; height: 36px; background: rgba(255,255,255,0.1); }

/* ===== PLATFORMS STRIP ===== */
.platforms-strip {
  width: 100%;
  overflow: hidden;
  border-top: 1px solid rgba(255,255,255,0.06);
  border-bottom: 1px solid rgba(255,255,255,0.06);
  background: rgba(15,23,42,0.7);
  backdrop-filter: blur(8px);
  padding: 13px 0;
  /* fade edges */
  mask-image: linear-gradient(to right, transparent 0%, black 6%, black 94%, transparent 100%);
  -webkit-mask-image: linear-gradient(to right, transparent 0%, black 6%, black 94%, transparent 100%);
}
.platforms-track {
  display: flex;
  align-items: center;
  width: max-content;
  animation: tickerScroll 28s linear infinite;
}
.platform-item {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  white-space: nowrap;
  padding: 0 28px;
  border-right: 1px solid rgba(255,255,255,0.06);
  cursor: default;
  transition: opacity 0.25s;
}
.platform-item:last-child { border-right: none; }

/*
  mix-blend-mode: screen делает тёмные/чёрные пиксели SVG прозрачными
  на тёмном фоне — работает для ВСЕХ SVG включая те, у кого чёрный фон (battle-net).
  opacity 0.55 — приглушённый вид по умолчанию.
*/
.platform-icon {
  mix-blend-mode: screen;
  opacity: 0.55;
  transition: opacity 0.3s;
  flex-shrink: 0;
}
.platform-item:hover .platform-icon {
  opacity: 1;
}

.platform-name {
  font-size: 0.88rem;
  font-weight: 600;
  color: #6b7280;
  letter-spacing: 0.4px;
  transition: color 0.25s;
}
.platform-item:hover .platform-name { color: #e2e8f0; }

/* ===== CAROUSEL SECTION ===== */
.game-carousel-section {
  position: relative;
  padding: 80px 0 60px;
}

/* ===== SHARED SECTION STYLES ===== */
.page-content-wrapper {
  max-width: 1200px;
  margin: 0 auto 60px;
  padding: 0 24px;
}
.home-section { margin-bottom: 80px; }

.section-head {
  margin-bottom: 40px;
}
.section-eyebrow {
  display: block;
  font-size: 0.78rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 3px;
  color: #3b82f6;
  margin-bottom: 10px;
}
.section-title {
  font-size: clamp(1.6rem, 3vw, 2.2rem);
  font-weight: 800;
  color: #f9fafb;
  margin: 0;
  letter-spacing: -0.5px;
}

/* ===== FEATURES ===== */
.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 20px;
}
.feature-card {
  padding: 28px;
  border-radius: 16px;
  background: rgba(17,24,39,0.7);
  border: 1px solid rgba(255,255,255,0.07);
  backdrop-filter: blur(12px);
  transition: transform 0.25s, border-color 0.25s, box-shadow 0.25s;
  animation: fadeInUp 0.5s ease both;
}
.feature-card:hover {
  transform: translateY(-6px);
  border-color: rgba(59,130,246,0.3);
  box-shadow: 0 20px 50px rgba(0,0,0,0.3), 0 0 30px rgba(59,130,246,0.08);
}
.feature-icon-wrap {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: color-mix(in srgb, var(--fc) 15%, transparent);
  border: 1px solid color-mix(in srgb, var(--fc) 25%, transparent);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  margin-bottom: 18px;
}
.feature-title { font-size: 1.1rem; font-weight: 700; color: #fff; margin: 0 0 10px; }
.feature-desc { font-size: 0.9rem; color: #9ca3af; line-height: 1.65; margin: 0; }

/* ===== HOW IT WORKS ===== */
.steps-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 0;
  position: relative;
}
.step-card {
  text-align: center;
  padding: 32px 24px;
  position: relative;
  border-radius: 16px;
  transition: background 0.25s;
}
.step-card:hover { background: rgba(255,255,255,0.03); }
.step-num {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: #fff;
  font-size: 1.1rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
  box-shadow: 0 8px 20px rgba(99,102,241,0.35);
}
.step-connector {
  position: absolute;
  top: 53px;
  right: -12px;
  width: 24px;
  height: 2px;
  background: linear-gradient(90deg, #3b82f6, #6366f1);
  opacity: 0.4;
  z-index: 1;
}
.step-icon { font-size: 2.2rem; margin-bottom: 12px; }
.step-title { font-size: 1rem; font-weight: 700; color: #fff; margin: 0 0 8px; }
.step-desc { font-size: 0.85rem; color: #6b7280; line-height: 1.6; margin: 0; }

/* ===== FAQ ===== */
.faq-container {
  max-width: 860px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.faq-item {
  border-radius: 12px;
  background: rgba(17,24,39,0.7);
  border: 1px solid rgba(255,255,255,0.07);
  backdrop-filter: blur(12px);
  overflow: hidden;
  transition: border-color 0.25s;
}
.faq-item.open { border-color: rgba(59,130,246,0.3); }
.faq-question {
  padding: 20px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  font-weight: 600;
  font-size: 1rem;
  color: #f3f4f6;
  gap: 16px;
  user-select: none;
  transition: background 0.2s;
}
.faq-question:hover { background: rgba(255,255,255,0.04); }
.faq-icon {
  position: relative;
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}
.faq-icon-bar {
  position: absolute;
  background: #3b82f6;
  border-radius: 2px;
  transition: transform 0.3s ease, opacity 0.3s;
}
.faq-icon-bar.h { width: 20px; height: 2px; top: 9px; left: 0; }
.faq-icon-bar.v { width: 2px; height: 20px; top: 0; left: 9px; }
.faq-item.open .faq-icon-bar.v { transform: rotate(90deg); opacity: 0; }
.faq-answer { color: #9ca3af; line-height: 1.75; overflow: hidden; }
.faq-answer p { margin: 0; padding: 0 24px 20px; font-size: 0.95rem; }

.faq-slide-enter-active, .faq-slide-leave-active { transition: max-height 0.35s ease, opacity 0.25s ease; max-height: 200px; }
.faq-slide-enter-from, .faq-slide-leave-to { max-height: 0; opacity: 0; }

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
  .hero-section { padding: 100px 20px 80px; }
  .hero-stats { gap: 16px; padding: 16px 20px; }
  .hstat-sep { display: none; }
  .steps-grid { grid-template-columns: 1fr 1fr; }
  .step-connector { display: none; }
  .hero-actions { flex-direction: column; align-items: center; }
}
@media (max-width: 480px) {
  .steps-grid { grid-template-columns: 1fr; }
  .features-grid { grid-template-columns: 1fr; }
}
</style>
