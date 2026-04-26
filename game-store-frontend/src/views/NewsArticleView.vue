<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import { useHead } from '@vueuse/head';
import api from '../api/axios';
import { resolveMediaUrl } from '../utils/media';

const route = useRoute();
const article = ref(null);
const loading = ref(true);
const error = ref('');
const readProgress = ref(0);
const articleId = computed(() => route.params.id);
let revealObs = null;

useHead(computed(() => {
  const title = article.value ? `${article.value.title} — GameStore` : 'Загрузка...';
  const desc = article.value?.excerpt || article.value?.title || 'Хроники GameStore';
  const img = article.value?.image ? resolveMediaUrl(article.value.image) : '/images.png';
  return {
    title,
    meta: [
      { name: 'description', content: desc },
      { property: 'og:type', content: 'article' },
      { property: 'og:title', content: title },
      { property: 'og:description', content: desc },
      { property: 'og:image', content: img },
      { name: 'robots', content: 'index, follow' }, 
    ],
  };
})); 

const onScroll = () => {
  const el = document.documentElement;
  const total = el.scrollHeight - el.clientHeight;
  readProgress.value = total > 0 ? Math.min(100, Math.round((el.scrollTop / total) * 100)) : 0; 
};

const setupReveal = () => {
  if (revealObs) revealObs.disconnect();
  revealObs = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('is-visible'); revealObs.unobserve(e.target); } });
  }, { threshold: 0.07, rootMargin: '0px 0px -40px 0px' });
  setTimeout(() => document.querySelectorAll('.article-root .reveal').forEach(el => revealObs.observe(el)), 150);
};

const formatDate = (ds) => {
  if (!ds) return '';
  return new Date(ds).toLocaleDateString('ru-RU', { year: 'numeric', month: 'long', day: 'numeric' });
};

const fetchArticle = async () => {
  loading.value = true;
  try {
    const res = await api.get(`/news/${articleId.value}`);
    article.value = res.data;
    setupReveal();
  } catch (e) {
    error.value = e.response?.status === 404
      ? 'Хроника не найдена. Возможно, свиток потерян.'
      : 'Не удалось открыть свиток. Попробуйте позже.';
    console.error(e);
  } finally { loading.value = false; }
};

onMounted(() => {
  fetchArticle();
  window.addEventListener('scroll', onScroll, { passive: true });
});
onUnmounted(() => {
  window.removeEventListener('scroll', onScroll);
  revealObs?.disconnect();
});
</script>

