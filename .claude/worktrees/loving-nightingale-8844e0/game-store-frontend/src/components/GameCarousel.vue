<template>
  <div class="carousel-wrapper">
    <!-- Winner overlay -->
    <div class="overlay" :class="{ active: isOverlayVisible }" @click="resetRoulette"></div>

    <div class="carousel-container">
      <!-- Header -->
      <div class="carousel-header">
        <div class="carousel-titles">
          <span class="carousel-eyebrow">🎲 Рулетка игр</span>
          <h2 class="carousel-title">Что купить сегодня?</h2>
        </div>
        <!-- Manual nav arrows -->
        <div class="carousel-nav" v-if="!winnerGame && !isSpinning">
          <button class="nav-arrow" @click="shift(-1)" :disabled="!games.length" aria-label="Назад">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
          </button>
          <button class="nav-arrow" @click="shift(1)" :disabled="!games.length" aria-label="Вперёд">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
        </div>
      </div>

      <!-- Track -->
      <div class="carousel-viewport" :class="{ spinning: isSpinning }">
        <div v-if="!games.length" class="loading-placeholder">
          <div class="loading-dots">
            <span></span><span></span><span></span>
          </div>
          <p>Загружаем игры...</p>
        </div>

        <TransitionGroup v-else tag="div" name="carousel" class="carousel-track">
          <router-link
            v-for="card in visibleCards"
            :key="card.game.id + '_' + card.offset"
            :to="{ name: 'game', params: { id: card.game.id } }"
            class="card-link"
            :style="card.dynamicStyle"
            :class="{ 'is-center': card.isCenter, 'is-side': !card.isCenter }"
            @click.prevent="card.isCenter ? null : handleSideCardClick(card.offset)"
          >
            <div class="game-card" :class="{ center: card.isCenter }">
              <div class="card-img-wrap">
                <img
                  :src="resolveImageUrl(card.game.image)"
                  :alt="card.game.title"
                  class="card-img"
                  loading="lazy"
                />
                <div class="card-overlay"></div>
                <!-- Center card extras -->
                <div v-if="card.isCenter" class="card-badge-center">
                  <span class="center-badge">В центре</span>
                </div>
              </div>
              <div class="card-info">
                <p class="card-title">{{ card.game.title }}</p>
                <p class="card-price">{{ Number(card.game.price).toFixed(0) }} ₽</p>
              </div>
            </div>
          </router-link>
        </TransitionGroup>

        <!-- Spin glow rings during roulette -->
        <div class="spin-glow" v-if="isSpinning">
          <div class="spin-ring r1"></div>
          <div class="spin-ring r2"></div>
        </div>
      </div>

      <!-- Pagination dots -->
      <div class="pagination-dots" v-if="games.length && !winnerGame">
        <button
          v-for="(_, i) in Math.min(games.length, 9)"
          :key="i"
          class="dot"
          :class="{ active: getDotIndex(i) }"
          @click="goTo(i)"
          :aria-label="`Игра ${i + 1}`"
        ></button>
        <span v-if="games.length > 9" class="dots-more">+{{ games.length - 9 }}</span>
      </div>

      <!-- Controls -->
      <div class="carousel-controls">
        <template v-if="!winnerGame">
          <button
            class="roulette-btn"
            @click="startRoulette"
            :disabled="isSpinning || !games.length"
            :class="{ spinning: isSpinning }"
          >
            <span>{{ isSpinning ? 'Крутится...' : 'Испытать удачу!' }}</span>
          </button>
        </template>
        <template v-else>
          <div class="winner-actions">
            <router-link :to="{ name: 'game', params: { id: winnerGame.id } }" class="winner-btn-buy">
              Купить за {{ Number(winnerGame.price).toFixed(0) }} ₽
            </router-link>
            <button class="winner-btn-reset" @click="resetRoulette">Крутить ещё</button>
          </div>
        </template>
      </div>
    </div>

    <!-- Winner spotlight (fixed center) -->
    <Transition name="winner-pop">
      <div class="winner-spotlight" v-if="winnerGame && isOverlayVisible">
        <div class="winner-card">
          <div class="winner-badge">ПОБЕДИТЕЛЬ</div>
          <div class="winner-img-wrap">
            <img :src="resolveImageUrl(winnerGame.image)" :alt="winnerGame.title" class="winner-img" loading="lazy" />
            <div class="winner-glow-ring r1"></div>
            <div class="winner-glow-ring r2"></div>
          </div>
          <div class="winner-info">
            <h3 class="winner-title">{{ winnerGame.title }}</h3>
            <p class="winner-price">{{ Number(winnerGame.price).toFixed(0) }} ₽</p>
          </div>
          <p class="winner-hint">Нажмите на затемнение или «Крутить ещё» чтобы продолжить</p>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, onActivated, onDeactivated } from 'vue';
