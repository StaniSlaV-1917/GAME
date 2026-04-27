<script setup>
import { ref, onMounted, onUnmounted, computed, nextTick, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useHead } from '@vueuse/head';
import api from '../api/axios';
import GameCard from '../components/GameCard.vue';

const route = useRoute();
const games = ref([]);
const genres = ref([]);
const loading = ref(true);
const error = ref('');
const searchQuery = ref('');
const selectedGenre = ref(route.query.genre || 'all');
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
    title: `${g} — Оружейная GameStore`,
    meta: [
      { name: 'description', content: `Оружейная GameStore. Покупайте лицензионные ключи по выгодным ценам.` },
      { property: 'og:type', content: 'website' },
      { property: 'og:title', content: `${g} — Оружейная GameStore` },
      { property: 'og:description', content: 'Лицензионные ключи для игр. Steam, Epic Games, GOG по выгодным ценам.' },
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
    error.value = 'Не удалось загрузить оружейную. Попробуй позже.';
  } finally { loading.value = false; }
};

const applyFilters = () => { displayCount.value = PAGE_SIZE; fetchGames(); };

// Reset displayCount when search/genre changes
watch([searchQuery, selectedGenre], () => { displayCount.value = PAGE_SIZE; });

// Watch route changes for genre query param
watch(() => route.query.genre, (newGenre) => {
  if (newGenre !== undefined) {
    selectedGenre.value = newGenre || 'all';
    applyFilters();
  }
});

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
    <!-- ═══ HERO · Оружейная ═══ -->
    <section class="catalog-hero">
      <!-- Небо горна -->
      <div class="hero-sky"></div>
      <!-- Горящий горн снизу -->
      <div class="hero-forge-glow"></div>
      <!-- Каменная "стена" снизу -->
      <div class="hero-stone-wall"></div>

      <div class="hero-inner">
        <div class="hero-badge reveal">
          <span class="badge-spike"></span>
          Оружейная воина
          <span class="badge-spike right"></span>
        </div>
        <h1 class="hero-title reveal">
          <span class="hero-title-line">Выбери</span>
          <span class="hero-title-line hero-title-accent">своё оружие</span>
        </h1>
        <p class="hero-sub reveal">Лицензионные ключи для ПК, PlayStation и Xbox — мгновенная доставка</p>

        <div class="hero-search reveal">
          <span class="srch-icon" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          </span>
          <input v-model="searchQuery" type="text" placeholder="Искать игру, жанр, платформу..." class="srch-input" />
          <button v-if="searchQuery" class="srch-clear" @click="searchQuery = ''">✕</button>
        </div>
      </div>
    </section>

    <!-- ═══ FILTER BAR ═══ -->
    <div class="filters-bar-wrap">
      <div class="filters-bar reveal">
        <div class="genre-chips">
          <button
            v-for="g in ['all', ...genres]" :key="g"
            class="genre-chip" :class="{ active: selectedGenre === g }"
            @click="selectedGenre = g; applyFilters()"
          >
            <span class="chip-marker"></span>
            {{ g === 'all' ? 'Все жанры' : g }}
          </button>
        </div>
        <div class="sort-wrap">
          <label class="sort-label">Сортировка</label>
          <div class="sort-field">
            <select v-model="sortBy" @change="applyFilters" class="sort-sel">
              <option value="release_date_desc">Новинки</option>
              <option value="price_asc">Дешевле</option>
              <option value="price_desc">Дороже</option>
              <option value="rating_desc">По рейтингу</option>
              <option value="title_asc">А → Я</option>
              <option value="title_desc">Я → А</option>
            </select>
            <span class="sort-chevron" aria-hidden="true">▾</span>
          </div>
        </div>
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
        <div class="empty-icon">⚠</div>
        <p class="empty-text">{{ error }}</p>
        <button @click="fetchGames" class="empty-btn">Попробовать снова</button>
      </div>

      <!-- Empty -->
      <div v-else-if="filteredGames.length === 0" class="empty-box">
        <div class="empty-icon">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </div>
        <p class="empty-text">Разведчики не нашли ничего по твоему запросу</p>
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
            Поднимаем следующую партию…
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
/* ============================================================
   ASHENFORGE · CatalogView — Оружейная
   ============================================================ */

