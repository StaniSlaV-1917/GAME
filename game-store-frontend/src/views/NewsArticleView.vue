<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import { useHead } from '@vueuse/head';
import api from '../api/axios';

const route = useRoute();
const article = ref(null);
const loading = ref(true);
const error = ref('');
const readProgress = ref(0);
const articleId = computed(() => route.params.id);
let revealObs = null;

useHead(computed(() => ({
  title: article.value ? `${article.value.title} — GameStore` : 'Загрузка...',
  meta: [{ name: 'description', content: article.value?.title || '' }]
})));

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
      ? 'Новость не найдена. Возможно, она была удалена.'
      : 'Произошла ошибка при загрузке.';
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
    <!-- Reading progress bar -->
    <div class="read-progress" :style="{ width: readProgress + '%' }"></div>

    <!-- Loading -->
    <div v-if="loading" class="status-wrap">
      <div class="loading-ring"></div>
      <p>Загружаем статью...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="status-wrap">
      <div class="status-icon">😕</div>
      <p class="error-text">{{ error }}</p>
      <RouterLink to="/news" class="back-link-btn">← Вернуться к новостям</RouterLink>
    </div>

    <!-- Article -->
    <article v-else-if="article" class="article-wrap">

      <!-- ═══ HERO HEADER ═══ -->
      <header class="article-hero">
        <div class="hero-img-wrap">
          <img
            :src="article.image || 'https://via.placeholder.com/1400x600'"
            :alt="article.title"
            class="hero-img"
          />
          <div class="hero-overlay"></div>
        </div>

        <div class="hero-content reveal">
          <RouterLink to="/news" class="back-link">← Все новости</RouterLink>
          <div class="article-meta">
            <span class="meta-date">📅 {{ formatDate(article.published_at) }}</span>
          </div>
          <h1 class="article-title">{{ article.title }}</h1>
        </div>
      </header>

      <!-- ═══ CONTENT GRID ═══ -->
      <div class="content-grid">

        <!-- Main column -->
        <div class="main-col reveal">
          <div class="article-body" v-html="article.content"></div>

          <!-- Tags / Footer -->
          <div class="article-footer">
            <RouterLink to="/news" class="footer-back">← Обратно к новостям</RouterLink>
            <div class="footer-date">{{ formatDate(article.published_at) }}</div>
          </div>
        </div>

        <!-- Sidebar -->
        <aside class="sidebar reveal">

          <!-- Share widget -->
          <div class="sidebar-widget">
            <h3 class="widget-title">🔗 Поделиться</h3>
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

          <!-- Navigation widget -->
          <div class="sidebar-widget">
            <h3 class="widget-title">🗓 Опубликовано</h3>
            <p class="widget-date">{{ formatDate(article.published_at) }}</p>
          </div>

          <!-- Back link -->
          <div class="sidebar-widget back-widget">
            <RouterLink to="/news" class="sidebar-back-link">
              <span>←</span>
              <span>Все новости</span>
            </RouterLink>
            <RouterLink to="/catalog" class="sidebar-catalog-link">
              <span>🎮</span>
              <span>Перейти в каталог</span>
            </RouterLink>
          </div>

        </aside>
      </div>

    </article>
  </div>
</template>

<style scoped>
/* ─── Reveal ─── */
.reveal { opacity: 0; transform: translateY(28px); transition: opacity 0.6s ease, transform 0.6s ease; }
.reveal.is-visible { opacity: 1; transform: none; }

