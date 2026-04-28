<template>
  <div class="news-root">
    <!-- ═══ HERO ═══ -->
    <section class="news-hero news-hero--video">
      <!-- ═══ ВИДЕО-ФОН ═══ Воин с факелом перед строем башен -->
      <video
        class="news-video"
        src="/hero/news-bg.mp4"
        poster="/hero/news-bg-poster.jpg"
        autoplay
        loop
        muted
        playsinline
        preload="auto"
        aria-hidden="true"
      ></video>
      <div class="news-video-overlay" aria-hidden="true"></div>

      <!-- CSS-фон оставляем в DOM, но прячем через --video модификатор -->
      <div class="hero-bg" aria-hidden="true">
        <div class="forge-glow"></div>
        <svg class="anvil-silhouette" viewBox="0 0 120 80" preserveAspectRatio="xMidYMax meet">
          <path d="M 14 26 L 106 26 L 100 38 L 76 38 L 76 54 L 88 54 L 88 64 L 32 64 L 32 54 L 44 54 L 44 38 L 20 38 Z"
                fill="currentColor" />
        </svg>
        <span v-for="n in 6" :key="`hs-${n}`" class="hero-spark" :style="{ '--i': n }"></span>
      </div>

      <!-- Гвозди по бокам шапки — будто "Хроники" приколочены к стене -->
      <span class="hero-nail hero-nail--l" aria-hidden="true"></span>
      <span class="hero-nail hero-nail--r" aria-hidden="true"></span>

      <div class="hero-inner">
        <span class="tribal-eyebrow reveal">
          <span class="eb-spike"></span>
          Глашатай кузницы
          <span class="eb-spike"></span>
        </span>
        <h1 class="hero-title reveal">Вести из <span class="grad-text">оружейной</span></h1>
        <p class="hero-sub reveal">
          Анонсы, обзоры и события из миров, где куются клинки.<br>
          Свежие искры с наших горнов.
        </p>
      </div>
    </section>

    <!-- ═══ CONTENT ═══ -->
    <div class="news-body">

      <!-- Loading -->
      <div v-if="loading" class="news-loading">
        <div class="news-loading-grid">
          <div v-for="i in 6" :key="i" class="skel-card">
            <div class="skel-img"></div>
            <div class="skel-body">
              <div class="skel-line w80"></div>
              <div class="skel-line w60"></div>
              <div class="skel-line w40"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="empty-box">
        <div class="empty-sigil" aria-hidden="true">
          <svg viewBox="-32 -32 64 64" width="72" height="72">
            <circle r="22" class="es-ring" />
            <line x1="-12" y1="-12" x2="12" y2="12" class="es-cross" />
            <line x1="12" y1="-12" x2="-12" y2="12" class="es-cross" />
          </svg>
        </div>
        <p class="empty-text">{{ error }}</p>
        <button @click="fetchNews" class="forge-btn">
          <span class="fb-label">Попробовать снова</span>
        </button>
      </div>

      <div v-else-if="newsItems.length">
        <!-- Главная весть — оформлена как развёрнутый свиток-плакат -->
        <RouterLink :to="'/news/' + newsItems[0].id" class="featured-card reveal">
          <span class="feat-rivet feat-rivet--tl" aria-hidden="true"></span>
          <span class="feat-rivet feat-rivet--tr" aria-hidden="true"></span>
          <span class="feat-rivet feat-rivet--bl" aria-hidden="true"></span>
          <span class="feat-rivet feat-rivet--br" aria-hidden="true"></span>
          <!-- Декоративные шипы по краям — будто прибито к стене -->
          <span class="feat-spike feat-spike--top" aria-hidden="true"></span>
          <span class="feat-spike feat-spike--bottom" aria-hidden="true"></span>
          <div class="featured-img-wrap">
            <img
              :src="resolveMediaUrl(newsItems[0].image)"
              :alt="newsItems[0].title"
              class="featured-img" loading="lazy"
            />
            <div class="featured-overlay"></div>
          </div>
          <div class="featured-content">
            <span class="featured-banner">
              <span class="fb-spike fb-spike--l" aria-hidden="true"></span>
              <span class="fb-text">⚒ Главная весть</span>
              <span class="fb-spike fb-spike--r" aria-hidden="true"></span>
            </span>
            <h2 class="featured-title">{{ newsItems[0].title }}</h2>
            <!-- Tribal-divider под заголовком (как у game-card) -->
            <div class="feat-divider" aria-hidden="true">
              <span></span>
              <span class="feat-divider-spike"></span>
              <span></span>
            </div>
            <div class="featured-meta">
              <span class="news-date">
                <span class="date-icon" aria-hidden="true">📜</span>
                {{ formatDate(newsItems[0].published_at) }}
              </span>
              <span class="read-label">Читать хронику ⚔</span>
            </div>
          </div>
        </RouterLink>

        <!-- Лента — каждая карточка как «прибитая к стене дощечка с вестью» -->
        <div v-if="newsItems.length > 1" class="news-grid">
          <article
            v-for="(item, i) in newsItems.slice(1)"
            :key="item.id"
            class="news-card reveal"
            :style="{ '--i': i % 3 }"
          >
            <!-- 4 заклёпки (вместо 2 — как кованая дощечка с гвоздями по углам) -->
            <span class="card-rivet card-rivet--tl" aria-hidden="true"></span>
            <span class="card-rivet card-rivet--tr" aria-hidden="true"></span>
            <span class="card-rivet card-rivet--bl" aria-hidden="true"></span>
            <span class="card-rivet card-rivet--br" aria-hidden="true"></span>
            <!-- Шип сверху — будто весть прибита железным гвоздём -->
            <span class="card-spike" aria-hidden="true"></span>

            <RouterLink :to="'/news/' + item.id" class="card-img-link">
              <img
                :src="resolveMediaUrl(item.image)"
                :alt="item.title" class="card-img" loading="lazy"
                width="400" height="220"
              />
              <div class="card-img-overlay"></div>
            </RouterLink>

            <!-- Кованая полоса-разделитель между картинкой и текстом -->
            <div class="card-divider" aria-hidden="true">
              <span class="cd-line"></span>
              <span class="cd-spike"></span>
              <span class="cd-line"></span>
            </div>

            <div class="card-body">
              <RouterLink :to="'/news/' + item.id" class="card-title">{{ item.title }}</RouterLink>
              <div class="card-footer">
                <span class="news-date">
                  <span class="date-icon" aria-hidden="true">📜</span>
                  {{ formatDate(item.published_at) }}
                </span>
                <RouterLink :to="'/news/' + item.id" class="card-read-btn">
                  <span>Развернуть</span>
                  <span class="crb-arrow" aria-hidden="true">→</span>
                </RouterLink>
              </div>
            </div>
          </article>
        </div>
      </div>

      <!-- Empty -->
      <div v-else class="empty-box">
        <div class="empty-sigil" aria-hidden="true">
          <svg viewBox="-32 -32 64 64" width="72" height="72">
            <circle r="22" class="es-ring" />
            <circle r="4" class="es-core" />
          </svg>
        </div>
        <p class="empty-text">Пока тихо — ни одной вести. Загляните чуть позже.</p>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { RouterLink } from 'vue-router';
