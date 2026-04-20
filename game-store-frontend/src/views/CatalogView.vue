<script setup>
import { ref, onMounted, onUnmounted, computed, nextTick, watch } from 'vue';
import { useHead } from '@vueuse/head';
import api from '../api/axios';
import GameCard from '../components/GameCard.vue';

const games = ref([]);
const genres = ref([]);
const loading = ref(true);
const error = ref('');
const searchQuery = ref('');
const selectedGenre = ref('all');
const sortBy = ref('release_date_desc');
let revealObs = null;

// ── Infinite scroll / progressive loading ──
const PAGE_SIZE = 12;
const displayCount = ref(PAGE_SIZE);
const sentinelRef = ref(null);
let sentinelObs = null;

const visibleGames = computed(() => filteredGames.value.slice(0, displayCount.value));
const hasMore = computed(() => displayCount.value < filteredGames.value.length);

useHead(computed(() => {
  const g = selectedGenre.value === 'all' ? 'Все игры' : `${selectedGenre.value} игры`;
  return {
    title: `${g} — Каталог GameStore`,
    meta: [
      { name: 'description', content: `Каталог игр GameStore. Покупайте лицензионные ключи по выгодным ценам.` },
      { property: 'og:type', content: 'website' },
      { property: 'og:title', content: `${g} — Каталог GameStore` },
      { property: 'og:description', content: 'Каталог лицензионных ключей для игр. Steam, Epic Games, GOG по выгодным ценам.' },
      { property: 'og:image', content: '/images.png' },
      { name: 'robots', content: 'index, follow' },
    ],
  };
}));

const filteredGames = computed(() => {
  if (!searchQuery.value.trim()) return games.value;
  const q = searchQuery.value.trim().toLowerCase();
  return games.value.filter(g => g.title?.toLowerCase().includes(q) || g.genre?.toLowerCase().includes(q));
});

const pluralGame = (n) => {
  const m = n % 100;
  if (m >= 11 && m <= 14) return 'игр';
  switch (n % 10) {
    case 1: return 'игра';
    case 2: case 3: case 4: return 'игры';
    default: return 'игр';
  }
};

const resetFilters = () => {
  searchQuery.value = '';
  selectedGenre.value = 'all';
  sortBy.value = 'release_date_desc';
  fetchGames();
};

const setupReveal = () => {
  nextTick(() => {
    if (revealObs) revealObs.disconnect();
    revealObs = new IntersectionObserver((entries) => {
      entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('is-visible'); revealObs.unobserve(e.target); } });
    }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });
    document.querySelectorAll('.catalog-root .reveal').forEach(el => revealObs.observe(el));
  });
};

const fetchGenres = async () => {
  try { const { data } = await api.get('/genres'); genres.value = data; } catch (e) { console.error(e); }
};

const fetchGames = async () => {
  loading.value = true; error.value = '';
  try {
    const params = { genre: selectedGenre.value === 'all' ? undefined : selectedGenre.value, sortBy: sortBy.value };
    const { data } = await api.get('/games', { params });
    games.value = data;
    setupReveal();
  } catch (e) {
    error.value = 'Не удалось загрузить каталог. Попробуйте позже.';
  } finally { loading.value = false; }
};

const applyFilters = () => { displayCount.value = PAGE_SIZE; fetchGames(); };

// Reset displayCount when search/genre changes
watch([searchQuery, selectedGenre], () => { displayCount.value = PAGE_SIZE; });

const setupSentinel = () => {
  nextTick(() => {
    sentinelObs?.disconnect();
    if (!sentinelRef.value) return;
    sentinelObs = new IntersectionObserver((entries) => {
      if (entries[0].isIntersecting && hasMore.value) {
        displayCount.value += PAGE_SIZE;
        nextTick(setupReveal);
      }
    }, { rootMargin: '200px' });
    sentinelObs.observe(sentinelRef.value);
  });
};

onMounted(() => { fetchGenres(); fetchGames(); setupReveal(); setupSentinel(); });
onUnmounted(() => { revealObs?.disconnect(); sentinelObs?.disconnect(); });
</script>