import { RouterLink } from 'vue-router';
import axios from '@/api/axios';
import { resolveMediaUrl as resolveImageUrl } from '../utils/media';

const games = ref([]);
const centerIndex = ref(0);
const isSpinning = ref(false);
const winnerGame = ref(null);
const isOverlayVisible = ref(false);

const VISIBLE = 7;
const centerSlot = Math.floor(VISIBLE / 2);

let autoTimer = null;
let spinTimer = null;

const visibleCards = computed(() => {
  const len = games.value.length;
  if (!len) return [];
  const cards = [];
  for (let slot = 0; slot < VISIBLE; slot++) {
    const offset = slot - centerSlot;
    const realIndex = (((centerIndex.value + offset) % len) + len) % len;
    const distance = Math.abs(offset);
    cards.push({
      game: games.value[realIndex],
      realIndex,
      offset,
      isCenter: offset === 0,
      dynamicStyle: {
        filter: `blur(${distance * 1.1}px)`,
        transform: `scale(${offset === 0 ? 1.12 : 1 - distance * 0.17})`,
        opacity: offset === 0 ? 1 : Math.max(0.25, 1 - distance * 0.22),
        zIndex: offset === 0 ? 99 : VISIBLE - distance,
      },
    });
  }
  return cards;
});

const getDotIndex = (i) => {
  const len = games.value.length;
  const real = ((centerIndex.value % len) + len) % len;
  return real === i;
};

const goTo = (i) => {
  if (isSpinning.value || winnerGame.value) return;
  stopAutoScroll();
  centerIndex.value = i;
  startAutoScroll();
};

const shift = (dir) => {
  if (isSpinning.value || winnerGame.value) return;
  stopAutoScroll();
  centerIndex.value += dir;
  startAutoScroll();
};

const startAutoScroll = () => {
  if (autoTimer || !games.value.length) return;
  autoTimer = setInterval(() => {
    if (isSpinning.value || winnerGame.value) return;
    centerIndex.value++;
  }, 2200);
};

const stopAutoScroll = () => {
  if (autoTimer) { clearInterval(autoTimer); autoTimer = null; }
};

const handleSideCardClick = (offset) => {
  if (isSpinning.value || winnerGame.value) return;
  stopAutoScroll();
  centerIndex.value += offset;
  startAutoScroll();
};

const startRoulette = () => {
  if (isSpinning.value || !games.value.length) return;
  stopAutoScroll();
  isSpinning.value = true;
  winnerGame.value = null;
  isOverlayVisible.value = false;

  const len = games.value.length;
  const randomOffset = Math.floor(Math.random() * len);
  const currentReal = ((centerIndex.value % len) + len) % len;
  const steps = 4 * len + ((randomOffset - currentReal + len) % len);
  let currentStep = 0;

  const spin = () => {
    centerIndex.value++;
    currentStep++;
    if (currentStep < steps) {
      const progress = currentStep / steps;
      const base = 2200 / steps;
      let mult;
      if (progress < 0.2)       mult = 0.45;
      else if (progress < 0.68) mult = 1;
      else {
        const t = (progress - 0.68) / 0.32;
        mult = 1 + (1 - Math.pow(1 - t, 3)) * 5.5;
      }
      spinTimer = setTimeout(spin, base * mult);
    } else {
      const winnerReal = ((centerIndex.value % len) + len) % len;
      setTimeout(() => {
        winnerGame.value = games.value[winnerReal];
        isSpinning.value = false;
        setTimeout(() => { isOverlayVisible.value = true; }, 500);
      }, 250);
    }
  };
  spin();
};