import { useHead } from '@vueuse/head';
import api from '../api/axios';
import { resolveMediaUrl } from '../utils/media';

useHead({
  title: 'Хроники — GameStore',
  meta: [
    { name: 'description', content: 'Свежие хроники из мира игр: анонсы, обзоры и события. Блог GameStore.' },
    { property: 'og:type', content: 'website' },
    { property: 'og:title', content: 'Хроники — GameStore' },
    { property: 'og:description', content: 'Свежие хроники из мира игр: анонсы, обзоры и события. Блог GameStore.' },
    { name: 'robots', content: 'index, follow' },
  ],
});

const newsItems = ref([]);
const loading = ref(true);
const error = ref(null);
let revealObs = null;

const formatDate = (ds) => {
  if (!ds) return '';
  return new Date(ds).toLocaleDateString('ru-RU', { year: 'numeric', month: 'long', day: 'numeric' });
};

const setupReveal = () => {
  if (revealObs) revealObs.disconnect();
  revealObs = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('is-visible'); revealObs.unobserve(e.target); } });
  }, { threshold: 0.07, rootMargin: '0px 0px -30px 0px' });
  setTimeout(() => document.querySelectorAll('.news-root .reveal').forEach(el => revealObs.observe(el)), 100);
};

const fetchNews = async () => {
  loading.value = true;
  error.value = null;
  try {
    const res = await api.get('/news');
    newsItems.value = res.data.data;
    setTimeout(setupReveal, 150);
  } catch (e) {
    error.value = 'Не удалось загрузить хроники. Попробуйте позже.';
    console.error(e);
  } finally { loading.value = false; }
};