@keyframes revealUp {
  from { opacity: 0; transform: translateY(26px); }
  to   { opacity: 1; transform: translateY(0); }
}
@keyframes shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
@keyframes lmSpin { to { transform: rotate(360deg); } }

.reveal {
  opacity: 0;
  transform: translateY(26px);
  transition:
    opacity 0.6s var(--ease-smoke) calc(var(--i, 0) * 60ms),
    transform 0.6s var(--ease-smoke) calc(var(--i, 0) * 60ms);
}
.reveal.is-visible { opacity: 1; transform: none; }

.catalog-root { min-height: 100vh; color: var(--text-bone); }

/* ==========================================================
   HERO
   ========================================================== */
.catalog-hero {
  position: relative;
  min-height: 44vh;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  padding: 80px 24px 70px;
  isolation: isolate;
}

/* Небо */
.hero-sky {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse 100% 70% at 50% 0%, rgba(194, 40, 26, 0.28) 0%, transparent 55%),
    radial-gradient(ellipse 80% 50% at 50% 100%, rgba(226, 67, 16, 0.3) 0%, transparent 65%),
    linear-gradient(180deg, #0a0806 0%, #1a100c 35%, #2a1612 75%, #3a1a12 100%);
  z-index: -2;
}
/* Сияние горна */
.hero-forge-glow {
  position: absolute;
  bottom: -40px; left: 50%;
  transform: translateX(-50%);
  width: 120%;
  height: 220px;
  background: radial-gradient(ellipse 50% 80% at 50% 100%,
    rgba(255, 122, 43, 0.5) 0%,
    rgba(226, 67, 16, 0.3) 25%,
    transparent 60%);
  filter: blur(12px);
  z-index: -1;
  animation: emberPulse 4s ease-in-out infinite;
  pointer-events: none;
}
/* Каменная стена снизу */
.hero-stone-wall {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 12vh;
  background:
    linear-gradient(180deg,
      transparent 0%,
      rgba(18, 16, 13, 0.4) 40%,
      var(--ash-obsidian) 100%);
  z-index: -1;
  pointer-events: none;
}

.hero-inner {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: var(--sp-5);
  max-width: 780px;
}

/* Badge */
.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-family: var(--font-display);
  font-size: 0.78rem;
  font-weight: var(--fw-semibold);
  color: var(--brass);
  background: linear-gradient(180deg, rgba(42, 10, 8, 0.75) 0%, rgba(18, 16, 13, 0.6) 100%);
  border: 1px solid var(--iron-mid);
  padding: 8px 20px;
  text-transform: uppercase;
  letter-spacing: var(--ls-wider);
  box-shadow:
    var(--inset-iron-top),
    0 0 18px rgba(194, 40, 26, 0.25);
  backdrop-filter: blur(8px);
  clip-path: polygon(10px 0, calc(100% - 10px) 0, 100% 50%, calc(100% - 10px) 100%, 10px 100%, 0 50%);
}
.badge-spike {
  width: 0; height: 0;
  border-top: 5px solid transparent;
  border-bottom: 5px solid transparent;
  border-right: 8px solid var(--ember-heart);
  filter: drop-shadow(0 0 4px rgba(194, 40, 26, 0.6));
}
.badge-spike.right {
  border-right: none;
  border-left: 8px solid var(--ember-heart);
}

