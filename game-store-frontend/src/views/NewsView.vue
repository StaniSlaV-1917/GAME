<template>
  <div class="news-page">
    <div class="page-header">
      <h1 class="page-title">Новости игровой индустрии</h1>
      <p class="page-subtitle">Самые свежие анонсы, обзоры и события из мира игр. Будьте в курсе вместе с GameStore!</p>
    </div>

    <div class="news-grid">
      <article v-for="item in newsItems" :key="item.id" class="news-card">
        <RouterLink :to="'/news/' + item.id" class="card-link-wrapper">
          <img :src="item.image" :alt="item.title" class="news-image" />
        </RouterLink>
        <div class="news-card-content">
            <div class="news-tags">
                <span v-for="tag in item.tags" :key="tag" class="tag">{{ tag }}</span>
            </div>
            <h2 class="news-title">
              <RouterLink :to="'/news/' + item.id">{{ item.title }}</RouterLink>
            </h2>
            <p class="news-excerpt">{{ item.excerpt }}</p>
            <div class="news-footer">
              <span class="news-date">{{ formatDate(item.date) }}</span>
              <RouterLink :to="'/news/' + item.id" class="read-more-btn">Читать подробнее</RouterLink>
            </div>
        </div>
      </article>
    </div>
  </div>
</template>

<script setup>
import { RouterLink } from 'vue-router';
import { newsItems } from '../data/news.js';

const formatDate = (dateString) => {
  const options = { year: 'numeric', month: 'long', day: 'numeric' };
  return new Date(dateString).toLocaleDateString('ru-RU', options);
};

</script>

<style scoped>
.news-page { max-width: 1200px; margin: 30px auto 50px; padding: 0 18px; color: #e5e7eb; }

.page-header { text-align: center; margin-bottom: 40px; }
.page-title { font-size: 2.8rem; font-weight: 800; color: #fff; margin-bottom: 0.5rem; }
.page-subtitle { font-size: 1.1rem; color: #9ca3af; max-width: 600px; margin: 0 auto; }

.news-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 30px; }

.news-card { 
  background-color: #0f172a; 
  border: 1px solid #1e293b; 
  border-radius: 12px; 
  overflow: hidden; 
  display: flex; 
  flex-direction: column; 
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.news-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.4); border-color: #3b82f6; }

.card-link-wrapper { display: block; }
.news-image { width: 100%; height: 200px; object-fit: cover; transition: transform 0.2s ease; }
.news-card:hover .news-image { transform: scale(1.05); }

.news-card-content { padding: 20px; display: flex; flex-direction: column; flex-grow: 1; }

.news-tags { display: flex; gap: 8px; margin-bottom: 12px; }
.tag { background-color: #3b82f6; color: #fff; padding: 4px 10px; font-size: 0.8rem; font-weight: 500; border-radius: 12px; }

.news-title { font-size: 1.4rem; font-weight: 600; line-height: 1.4; margin: 0 0 10px; }
.news-title a { color: #fff; text-decoration: none; transition: color 0.2s; }
.news-title a:hover { color: #60a5fa; }

.news-excerpt { font-size: 0.95rem; color: #a0aec0; line-height: 1.6; margin-bottom: 20px; flex-grow: 1; }

.news-footer { display: flex; justify-content: space-between; align-items: center; margin-top: auto; border-top: 1px solid #1e293b; padding-top: 15px; }
.news-date { font-size: 0.85rem; color: #718096; }

.read-more-btn {
  background-color: #1e293b;
  color: #d1d5db;
  padding: 8px 16px;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 500;
  transition: background-color 0.2s, color 0.2s;
}
.read-more-btn:hover { background-color: #3b82f6; color: #fff; }

</style>