const resetRoulette = () => {
  if (!winnerGame.value) return;
  isOverlayVisible.value = false;
  setTimeout(() => {
    winnerGame.value = null;
    isSpinning.value = false;
    startAutoScroll();
  }, 350);
};

const loadGames = async () => {
  try {
    const { data } = await axios.get('/games');
    if (data?.length && JSON.stringify(games.value) !== JSON.stringify(data)) {
      games.value = data;
    }
  } catch (e) { console.error('Ошибка загрузки игр:', e); }
};

const init = async () => { await loadGames(); startAutoScroll(); };

onMounted(init);
onActivated(init);
onUnmounted(() => { stopAutoScroll(); if (spinTimer) clearTimeout(spinTimer); });
onDeactivated(stopAutoScroll);
</script>

<style scoped>
/* ===== ANIMATIONS ===== */
@keyframes spin-icon { to { transform: rotate(360deg); } }
@keyframes ringPulse {
  0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.6; }
  50%       { transform: translate(-50%, -50%) scale(1.12); opacity: 0.2; }
}
@keyframes winnerRing {
  0%   { transform: scale(1); opacity: 0.7; }
  100% { transform: scale(1.35); opacity: 0; }
}
@keyframes loadingDot {
  0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
  40%           { transform: scale(1); opacity: 1; }
}

/* ===== WRAPPER ===== */
.carousel-wrapper { position: relative; width: 100%; overflow: hidden; }

/* ===== OVERLAY ===== */
.overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0);
  transition: background 0.5s ease;
  z-index: 100; pointer-events: none; cursor: pointer;
}
.overlay.active { background: rgba(0,0,0,0.88); pointer-events: auto; }

/* ===== CONTAINER ===== */
.carousel-container { width: 100%; padding: 0 0 40px; text-align: center; }

/* ===== HEADER ===== */
.carousel-header {
  max-width: 1200px;
  margin: 0 auto 32px;
  padding: 0 24px;
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
}
.carousel-eyebrow {
  display: block;
  font-size: 0.78rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 3px;
  color: #3b82f6;
  margin-bottom: 8px;
}
.carousel-title {
  font-size: clamp(1.6rem, 3vw, 2.2rem);
  font-weight: 800;
  color: #fff;
  margin: 0;
  letter-spacing: -0.5px;
  text-align: left;
}
.carousel-nav { display: flex; gap: 10px; padding-bottom: 6px; }
.nav-arrow {
  width: 42px; height: 42px;
  border-radius: 10px;
  border: 1px solid rgba(255,255,255,0.1);
  background: rgba(255,255,255,0.04);
  color: #9ca3af;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.2s;
}
.nav-arrow:hover:not(:disabled) { background: rgba(59,130,246,0.15); border-color: rgba(59,130,246,0.4); color: #60a5fa; }
.nav-arrow:disabled { opacity: 0.3; cursor: not-allowed; }

/* ===== VIEWPORT ===== */
.carousel-viewport {
  width: 100%;
  position: relative;
  min-height: 420px;
  transition: filter 0.3s;
}
.carousel-viewport.spinning { filter: brightness(0.85); }

/* ===== TRACK ===== */
.carousel-track { display: flex; justify-content: center; align-items: center; gap: 20px; padding: 20px 0; }

/* ===== CARD LINK ===== */
.card-link {
  display: block;
  text-decoration: none;
  border-radius: 14px;
  flex-shrink: 0;
  transition:
    transform 0.4s cubic-bezier(0.25, 0.8, 0.25, 1),
    filter 0.4s cubic-bezier(0.25, 0.8, 0.25, 1),
    opacity 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
  will-change: transform, filter, opacity;
}
.card-link.is-side { cursor: pointer; }
.card-link.is-side:hover { filter: brightness(1.1) !important; }
.card-link.is-center { cursor: default; }

/* ===== GAME CARD ===== */
.game-card {
  width: 220px;
  border-radius: 14px;
  background: #0f172a;
  overflow: hidden;
  border: 1px solid rgba(255,255,255,0.06);
  transition: box-shadow 0.4s, border-color 0.4s;
}
.game-card.center {
  width: 250px;
  border-color: rgba(59,130,246,0.4);
  box-shadow:
    0 0 0 1px rgba(59,130,246,0.25),
    0 20px 60px rgba(0,0,0,0.6),
    0 0 40px rgba(59,130,246,0.15);
}

/* Card image */
.card-img-wrap { position: relative; overflow: hidden; }
.card-img {
  width: 100%;
  height: 300px;
  object-fit: cover;
  display: block;
  transition: transform 0.4s ease;
}
.card-link.is-center .card-img { height: 330px; }
.card-link.is-side:hover .card-img { transform: scale(1.04); }

.card-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(2,6,23,0.85) 0%, rgba(2,6,23,0.1) 50%, transparent 100%);
  pointer-events: none;
}

