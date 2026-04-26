<template>
  <div class="news-root">
    <!-- ═══ HERO ═══ -->
    <section class="news-hero">
      <div class="hero-blobs">
        <div class="blob b1"></div>
        <div class="blob b2"></div>
        <div class="blob b3"></div>
      </div>
      <div class="grid-overlay"></div>
      <div class="hero-inner">
        <div class="hero-badge reveal">Новости</div>
        <h1 class="hero-title reveal">Игровые <span class="grad-text">новости</span></h1>
        <p class="hero-sub reveal">Самые свежие анонсы, обзоры и события из мира игр. Будьте в курсе вместе с GameStore.</p>
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
        <div class="empty-icon">😕</div>
        <p class="empty-text">{{ error }}</p>
        <button @click="fetchNews" class="retry-btn">Попробовать снова</button>
      </div>

      <div v-else-if="newsItems.length">
        <!-- Featured Article (first item, large) -->
        <RouterLink :to="'/news/' + newsItems[0].id" class="featured-card reveal">
          <div class="featured-img-wrap">
            <img
              :src="resolveMediaUrl(newsItems[0].image)"
              :alt="newsItems[0].title"
              class="featured-img" loading="lazy"
            />
            <div class="featured-overlay"></div>
          </div>
          <div class="featured-content">
            <div class="featured-badge">Главная новость</div>
            <h2 class="featured-title">{{ newsItems[0].title }}</h2>
            <div class="featured-meta">
              <span class="news-date">📅 {{ formatDate(newsItems[0].published_at) }}</span>
              <span class="read-label">Читать →</span>
            </div>
          </div>
        </RouterLink>

        <!-- News Grid (remaining items) -->
        <div v-if="newsItems.length > 1" class="news-grid">
          <article
            v-for="(item, i) in newsItems.slice(1)"
            :key="item.id"
            class="news-card reveal"
            :style="{ '--i': i % 3 }"
          >
            <RouterLink :to="'/news/' + item.id" class="card-img-link">
              <img
                :src="resolveMediaUrl(item.image)"
                :alt="item.title" class="card-img" loading="lazy"
                width="400" height="220"
              />
              <div class="card-img-overlay"></div>
              <div class="card-img-hover">Читать →</div>
            </RouterLink>

            <div class="card-body">
              <RouterLink :to="'/news/' + item.id" class="card-title">{{ item.title }}</RouterLink>
              <div class="card-footer">
                <span class="news-date">{{ formatDate(item.published_at) }}</span>
                <RouterLink :to="'/news/' + item.id" class="card-read-btn">Подробнее</RouterLink>
              </div>
            </div>
          </article>
        </div>
      </div>

      <!-- Empty -->
      <div v-else class="empty-box">
        <div class="empty-icon">📭</div>
        <p class="empty-text">Новостей пока нет. Загляните позже!</p>
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
  title: 'Новости игровой индустрии — GameStore',
  meta: [
    { name: 'description', content: 'Читайте последние новости из мира игр. Свежие анонсы, обзоры и события в блоге GameStore.' },
    { property: 'og:type', content: 'website' },
    { property: 'og:title', content: 'Новости игровой индустрии — GameStore' },
    { property: 'og:description', content: 'Читайте последние новости из мира игр. Свежие анонсы, обзоры и события в блоге GameStore.' },
    { property: 'og:image', content: '/images.png' },
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
    error.value = 'Не удалось загрузить новости. Попробуйте позже.';
    console.error(e);
  } finally { loading.value = false; }
};

onMounted(() => { fetchNews(); setupReveal(); });
onUnmounted(() => revealObs?.disconnect());
</script>

<style scoped>
/* ─── Reveal ─── */
.reveal { opacity: 0; transform: translateY(30px); transition: opacity 0.55s ease calc(var(--i, 0) * 80ms), transform 0.55s ease calc(var(--i, 0) * 80ms); }
.reveal.is-visible { opacity: 1; transform: none; }

