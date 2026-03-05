<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, RouterLink } from 'vue-router';

import { newsItems as allNews } from '../data/news.js';

const route = useRoute();
const article = ref(null);
const loading = ref(true);
const error = ref('');

const articleId = computed(() => parseInt(route.params.id));

const relatedGames = computed(() => {
    if (article.value?.tags.includes('Cyberpunk 2077')) {
        return [{ id: 1, title: 'Cyberpunk 2077', image: 'cyberpunk.jpg'}];
    }
    return [];
});

onMounted(() => {
  try {
    loading.value = true;
    const foundArticle = allNews.find(a => a.id === articleId.value);
    if (foundArticle) {
      article.value = foundArticle;
    } else {
      error.value = 'Новость не найдена. Возможно, она была удалена или перенесена.';
    }
  } catch (e) {
    console.error(e);
    error.value = 'Произошла ошибка при загрузке новости.';
  } finally {
    loading.value = false;
  }
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
            <img :src="article.image" :alt="article.title" class="header-image"/>
            <div class="header-overlay"></div>
        </div>
        <div class="header-text">
            <div class="tags-container">
                <span v-for="tag in article.tags" :key="tag" class="tag">{{ tag }}</span>
            </div>
            <h1 class="article-title">{{ article.title }}</h1>
            <p class="article-meta">Опубликовано: {{ formatDate(article.date) }}</p>
        </div>
      </header>

      <!-- Основной контент статьи -->
      <section class="content-body">
        <div class="article-main-column">
            <p class="article-excerpt">{{ article.excerpt }}</p>
            
            <!-- НОВОЕ: Чистый, структурированный рендеринг контента -->
            <div class="full-text">
                <div v-for="(block, index) in article.content_blocks" :key="index">
                    <h3 v-if="block.type === 'heading'" class="content-heading">{{ block.content }}</h3>
                    <p v-if="block.type === 'paragraph'">{{ block.content }}</p>
                    <ul v-if="block.type === 'list'" class="content-list">
                        <li v-for="(item, itemIndex) in block.items" :key="itemIndex">
                            <strong>{{ item.label }}</strong> {{ item.text }}
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Галерея изображений -->
            <section v-if="article.gallery && article.gallery.length" class="content-section">
                <h2 class="section-title">Галерея</h2>
                <div class="gallery-grid">
                    <div v-for="(img, index) in article.gallery" :key="index" class="gallery-item">
                         <img :src="img" alt="Gallery image"/>
                    </div>
                </div>
            </section>
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

            <div v-if="relatedGames.length" class="sidebar-widget related-games-widget">
                <h3 class="widget-title">Обсуждаемые игры</h3>
                <div v-for="game in relatedGames" :key="game.id" class="related-game-card">
                    <RouterLink :to="{name: 'game', params: {id: game.id}}">
                        <img :src="`/img/${game.image}`" :alt="game.title"/>
                        <span>{{ game.title }}</span>
                    </RouterLink>
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
.tags-container { display: flex; gap: 8px; margin-bottom: 12px; }
.tag { background: rgba(59, 130, 246, 0.8); backdrop-filter: blur(5px); padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
.article-title { font-size: 2.5rem; font-weight: 800; line-height: 1.2; margin: 0 0 8px; text-shadow: 0 2px 15px rgba(0,0,0,0.7); }
.article-meta { color: #9ca3af; font-size: 0.9rem; }
.content-body { display: grid; grid-template-columns: 1fr; gap: 40px; }
@media (min-width: 992px) { .content-body { grid-template-columns: 1fr 300px; } }
.article-main-column { min-width: 0; }
.article-excerpt { font-size: 1.2rem; line-height: 1.6; color: #d1d5db; border-left: 3px solid #3b82f6; padding-left: 20px; margin-bottom: 24px; }

.full-text { font-size: 1rem; line-height: 1.8; color: #bdc1c6; }
.full-text p { margin-bottom: 1.5em; }
.content-heading { font-size: 1.5rem; font-weight: 700; color: #e5e7eb; margin: 2em 0 1em; }
.content-list { list-style: disc; padding-left: 25px; margin-bottom: 1.5em; }
.content-list li { margin-bottom: 0.75em; } 
.content-list strong { color: #e5e7eb; font-weight: 600; }

.content-section { margin-top: 40px; }
.section-title { font-size: 1.8rem; font-weight: 700; color: #f9fafb; margin-bottom: 20px; border-left: 4px solid #3b82f6; padding-left: 1rem; }
.gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; }
.gallery-item img { width: 100%; height: auto; border-radius: 8px; border: 1px solid #374151; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; }
.gallery-item img:hover { transform: scale(1.05); box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
.article-sidebar { display: flex; flex-direction: column; gap: 30px; }
.sidebar-widget { background: #0f172a; border: 1px solid #1e293b; border-radius: 12px; padding: 20px; box-shadow: 0 8px 25px rgba(0,0,0,0.5); }
.widget-title { font-size: 1.1rem; font-weight: 600; color: #fff; margin: 0 0 15px; border-bottom: 1px solid #374151; padding-bottom: 10px; }
.share-buttons { display: flex; flex-direction: column; gap: 10px; }
.share-btn { display: block; text-align: center; padding: 10px; border-radius: 8px; color: #fff; font-weight: 500; text-decoration: none; transition: filter 0.2s; }
.share-btn:hover { filter: brightness(1.15); }
.share-vk { background-color: #4a76a8; }
.share-telegram { background-color: #24a1de; }
.share-twitter { background-color: #1da1f2; }
.related-game-card a { display: block; text-decoration: none; color: #e5e7eb; transition: background-color 0.2s; border-radius: 8px; overflow: hidden; }
.related-game-card a:hover { background-color: #1e293b; }
.related-game-card img { width: 100%; height: 120px; object-fit: cover; }
.related-game-card span { display: block; padding: 12px; font-weight: 500; }
</style>