/* Title */
.hero-title {
  font-family: var(--font-display);
  font-weight: var(--fw-black);
  font-size: clamp(2rem, 5.5vw, 4.2rem);
  line-height: 1;
  letter-spacing: var(--ls-tight);
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.hero-title-line {
  color: var(--text-bright);
  text-shadow:
    0 2px 0 rgba(0, 0, 0, 0.7),
    0 4px 14px rgba(0, 0, 0, 0.8),
    0 0 40px rgba(194, 40, 26, 0.15);
}
.hero-title-accent {
  background: var(--grad-ember-text);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  filter: drop-shadow(0 0 18px rgba(226, 67, 16, 0.45));
}

.hero-sub {
  font-family: var(--font-body);
  font-style: italic;
  color: var(--text-parchment);
  font-size: 1.05rem;
  margin: 0;
  max-width: 560px;
  line-height: 1.6;
}

/* Search */
.hero-search {
  display: flex;
  align-items: center;
  width: 100%;
  max-width: 620px;
  gap: 12px;
  padding: 6px 10px 6px 20px;
  background: linear-gradient(180deg, var(--ash-obsidian) 0%, var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  box-shadow:
    inset 0 2px 4px rgba(0, 0, 0, 0.5),
    var(--inset-iron-top),
    var(--shadow-cast);
  transition: all var(--dur-med) var(--ease-smoke);
  clip-path: polygon(14px 0, calc(100% - 14px) 0, 100% 50%, calc(100% - 14px) 100%, 14px 100%, 0 50%);
}
.hero-search:focus-within {
  border-color: var(--ember-heart);
  box-shadow:
    inset 0 2px 4px rgba(0, 0, 0, 0.5),
    var(--inset-iron-top),
    var(--glow-ember-soft),
    var(--shadow-cast);
}
.srch-icon {
  color: var(--bronze);
  display: flex;
  flex-shrink: 0;
  transition: color var(--dur-fast);
}
.hero-search:focus-within .srch-icon { color: var(--ember-spark); }

.srch-input {
  flex: 1;
  background: none;
  border: none;
  outline: none;
  color: var(--text-bone);
  font-family: var(--font-ui);
  font-size: 1rem;
  padding: 12px 0;
}
.srch-input::placeholder { color: var(--text-smoke); font-style: italic; }

.srch-clear {
  background: var(--ash-stone);
  border: 1px solid var(--iron-mid);
  color: var(--text-ash);
  cursor: pointer;
  width: 30px; height: 30px;
  border-radius: var(--r-xs);
  font-size: 0.8rem;
  display: grid;
  place-items: center;
  transition: all var(--dur-fast) var(--ease-smoke);
  flex-shrink: 0;
}
.srch-clear:hover {
  background: var(--ash-bloodrock);
  border-color: var(--ember-deep);
  color: var(--ember-flame);
}

/* ==========================================================
   FILTER BAR
   ========================================================== */
.filters-bar-wrap {
  position: sticky;
  top: var(--header-h);
  z-index: 40;
  background: linear-gradient(180deg,
    rgba(18, 16, 13, 0.92) 0%,
    rgba(27, 22, 17, 0.92) 100%);
  backdrop-filter: blur(14px);
  border-bottom: 1px solid var(--iron-dark);
  box-shadow: var(--shadow-subtle);
}
.filters-bar {
  max-width: var(--page-max);
  margin: 0 auto;
  padding: 18px var(--sp-6);
  display: flex;
  flex-wrap: wrap;
  gap: 18px;
  align-items: center;
  justify-content: space-between;
}

.genre-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  flex: 1;
}

.genre-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: var(--r-xs);
  border: 1px solid var(--iron-mid);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  color: var(--text-parchment);
  font-family: var(--font-display);
  font-size: 0.78rem;
  font-weight: var(--fw-semibold);
  text-transform: uppercase;
  letter-spacing: var(--ls-wide);
  cursor: pointer;
  white-space: nowrap;
  transition: all var(--dur-fast) var(--ease-smoke);
  position: relative;
  box-shadow: var(--inset-iron-top);
}
.chip-marker {
  display: inline-block;
  width: 6px; height: 6px;
  border-radius: 50%;
  background: var(--iron-warm);
  transition: all var(--dur-fast) var(--ease-smoke);
}
.genre-chip:hover {
  color: var(--text-bright);
  border-color: var(--iron-warm);
  background: linear-gradient(180deg, var(--ash-ironrust) 0%, var(--ash-stone) 100%);
}
.genre-chip:hover .chip-marker { background: var(--brass); box-shadow: 0 0 4px rgba(199, 154, 94, 0.5); }