<template>
  <div class="catalog-root">
    <!-- ═══ HERO ═══ -->
    <section class="catalog-hero">
      <div class="hero-blobs">
        <div class="blob b1"></div>
        <div class="blob b2"></div>
        <div class="blob b3"></div>
      </div>
      <div class="grid-overlay"></div>
      <div class="hero-inner">
        <div class="hero-badge reveal">Каталог игр</div>
        <h1 class="hero-title reveal">Найди свою <span class="grad-text">идеальную игру</span></h1>
        <p class="hero-sub reveal">Лицензионные ключи для ПК, PlayStation и Xbox — мгновенная доставка</p>

        <div class="hero-search reveal">
          <input v-model="searchQuery" type="text" placeholder="Поиск по названию или жанру..." class="srch-input" />
          <button v-if="searchQuery" class="srch-clear" @click="searchQuery = ''">✕</button>
        </div>


      </div>
    </section>

    <!-- ═══ FILTER BAR ═══ -->
    <div class="filters-bar reveal">
      <div class="genre-chips">
        <button
          v-for="g in ['all', ...genres]" :key="g"
          class="genre-chip" :class="{ active: selectedGenre === g }"
          @click="selectedGenre = g; applyFilters()"
        >{{ g === 'all' ? 'Все жанры' : g }}</button>
      </div>
      <div class="sort-wrap">
        <label class="sort-label">Сортировка:</label>
        <select v-model="sortBy" @change="applyFilters" class="sort-sel">
          <option value="release_date_desc">Новинки</option>
          <option value="price_asc">Дешевле</option>
          <option value="price_desc">Дороже</option>
          <option value="rating_desc">По рейтингу</option>
          <option value="title_asc">А → Я</option>
          <option value="title_desc">Я → А</option>
        </select>
      </div>
    </div>

    <!-- ═══ BODY ═══ -->
    <div class="catalog-body">
      <!-- Loading skeletons -->
      <div v-if="loading" class="games-grid">
        <div v-for="i in 12" :key="i" class="skel-card">
          <div class="skel-img"></div>
          <div class="skel-body">
            <div class="skel-line w80"></div>
            <div class="skel-line w50"></div>
            <div class="skel-line w65"></div>
          </div>
        </div>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="empty-box">
        <div class="empty-icon">⚠️</div>
        <p class="empty-text">{{ error }}</p>
        <button @click="fetchGames" class="empty-btn">Попробовать снова</button>
      </div>

      <!-- Empty -->
      <div v-else-if="filteredGames.length === 0" class="empty-box">
        <div class="empty-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" style="opacity:0.25"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div>
        <p class="empty-text">По вашему запросу ничего не найдено</p>
        <button @click="resetFilters" class="empty-btn">Сбросить фильтры</button>
      </div>

      <!-- Games Grid -->
      <TransitionGroup v-else name="grid" tag="div" class="games-grid">
        <GameCard
          v-for="(game, i) in visibleGames"
          :key="game.id"
          :game="game"
          class="reveal"
          :style="{ '--i': i % 6 }"
        />
      </TransitionGroup>

      <!-- Infinite scroll sentinel + load-more indicator -->
      <div ref="sentinelRef" class="sentinel">
        <Transition name="fade">
          <div v-if="hasMore && !loading" class="load-more-hint">
            <span class="lm-spinner"></span>
            Загружаем ещё...
          </div>
        </Transition>
      </div>

      <div v-if="!loading && filteredGames.length" class="results-bar">
        Показано <strong>{{ visibleGames.length }}</strong> из <strong>{{ filteredGames.length }}</strong> {{ pluralGame(filteredGames.length) }}
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ─── Reveal ─── */
.reveal { opacity: 0; transform: translateY(28px); transition: opacity 0.55s ease calc(var(--i, 0) * 65ms), transform 0.55s ease calc(var(--i, 0) * 65ms); }
.reveal.is-visible { opacity: 1; transform: none; }