<template>
  <div class="article-root">
    <!-- Прогресс чтения — ember-полоса -->
    <div class="read-progress" :style="{ width: readProgress + '%' }"></div>

    <!-- Loading -->
    <div v-if="loading" class="status-wrap">
      <div class="loading-ring" aria-hidden="true">
        <svg viewBox="-32 -32 64 64" width="56" height="56">
          <circle r="24" class="lr-bg" />
          <circle r="24" class="lr-fg" pathLength="100" />
        </svg>
      </div>
      <p>Разворачиваем свиток…</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="status-wrap">
      <div class="status-sigil" aria-hidden="true">
        <svg viewBox="-32 -32 64 64" width="64" height="64">
          <circle r="22" class="ss-ring" />
          <line x1="-12" y1="-12" x2="12" y2="12" class="ss-cross" />
          <line x1="12" y1="-12" x2="-12" y2="12" class="ss-cross" />
        </svg>
      </div>
      <p class="error-text">{{ error }}</p>
      <RouterLink to="/news" class="back-link-btn">← Обратно к хроникам</RouterLink>
    </div>

    <!-- Article -->
    <article v-else-if="article" class="article-wrap">

      <!-- ═══ HERO HEADER ═══ -->
      <header class="article-hero">
        <div class="hero-img-wrap">
          <img
            :src="resolveMediaUrl(article.image)"
            :alt="article.title"
            class="hero-img"
          />
          <div class="hero-overlay"></div>
        </div>

        <div class="hero-content reveal">
          <RouterLink to="/news" class="back-link">
            <span class="bl-spike" aria-hidden="true"></span>
            <span>Все хроники</span>
          </RouterLink>
          <div class="article-meta">
            <span class="meta-date">{{ formatDate(article.published_at) }}</span>
          </div>
          <h1 class="article-title">{{ article.title }}</h1>
        </div>
      </header>

      <!-- ═══ CONTENT GRID ═══ -->
      <div class="content-grid">

        <!-- Main column -->
        <div class="main-col reveal">
          <div class="article-body-wrap">
            <span class="ab-rivet ab-rivet--tl" aria-hidden="true"></span>
            <span class="ab-rivet ab-rivet--tr" aria-hidden="true"></span>
            <span class="ab-rivet ab-rivet--bl" aria-hidden="true"></span>
            <span class="ab-rivet ab-rivet--br" aria-hidden="true"></span>
            <div class="article-body" v-html="article.content"></div>
          </div>

          <!-- Footer bar -->
          <div class="article-footer">
            <RouterLink to="/news" class="footer-back">
              <span class="fb-arrow">←</span>
              <span>Обратно к хроникам</span>
            </RouterLink>
            <div class="footer-date">{{ formatDate(article.published_at) }}</div>
          </div>
        </div>

        <!-- Sidebar -->
        <aside class="sidebar">
          <!-- Поделиться -->
          <div class="sidebar-widget reveal">
            <span class="sw-rivet sw-rivet--tl" aria-hidden="true"></span>
            <span class="sw-rivet sw-rivet--tr" aria-hidden="true"></span>
            <h3 class="widget-title">
              <span class="wt-spike" aria-hidden="true"></span>
              Разнести весть
            </h3>
            <div class="share-btns">
              <a
                :href="`https://vk.com/share.php?url=${encodeURIComponent(String($route?.fullPath || ''))}&title=${encodeURIComponent(article.title)}`"
                target="_blank" rel="noopener" class="share-btn share-vk"
              >ВКонтакте</a>
              <a
                :href="`https://t.me/share/url?url=${encodeURIComponent(String($route?.fullPath || ''))}&text=${encodeURIComponent(article.title)}`"
                target="_blank" rel="noopener" class="share-btn share-tg"
              >Telegram</a>
            </div>
          </div>

          <!-- Когда высечено -->
          <div class="sidebar-widget reveal">
            <span class="sw-rivet sw-rivet--tl" aria-hidden="true"></span>
            <span class="sw-rivet sw-rivet--tr" aria-hidden="true"></span>
            <h3 class="widget-title">
              <span class="wt-spike" aria-hidden="true"></span>
              Высечено
            </h3>
            <p class="widget-date">{{ formatDate(article.published_at) }}</p>
          </div>

          <!-- Переходы -->
          <div class="sidebar-widget back-widget reveal">
            <RouterLink to="/news" class="sidebar-back-link">
              <span>←</span>
              <span>Все хроники</span>
            </RouterLink>
            <RouterLink to="/catalog" class="sidebar-catalog-link">
              <span>В оружейную →</span>
            </RouterLink>
          </div>
        </aside>
      </div>

    </article>
  </div>
</template>

<style scoped>
/* ══ Reveal ══ */
.reveal { opacity: 0; transform: translateY(28px); transition: opacity 0.6s var(--ease-smoke), transform 0.6s var(--ease-smoke); }
.reveal.is-visible { opacity: 1; transform: none; }

.article-root { min-height: 100vh; color: var(--text-bone); }

/* ══ Progress Bar ══ */
.read-progress {
  position: fixed;
  top: 0; left: 0;
  height: 3px;
  z-index: 1000;
  background: linear-gradient(90deg,
    var(--ember-heart) 0%,
    var(--ember-flame) 30%,
    var(--ember-glow) 55%,
    var(--ember-gold) 100%);
  transition: width 0.12s linear;
  box-shadow: 0 0 12px rgba(226, 67, 16, 0.55);
}

/* ══ Status (loading/error) ══ */
.status-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  gap: 22px;
  text-align: center;
  padding: 40px;
  font-family: var(--font-body);
  color: var(--text-parchment);
}
.loading-ring {
  display: flex;
  align-items: center;
  justify-content: center;
  animation: lrRotate 1.6s linear infinite;
}
.loading-ring svg { overflow: visible; }
.lr-bg {
  fill: none;
  stroke: var(--iron-dark);
  stroke-width: 3;
}
.lr-fg {
  fill: none;
  stroke: var(--ember-flame);
  stroke-width: 3;
  stroke-linecap: round;
  stroke-dasharray: 30, 100;
  filter: drop-shadow(0 0 6px rgba(226, 67, 16, 0.55));
}
@keyframes lrRotate { to { transform: rotate(360deg); } }