onMounted(() => { fetchNews(); setupReveal(); });
onUnmounted(() => revealObs?.disconnect());
</script>

<style scoped>
/* ══ Reveal ══ */
.reveal { opacity: 0; transform: translateY(30px); transition: opacity 0.55s var(--ease-smoke) calc(var(--i, 0) * 80ms), transform 0.55s var(--ease-smoke) calc(var(--i, 0) * 80ms); }
.reveal.is-visible { opacity: 1; transform: none; }

.news-root { min-height: 100vh; color: var(--text-bone); }

/* ══ HERO ══ */
.news-hero {
  position: relative;
  min-height: 42vh;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  padding: 80px 24px 64px;
  isolation: isolate;
}

/* ═══ Видео-режим: hero подтягивается под sticky-хедер ═══
   min-height = 56.25vw (16:9 от ширины экрана) + 73px на хедер,
   чтобы видео отображалось В ПОЛНЫЙ РОСТ без cover-обрезки.
   На мобиле (узкие экраны) минимум 360px, чтоб контент остался
   читабельным. На сверхшироких — кэп 1100px, чтоб не было как
   киноэкран на весь монитор. */
.news-hero--video {
  margin-top: -73px;
  padding-top: 153px;
  /* 75% от полного 16:9 (было 56.25vw → стало ~42vw) — компактнее,
     но всё ещё достаточно высокий чтобы видеть всю сцену без обрезки
     по верху/низу. Cap снизили с 1100 → 825. */
  min-height: clamp(320px, calc(42vw + 73px), 825px);
}
.news-video {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center center;
  z-index: -3;
  pointer-events: none;
}
.news-video-overlay {
  position: absolute;
  inset: 0;
  z-index: -2;
  pointer-events: none;
  background:
    linear-gradient(180deg,
      rgba(0, 0, 0, 0.5) 0%,
      rgba(0, 0, 0, 0.18) 25%,
      rgba(0, 0, 0, 0.08) 50%,
      rgba(0, 0, 0, 0.45) 80%,
      rgba(0, 0, 0, 0.75) 100%),
    radial-gradient(ellipse 110% 100% at 50% 50%,
      transparent 55%,
      rgba(0, 0, 0, 0.4) 100%);
}
.news-hero--video .hero-bg {
  display: none;
}
.hero-bg { position: absolute; inset: 0; pointer-events: none; z-index: 0; overflow: hidden; }

/* ── Дальняя наковальня в силуэте у нижнего края hero ── */
.anvil-silhouette {
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: clamp(180px, 28vw, 320px);
  color: var(--iron-void);
  opacity: 0.7;
  filter: drop-shadow(0 -2px 4px rgba(0, 0, 0, 0.5));
}

/* ── Пульсирующее свечение горна позади наковальни ── */
.forge-glow {
  position: absolute;
  bottom: -40px;
  left: 50%;
  transform: translateX(-50%);
  width: clamp(280px, 36vw, 480px);
  height: clamp(160px, 22vw, 280px);
  background: radial-gradient(ellipse 70% 90% at 50% 100%,
    rgba(255, 122, 43, 0.55) 0%,
    rgba(226, 67, 16, 0.35) 25%,
    rgba(138, 31, 24, 0.18) 55%,
    transparent 80%);
  filter: blur(20px);
  animation: forgeGlowPulse 4.5s ease-in-out infinite;
}
@keyframes forgeGlowPulse {
  0%, 100% { opacity: 0.65; transform: translateX(-50%) scale(1); }
  50%      { opacity: 1;    transform: translateX(-50%) scale(1.06); }
}