/* ─── Root ─── */
.catalog-root { min-height: 100vh; color: #e5e7eb; }

/* ─── Hero ─── */
.catalog-hero {
  position: relative; min-height: 46vh; display: flex; align-items: center;
  justify-content: center; overflow: hidden; background: #030712;
}
.hero-blobs { position: absolute; inset: 0; pointer-events: none; z-index: 0; }
.blob { position: absolute; border-radius: 50%; filter: blur(90px); opacity: 0.22; }
.b1 { width: 560px; height: 560px; background: #3b82f6; top: -20%; left: -8%; animation: blobFloat 14s ease-in-out infinite; }
.b2 { width: 420px; height: 420px; background: #6366f1; bottom: -25%; right: 3%; animation: blobFloat 18s ease-in-out infinite reverse; }
.b3 { width: 320px; height: 320px; background: #8b5cf6; top: 15%; right: 28%; animation: blobFloat 22s ease-in-out infinite 3s; }

@keyframes blobFloat {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33% { transform: translate(30px, -20px) scale(1.05); }
  66% { transform: translate(-20px, 15px) scale(0.95); }
}

.grid-overlay {
  position: absolute; inset: 0; z-index: 0;
  background-image: linear-gradient(rgba(59,130,246,0.06) 1px, transparent 1px), linear-gradient(90deg, rgba(59,130,246,0.06) 1px, transparent 1px);
  background-size: 44px 44px;
  mask-image: radial-gradient(ellipse at center, black 20%, transparent 75%);
}

.hero-inner {
  position: relative; z-index: 1;
  display: flex; flex-direction: column; align-items: center;
  text-align: center; padding: 70px 24px 50px; gap: 18px; max-width: 720px;
}

.hero-badge {
  display: inline-flex; align-items: center; gap: 8px;
  background: rgba(59,130,246,0.12); border: 1px solid rgba(59,130,246,0.3);
  color: #93c5fd; padding: 6px 20px; border-radius: 999px;
  font-size: 0.85rem; font-weight: 600; letter-spacing: 0.05em;
}

.hero-title {
  font-size: clamp(2rem, 5vw, 3.6rem); font-weight: 800; line-height: 1.15; margin: 0; color: #fff;
}
.grad-text {
  background: linear-gradient(135deg, #3b82f6, #8b5cf6, #ec4899);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.hero-sub { color: #9ca3af; font-size: 1.05rem; margin: 0; }

.hero-search {
  display: flex; align-items: center; width: 100%; max-width: 580px;
  background: rgba(17,24,39,0.8); backdrop-filter: blur(16px);
  border: 1px solid rgba(255,255,255,0.1); border-radius: 999px;
  padding: 6px 8px 6px 22px; gap: 10px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.4); transition: border-color 0.3s, box-shadow 0.3s;
}
.hero-search:focus-within { border-color: #3b82f6; box-shadow: 0 8px 32px rgba(0,0,0,0.4), 0 0 0 3px rgba(59,130,246,0.15); }
.srch-icon { font-size: 1.1rem; opacity: 0.5; }
.srch-input { flex: 1; background: none; border: none; outline: none; color: #e5e7eb; font-size: 0.96rem; }
.srch-input::placeholder { color: #6b7280; }
.srch-clear { background: rgba(255,255,255,0.08); border: none; color: #9ca3af; cursor: pointer; width: 28px; height: 28px; border-radius: 50%; font-size: 0.8rem; display: grid; place-items: center; transition: all 0.2s; }
.srch-clear:hover { background: rgba(239,68,68,0.2); color: #fca5a5; }

.hero-stats { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
.stat-pill {
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
  color: #d1d5db; padding: 6px 18px; border-radius: 999px; font-size: 0.82rem;
}

/* ─── Filters Bar ─── */
.filters-bar {
  max-width: 1320px; margin: 0 auto; padding: 22px 24px;
  display: flex; flex-wrap: wrap; gap: 16px; align-items: center; justify-content: space-between;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}
.genre-chips { display: flex; flex-wrap: wrap; gap: 8px; flex: 1; }
.genre-chip {
  padding: 7px 18px; border-radius: 999px; border: 1px solid rgba(255,255,255,0.1);
  background: rgba(255,255,255,0.04); color: #9ca3af; font-size: 0.84rem; font-weight: 500;
  cursor: pointer; transition: all 0.22s ease; white-space: nowrap;
}
.genre-chip:hover { border-color: rgba(59,130,246,0.5); color: #93c5fd; background: rgba(59,130,246,0.08); }
.genre-chip.active { background: rgba(59,130,246,0.18); border-color: #3b82f6; color: #fff; }

.sort-wrap { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.sort-label { color: #6b7280; font-size: 0.85rem; white-space: nowrap; }
.sort-sel {
  padding: 8px 16px; border-radius: 10px;
  background: rgba(17,24,39,0.8); border: 1px solid rgba(255,255,255,0.1);
  color: #e5e7eb; font-size: 0.84rem; outline: none; cursor: pointer; transition: border-color 0.2s;
}
.sort-sel:focus { border-color: #3b82f6; }

/* ─── Catalog Body ─── */
.catalog-body { max-width: 1320px; margin: 0 auto; padding: 32px 24px 80px; }

.games-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(262px, 1fr)); gap: 22px; }

/* ─── Skeletons ─── */
.skel-card { border-radius: 12px; overflow: hidden; background: rgba(17,24,39,0.7); border: 1px solid rgba(255,255,255,0.05); }
.skel-img { height: 180px; background: linear-gradient(90deg, #1f2937 25%, #2d3748 50%, #1f2937 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
.skel-body { padding: 14px; display: flex; flex-direction: column; gap: 10px; }
.skel-line { height: 11px; border-radius: 6px; background: linear-gradient(90deg, #1f2937 25%, #2d3748 50%, #1f2937 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
.w80 { width: 80%; } .w50 { width: 50%; } .w65 { width: 65%; }
@keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

/* ─── Empty / Error ─── */
.empty-box {
  text-align: center; padding: 80px 40px;
  background: rgba(17,24,39,0.5); border: 1px solid rgba(255,255,255,0.07);
  border-radius: 20px; display: flex; flex-direction: column; align-items: center; gap: 18px;
  margin: 20px 0;
}
.empty-icon { font-size: 3.5rem; }
.empty-text { color: #9ca3af; font-size: 1.1rem; margin: 0; }
.empty-btn {
  padding: 11px 28px; background: linear-gradient(135deg, #3b82f6, #6366f1);
  border: none; border-radius: 12px; color: #fff; font-weight: 600; cursor: pointer; transition: all 0.22s;
}
.empty-btn:hover { transform: translateY(-2px); filter: brightness(1.1); }

/* ─── Grid Transition ─── */
.grid-enter-active { transition: all 0.4s ease; }
.grid-leave-active { transition: all 0.3s ease; position: absolute; }
.grid-enter-from { opacity: 0; transform: translateY(20px) scale(0.97); }
.grid-leave-to { opacity: 0; transform: scale(0.95); }

/* ─── Infinite scroll sentinel ─── */
.sentinel { height: 60px; display: flex; align-items: center; justify-content: center; }
.load-more-hint {
  display: flex; align-items: center; gap: 8px;
  color: #6b7280; font-size: 0.88rem;
}
.lm-spinner {
  display: inline-block; width: 14px; height: 14px;
  border: 2px solid rgba(99,102,241,0.3); border-top-color: #6366f1;
  border-radius: 50%; animation: lmspin 0.7s linear infinite;
}
@keyframes lmspin { to { transform: rotate(360deg); } }
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* ─── Results Bar ─── */
.results-bar { margin-top: 16px; text-align: center; color: #6b7280; font-size: 0.9rem; }
.results-bar strong { color: #9ca3af; }

/* ─── Responsive ─── */
@media (max-width: 768px) {
  .filters-bar { flex-direction: column; align-items: stretch; }
  .sort-wrap { justify-content: flex-end; }
}
</style>