.card-badge-center {
  position: absolute;
  top: 10px;
  left: 10px;
}
.center-badge {
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 1px;
  color: #fff;
  background: rgba(59,130,246,0.8);
  backdrop-filter: blur(6px);
  padding: 3px 10px;
  border-radius: 999px;
  text-transform: uppercase;
}

/* Card info */
.card-info {
  padding: 12px 14px 14px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 8px;
}
.card-title {
  font-size: 0.9rem;
  font-weight: 600;
  color: #e5e7eb;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
}
.card-price {
  font-size: 0.95rem;
  font-weight: 700;
  color: #4ade80;
  margin: 0;
  white-space: nowrap;
  flex-shrink: 0;
}

/* Carousel transition */
.carousel-move { transition: transform 0.45s cubic-bezier(0.55, 0, 0.1, 1); }
.carousel-enter-active, .carousel-leave-active { transition: all 0.5s cubic-bezier(0.55, 0, 0.1, 1); }
.carousel-enter-from, .carousel-leave-to { opacity: 0; transform: scale(0.6) !important; }
.carousel-leave-active { position: absolute; }

/* ===== SPIN GLOW ===== */
.spin-glow { position: absolute; top: 50%; left: 50%; pointer-events: none; z-index: 5; }
.spin-ring {
  position: absolute;
  border-radius: 50%;
  border: 2px solid rgba(59,130,246,0.5);
  animation: ringPulse 1.2s ease-in-out infinite;
}
.spin-ring.r1 { width: 300px; height: 300px; transform: translate(-50%,-50%); }
.spin-ring.r2 { width: 460px; height: 460px; transform: translate(-50%,-50%); animation-delay: 0.4s; border-color: rgba(99,102,241,0.3); }

