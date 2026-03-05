<script setup>
/* =========================================
 *  Импорты и базовая инициализация
 * ========================================= */
import { ref, onMounted, computed, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import api from '../api/axios';

const route = useRoute();

/* =========================================
 *  Базовое состояние каталога игр
 * ========================================= */
const games = ref([]);
const loading = ref(true);
const error = ref('');
const popup = ref(false);
const toast = ref('');

/* =========================================
 *  Состояние фильтров и сортировки
 * ========================================= */
const searchQuery = ref('');
const selectedGenre = ref('all');
const selectedPlatform = ref('all');
const priceMax = ref(null);
const sortBy = ref('title_asc');
const onlyDiscounted = ref(false);
const onlyFeatured = ref(false);
const onlyNew = ref(false);
const releaseYearMin = ref('');
const releaseYearMax = ref('');

// --- НОВОЕ: Состояние для аккордеона фильтров ---
const openFilterSections = ref({
  main: true,
  price: true,
  year: false,
  options: true,
});
const toggleFilterSection = (section) => {
  openFilterSections.value[section] = !openFilterSections.value[section];
};

/* =========================================
 *  Открытие/закрытие панели фильтров
 * ========================================= */
const filtersOpen = ref(false);
const openFilters = () => { filtersOpen.value = true; };
const closeFilters = () => { filtersOpen.value = false; };

/* =========================================
 *  Загрузка и обработка данных
 * ========================================= */
const loadGames = async () => {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await api.get('/games');
    // ИСПРАВЛЕНИЕ: Проверяем, является ли ответ массивом или объектом пагинации Laravel
    games.value = Array.isArray(data) ? data : data.data;
  } catch (e) {
    console.error(e);
    error.value = 'Ошибка загрузки каталога';
  } finally {
    loading.value = false;
  }
};

const showToast = (text) => {
  toast.value = text;
  popup.value = true;
  setTimeout(() => { popup.value = false; }, 1700);
};

const addToCart = async (gameId) => {
  try {
    const { data } = await api.post('/cart/add', { game_id: gameId });
    showToast(data.message || 'Игра добавлена в корзину');
  } catch (e) {
    showToast('Ошибка при добавлении в корзину');
  }
};

/* =========================================
 *  Хуки жизненного цикла
 * ========================================= */
onMounted(loadGames);

/* =========================================
 *  Вычисляемые свойства для фильтров
 * ========================================= */
const maxPriceInCatalog = computed(() => {
  if (!games.value || games.value.length === 0) return 5000;
  const max = Math.max(...games.value.map(g => Number(g.price) || 0));
  return Math.ceil(max / 100) * 100;
});

watch(maxPriceInCatalog, (newMax) => {
  if (priceMax.value === null) {
    priceMax.value = newMax;
  }
});

const genres = computed(() => {
  if (!games.value) return []; // Защита от ошибки
  const set = new Set();
  games.value.forEach(g => g.genre && set.add(g.genre));
  return Array.from(set).sort((a, b) => a.localeCompare(b));
});

const platforms = computed(() => {
  if (!games.value) return []; // Защита от ошибки
  const set = new Set();
  games.value.forEach(g => g.platform && set.add(g.platform));
  return Array.from(set).sort((a, b) => a.localeCompare(b));
});

/* =========================================
 *  Основная логика фильтрации и сортировки
 * ========================================= */
