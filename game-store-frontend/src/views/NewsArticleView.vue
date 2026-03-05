<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import api from '../api/axios';

const route = useRoute();
const article = ref(null);
const loading = ref(true);
const error = ref('');

const articleId = computed(() => route.params.id);

const fetchArticle = async () => {
  loading.value = true;
  try {
    const response = await api.get(`/news/${articleId.value}`);
    article.value = response.data;
  } catch (e) {
    console.error(e);
    if (e.response && e.response.status === 404) {
      error.value = 'Новость не найдена. Возможно, она была удалена или перенесена.';
    } else {
      error.value = 'Произошла ошибка при загрузке новости.';
    }
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchArticle();
});

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('ru-RU', { year: 'numeric', month: 'long', day: 'numeric' });
};

</script>

<template>
  <main class="article-page">
    <div v-if="loading" class="loading-placeholder">Загрузка статьи...</div>
    <div v-if="error" class="error-message">{{ error }}</div>

    <article v-if="article" class="article-content">
      <!-- Заголовок и баннер -->
      <header class="article-header">
        <div class="header-image-container">
            <img :src="article.image || 'https://via.placeholder.com/1200x400'" :alt="article.title" class="header-image"/>
            <div class="header-overlay"></div>
        </div>
        <div class="header-text">
            <h1 class="article-title">{{ article.title }}</h1>
            <p class="article-meta">Опубликовано: {{ formatDate(article.published_at) }}</p>
        </div>
      </header>

      <!-- Основной контент статьи -->
      <section class="content-body">
        <div class="article-main-column">
            <div class="full-text" v-html="article.content"></div>
        </div>

        <!-- Боковая колонка -->
        <aside class="article-sidebar">
            <div class="sidebar-widget share-widget">
                <h3 class="widget-title">Поделиться</h3>
                <div class="share-buttons">
                    <a href="#" class="share-btn share-vk">ВКонтакте</a>
                    <a href="#" class="share-btn share-telegram">Telegram</a>
                    <a href="#" class="share-btn share-twitter">Twitter</a>
                </div>
            </div>
        </aside>
      </section>

    </article>
  </main>
</template>

<style scoped>
.article-page { max-width: 1200px; margin: 30px auto 40px; padding: 0 18px; color: #e5e7eb; }
.loading-placeholder, .error-message { text-align: center; color: #9ca3af; padding: 60px; font-size: 1.2rem; }
.error-message { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.4); border-radius: 12px; }
.article-header { position: relative; margin-bottom: 40px; border-radius: 16px; overflow: hidden; background: #0f172a; }
.header-image-container { width: 100%; height: 400px; }
.header-image { width: 100%; height: 100%; object-fit: cover; }
.header-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(15, 23, 42, 1) 0%, rgba(15, 23, 42, 0.8) 20%, rgba(15, 23, 42, 0) 60%); }
.header-text { position: absolute; bottom: 0; left: 0; right: 0; padding: 40px; color: #fff; }
.article-title { font-size: 2.5rem; font-weight: 800; line-height: 1.2; margin: 0 0 8px; text-shadow: 0 2px 15px rgba(0,0,0,0.7); }
.article-meta { color: #9ca3af; font-size: 0.9rem; }
.content-body { display: grid; grid-template-columns: 1fr; gap: 40px; }
@media (min-width: 992px) { .content-body { grid-template-columns: 1fr 300px; } }
.article-main-column { min-width: 0; }

.full-text {
  font-size: 1rem;
  line-height: 1.8;
  color: #bdc1c6;
}

.full-text >>> h2, .full-text >>> h3 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #e5e7eb;
  margin: 2em 0 1em;
}

.full-text >>> p {
  margin-bottom: 1.5em;
}

.full-text >>> ul, .full-text >>> ol {
  padding-left: 25px;
  margin-bottom: 1.5em;
}

.full-text >>> li {
  margin-bottom: 0.75em;
}

.full-text >>> a {
  color: #60a5fa;
  text-decoration: none;
  transition: color .2s;
}

.full-text >>> a:hover {
  color: #3b82f6;
}

.full-text >>> blockquote {
  border-left: 3px solid #3b82f6;
  padding-left: 20px;
  margin: 1.5em 0;
  font-style: italic;
  color: #d1d5db;
}

.article-sidebar { display: flex; flex-direction: column; gap: 30px; }
.sidebar-widget { background: #0f172a; border: 1px solid #1e293b; border-radius: 12px; padding: 20px; box-shadow: 0 8px 25px rgba(0,0,0,0.5); }
.widget-title { font-size: 1.1rem; font-weight: 600; color: #fff; margin: 0 0 15px; border-bottom: 1px solid #374151; padding-bottom: 10px; }
.share-buttons { display: flex; flex-direction: column; gap: 10px; }
.share-btn { display: block; text-align: center; padding: 10px; border-radius: 8px; color: #fff; font-weight: 500; text-decoration: none; transition: filter 0.2s; }
.share-btn:hover { filter: brightness(1.15); }
.share-vk { background-color: #4a76a8; }
.share-telegram { background-color: #24a1de; }
.share-twitter { background-color: #1da1f2; }
</style>