/* ─── Root ─── */
.article-root { min-height: 100vh; color: #e5e7eb; }

/* ─── Progress Bar ─── */
.read-progress {
  position: fixed; top: 0; left: 0; height: 3px; z-index: 1000;
  background: linear-gradient(90deg, #3b82f6, #8b5cf6, #06b6d4);
  transition: width 0.12s linear;
  box-shadow: 0 0 10px rgba(99,102,241,0.6);
}

/* ─── Status ─── */
.status-wrap {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  min-height: 60vh; gap: 20px; text-align: center; padding: 40px;
}
.loading-ring { width: 48px; height: 48px; border-radius: 50%; border: 3px solid rgba(59,130,246,0.15); border-top-color: #3b82f6; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.status-icon { font-size: 3rem; }
.error-text { color: #fca5a5; font-size: 1.05rem; margin: 0; }
.back-link-btn { color: #60a5fa; text-decoration: none; font-weight: 600; transition: color 0.2s; }
.back-link-btn:hover { color: #93c5fd; }

/* ─── Article Wrap ─── */
.article-wrap { max-width: 1200px; margin: 0 auto; padding-bottom: 80px; }

/* ─── Hero ─── */
.article-hero { position: relative; overflow: hidden; background: #0a0f1e; }
.hero-img-wrap { width: 100%; height: clamp(280px, 45vw, 520px); overflow: hidden; }
.hero-img { width: 100%; height: 100%; object-fit: cover; display: block; animation: heroReveal 1.2s ease-out both; }
@keyframes heroReveal { from { transform: scale(1.08); opacity: 0.5; } to { transform: scale(1); opacity: 1; } }

.hero-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to top, rgba(10,15,30,1) 0%, rgba(10,15,30,0.65) 30%, rgba(10,15,30,0.1) 65%);
}

.hero-content {
  position: absolute; bottom: 0; left: 0; right: 0;
  padding: 40px clamp(24px, 5vw, 60px);
  display: flex; flex-direction: column; gap: 14px;
}

.back-link {
  display: inline-flex; align-items: center; gap: 6px;
  color: #9ca3af; text-decoration: none; font-size: 0.88rem;
  transition: color 0.2s; align-self: flex-start;
  background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12);
  padding: 6px 14px; border-radius: 999px;
}
.back-link:hover { color: #fff; background: rgba(255,255,255,0.14); }

.article-meta { display: flex; align-items: center; gap: 12px; }
.meta-date { font-size: 0.85rem; color: #9ca3af; }

.article-title {
  font-size: clamp(1.6rem, 4vw, 2.8rem); font-weight: 800; color: #fff;
  margin: 0; line-height: 1.25; text-shadow: 0 2px 20px rgba(0,0,0,0.5);
  max-width: 900px;
}

/* ─── Content Grid ─── */
.content-grid {
  display: grid; grid-template-columns: 1fr 300px;
  gap: 36px; padding: 40px clamp(20px, 4vw, 40px) 0;
  align-items: start;
}

/* ─── Article Body ─── */
.main-col { min-width: 0; }

.article-body {
  font-size: 1.05rem; line-height: 1.85; color: #c4cad4;
  background: rgba(15,23,42,0.65); backdrop-filter: blur(12px);
  border: 1px solid rgba(255,255,255,0.07); border-radius: 16px;
  padding: 36px; margin-bottom: 28px;
}
.article-body :deep(h2),
.article-body :deep(h3) {
  font-size: 1.5rem; font-weight: 700; color: #e5e7eb;
  margin: 2.2em 0 0.8em;
  padding-bottom: 0.4em; border-bottom: 1px solid rgba(59,130,246,0.25);
}
.article-body :deep(h3) { font-size: 1.25rem; }
.article-body :deep(p) { margin: 0 0 1.5em; }
.article-body :deep(p:last-child) { margin: 0; }
.article-body :deep(ul),
.article-body :deep(ol) { padding-left: 28px; margin: 0 0 1.5em; }
.article-body :deep(li) { margin-bottom: 0.6em; }
.article-body :deep(a) { color: #60a5fa; text-decoration: none; border-bottom: 1px solid rgba(96,165,250,0.3); transition: all 0.2s; }
.article-body :deep(a:hover) { color: #93c5fd; border-color: #93c5fd; }
.article-body :deep(blockquote) {
  border-left: 3px solid #3b82f6; padding: 14px 20px;
  margin: 1.8em 0; font-style: italic; color: #d1d5db;
  background: rgba(59,130,246,0.07); border-radius: 0 10px 10px 0;
}
.article-body :deep(img) { max-width: 100%; border-radius: 10px; margin: 1.5em 0; }
.article-body :deep(code) { background: rgba(59,130,246,0.1); color: #93c5fd; padding: 2px 8px; border-radius: 5px; font-size: 0.9em; }
.article-body :deep(pre) { background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 20px; overflow-x: auto; margin: 1.5em 0; }
.article-body :deep(strong) { color: #e5e7eb; font-weight: 700; }

.article-footer {
  display: flex; justify-content: space-between; align-items: center;
  padding: 20px 24px; background: rgba(15,23,42,0.5);
  border: 1px solid rgba(255,255,255,0.07); border-radius: 12px; flex-wrap: wrap; gap: 12px;
}
.footer-back {
  color: #60a5fa; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: color 0.2s;
}
.footer-back:hover { color: #93c5fd; }
.footer-date { color: #6b7280; font-size: 0.85rem; }

/* ─── Sidebar ─── */
.sidebar { display: flex; flex-direction: column; gap: 20px; position: sticky; top: 88px; }

.sidebar-widget {
  background: rgba(15,23,42,0.75); backdrop-filter: blur(14px);
  border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 22px;
}
.widget-title { font-size: 0.95rem; font-weight: 700; color: #e5e7eb; margin: 0 0 16px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.08); }
.widget-date { color: #9ca3af; font-size: 0.9rem; margin: 0; }

.share-btns { display: flex; flex-direction: column; gap: 10px; }
.share-btn { display: block; text-align: center; padding: 11px; border-radius: 10px; color: #fff; font-weight: 600; font-size: 0.88rem; text-decoration: none; transition: filter 0.2s, transform 0.2s; }
.share-btn:hover { filter: brightness(1.15); transform: translateY(-2px); }
.share-vk { background: #4a76a8; }
.share-tg { background: #24a1de; }

.back-widget { display: flex; flex-direction: column; gap: 10px; padding: 16px; }
.sidebar-back-link,
.sidebar-catalog-link {
  display: flex; align-items: center; gap: 10px; padding: 12px 16px;
  border-radius: 10px; text-decoration: none; font-size: 0.88rem; font-weight: 600;
  transition: all 0.22s;
}
.sidebar-back-link { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #d1d5db; }
.sidebar-back-link:hover { background: rgba(255,255,255,0.1); color: #fff; }
.sidebar-catalog-link { background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.25); color: #93c5fd; }
.sidebar-catalog-link:hover { background: rgba(59,130,246,0.2); border-color: #3b82f6; color: #fff; }

/* ─── Responsive ─── */
@media (max-width: 1024px) { .content-grid { grid-template-columns: 1fr; } .sidebar { position: static; } }
@media (max-width: 768px) {
  .hero-content { padding: 24px; }
  .article-title { font-size: 1.5rem; }
  .article-body { padding: 22px; }
}
</style>