/* ── Восходящие искры от горна — летят вверх и тают ── */
.hero-spark {
  position: absolute;
  bottom: 12%;
  left: calc(35% + (var(--i) * 5%));
  width: 3px; height: 3px;
  border-radius: 50%;
  background: var(--ember-gold);
  box-shadow: 0 0 6px var(--ember-flame);
  opacity: 0;
  animation: heroSparkRise 5s ease-out infinite;
  animation-delay: calc(var(--i) * -0.7s);
}
@keyframes heroSparkRise {
  0%   { opacity: 0;    transform: translate(0, 0) scale(0.6); }
  20%  { opacity: 0.95; }
  100% { opacity: 0;
         transform: translate(calc(var(--i) * 4px - 12px), -180px) scale(0.3); }
}

/* ── Гвозди по бокам шапки: будто "Хроники" приколочены к стене ── */
.hero-nail {
  position: absolute;
  top: 40px;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%,
    var(--brass) 0%,
    var(--bronze) 45%,
    var(--iron-void) 100%);
  box-shadow:
    inset -2px -2px 3px rgba(0, 0, 0, 0.7),
    inset 2px 2px 2px rgba(255, 201, 121, 0.4),
    0 0 8px rgba(199, 154, 94, 0.5);
  z-index: 2;
}
.hero-nail--l { left: clamp(20px, 6vw, 80px); }
.hero-nail--r { right: clamp(20px, 6vw, 80px); }

.hero-inner {
  position: relative;
  z-index: 1;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 18px;
  max-width: 760px;
}

.tribal-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  font-family: var(--font-ui);
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 3.2px;
  text-transform: uppercase;
  color: var(--bronze);
}
.eb-spike {
  width: 0; height: 0;
  border-left: 5px solid transparent;
  border-right: 5px solid transparent;
  border-top: 7px solid var(--bronze);
  filter: drop-shadow(0 0 4px rgba(199, 154, 94, 0.55));
}

.hero-title {
  font-family: var(--font-display);
  font-weight: var(--fw-black, 900);
  font-size: clamp(2.2rem, 5vw, 3.8rem);
  color: var(--text-bright);
  margin: 0;
  line-height: 1.1;
  letter-spacing: 0.3px;
  text-shadow: 0 4px 16px rgba(0, 0, 0, 0.55);
}
.grad-text {
  background: linear-gradient(135deg,
    var(--ember-spark) 0%,
    var(--ember-glow) 35%,
    var(--ember-flame) 70%,
    var(--ember-heart) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.hero-sub {
  font-family: var(--font-body);
  color: var(--text-parchment);
  font-size: 1.05rem;
  margin: 0;
  max-width: 600px;
  line-height: 1.75;
}

/* ══ BODY ══ */
.news-body { max-width: 1280px; margin: 0 auto; padding: 44px 24px 96px; }

/* ══ Loading skeletons ══ */
.news-loading-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
.skel-card {
  overflow: hidden;
  background: linear-gradient(180deg,
    var(--ash-stone) 0%,
    var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  clip-path: var(--clip-forged-sm);
  box-shadow: var(--inset-iron-top);
}
.skel-img {
  height: 220px;
  background: linear-gradient(90deg,
    var(--ash-stone) 25%,
    var(--ash-ironrust) 50%,
    var(--ash-stone) 75%);
  background-size: 200% 100%;
  animation: newsShimmer 1.5s linear infinite;
}
.skel-body { padding: 20px; display: flex; flex-direction: column; gap: 12px; }
.skel-line {
  height: 12px;
  background: linear-gradient(90deg,
    var(--ash-stone) 25%,
    var(--ash-ironrust) 50%,
    var(--ash-stone) 75%);
  background-size: 200% 100%;
  animation: newsShimmer 1.5s linear infinite;
}
.w80 { width: 80%; } .w60 { width: 60%; } .w40 { width: 40%; }
@keyframes newsShimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

/* ══ Empty / Error ══ */
.empty-box {
  text-align: center;
  padding: 96px 40px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 22px;
}
.empty-sigil {
  display: flex;
  align-items: center;
  justify-content: center;
}
.empty-sigil svg { overflow: visible; }
.es-ring {
  fill: none;
  stroke: var(--bronze);
  stroke-width: 2;
  opacity: 0.55;
  filter: drop-shadow(0 0 4px rgba(199, 154, 94, 0.4));
}
.es-cross {
  stroke: var(--ember-flame);
  stroke-width: 3;
  stroke-linecap: round;
  opacity: 0.75;
  filter: drop-shadow(0 0 4px rgba(255, 122, 43, 0.5));
}
.es-core {
  fill: var(--ember-gold);
  filter: drop-shadow(0 0 8px rgba(255, 201, 121, 0.7));
}
.empty-text {
  font-family: var(--font-body);
  color: var(--text-parchment);
  font-size: 1.05rem;
  margin: 0;
}

/* ══ Кованая кнопка (для retry) ══ */
.forge-btn {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 12px 30px;
  border: 1px solid var(--ember-heart);
  background: var(--grad-ember);
  color: var(--text-bright);
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 0.95rem;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  cursor: pointer;
  overflow: hidden;
  box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.35), var(--glow-ember);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.65);
  transition: transform 0.18s var(--ease-forge), box-shadow 0.2s var(--ease-smoke);
  clip-path: var(--clip-forged-sm);
}
.forge-btn::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg,
    transparent 0%, rgba(255, 201, 121, 0.4) 50%, transparent 100%);
  transform: translateX(-120%);
  transition: transform 0.6s var(--ease-smoke);
  pointer-events: none;
}
.forge-btn:hover {
  transform: translateY(-2px);
  box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.35), var(--glow-ember-strong);
}
.forge-btn:hover::after { transform: translateX(120%); }
.fb-label { position: relative; z-index: 1; }