.status-sigil svg { overflow: visible; }
.ss-ring {
  fill: none;
  stroke: var(--bronze);
  stroke-width: 2;
  opacity: 0.55;
}
.ss-cross {
  stroke: var(--ember-flame);
  stroke-width: 3;
  stroke-linecap: round;
  opacity: 0.8;
  filter: drop-shadow(0 0 4px rgba(255, 122, 43, 0.55));
}

.error-text {
  font-size: 1.05rem;
  margin: 0;
  color: #ffb4a8;
}
.back-link-btn {
  font-family: var(--font-ui);
  color: var(--ember-spark);
  text-decoration: none;
  font-weight: 600;
  letter-spacing: 1px;
  transition: color 0.2s var(--ease-smoke);
}
.back-link-btn:hover { color: var(--ember-gold); }

/* ══ Article wrap ══ */
.article-wrap {
  max-width: 1220px;
  margin: 0 auto;
  padding-bottom: 96px;
}

/* ══ Hero ══ */
.article-hero {
  position: relative;
  overflow: hidden;
  background: var(--ash-obsidian);
}
.hero-img-wrap {
  width: 100%;
  height: clamp(300px, 46vw, 540px);
  overflow: hidden;
}
.hero-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  filter: saturate(0.85) contrast(1.05);
  animation: heroReveal 1.2s var(--ease-smoke) both;
}
@keyframes heroReveal {
  from { transform: scale(1.08); opacity: 0.4; }
  to   { transform: scale(1); opacity: 1; }
}

.hero-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to top,
    var(--ash-obsidian) 0%,
    rgba(18, 16, 13, 0.75) 30%,
    rgba(18, 16, 13, 0.12) 65%);
}

.hero-content {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  padding: 44px clamp(24px, 5vw, 60px);
  display: flex;
  flex-direction: column;
  gap: 16px;
  z-index: 2;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  color: var(--text-parchment);
  text-decoration: none;
  font-family: var(--font-ui);
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 1.8px;
  text-transform: uppercase;
  align-self: flex-start;
  background: rgba(8, 6, 10, 0.55);
  border: 1px solid var(--bronze-dark);
  padding: 8px 16px;
  box-shadow: var(--inset-iron-top);
  transition: all 0.22s var(--ease-smoke);
}
.back-link:hover {
  color: var(--ember-gold);
  border-color: var(--bronze);
  background: rgba(8, 6, 10, 0.75);
}
.bl-spike {
  width: 0; height: 0;
  border-top: 4px solid transparent;
  border-bottom: 4px solid transparent;
  border-right: 6px solid var(--bronze);
}

.article-meta { display: flex; align-items: center; gap: 12px; }
.meta-date {
  font-family: var(--font-ui);
  font-size: 0.85rem;
  color: var(--text-ash);
  letter-spacing: 0.4px;
}

.article-title {
  font-family: var(--font-display);
  font-weight: var(--fw-black, 900);
  font-size: clamp(1.8rem, 4vw, 3rem);
  color: var(--text-bright);
  margin: 0;
  line-height: 1.2;
  letter-spacing: 0.3px;
  max-width: 920px;
  text-shadow: 0 3px 18px rgba(0, 0, 0, 0.7);
}

/* ══ Content grid ══ */
.content-grid {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 40px;
  padding: 44px clamp(20px, 4vw, 44px) 0;
  align-items: start;
}

/* ══ Article body ══ */
.main-col { min-width: 0; }