.genre-chip.active {
  color: var(--text-bright);
  border-color: var(--ember-heart);
  background: linear-gradient(180deg,
    rgba(194, 40, 26, 0.35) 0%,
    rgba(90, 20, 18, 0.4) 100%);
  box-shadow:
    var(--inset-iron-top),
    var(--glow-ember-soft),
    var(--inset-forge);
}
.genre-chip.active .chip-marker {
  background: var(--ember-glow);
  box-shadow:
    0 0 8px rgba(255, 122, 43, 0.9),
    0 0 16px rgba(226, 67, 16, 0.5);
}

/* Sort */
.sort-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}
.sort-label {
  font-family: var(--font-tribal);
  font-size: 0.72rem;
  color: var(--brass);
  letter-spacing: var(--ls-widest);
  text-transform: uppercase;
  white-space: nowrap;
}
.sort-field {
  position: relative;
  display: inline-flex;
}
.sort-sel {
  appearance: none;
  -webkit-appearance: none;
  padding: 9px 36px 9px 16px;
  border-radius: var(--r-xs);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  color: var(--text-bone);
  font-family: var(--font-ui);
  font-size: 0.88rem;
  font-weight: var(--fw-medium);
  outline: none;
  cursor: pointer;
  transition: all var(--dur-fast) var(--ease-smoke);
  box-shadow: var(--inset-iron-top);
}
.sort-sel:hover { border-color: var(--iron-warm); }
.sort-sel:focus {
  border-color: var(--ember-heart);
  box-shadow:
    var(--inset-iron-top),
    var(--glow-ember-soft);
}
.sort-sel option {
  background: var(--ash-coal);
  color: var(--text-bone);
}
.sort-chevron {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--brass);
  font-size: 0.75rem;
  pointer-events: none;
}

/* ==========================================================
   CATALOG BODY
   ========================================================== */
.catalog-body {
  max-width: var(--page-max);
  margin: 0 auto;
  padding: var(--sp-8) var(--sp-6) var(--sp-20);
}

.games-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(262px, 1fr));
  gap: 22px;
}

/* ==========================================================
   SKELETONS
   ========================================================== */
.skel-card {
  overflow: hidden;
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  clip-path: var(--clip-forged-sm);
  box-shadow:
    inset 0 0 0 1px var(--iron-mid),
    inset 0 0 0 3px var(--iron-void),
    var(--shadow-cast);
  height: 380px;
}
.skel-img {
  height: 210px;
  background: linear-gradient(90deg, var(--ash-coal) 25%, var(--ash-stone) 50%, var(--ash-coal) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-bottom: 1px solid var(--iron-dark);
}
.skel-body {
  padding: 18px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.skel-line {
  height: 12px;
  border-radius: var(--r-xs);
  background: linear-gradient(90deg, var(--ash-coal) 25%, var(--iron-dark) 50%, var(--ash-coal) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}
.w80 { width: 80%; } .w50 { width: 50%; } .w65 { width: 65%; }

/* ==========================================================
   EMPTY / ERROR
   ========================================================== */
.empty-box {
  text-align: center;
  padding: 80px 40px;
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  clip-path: var(--clip-forged-md);
  box-shadow:
    inset 0 0 0 1px var(--iron-mid),
    inset 0 0 0 3px var(--iron-void),
    var(--shadow-cast);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--sp-5);
  margin: var(--sp-5) 0;
}
.empty-icon {
  font-size: 3rem;
  color: var(--bronze);
  opacity: 0.6;
}
.empty-text {
  font-family: var(--font-body);
  font-style: italic;
  color: var(--text-parchment);
  font-size: 1.08rem;
  margin: 0;
}
.empty-btn {
  padding: 12px 28px;
  font-family: var(--font-display);
  font-size: 0.88rem;
  font-weight: var(--fw-bold);
  text-transform: uppercase;
  letter-spacing: var(--ls-wide);
  background: var(--grad-ember);
  border: 1px solid var(--ember-heart);
  color: var(--text-bright);
  cursor: pointer;
  border-radius: var(--r-xs);
  box-shadow:
    var(--inset-iron-top),
    inset 0 -2px 3px rgba(0, 0, 0, 0.35),
    var(--glow-ember-soft);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.7);
  transition: all var(--dur-fast) var(--ease-smoke);
}
.empty-btn:hover {
  filter: brightness(1.15);
  box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.35), var(--glow-ember-strong);
  transform: translateY(-1px);
}

