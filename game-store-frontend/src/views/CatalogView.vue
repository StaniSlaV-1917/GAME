<template>
  <main class="catalog-page">
    <h1 class="page-title">Каталог игр</h1>

    <!-- Панель фильтров -->
    <div class="filters-panel">
      <div class="filter-group">
        <label for="genre-filter">Жанр:</label>
        <select id="genre-filter" v-model="selectedGenre" @change="applyFilters">
          <option value="all">Все жанры</option>
          <option v-for="genre in genres" :key="genre" :value="genre">{{ genre }}</option>
        </select>
      </div>
      <div class="filter-group">
        <label for="sort-by">Сортировать по:</label>
        <select id="sort-by" v-model="sortBy" @change="applyFilters">
          <option value="release_date_desc">Дате выхода (сначала новые)</option>
          <option value="price_asc">Цене (сначала дешевые)</option>
          <option value="price_desc">Цене (сначала дорогие)</option>
          <option value="rating_desc">Рейтингу (сначала высокие)</option>
          <option value="title_asc">Названию (А-Я)</option>
          <option value="title_desc">Названию (Я-А)</option>
        </select>
      </div>
    </div>

    <!-- Сообщения о статусе -->
    <div v-if="loading" class="status-message">Загрузка игр...</div>
    <div v-if="error" class="status-message error">{{ error }}</div>

    <!-- Сетка игр -->
    <div v-if="!loading && games.length" class="games-grid">
      <GameCard v-for="game in games" :key="game.id" :game="game" />
    </div>

    <div v-if="!loading && games.length === 0" class="status-message">
      По вашему запросу ничего не найдено.
    </div>

  </main>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useHead } from '@vueuse/head';
import api from '../api/axios';
import GameCard from '../components/GameCard.vue';

const games = ref([]);
const genres = ref([]);
const loading = ref(true);
const error = ref('');

const selectedGenre = ref('all');
const sortBy = ref('release_date_desc');

// SEO с помощью useHead
useHead(computed(() => {
  const genreText = selectedGenre.value === 'all' ? 'Все игры' : `${selectedGenre.value} игры`;
  return {
    title: `${genreText} - Купить в каталоге GameStore`,
    meta: [
      {
        name: 'description',
        content: `Ознакомьтесь с каталогом игр в GameStore. Покупайте лицензионные ключи для ПК по выгодным ценам. Сортируйте ${genreText.toLowerCase()} по цене, рейтингу и дате выхода.`
      }
    ]
  };
}));

// Загрузка списка доступных жанров
const fetchGenres = async () => {
  try {
    const { data } = await api.get('/genres');
    genres.value = data;
  } catch (e) {
    console.error('Не удалось загрузить жанры:', e);
  }
};

// Основная функция загрузки и фильтрации игр
const fetchGames = async () => {
  loading.value = true;
  error.value = '';
  try {
    const params = {
      genre: selectedGenre.value === 'all' ? undefined : selectedGenre.value,
      sortBy: sortBy.value
    };
    const { data } = await api.get('/games', { params });
    games.value = data;
  } catch (e) {
    console.error('Ошибка загрузки игр:', e);
    error.value = 'Не удалось загрузить каталог. Попробуйте позже.';
  } finally {
    loading.value = false;
  }
};

// Функция, которая вызывается при изменении фильтров
const applyFilters = () => {
  fetchGames();
};

// Первичная загрузка данных при монтировании компонента
onMounted(() => {
  fetchGenres();
  fetchGames();
});

</script>

<style scoped>
.catalog-page { max-width: 1200px; margin: 30px auto 40px; padding: 0 18px; color: #e5e7eb; }
.page-title { font-size: 1.8rem; font-weight: 700; color: #f9fafb; margin-bottom: 1.5rem; border-left: 4px solid #3b82f6; padding-left: 1rem; }

/* Панель фильтров */
.filters-panel { display: flex; flex-wrap: wrap; gap: 20px; padding: 15px; margin-bottom: 30px; background: rgba(17, 24, 39, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; }
.filter-group { display: flex; align-items: center; gap: 10px; }
.filter-group label { font-weight: 500; color: #9ca3af; font-size: 0.9rem; }
.filter-group select { padding: 8px 12px; border-radius: 8px; border: 1px solid #1e293b; background: #0f172a; color: #e5e7eb; font-size: 0.95rem; outline: none; transition: all 0.2s ease; }
.filter-group select:focus { border-color: #3b82f6; }

/* Статусы и сетка */
.status-message { text-align: center; color: #9ca3af; padding: 40px; font-size: 1.1rem; }
.status-message.error { color: #fca5a5; }
.games-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px; }

</style>