/* ══ FEATURED (главная весть) ══ */
.featured-card {
  display: block;
  text-decoration: none;
  overflow: hidden;
  position: relative;
  margin-bottom: 40px;
  background: var(--ash-obsidian);
  border: 1px solid var(--iron-mid);
  clip-path: var(--clip-forged-md);
  box-shadow: var(--inset-iron-top), var(--shadow-cast);
  transition: transform 0.35s var(--ease-forge), box-shadow 0.35s var(--ease-smoke);
}
.featured-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--inset-iron-top), var(--shadow-deep), 0 0 36px rgba(226, 67, 16, 0.3);
}

.feat-rivet {
  position: absolute;
  width: 10px; height: 10px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%,
    var(--brass) 0%,
    var(--bronze) 50%,
    var(--iron-void) 100%);
  z-index: 2;
  box-shadow: inset -1px -1px 2px rgba(0, 0, 0, 0.7), 0 0 5px rgba(199, 154, 94, 0.5);
}
.feat-rivet--tl { top: 16px; left: 16px; }
.feat-rivet--tr { top: 16px; right: 16px; }
.feat-rivet--bl { bottom: 16px; left: 16px; }
.feat-rivet--br { bottom: 16px; right: 16px; }

/* Декоративные шипы по краям featured — вверху и внизу по центру —
   будто "плакат-весть" прибит шипами к стене */
.feat-spike {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  width: 0; height: 0;
  border-left: 8px solid transparent;
  border-right: 8px solid transparent;
  z-index: 3;
}
.feat-spike--top {
  top: -2px;
  border-top: 14px solid var(--iron-warm);
  filter: drop-shadow(0 1px 2px rgba(199, 154, 94, 0.4));
}
.feat-spike--bottom {
  bottom: -2px;
  border-bottom: 14px solid var(--iron-warm);
  filter: drop-shadow(0 -1px 2px rgba(199, 154, 94, 0.4));
}

.featured-img-wrap {
  width: 100%;
  height: clamp(280px, 42vw, 500px);
  overflow: hidden;
}
.featured-img {
  width: 100%; height: 100%;
  object-fit: cover;
  transition: transform 0.6s var(--ease-smoke);
  filter: saturate(0.9) contrast(1.05);
}
.featured-card:hover .featured-img {
  transform: scale(1.04);
  filter: saturate(1) contrast(1.08);
}
.featured-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to top,
    var(--ash-obsidian) 0%,
    rgba(18, 16, 13, 0.75) 30%,
    rgba(18, 16, 13, 0.1) 70%);
}

.featured-content {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  padding: 38px clamp(30px, 5vw, 56px);
  z-index: 1;
}