/* ─── Root ─── */
.news-root { min-height: 100vh; color: #e5e7eb; }

/* ─── Hero ─── */
.news-hero {
  position: relative; min-height: 40vh; display: flex; align-items: center;
  justify-content: center; overflow: hidden; background: #030712;
}
.hero-blobs { position: absolute; inset: 0; pointer-events: none; z-index: 0; }
.blob { position: absolute; border-radius: 50%; filter: blur(90px); opacity: 0.2; }
.b1 { width: 500px; height: 500px; background: #3b82f6; top: -25%; left: -5%; animation: blobFloat 16s ease-in-out infinite; }
.b2 { width: 380px; height: 380px; background: #8b5cf6; bottom: -20%; right: 10%; animation: blobFloat 20s ease-in-out infinite reverse; }
.b3 { width: 280px; height: 280px; background: #06b6d4; top: 10%; right: 25%; animation: blobFloat 24s ease-in-out infinite 4s; }

@keyframes blobFloat {
  0%,100% { transform: translate(0,0) scale(1); }
  33% { transform: translate(25px,-18px) scale(1.04); }
  66% { transform: translate(-18px,12px) scale(0.96); }
}

.grid-overlay {
  position: absolute; inset: 0; z-index: 0;
  background-image: linear-gradient(rgba(59,130,246,0.06) 1px, transparent 1px), linear-gradient(90deg, rgba(59,130,246,0.06) 1px, transparent 1px);
  background-size: 44px 44px; mask-image: radial-gradient(ellipse at center, black 20%, transparent 75%);
}

.hero-inner {
  position: relative; z-index: 1; text-align: center;
  padding: 70px 24px 50px; display: flex; flex-direction: column; align-items: center; gap: 18px;
  max-width: 700px;
}
.hero-badge {
  display: inline-flex; align-items: center; gap: 8px;
  background: rgba(59,130,246,0.12); border: 1px solid rgba(59,130,246,0.3);
  color: #93c5fd; padding: 6px 20px; border-radius: 999px; font-size: 0.85rem; font-weight: 600;
}
.hero-title { font-size: clamp(2rem, 5vw, 3.4rem); font-weight: 800; color: #fff; margin: 0; line-height: 1.15; }
.grad-text { background: linear-gradient(135deg, #3b82f6, #8b5cf6, #06b6d4); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.hero-sub { color: #9ca3af; font-size: 1.05rem; margin: 0; max-width: 560px; line-height: 1.6; }

/* ─── Body ─── */
.news-body { max-width: 1280px; margin: 0 auto; padding: 40px 24px 80px; }

/* ─── Loading Skeletons ─── */
.news-loading-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
.skel-card { border-radius: 16px; overflow: hidden; background: rgba(15,23,42,0.7); border: 1px solid rgba(255,255,255,0.06); }
.skel-img { height: 220px; background: linear-gradient(90deg, #1f2937 25%, #2d3748 50%, #1f2937 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
.skel-body { padding: 18px; display: flex; flex-direction: column; gap: 12px; }
.skel-line { height: 12px; border-radius: 6px; background: linear-gradient(90deg, #1f2937 25%, #2d3748 50%, #1f2937 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
.w80 { width: 80%; } .w60 { width: 60%; } .w40 { width: 40%; }
@keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

/* ─── Empty / Error ─── */
.empty-box { text-align: center; padding: 80px 40px; display: flex; flex-direction: column; align-items: center; gap: 18px; }
.empty-icon { font-size: 3.5rem; }
.empty-text { color: #9ca3af; font-size: 1.05rem; margin: 0; }
.retry-btn { padding: 11px 28px; background: linear-gradient(135deg, #3b82f6, #6366f1); border: none; border-radius: 12px; color: #fff; font-weight: 600; cursor: pointer; transition: all 0.22s; }
.retry-btn:hover { transform: translateY(-2px); filter: brightness(1.1); }

/* ─── Featured Card ─── */
.featured-card {
  display: block; text-decoration: none; border-radius: 20px; overflow: hidden;
  position: relative; margin-bottom: 36px;
  background: #0f172a; border: 1px solid rgba(255,255,255,0.08);
  transition: all 0.3s ease;
}
.featured-card:hover { border-color: rgba(59,130,246,0.4); box-shadow: 0 20px 60px rgba(0,0,0,0.5); transform: translateY(-4px); }

.featured-img-wrap { width: 100%; height: clamp(260px, 40vw, 480px); overflow: hidden; }
.featured-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
.featured-card:hover .featured-img { transform: scale(1.04); }
.featured-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to top, rgba(10,15,30,0.95) 0%, rgba(10,15,30,0.6) 35%, rgba(10,15,30,0.1) 65%);
}

.featured-content { position: absolute; bottom: 0; left: 0; right: 0; padding: 36px 40px; }
.featured-badge {
  display: inline-block; margin-bottom: 14px;
  background: linear-gradient(135deg, rgba(59,130,246,0.3), rgba(99,102,241,0.3));
  border: 1px solid rgba(59,130,246,0.5); color: #93c5fd;
  padding: 5px 16px; border-radius: 999px; font-size: 0.78rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;
}
.featured-title { font-size: clamp(1.4rem, 3vw, 2.2rem); font-weight: 800; color: #fff; margin: 0 0 16px; line-height: 1.3; }
.featured-meta { display: flex; align-items: center; justify-content: space-between; }
.news-date { font-size: 0.85rem; color: #9ca3af; }
.read-label { font-size: 0.9rem; color: #60a5fa; font-weight: 600; }

/* ─── News Grid ─── */
.news-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 24px; }

.news-card {
  background: rgba(15,23,42,0.75); backdrop-filter: blur(12px);
  border: 1px solid rgba(255,255,255,0.07); border-radius: 16px;
  overflow: hidden; display: flex; flex-direction: column;
  transition: all 0.28s ease;
}
.news-card:hover { transform: translateY(-6px); border-color: rgba(59,130,246,0.35); box-shadow: 0 16px 40px rgba(0,0,0,0.5); }

.card-img-link { position: relative; display: block; overflow: hidden; }
.card-img { width: 100%; height: 220px; object-fit: cover; display: block; transition: transform 0.4s ease; }
.news-card:hover .card-img { transform: scale(1.06); }
.card-img-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(15,23,42,0.7) 0%, transparent 60%); pointer-events: none; }
.card-img-hover {
  position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
  background: rgba(59,130,246,0.15); color: #93c5fd; font-size: 1rem; font-weight: 700;
  opacity: 0; transition: opacity 0.25s;
}
.news-card:hover .card-img-hover { opacity: 1; }

.card-body { padding: 20px; display: flex; flex-direction: column; flex-grow: 1; gap: 14px; }
.card-title { font-size: 1.1rem; font-weight: 700; color: #f1f5f9; text-decoration: none; line-height: 1.45; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; transition: color 0.2s; }
.card-title:hover { color: #60a5fa; }
.card-footer { display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: 14px; border-top: 1px solid rgba(255,255,255,0.07); }
.card-read-btn {
  background: rgba(59,130,246,0.12); border: 1px solid rgba(59,130,246,0.25);
  color: #93c5fd; padding: 6px 16px; border-radius: 8px; text-decoration: none;
  font-size: 0.82rem; font-weight: 600; transition: all 0.2s;
}
.card-read-btn:hover { background: rgba(59,130,246,0.25); border-color: #3b82f6; color: #fff; }

/* ─── Responsive ─── */
@media (max-width: 768px) { .featured-content { padding: 24px; } .featured-title { font-size: 1.4rem; } }
@media (max-width: 520px) { .news-grid { grid-template-columns: 1fr; } }
</style>