const filteredAndSortedGames = computed(() => {
  if (!games.value) return []; // Защита от ошибки
  let list = [...games.value];
  const q = searchQuery.value.trim().toLowerCase();
  if (q) {
    list = list.filter(g =>
      g.title.toLowerCase().includes(q) ||
      (g.genre && g.genre.toLowerCase().includes(q)) ||
      (g.platform && g.platform.toLowerCase().includes(q))
    );
  }
  if (selectedGenre.value !== 'all') list = list.filter(g => g.genre === selectedGenre.value);
  if (selectedPlatform.value !== 'all') list = list.filter(g => g.platform === selectedPlatform.value);
  const max = priceMax.value;
  if (max !== null && max < maxPriceInCatalog.value) {
    list = list.filter(g => Number(g.price) <= max);
  }
  const yMin = releaseYearMin.value !== '' ? Number(releaseYearMin.value) : null;
  if (yMin) list = list.filter(g => g.release_year == null || Number(g.release_year) >= yMin);
  const yMax = releaseYearMax.value !== '' ? Number(releaseYearMax.value) : null;
  if (yMax) list = list.filter(g => g.release_year == null || Number(g.release_year) <= yMax);
  if (onlyDiscounted.value) list = list.filter(g => Number(g.discount_percent || 0) > 0);
  if (onlyFeatured.value) list = list.filter(g => !!g.is_featured);
  if (onlyNew.value) list = list.filter(g => !!g.is_new);

  list.sort((a, b) => {
    switch (sortBy.value) {
      case 'price_asc': return Number(a.price) - Number(b.price);
      case 'price_desc': return Number(b.price) - Number(a.price);
      case 'rating_desc': return Number(b.rating || 0) - Number(a.rating || 0);
      case 'discount_desc': return Number(b.discount_percent || 0) - Number(a.discount_percent || 0);
      case 'release_year_desc': return Number(b.release_year || 0) - Number(a.release_year || 0);
      case 'title_desc': return b.title.localeCompare(a.title);
      default: return a.title.localeCompare(b.title);
    }
  });
  return list;
});
</script>