/* Banner-стиль badge "Главная весть" с шипами по бокам — вместо плоского rect */
.featured-banner {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 18px;
  padding: 6px 16px;
  background: linear-gradient(180deg,
    var(--ember-blood) 0%,
    var(--ember-deep) 100%);
  border: 1px solid var(--ember-heart);
  color: var(--ember-gold);
  font-family: var(--font-display);
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 2px;
  text-transform: uppercase;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.7);
  box-shadow:
    inset 0 1px 0 rgba(255, 201, 121, 0.25),
    inset 0 -1px 2px rgba(0, 0, 0, 0.5),
    0 0 14px rgba(226, 67, 16, 0.4);
  position: relative;
}
.fb-spike {
  width: 0; height: 0;
  border-top: 5px solid transparent;
  border-bottom: 5px solid transparent;
}
.fb-spike--l { border-right: 6px solid var(--ember-gold); }
.fb-spike--r { border-left: 6px solid var(--ember-gold); }

.featured-title {
  font-family: var(--font-display);
  font-weight: var(--fw-black, 900);
  font-size: clamp(1.5rem, 3vw, 2.3rem);
  color: var(--text-bright);
  margin: 0 0 12px;
  line-height: 1.25;
  letter-spacing: 0.3px;
  text-shadow: 0 2px 14px rgba(0, 0, 0, 0.85);
}

/* Tribal-divider под заголовком — горизонтальная линия с шипом по центру */
.feat-divider {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 0 0 18px;
  max-width: 280px;
}
.feat-divider > span:first-child,
.feat-divider > span:last-child {
  flex: 1;
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--bronze), transparent);
}
.feat-divider-spike {
  width: 0; height: 0;
  border-left: 5px solid transparent;
  border-right: 5px solid transparent;
  border-top: 8px solid var(--ember-deep);
  filter: drop-shadow(0 0 4px rgba(194, 40, 26, 0.6));
}

.featured-meta { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.news-date {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-family: var(--font-ui);
  font-size: 0.85rem;
  color: var(--text-parchment);
  letter-spacing: 0.5px;
}
.date-icon {
  font-size: 0.95rem;
  filter: drop-shadow(0 0 4px rgba(199, 154, 94, 0.4));
}
.read-label {
  font-family: var(--font-display);
  font-size: 0.95rem;
  color: var(--ember-spark);
  font-weight: 700;
  letter-spacing: 1.4px;
  text-transform: uppercase;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
}

/* ══ News grid ══ */
.news-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 24px;
}

.news-card {
  position: relative;
  background: linear-gradient(180deg,
    var(--ash-stone) 0%,
    var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  clip-path: var(--clip-forged-sm);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: var(--inset-iron-top), var(--shadow-cast);
  transition: transform 0.3s var(--ease-forge), box-shadow 0.3s var(--ease-smoke);
}
.news-card:hover {
  transform: translateY(-4px);
  border-color: var(--bronze-dark);
  box-shadow:
    inset 0 0 0 1px var(--bronze-dark),
    var(--inset-iron-top),
    var(--shadow-cast),
    0 0 28px rgba(226, 67, 16, 0.32);
}
/* Заклёпки тоже подсвечиваются на hover */
.news-card:hover .card-rivet {
  box-shadow:
    inset -1px -1px 2px rgba(0, 0, 0, 0.7),
    inset 1px 1px 1px rgba(255, 201, 121, 0.5),
    0 0 8px rgba(255, 122, 43, 0.55);
}

/* 4 заклёпки в углах карточки — кованая дощечка с гвоздями */
.card-rivet {
  position: absolute;
  width: 8px; height: 8px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%,
    var(--brass) 0%,
    var(--bronze) 45%,
    var(--iron-void) 100%);
  z-index: 3;
  box-shadow:
    inset -1px -1px 2px rgba(0, 0, 0, 0.7),
    inset 1px 1px 1px rgba(255, 201, 121, 0.35),
    0 0 4px rgba(199, 154, 94, 0.5);
}
.card-rivet--tl { top: 10px;    left: 10px; }
.card-rivet--tr { top: 10px;    right: 10px; }
.card-rivet--bl { bottom: 10px; left: 10px; }
.card-rivet--br { bottom: 10px; right: 10px; }