.article-body-wrap {
  position: relative;
  background: linear-gradient(180deg,
    var(--ash-ironrust) 0%,
    var(--ash-stone) 45%,
    var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  clip-path: var(--clip-forged-md);
  padding: 8px;
  box-shadow: var(--inset-iron-top), var(--shadow-cast);
  margin-bottom: 30px;
}
.ab-rivet {
  position: absolute;
  width: 10px; height: 10px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%,
    var(--brass) 0%,
    var(--bronze) 50%,
    var(--iron-void) 100%);
  z-index: 3;
  box-shadow: inset -1px -1px 2px rgba(0, 0, 0, 0.7), 0 0 4px rgba(199, 154, 94, 0.45);
}
.ab-rivet--tl { top: 16px; left: 16px; }
.ab-rivet--tr { top: 16px; right: 16px; }
.ab-rivet--bl { bottom: 16px; left: 16px; }
.ab-rivet--br { bottom: 16px; right: 16px; }

.article-body {
  font-family: var(--font-body);
  font-size: 1.06rem;
  line-height: 1.88;
  color: var(--text-bone);
  padding: 34px clamp(26px, 4vw, 42px);
}
.article-body :deep(h2),
.article-body :deep(h3) {
  font-family: var(--font-display);
  font-size: 1.55rem;
  font-weight: 700;
  color: var(--text-bright);
  margin: 2.2em 0 0.8em;
  padding-bottom: 0.4em;
  border-bottom: 1px solid var(--bronze-dark);
  letter-spacing: 0.3px;
}
.article-body :deep(h3) { font-size: 1.28rem; }
.article-body :deep(p) { margin: 0 0 1.5em; }
.article-body :deep(p:last-child) { margin: 0; }
.article-body :deep(ul),
.article-body :deep(ol) { padding-left: 28px; margin: 0 0 1.5em; }
.article-body :deep(li) { margin-bottom: 0.6em; }
.article-body :deep(a) {
  color: var(--ember-spark);
  text-decoration: none;
  border-bottom: 1px solid rgba(255, 167, 88, 0.35);
  transition: color 0.2s var(--ease-smoke), border-color 0.2s var(--ease-smoke);
}
.article-body :deep(a:hover) {
  color: var(--ember-gold);
  border-bottom-color: var(--ember-gold);
}
.article-body :deep(blockquote) {
  position: relative;
  border-left: 3px solid var(--ember-flame);
  padding: 18px 22px;
  margin: 1.8em 0;
  font-style: italic;
  color: var(--text-bone);
  background: linear-gradient(90deg,
    rgba(226, 67, 16, 0.08) 0%,
    transparent 100%);
  box-shadow: var(--inset-iron-top);
}
.article-body :deep(blockquote)::before {
  content: '';
  position: absolute;
  top: -1px; bottom: -1px;
  left: -3px;
  width: 3px;
  background: linear-gradient(180deg,
    var(--ember-gold) 0%,
    var(--ember-flame) 50%,
    var(--ember-heart) 100%);
  box-shadow: 0 0 8px rgba(226, 67, 16, 0.55);
}
.article-body :deep(img) {
  max-width: 100%;
  margin: 1.6em 0;
  border: 1px solid var(--iron-mid);
  box-shadow: var(--shadow-cast);
}
.article-body :deep(code) {
  font-family: var(--font-mono);
  background: rgba(8, 6, 10, 0.6);
  color: var(--ember-gold);
  padding: 2px 8px;
  border: 1px solid var(--iron-dark);
  font-size: 0.9em;
}
.article-body :deep(pre) {
  font-family: var(--font-mono);
  background: rgba(8, 6, 10, 0.75);
  border: 1px solid var(--iron-mid);
  padding: 22px;
  overflow-x: auto;
  margin: 1.5em 0;
  box-shadow: var(--inset-iron-top);
}
.article-body :deep(strong) {
  color: var(--text-bright);
  font-weight: 700;
}

.article-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 26px;
  background: linear-gradient(180deg,
    var(--ash-stone) 0%,
    var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  box-shadow: var(--inset-iron-top);
  flex-wrap: wrap;
  gap: 12px;
}
.footer-back {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: var(--ember-spark);
  text-decoration: none;
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 0.95rem;
  letter-spacing: 1px;
  transition: color 0.2s var(--ease-smoke);
}
.footer-back:hover { color: var(--ember-gold); }
.fb-arrow {
  color: var(--bronze);
  font-size: 1.1rem;
}
.footer-date {
  font-family: var(--font-ui);
  color: var(--text-ash);
  font-size: 0.85rem;
}

