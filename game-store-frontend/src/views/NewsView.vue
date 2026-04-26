<template>
  <div class="news-root">
    <!-- ═══ HERO ═══ -->
    <section class="news-hero">
      <div class="hero-bg" aria-hidden="true">
        <div class="hero-ember hero-ember-1"></div>
        <div class="hero-ember hero-ember-2"></div>
        <div class="hero-grid"></div>
      </div>
      <div class="hero-inner">
        <span class="tribal-eyebrow reveal">
          <span class="eb-spike"></span>
          Хроники
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
        <!-- Главная весть -->
        <RouterLink :to="'/news/' + newsItems[0].id" class="featured-card reveal">
          <span class="feat-rivet feat-rivet--tl" aria-hidden="true"></span>
          <span class="feat-rivet feat-rivet--tr" aria-hidden="true"></span>
          <span class="feat-rivet feat-rivet--bl" aria-hidden="true"></span>
          <span class="feat-rivet feat-rivet--br" aria-hidden="true"></span>
          <div class="featured-img-wrap">
            <img
              :src="resolveMediaUrl(newsItems[0].image)"
              :alt="newsItems[0].title"
              class="featured-img" loading="lazy"
            />
            <div class="featured-overlay"></div>
          </div>
          <div class="featured-content">
            <span class="featured-badge">Главная весть</span>
            <h2 class="featured-title">{{ newsItems[0].title }}</h2>
            <div class="featured-meta">
              <span class="news-date">{{ formatDate(newsItems[0].published_at) }}</span>
              <span class="read-label">Читать хронику →</span>
            </div>
          </div>
        </RouterLink>

        <!-- Лента -->
        <div v-if="newsItems.length > 1" class="news-grid">
          <article
            v-for="(item, i) in newsItems.slice(1)"
            :key="item.id"
            class="news-card reveal"
            :style="{ '--i': i % 3 }"
          >
            <span class="card-rivet card-rivet--tl" aria-hidden="true"></span>
            <span class="card-rivet card-rivet--tr" aria-hidden="true"></span>
            <RouterLink :to="'/news/' + item.id" class="card-img-link">
              <img
                :src="resolveMediaUrl(item.image)"
                :alt="item.title" class="card-img" loading="lazy"
                width="400" height="220"
              />
              <div class="card-img-overlay"></div>
              <div class="card-img-hover">
                <span>Читать</span>
              </div>
            </RouterLink>

            <div class="card-body">
              <RouterLink :to="'/news/' + item.id" class="card-title">{{ item.title }}</RouterLink>
              <div class="card-footer">
                <span class="news-date">{{ formatDate(item.published_at) }}</span>
                <RouterLink :to="'/news/' + item.id" class="card-read-btn">Подробнее →</RouterLink>
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
}
.hero-bg { position: absolute; inset: 0; pointer-events: none; z-index: 0; }
.hero-ember {
  position: absolute;
  border-radius: 50%;
  filter: blur(110px);
}
.hero-ember-1 {
  width: 560px; height: 560px;
  background: radial-gradient(circle, var(--ember-glow) 0%, transparent 70%);
  top: -120px; left: -80px;
  opacity: 0.3;
  animation: newsFloat 14s ease-in-out infinite;
}
.hero-ember-2 {
  width: 440px; height: 440px;
  background: radial-gradient(circle, var(--ember-heart) 0%, transparent 70%);
  bottom: -100px; right: -60px;
  opacity: 0.25;
  animation: newsFloat 18s ease-in-out infinite reverse;
}
.hero-grid {
  position: absolute; inset: 0;
  background-image:
    linear-gradient(rgba(122, 93, 72, 0.07) 1px, transparent 1px),
    linear-gradient(90deg, rgba(122, 93, 72, 0.07) 1px, transparent 1px);
  background-size: 54px 54px;
  mask-image: radial-gradient(ellipse at center, black 25%, transparent 80%);
  -webkit-mask-image: radial-gradient(ellipse at center, black 25%, transparent 80%);
}
@keyframes newsFloat {
  0%, 100% { transform: translate(0, 0); }
  50%      { transform: translate(22px, -18px); }
}

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
.featured-badge {
  display: inline-block;
  margin-bottom: 16px;
  padding: 5px 16px;
  background: linear-gradient(135deg,
    rgba(226, 67, 16, 0.25),
    rgba(194, 40, 26, 0.2));
  border: 1px solid var(--ember-heart);
  color: var(--ember-gold);
  font-family: var(--font-ui);
  font-size: 0.74rem;
  font-weight: 700;
  letter-spacing: 2.5px;
  text-transform: uppercase;
  box-shadow: 0 0 12px rgba(226, 67, 16, 0.35);
}
.featured-title {
  font-family: var(--font-display);
  font-weight: var(--fw-black, 900);
  font-size: clamp(1.5rem, 3vw, 2.3rem);
  color: var(--text-bright);
  margin: 0 0 18px;
  line-height: 1.25;
  letter-spacing: 0.3px;
  text-shadow: 0 2px 14px rgba(0, 0, 0, 0.8);
}
.featured-meta { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.news-date {
  font-family: var(--font-ui);
  font-size: 0.85rem;
  color: var(--text-parchment);
  letter-spacing: 0.4px;
}
.read-label {
  font-family: var(--font-display);
  font-size: 0.95rem;
  color: var(--ember-spark);
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: uppercase;
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
  transform: translateY(-6px);
  box-shadow: var(--inset-iron-top), var(--shadow-cast), 0 0 22px rgba(226, 67, 16, 0.25);
}

.card-rivet {
  position: absolute;
  width: 7px; height: 7px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%,
    var(--brass) 0%,
    var(--bronze) 50%,
    var(--iron-void) 100%);
  z-index: 3;
  box-shadow: inset -1px -1px 1px rgba(0, 0, 0, 0.7);
}
.card-rivet--tl { top: 10px; left: 10px; }
.card-rivet--tr { top: 10px; right: 10px; }

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
.card-img-hover {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(226, 67, 16, 0.18);
  color: var(--ember-gold);
  font-family: var(--font-display);
  font-size: 1rem;
  font-weight: 700;
  letter-spacing: 2px;
  text-transform: uppercase;
  text-shadow: 0 0 10px rgba(255, 201, 121, 0.45);
  opacity: 0;
  transition: opacity 0.25s var(--ease-smoke);
}
.news-card:hover .card-img-hover { opacity: 1; }

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
  background: rgba(8, 6, 10, 0.5);
  border: 1px solid var(--bronze-dark);
  color: var(--ember-spark);
  padding: 6px 14px;
  font-family: var(--font-ui);
  font-size: 0.76rem;
  font-weight: 700;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  text-decoration: none;
  transition: all 0.2s var(--ease-smoke);
  box-shadow: var(--inset-iron-top);
}
.card-read-btn:hover {
  background: rgba(226, 67, 16, 0.18);
  border-color: var(--ember-flame);
  color: var(--ember-gold);
}

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