/* Шип-гвоздь сверху по центру — будто весть пригвождена железным шипом */
.card-spike {
  position: absolute;
  top: -2px;
  left: 50%;
  transform: translateX(-50%);
  width: 0; height: 0;
  border-left: 7px solid transparent;
  border-right: 7px solid transparent;
  border-top: 11px solid var(--iron-warm);
  filter: drop-shadow(0 1px 2px rgba(199, 154, 94, 0.4));
  z-index: 3;
}
.news-card:hover .card-spike {
  filter: drop-shadow(0 0 4px rgba(255, 167, 88, 0.6));
}

.card-img-link { position: relative; display: block; overflow: hidden; }
.card-img {
  width: 100%;
  height: 220px;
  object-fit: cover;
  display: block;
  transition: transform 0.5s var(--ease-smoke);
  filter: saturate(0.9);
}
.news-card:hover .card-img { transform: scale(1.06); filter: saturate(1); }
.card-img-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to top,
    var(--ash-coal) 0%,
    transparent 65%);
  pointer-events: none;
}

/* Kovani-divider между картинкой и текстом: тонкая бронзовая линия с шипом
   по центру — заменяет «современный» hover-overlay с надписью «Читать» */
.card-divider {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 16px;
  margin-top: -2px;
  position: relative;
  z-index: 2;
}
.cd-line {
  flex: 1;
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--bronze), transparent);
  opacity: 0.7;
}
.cd-spike {
  width: 0; height: 0;
  border-left: 4px solid transparent;
  border-right: 4px solid transparent;
  border-top: 6px solid var(--ember-deep);
  filter: drop-shadow(0 0 3px rgba(194, 40, 26, 0.55));
}

/* hover-overlay со словом «Читать» убран — заменён ember-glow по контуру
   через .news-card:hover box-shadow (см. выше). Текст «Развернуть» теперь
   живёт только в footer-кнопке .card-read-btn — и так понятно куда клик. */

.card-body {
  padding: 22px 22px 20px;
  display: flex;
  flex-direction: column;
  flex-grow: 1;
  gap: 16px;
}
.card-title {
  font-family: var(--font-display);
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-bright);
  text-decoration: none;
  line-height: 1.4;
  letter-spacing: 0.2px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  transition: color 0.2s var(--ease-smoke);
}
.card-title:hover { color: var(--ember-gold); }
.card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-top: auto;
  padding-top: 14px;
  border-top: 1px dashed var(--iron-dark);
}
.card-read-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: linear-gradient(180deg,
    rgba(28, 24, 22, 0.85) 0%,
    rgba(18, 16, 13, 0.85) 100%);
  border: 1px solid var(--bronze-dark);
  color: var(--ember-spark);
  padding: 7px 14px;
  font-family: var(--font-display);
  font-size: 0.76rem;
  font-weight: 700;
  letter-spacing: 1.4px;
  text-transform: uppercase;
  text-decoration: none;
  transition: all 0.22s var(--ease-smoke);
  box-shadow:
    inset 0 1px 0 rgba(199, 154, 94, 0.18),
    inset 0 -1px 2px rgba(0, 0, 0, 0.55);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
}
.card-read-btn:hover {
  background: linear-gradient(180deg,
    var(--ember-blood) 0%,
    var(--ember-deep) 100%);
  border-color: var(--ember-heart);
  color: var(--ember-gold);
  box-shadow:
    inset 0 1px 0 rgba(255, 201, 121, 0.3),
    inset 0 -1px 2px rgba(0, 0, 0, 0.55),
    0 0 12px rgba(226, 67, 16, 0.45);
}
.crb-arrow {
  display: inline-block;
  font-size: 0.92rem;
  transition: transform 0.22s var(--ease-forge);
}
.card-read-btn:hover .crb-arrow { transform: translateX(3px); }

/* ══ Responsive ══ */
@media (max-width: 1024px) {
  .news-grid { grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
}
@media (max-width: 768px) {
  .featured-content { padding: 24px; }
  .featured-title { font-size: 1.4rem; }
  .feat-rivet--bl, .feat-rivet--br { display: none; }
  .news-grid { grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 18px; }
}
@media (max-width: 520px) {
  .news-grid { grid-template-columns: 1fr; gap: 16px; }
  .featured-content { padding: 18px; }
  .featured-title { font-size: 1.2rem; }
}
</style>