/* ===== PAGINATION DOTS ===== */
.pagination-dots {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin: 16px 0 24px;
}
.dot {
  width: 8px; height: 8px;
  border-radius: 999px;
  border: none;
  background: rgba(255,255,255,0.2);
  cursor: pointer;
  transition: all 0.25s;
  padding: 0;
}
.dot.active {
  background: #3b82f6;
  width: 24px;
  box-shadow: 0 0 10px rgba(59,130,246,0.6);
}
.dots-more { font-size: 0.8rem; color: #6b7280; }

/* ===== LOADING ===== */
.loading-placeholder { padding-top: 120px; color: #6b7280; }
.loading-dots { display: flex; justify-content: center; gap: 8px; margin-bottom: 16px; }
.loading-dots span {
  width: 10px; height: 10px;
  border-radius: 50%;
  background: #3b82f6;
  animation: loadingDot 1.2s ease infinite;
}
.loading-dots span:nth-child(2) { animation-delay: 0.2s; }
.loading-dots span:nth-child(3) { animation-delay: 0.4s; }

/* ===== CONTROLS ===== */
.carousel-controls {
  margin-top: 8px;
  min-height: 56px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.roulette-btn {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 14px 36px;
  font-size: 1.1rem;
  font-weight: 700;
  cursor: pointer;
  color: #fff;
  border: none;
  border-radius: 999px;
  background: linear-gradient(135deg, #22c55e, #15803d);
  box-shadow: 0 8px 28px rgba(34,197,94,0.4);
  transition: all 0.25s;
}
.roulette-btn:hover:not(:disabled) {
  transform: translateY(-3px) scale(1.03);
  box-shadow: 0 14px 40px rgba(34,197,94,0.55);
}
.roulette-btn:disabled {
  background: #1f2937;
  color: #4b5563;
  box-shadow: none;
  cursor: not-allowed;
}
.roulette-btn.spinning .roulette-btn-icon { display: inline-block; animation: spin-icon 0.8s linear infinite; }

.winner-actions { display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; }

.winner-btn-buy {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 14px 32px;
  border-radius: 999px;
  font-size: 1.05rem;
  font-weight: 700;
  text-decoration: none;
  color: #fff;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  box-shadow: 0 8px 28px rgba(99,102,241,0.4);
  transition: all 0.25s;
}
.winner-btn-buy:hover { transform: translateY(-3px); box-shadow: 0 14px 40px rgba(99,102,241,0.55); }

.winner-btn-reset {
  padding: 14px 28px;
  border-radius: 999px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  color: #9ca3af;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.12);
  transition: all 0.2s;
}
.winner-btn-reset:hover { background: rgba(255,255,255,0.1); color: #fff; }

/* ===== WINNER SPOTLIGHT ===== */
.winner-spotlight {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 110;
  pointer-events: none;
}

.winner-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  text-align: center;
}

.winner-badge {
  font-size: 0.85rem;
  font-weight: 800;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: #fbbf24;
  background: rgba(251,191,36,0.1);
  border: 1px solid rgba(251,191,36,0.3);
  padding: 6px 22px;
  border-radius: 999px;
}

.winner-img-wrap {
  position: relative;
  width: 260px;
  border-radius: 16px;
  overflow: visible;
}
.winner-img {
  width: 260px;
  height: 360px;
  object-fit: cover;
  border-radius: 16px;
  display: block;
  box-shadow: 0 0 0 3px rgba(251,191,36,0.5), 0 30px 70px rgba(0,0,0,0.8);
}
.winner-glow-ring {
  position: absolute;
  border-radius: 50%;
  border: 2px solid rgba(251,191,36,0.6);
  top: 50%; left: 50%;
  animation: winnerRing 2s ease-out infinite;
}
.winner-glow-ring.r1 { width: 340px; height: 340px; }
.winner-glow-ring.r2 { width: 340px; height: 340px; animation-delay: 0.8s; }

.winner-info { color: #fff; }
.winner-title { font-size: 1.4rem; font-weight: 800; margin: 0 0 6px; }
.winner-price { font-size: 1.5rem; font-weight: 900; color: #4ade80; margin: 0; }
.winner-hint { font-size: 0.78rem; color: #4b5563; margin: 0; }

/* Winner pop transition */
.winner-pop-enter-active { transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
.winner-pop-leave-active { transition: all 0.3s ease; }
.winner-pop-enter-from { opacity: 0; transform: translate(-50%, -50%) scale(0.7); }
.winner-pop-leave-to   { opacity: 0; transform: translate(-50%, -50%) scale(0.85); }

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
  .carousel-header { flex-direction: column; align-items: flex-start; gap: 16px; }
  .game-card { width: 180px; }
  .game-card.center { width: 210px; }
  .card-img { height: 240px; }
  .card-link.is-center .card-img { height: 270px; }
  .winner-img-wrap, .winner-img { width: 220px; }
  .winner-img { height: 300px; }
}
</style>