<template>
  <main class="games-page">
    <div class="games-header">
      <h1 class="page-title">Каталог игр</h1>
      <button class="filters-toggle" type="button" @click="openFilters">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>
        <span>Фильтры</span>
      </button>
    </div>

    <div v-if="popup" class="toast-success">{{ toast }}</div>
    <div v-if="error" class="auth-errors">{{ error }}</div>
    <div v-if="loading" class="catalog-loading">Загрузка...</div>

    <!-- Панель фильтров -->
    <transition name="filters-fade">
      <div v-if="filtersOpen" class="filters-overlay" @click.self="closeFilters">
        <aside class="filters-sidebar">
          <div class="filters-sidebar-header">
              <h2>Фильтры</h2>
              <div class="filter-group">
                <label>Сортировка
                    <select v-model="sortBy">
                    <option value="title_asc">Название (А-Я)</option>
                    <option value="title_desc">Название (Я-А)</option>
                    <option value="price_asc">Цена (по возрастанию)</option>
                    <option value="price_desc">Цена (по убыванию)</option>
                    <option value="rating_desc">Рейтинг (высокий)</option>
                    <option value="discount_desc">Скидка (большая)</option>
                    <option value="release_year_desc">Год (новые)</option>
                    </select>
                </label>
            </div>
          </div>
          <div class="filters-sidebar-body">
            <div class="filter-group">
              <label>Поиск <input v-model="searchQuery" type="text" placeholder="Название, жанр..."/></label>
            </div>

            <!-- Секция "Основные" -->
            <div class="filter-section">
                <div class="filter-section-header" @click="toggleFilterSection('main')" :aria-expanded="openFilterSections.main">
                    <h3>Основные</h3>
                    <span class="filter-section-icon"></span>
                </div>
                <Transition name="filter-slide">
                    <div v-if="openFilterSections.main" class="filter-section-content">
                        <div class="filter-group">
                            <label>Жанр
                                <select v-model="selectedGenre">
                                <option value="all">Все жанры</option>
                                <option v-for="g in genres" :key="g" :value="g">{{ g }}</option>
                                </select>
                            </label>
                        </div>
                        <div class="filter-group">
                            <label>Платформа
                                <select v-model="selectedPlatform">
                                <option value="all">Все платформы</option>
                                <option v-for="p in platforms" :key="p" :value="p">{{ p }}</option>
                                </select>
                            </label>
                        </div>
                    </div>
                </Transition>
            </div>

            <!-- Секция "Цена" -->
            <div class="filter-section">
                <div class="filter-section-header" @click="toggleFilterSection('price')" :aria-expanded="openFilterSections.price">
                    <h3>Цена</h3>
                    <span class="filter-section-icon"></span>
                </div>
                <Transition name="filter-slide">
                    <div v-if="openFilterSections.price" class="filter-section-content">
                         <div class="filter-group">
                            <label>Цена до: <span class="price-display">{{ Number(priceMax).toLocaleString() }} ₽</span>
                                <input v-if="priceMax !== null" type="range" v-model.number="priceMax" min="0" :max="maxPriceInCatalog" step="50" class="price-slider"/>
                            </label>
                        </div>
                    </div>
                </Transition>
            </div>

            <!-- Секция "Год выпуска" -->
            <div class="filter-section">
                <div class="filter-section-header" @click="toggleFilterSection('year')" :aria-expanded="openFilterSections.year">
                    <h3>Год выпуска</h3>
                    <span class="filter-section-icon"></span>
                </div>
                 <Transition name="filter-slide">
                    <div v-if="openFilterSections.year" class="filter-section-content">
                        <div class="filter-group filter-year-range">
                            <label>От <input v-model.number="releaseYearMin" type="number" min="1980" placeholder="2010"/></label>
                            <label>До <input v-model.number="releaseYearMax" type="number" min="1980" placeholder="2024"/></label>
                        </div>
                    </div>
                </Transition>
            </div>

             <!-- Секция "Опции" -->
            <div class="filter-section">
                <div class="filter-section-header" @click="toggleFilterSection('options')" :aria-expanded="openFilterSections.options">
                    <h3>Опции</h3>
                    <span class="filter-section-icon"></span>
                </div>
                <Transition name="filter-slide">
                    <div v-if="openFilterSections.options" class="filter-section-content">
                        <div class="filter-group filter-toggles">
                            <label><input type="checkbox" v-model="onlyDiscounted"/><span>Только со скидкой</span></label>
                            <label><input type="checkbox" v-model="onlyFeatured"/><span>Только хиты</span></label>
                            <label><input type="checkbox" v-model="onlyNew"/><span>Только новинки</span></label>
                        </div>
                    </div>
                </Transition>
            </div>

          </div>
          <div class="filters-sidebar-footer">
            <button type="button" class="filters-apply-btn" @click="closeFilters">Применить</button>
          </div>
        </aside>
      </div>
    </transition>

    <!-- Список игр -->
    <section v-if="!loading" class="games-container">
      <div v-if="filteredAndSortedGames.length" class="games-list">
        <div v-for="g in filteredAndSortedGames" :key="g.id" class="game-card">
          <div class="game-card-inner">
            <div class="game-card-top">
              <img :src="g.image ? '/img/' + g.image : '/img/noimage.png'" :alt="g.title"/>
              <div class="game-badges">
                <span v-if="g.is_featured" class="badge badge-featured">Хит</span>
                <span v-if="g.is_new" class="badge badge-new">Новинка</span>
                <span v-if="g.discount_percent" class="badge badge-discount">-%{{ g.discount_percent }}%</span>
              </div>
              <a class="external-link-icon" :href="g.stopgame_url_code" target="_blank" rel="noopener" title="Обзор на StopGame">
                 <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
              </a>
            </div>
            <div class="game-info">
              <h2>{{ g.title }}</h2>
              <p class="game-meta">{{ g.genre }} · {{ g.platform }}</p>
              <div class="game-rating" v-if="g.average_review_rating != null || g.rating != null">
                <div class="stars">
                  <span v-for="i in 5" :key="i" class="star" :class="{'star-filled': Number(g.average_review_rating ?? g.rating ?? 0) >= i - 0.25}">★</span>
                </div>
                <span class="rating-value">{{ Number(g.average_review_rating ?? g.rating ?? 0).toFixed(1) }}</span>
              </div>
              <div class="game-bottom">
                <div class="price-block">
                  <span v-if="g.old_price" class="game-old-price">{{ Number(g.old_price).toFixed(0) }} ₽</span>
                  <span class="game-price">{{ Number(g.price).toFixed(0) }} ₽</span>
                </div>
                <div class="game-actions">
                  <button class="game-buy-btn" type="button" @click="addToCart(g.id)">В корзину</button>
                  <RouterLink class="details-btn" :to="{ name: 'game', params: { id: g.id } }" title="Подробнее">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.4 2.2a2.3 2.3 0 013.2 0l7.3 8.3c.6.6.7 1.5.3 2.2l-5 8.3c-.6.9-1.8 1-2.7.3l-7.3-8.3c-.6-.6-.7-1.5-.3-2.2l5-8.3z"></path><path d="M9.2 12.3a3 3 0 11-2.8 4.3"></path></svg>
                  </RouterLink>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div v-else-if="!loading && games.length" class="catalog-empty">По заданным фильтрам игры не найдены.</div>
      <div v-else-if="!loading && !games.length">Каталог пуст.</div>
    </section>
  </main>