/* ==========================================================
   GRID TRANSITION
   ========================================================== */
.grid-enter-active { transition: all 0.4s var(--ease-smoke); }
.grid-leave-active { transition: all 0.3s var(--ease-smoke); position: absolute; }
.grid-enter-from { opacity: 0; transform: translateY(20px) scale(0.97); }
.grid-leave-to { opacity: 0; transform: scale(0.95); }

/* ==========================================================
   INFINITE SCROLL
   ========================================================== */
.sentinel {
  height: 72px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.load-more-hint {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--text-ash);
  font-family: var(--font-body);
  font-style: italic;
  font-size: 0.92rem;
}
.lm-spinner {
  display: inline-block;
  width: 15px;
  height: 15px;
  border: 2px solid var(--iron-dark);
  border-top-color: var(--ember-flame);
  border-radius: 50%;
  animation: lmSpin 0.7s linear infinite;
}
.fade-enter-active, .fade-leave-active { transition: opacity var(--dur-fast); }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* ==========================================================
   RESULTS BAR
   ========================================================== */
.results-bar {
  margin-top: var(--sp-6);
  text-align: center;
  font-family: var(--font-body);
  font-style: italic;
  color: var(--text-ash);
  font-size: 0.95rem;
}
.results-bar strong {
  color: var(--ember-gold);
  font-family: var(--font-display);
  font-weight: var(--fw-bold);
  font-style: normal;
  text-shadow: 0 0 6px rgba(255, 201, 121, 0.3);
}

/* ==========================================================
   RESPONSIVE
   ========================================================== */
@media (max-width: 1100px) {
  .games-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 18px; }
}
@media (max-width: 980px) {
  .filters-bar { flex-direction: column; align-items: stretch; gap: 14px; }
  .sort-wrap { justify-content: flex-end; }
  .genre-chips { justify-content: center; }
  .catalog-hero { min-height: 36vh; padding: 70px 22px 60px; }
  /* На мобиле sticky-фильтры съедают пол-экрана при прокрутке —
     отключаем "прилипание", фильтры скроллятся вместе со страницей. */
  .filters-bar-wrap { position: static; }
}
@media (max-width: 768px) {
  .games-grid { grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
  .catalog-body { padding: var(--sp-6) var(--sp-5) var(--sp-16); }
}
@media (max-width: 640px) {
  .catalog-hero { padding: 60px 18px 50px; min-height: 30vh; }
  .catalog-body { padding: var(--sp-5) var(--sp-4) var(--sp-14); }
  .games-grid { grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 14px; }
  .genre-chip { padding: 7px 12px; font-size: 0.72rem; }
  .chip-marker { width: 5px; height: 5px; }
  .empty-box { padding: 60px 24px; }
  .filters-bar { padding: 14px 14px; }
  .skel-card { height: 320px; }
  .skel-img { height: 170px; }
}
@media (max-width: 480px) {
  .catalog-hero { padding: 50px 16px 42px; }
  .catalog-body { padding: var(--sp-4) var(--sp-3) var(--sp-12); }
  /* Чтобы карточки не были слишком узкими — две колонки фикс */
  .games-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
}
@media (max-width: 380px) {
  .games-grid { grid-template-columns: 1fr; gap: 14px; }
}
</style>