/* ══ Sidebar ══ */
.sidebar {
  display: flex;
  flex-direction: column;
  gap: 20px;
  position: sticky;
  top: 92px;
}

.sidebar-widget {
  position: relative;
  background: linear-gradient(180deg,
    var(--ash-stone) 0%,
    var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  clip-path: var(--clip-forged-sm);
  padding: 24px 22px;
  box-shadow: var(--inset-iron-top), var(--shadow-cast);
}
.sw-rivet {
  position: absolute;
  width: 6px; height: 6px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%,
    var(--brass) 0%,
    var(--bronze) 50%,
    var(--iron-void) 100%);
  z-index: 2;
}
.sw-rivet--tl { top: 10px; left: 10px; }
.sw-rivet--tr { top: 10px; right: 10px; }

.widget-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-family: var(--font-display);
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--text-bright);
  margin: 0 0 16px;
  padding-bottom: 12px;
  border-bottom: 1px dashed var(--iron-dark);
  letter-spacing: 0.3px;
}
.wt-spike {
  width: 0; height: 0;
  border-left: 4px solid transparent;
  border-right: 4px solid transparent;
  border-top: 6px solid var(--bronze);
  filter: drop-shadow(0 0 3px rgba(199, 154, 94, 0.5));
}
.widget-date {
  font-family: var(--font-body);
  color: var(--text-parchment);
  font-size: 0.92rem;
  margin: 0;
}

.share-btns { display: flex; flex-direction: column; gap: 10px; }
.share-btn {
  display: block;
  text-align: center;
  padding: 11px 14px;
  color: var(--text-bright);
  font-family: var(--font-ui);
  font-weight: 700;
  font-size: 0.82rem;
  letter-spacing: 1.3px;
  text-transform: uppercase;
  text-decoration: none;
  box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.3);
  transition: transform 0.2s var(--ease-forge), filter 0.2s var(--ease-smoke);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
  border: 1px solid var(--iron-mid);
}
.share-btn:hover {
  transform: translateY(-2px);
  filter: brightness(1.15);
}
.share-vk {
  background: linear-gradient(180deg, #4a76a8 0%, #3a5f8f 100%);
  border-color: #3a5f8f;
}
.share-tg {
  background: linear-gradient(180deg, #2ca1dc 0%, #1f8bc4 100%);
  border-color: #1f8bc4;
}

/* Переходы-ссылки */
.back-widget {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.sidebar-back-link,
.sidebar-catalog-link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  text-decoration: none;
  font-family: var(--font-ui);
  font-size: 0.84rem;
  font-weight: 700;
  letter-spacing: 1.3px;
  text-transform: uppercase;
  transition: all 0.22s var(--ease-smoke);
  box-shadow: var(--inset-iron-top);
}
.sidebar-back-link {
  background: rgba(8, 6, 10, 0.55);
  border: 1px solid var(--iron-mid);
  color: var(--text-parchment);
}
.sidebar-back-link:hover {
  background: rgba(8, 6, 10, 0.75);
  color: var(--text-bright);
  border-color: var(--bronze-dark);
}
.sidebar-catalog-link {
  background: linear-gradient(180deg,
    rgba(226, 67, 16, 0.22) 0%,
    rgba(138, 31, 24, 0.25) 100%);
  border: 1px solid var(--ember-heart);
  color: var(--ember-gold);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
}
.sidebar-catalog-link:hover {
  background: linear-gradient(180deg,
    rgba(226, 67, 16, 0.35) 0%,
    rgba(194, 40, 26, 0.35) 100%);
  color: var(--text-bright);
  box-shadow: var(--inset-iron-top), 0 0 14px rgba(226, 67, 16, 0.35);
}

/* ══ Responsive ══ */
@media (max-width: 1024px) {
  .content-grid { grid-template-columns: 1fr; }
  .sidebar { position: static; }
}
@media (max-width: 768px) {
  .hero-content { padding: 24px; }
  .article-title { font-size: 1.6rem; }
  .article-body { padding: 24px; font-size: 1rem; }
  .ab-rivet--bl, .ab-rivet--br { display: none; }
}
</style>