</template>

<style scoped>
/* Основной layout и шапка */
.games-page { max-width: 1200px; margin: 30px auto 40px; padding: 0 18px; color: #e5e7eb; }
.games-header { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 10px; }
.page-title { font-size: 1.8rem; font-weight: 700; color: #f9fafb; }

/* Кнопка "Фильтры" */
.filters-toggle { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 999px; border: 1px solid #1f2937; background: #0f172a; color: #e5e7eb; cursor: pointer; font-size: 0.9rem; font-weight: 500; transition: all 0.2s ease; }
.filters-toggle:hover { background: #1e293b; border-color: #3b82f6; color: #fff; box-shadow: 0 8px 25px rgba(59, 130, 246, 0.2); transform: translateY(-1px); }

/* Панель фильтров */
.filters-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); z-index: 1150; }
.filters-sidebar { width: 340px; max-width: 85vw; height: 100%; background: #020617; border-right: 1px solid #111827; box-shadow: 10px 0 50px rgba(0,0,0,0.8); display: flex; flex-direction: column; }
.filters-sidebar-header { padding: 16px 14px; border-bottom: 1px solid #1e293b; }
.filters-sidebar-header h2 { font-size: 1.3rem; font-weight: 600; color: #f9fafb; margin: 0 0 16px; }
.filters-sidebar-body { flex: 1; overflow-y: auto; padding: 12px; display: flex; flex-direction: column; gap: 4px; }
.filter-group { display: flex; flex-direction: column; gap: 8px; font-size: 0.9rem; color: #9ca3af; }
.filter-group label { display: flex; flex-direction: column; gap: 8px; }
.filter-group input[type="text"], .filter-group input[type="number"], .filter-group select { width: 100%; box-sizing: border-box; padding: 9px 12px; border-radius: 8px; border: 1px solid #1e293b; background: #0f172a; color: #e5e7eb; font-size: 0.95rem; outline: none; box-shadow: 0 4px 15px rgba(0,0,0,0.5); transition: all 0.2s ease; }
.filter-group input::placeholder { color: #4b5563; }
.filter-group select { appearance: none; background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%239ca3af' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e"); background-position: right 0.5rem center; background-repeat: no-repeat; background-size: 1.5em 1.5em; padding-right: 2.5rem;}
.filter-group input:focus, .filter-group select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3), 0 4px 15px rgba(0,0,0,0.5); }
.filter-year-range { flex-direction: row; gap: 10px; }
.filter-year-range label { flex: 1; }
.price-display { font-weight: 600; font-size: 0.95rem; color: #e5e7eb; letter-spacing: 0.02em; }
.price-slider { -webkit-appearance: none; appearance: none; width: 100%; height: 6px; background: #1e293b; border-radius: 3px; outline: none; transition: all 0.2s ease; margin-top: 4px; }
.price-slider::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 20px; height: 20px; background: #3b82f6; border-radius: 50%; border: 3px solid #0f172a; cursor: pointer; box-shadow: 0 0 10px rgba(59, 130, 246, 0.5); transition: all 0.2s ease; }
.price-slider:hover::-webkit-slider-thumb { transform: scale(1.1); }
.price-slider::-moz-range-thumb { width: 20px; height: 20px; background: #3b82f6; border-radius: 50%; border: 3px solid #0f172a; cursor: pointer; }
.filter-toggles { gap: 10px; }
.filter-toggles label { flex-direction: row; align-items: center; user-select: none; cursor: pointer; }
.filter-toggles input[type="checkbox"] { appearance: none; width: 40px; height: 22px; border-radius: 11px; background: #1e293b; position: relative; cursor: pointer; transition: all 0.25s ease; }
.filter-toggles input[type="checkbox"]::before { content: ''; position: absolute; top: 2px; left: 2px; width: 18px; height: 18px; border-radius: 50%; background: #9ca3af; transition: all 0.25s ease; }
.filter-toggles input[type="checkbox"]:checked { background: #3b82f6; }
.filter-toggles input[type="checkbox"]:checked::before { transform: translateX(18px); background: #fff; }
.filter-toggles span { font-size: 0.9rem; text-transform: none; letter-spacing: 0.02em; color: #e5e7eb; }
.filters-sidebar-footer { margin-top: auto; padding: 14px; border-top: 1px solid #1e293b; }
.filters-apply-btn { width: 100%; padding: 10px 0; border-radius: 8px; border: none; cursor: pointer; background: linear-gradient(90deg, #3b82f6, #6366f1); color: #fff; font-weight: 600; font-size: 1rem; transition: all 0.2s ease; }
.filters-apply-btn:hover { filter: brightness(1.1); }

/* Анимации сайдбара */
.filters-fade-enter-active, .filters-fade-leave-active { transition: opacity 0.2s ease; }
.filters-fade-enter-from, .filters-fade-leave-to { opacity: 0; }
.filters-sidebar { transition: transform 0.25s ease-out; }
.filters-fade-enter-from .filters-sidebar, .filters-fade-leave-to .filters-sidebar { transform: translateX(-100%); }

/* Секции аккордеона фильтров */
.filter-section { border-bottom: 1px solid #1e293b; }
.filter-section-header { display: flex; justify-content: space-between; align-items: center; padding: 12px 6px; cursor: pointer; transition: background-color 0.2s; }
.filter-section-header:hover { background-color: #111827; }
.filter-section-header h3 { margin: 0; font-size: 1rem; font-weight: 500; color: #d1d5db; }
.filter-section-icon { width: 10px; height: 10px; border-left: 2px solid #9ca3af; border-bottom: 2px solid #9ca3af; transform: rotate(-45deg); transition: transform 0.3s ease-in-out; }
.filter-section-header[aria-expanded="true"] .filter-section-icon { transform: rotate(135deg); margin-top: -6px; }
.filter-section-content { padding: 4px; overflow: hidden; }
.filter-section-content .filter-group { padding: 8px 0; }

/* Анимация для аккордеона */
.filter-slide-enter-active, .filter-slide-leave-active {
  transition: grid-template-rows 0.3s ease-in-out;
  grid-template-rows: 1fr;
}
.filter-slide-enter-from, .filter-slide-leave-to {
  grid-template-rows: 0fr;
}
.filter-section-content { display: grid; }

/* Карточки игр (без изменений) */
.games-container { margin-top: 16px; }
.games-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px; padding: 4px; }
.game-card-inner { display: flex; flex-direction: column; background: #0f172a; border-radius: 12px; overflow: hidden; border: 1px solid #1e293b; box-shadow: 0 12px 30px rgba(0,0,0,0.6); transition: all 0.22s ease-in-out; }
.game-card-inner:hover { transform: translateY(-5px); background: #1e293b; border-color: #3b82f6; box-shadow: 0 18px 40px rgba(0,0,0,0.7), 0 0 20px rgba(59, 130, 246, 0.3); }
.game-card-top { position: relative; height: 230px; background: #000; overflow: hidden; }
.game-card-top img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.25s ease-in-out; }
.game-card-inner:hover .game-card-top img { transform: scale(1.05); }
.game-badges { position: absolute; left: 8px; top: 8px; display: flex; flex-direction: column; gap: 4px; z-index: 2; }
.badge { display: inline-flex; padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; color: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.5); backdrop-filter: blur(4px); }
.badge-featured { background: rgba(249, 115, 22, 0.8); }
.badge-new { background: rgba(34, 197, 94, 0.8); }
.badge-discount { background: rgba(239, 68, 68, 0.8); }
.external-link-icon { position: absolute; right: 10px; top: 10px; width: 32px; height: 32px; border-radius: 50%; border: none; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); color: #9ca3af; display: grid; place-items: center; cursor: pointer; z-index: 3; opacity: 0; transform: scale(0.8); transition: all 0.2s ease; }
.game-card-inner:hover .external-link-icon { opacity: 1; transform: scale(1); }
.external-link-icon:hover { background: #1d4ed8; color: #fff; }
.game-info { padding: 12px; display: flex; flex-direction: column; gap: 8px; }
.game-info h2 { margin: 0; font-size: 1.05rem; font-weight: 600; color: #f9fafb; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.game-meta { margin: 0; font-size: 0.85rem; color: #9ca3af; }
.game-rating { display: flex; align-items: center; gap: 6px; }
.stars { display: inline-flex; color: #4b5563; }
.star-filled { color: #facc15; text-shadow: 0 0 8px rgba(250,204,21,0.5); }
.rating-value { font-weight: 600; color: #fbbf24; }
.game-bottom { margin-top: 4px; display: flex; justify-content: space-between; align-items: center; }
.price-block { display: flex; align-items: baseline; gap: 6px; }
.game-old-price { font-size: 0.85rem; color: #9ca3af; text-decoration: line-through; }
.game-price { font-weight: 700; font-size: 1.1rem; color: #22c55e; }
.game-actions { display: flex; align-items: center; gap: 6px; }
.game-buy-btn { font-size: 0.9rem; padding: 8px 14px; border-radius: 8px; border: none; cursor: pointer; background-color: #22c55e; color: #fff; font-weight: 600; transition: all 0.2s ease; }
.game-buy-btn:hover { background-color: #16a34a; transform: scale(1.05); }
.details-btn { width: 36px; height: 36px; border-radius: 8px; border: 1px solid #1e293b; display: grid; place-items: center; color: #9ca3af; transition: all 0.2s ease; }
.details-btn:hover { border-color: #3b82f6; color: #3b82f6; background: #1e293b; }

/* Вспомогательные состояния */
.catalog-loading, .catalog-empty { font-size: 1rem; color: #9ca3af; text-align: center; padding: 40px; }
.auth-errors { color: #fca5a5; background: #450a0a; padding: 10px 14px; border-radius: 8px; text-align: center; border: 1px solid #7f1d1d; }
.toast-success { position: fixed; right: 20px; bottom: 20px; background: #0f172a; border: 1px solid #22c55e; color: #e5e7eb; padding: 12px 18px; border-radius: 8px; box-shadow: 0 10px 40px rgba(0,0,0,0.6); z-index: 1200; animation: fade-in-up 0.3s ease-out; }

@keyframes fade-in-up { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>