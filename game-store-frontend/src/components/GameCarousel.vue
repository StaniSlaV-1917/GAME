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
/* ==========================================================
   GAME CAROUSEL · Ashenforge
   Рулетка клинков — каменная сцена с ember-свечением,
   центральная карта как раскалённый клинок, победитель —
   в луче огня
   ========================================================== */
@keyframes spin-icon { to { transform: rotate(360deg); } }
@keyframes ringPulse {
  0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.55; }
  50%       { transform: translate(-50%, -50%) scale(1.14); opacity: 0.15; }
}
@keyframes winnerRing {
  0%   { transform: scale(1); opacity: 0.75; }
  100% { transform: scale(1.4); opacity: 0; }
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
  background: rgba(0, 0, 0, 0);
  transition: background 0.5s var(--ease-smoke);
  z-index: 100; pointer-events: none; cursor: pointer;
}
.overlay.active { background: rgba(8, 6, 10, 0.92); pointer-events: auto; }

/* ===== CONTAINER ===== */
.carousel-container { width: 100%; padding: 0 0 44px; text-align: center; }

/* ===== HEADER ===== */
.carousel-header {
  max-width: 1200px;
  margin: 0 auto 36px;
  padding: 0 24px;
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 20px;
}
.carousel-titles { text-align: left; }
.carousel-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-family: var(--font-ui);
  font-size: 0.78rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 3px;
  color: var(--bronze);
  margin-bottom: 10px;
}
.carousel-eyebrow::before,
.carousel-eyebrow::after {
  content: '';
  width: 0; height: 0;
  border-left: 4px solid transparent;
  border-right: 4px solid transparent;
  border-top: 6px solid var(--bronze);
  filter: drop-shadow(0 0 3px rgba(199, 154, 94, 0.5));
}
.carousel-title {
  font-family: var(--font-display);
  font-size: clamp(1.8rem, 3.2vw, 2.4rem);
  font-weight: var(--fw-black, 900);
  color: var(--text-bright);
  margin: 0;
  letter-spacing: 0.3px;
  text-align: left;
  text-shadow: 0 2px 12px rgba(0, 0, 0, 0.55);
}
.carousel-nav { display: flex; gap: 10px; padding-bottom: 6px; }
.nav-arrow {
  width: 44px; height: 44px;
  border: 1px solid var(--iron-mid);
  background: linear-gradient(180deg,
    var(--ash-stone) 0%,
    var(--ash-coal) 100%);
  color: var(--bronze);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: var(--inset-iron-top);
  transition: all 0.2s var(--ease-smoke);
  clip-path: polygon(20% 0%, 100% 0%, 100% 80%, 80% 100%, 0% 100%, 0% 20%);
}
.nav-arrow:hover:not(:disabled) {
  border-color: var(--ember-flame);
  color: var(--ember-gold);
  box-shadow: var(--inset-iron-top), 0 0 12px rgba(226, 67, 16, 0.3);
}
.nav-arrow:disabled { opacity: 0.3; cursor: not-allowed; }

/* ===== VIEWPORT ===== */
.carousel-viewport {
  width: 100%;
  position: relative;
  min-height: 440px;
  transition: filter 0.3s var(--ease-smoke);
}
.carousel-viewport.spinning { filter: brightness(0.82) saturate(1.15); }

/* ===== TRACK ===== */
.carousel-track { display: flex; justify-content: center; align-items: center; gap: 20px; padding: 20px 0; }

/* ===== CARD LINK ===== */
.card-link {
  display: block;
  text-decoration: none;
  flex-shrink: 0;
  transition:
    transform 0.45s var(--ease-smoke),
    filter 0.45s var(--ease-smoke),
    opacity 0.45s var(--ease-smoke);
  will-change: transform, filter, opacity;
}
.card-link.is-side { cursor: pointer; }
.card-link.is-side:hover { filter: brightness(1.12) !important; }
.card-link.is-center { cursor: default; }

/* ===== GAME CARD · каменная плита ===== */
.game-card {
  width: 220px;
  background: linear-gradient(180deg,
    var(--ash-ironrust) 0%,
    var(--ash-stone) 50%,
    var(--ash-coal) 100%);
  overflow: hidden;
  border: 1px solid var(--iron-mid);
  clip-path: var(--clip-forged-sm);
  box-shadow: var(--inset-iron-top), var(--shadow-cast);
  transition: box-shadow 0.4s var(--ease-smoke), border-color 0.4s var(--ease-smoke);
}
.game-card.center {
  width: 250px;
  border-color: var(--ember-heart);
  box-shadow:
    var(--inset-iron-top),
    0 20px 60px rgba(8, 6, 10, 0.7),
    0 0 32px rgba(226, 67, 16, 0.35);
}

/* Card image */
.card-img-wrap { position: relative; overflow: hidden; }
.card-img {
  width: 100%;
  height: 300px;
  object-fit: cover;
  display: block;
  transition: transform 0.5s var(--ease-smoke);
  filter: saturate(0.9);
}
.card-link.is-center .card-img { height: 330px; filter: saturate(1.05); }
.card-link.is-side:hover .card-img { transform: scale(1.05); }

.card-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top,
    var(--ash-obsidian) 0%,
    rgba(18, 16, 13, 0.2) 55%,
    transparent 100%);
  pointer-events: none;
}

.card-badge-center {
  position: absolute;
  top: 12px;
  left: 12px;
  z-index: 2;
}
.center-badge {
  font-family: var(--font-ui);
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 1.8px;
  text-transform: uppercase;
  color: var(--ember-gold);
  background: rgba(8, 6, 10, 0.75);
  border: 1px solid var(--ember-heart);
  padding: 4px 12px;
  box-shadow: 0 0 10px rgba(226, 67, 16, 0.35);
  text-shadow: 0 0 6px rgba(255, 201, 121, 0.5);
}

/* Card info */
.card-info {
  padding: 14px 16px 16px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 10px;
  background: linear-gradient(180deg,
    transparent 0%,
    rgba(8, 6, 10, 0.3) 100%);
  border-top: 1px solid var(--iron-dark);
}
.card-title {
  font-family: var(--font-display);
  font-size: 0.92rem;
  font-weight: 700;
  color: var(--text-bright);
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
  letter-spacing: 0.3px;
}
.card-price {
  font-family: var(--font-display);
  font-size: 0.98rem;
  font-weight: 700;
  color: var(--ember-gold);
  margin: 0;
  white-space: nowrap;
  flex-shrink: 0;
  text-shadow: 0 0 8px rgba(255, 201, 121, 0.3);
}

/* Carousel transition */
.carousel-move { transition: transform 0.45s cubic-bezier(0.55, 0, 0.1, 1); }
.carousel-enter-active, .carousel-leave-active { transition: all 0.5s cubic-bezier(0.55, 0, 0.1, 1); }
.carousel-enter-from, .carousel-leave-to { opacity: 0; transform: scale(0.6) !important; }
.carousel-leave-active { position: absolute; }

/* ===== SPIN GLOW — раскалённые кольца горна ===== */
.spin-glow { position: absolute; top: 50%; left: 50%; pointer-events: none; z-index: 5; }
.spin-ring {
  position: absolute;
  border-radius: 50%;
  border: 2px solid var(--ember-flame);
  animation: ringPulse 1.2s ease-in-out infinite;
  box-shadow: 0 0 24px rgba(226, 67, 16, 0.55);
}
.spin-ring.r1 { width: 300px; height: 300px; transform: translate(-50%,-50%); }
.spin-ring.r2 {
  width: 460px; height: 460px;
  transform: translate(-50%,-50%);
  animation-delay: 0.4s;
  border-color: var(--ember-heart);
  opacity: 0.6;
}

/* ===== PAGINATION DOTS ===== */
.pagination-dots {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin: 18px 0 26px;
}
.dot {
  width: 8px; height: 8px;
  border: 1px solid var(--iron-mid);
  background: var(--ash-coal);
  cursor: pointer;
  transition: all 0.25s var(--ease-smoke);
  padding: 0;
  clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%);
}
.dot.active {
  background: var(--grad-ember);
  border-color: var(--ember-heart);
  width: 26px;
  box-shadow: 0 0 10px rgba(226, 67, 16, 0.55);
}
.dots-more {
  font-family: var(--font-ui);
  font-size: 0.78rem;
  color: var(--text-ash);
  letter-spacing: 0.5px;
}

/* ===== LOADING ===== */
.loading-placeholder {
  padding-top: 120px;
  font-family: var(--font-body);
  color: var(--text-ash);
}
.loading-dots { display: flex; justify-content: center; gap: 8px; margin-bottom: 16px; }
.loading-dots span {
  width: 10px; height: 10px;
  border-radius: 50%;
  background: var(--ember-flame);
  animation: loadingDot 1.2s var(--ease-smoke) infinite;
  filter: drop-shadow(0 0 4px rgba(226, 67, 16, 0.55));
}
.loading-dots span:nth-child(2) { animation-delay: 0.2s; background: var(--ember-glow); }
.loading-dots span:nth-child(3) { animation-delay: 0.4s; background: var(--ember-gold); }

/* ===== CONTROLS ===== */
.carousel-controls {
  margin-top: 12px;
  min-height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* "Испытать удачу" — кованая кнопка */
.roulette-btn {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 15px 40px;
  font-family: var(--font-display);
  font-size: 1.02rem;
  font-weight: 700;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  cursor: pointer;
  color: var(--text-bright);
  border: 1px solid var(--ember-heart);
  background: var(--grad-ember);
  box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.35), var(--glow-ember);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.65);
  clip-path: var(--clip-forged-sm);
  overflow: hidden;
  transition: transform 0.2s var(--ease-forge), box-shadow 0.2s var(--ease-smoke);
}
.roulette-btn::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg,
    transparent 0%, rgba(255, 201, 121, 0.4) 50%, transparent 100%);
  transform: translateX(-120%);
  transition: transform 0.6s var(--ease-smoke);
  pointer-events: none;
}
.roulette-btn:hover:not(:disabled) {
  transform: translateY(-3px);
  box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.35), var(--glow-ember-strong);
}
.roulette-btn:hover:not(:disabled)::after { transform: translateX(120%); }
.roulette-btn:disabled {
  background: var(--ash-stone);
  border-color: var(--iron-mid);
  color: var(--text-smoke);
  box-shadow: var(--inset-iron-top);
  cursor: not-allowed;
  text-shadow: none;
}
.roulette-btn.spinning {
  background: linear-gradient(180deg, var(--ash-forge) 0%, var(--ash-bloodrock) 100%);
  color: var(--ember-gold);
}

.winner-actions { display: flex; gap: 14px; flex-wrap: wrap; justify-content: center; }

.winner-btn-buy {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 15px 36px;
  font-family: var(--font-display);
  font-size: 1.02rem;
  font-weight: 700;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  text-decoration: none;
  color: var(--text-bright);
  border: 1px solid var(--ember-heart);
  background: var(--grad-ember);
  box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.35), var(--glow-ember);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.65);
  clip-path: var(--clip-forged-sm);
  transition: transform 0.2s var(--ease-forge), box-shadow 0.2s var(--ease-smoke);
}
.winner-btn-buy:hover {
  transform: translateY(-3px);
  box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.35), var(--glow-ember-strong);
}

.winner-btn-reset {
  padding: 14px 28px;
  font-family: var(--font-ui);
  font-size: 0.88rem;
  font-weight: 700;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  cursor: pointer;
  color: var(--text-parchment);
  background: transparent;
  border: 1px solid var(--bronze-dark);
  box-shadow: var(--inset-iron-top);
  clip-path: var(--clip-forged-sm);
  transition: all 0.2s var(--ease-smoke);
}
.winner-btn-reset:hover {
  background: rgba(122, 93, 72, 0.15);
  color: var(--text-bright);
  border-color: var(--bronze);
}

/* ===== WINNER SPOTLIGHT — луч из горна ===== */
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
  gap: 18px;
  text-align: center;
}

.winner-badge {
  font-family: var(--font-display);
  font-size: 0.85rem;
  font-weight: var(--fw-black, 900);
  letter-spacing: 4px;
  text-transform: uppercase;
  color: var(--ember-gold);
  background: rgba(8, 6, 10, 0.75);
  border: 1px solid var(--ember-heart);
  padding: 7px 26px;
  box-shadow: 0 0 18px rgba(226, 67, 16, 0.45);
  text-shadow: 0 0 8px rgba(255, 201, 121, 0.6);
}

.winner-img-wrap {
  position: relative;
  width: 264px;
  overflow: visible;
}
.winner-img {
  width: 264px;
  height: 364px;
  object-fit: cover;
  display: block;
  clip-path: var(--clip-forged-md);
  box-shadow:
    0 0 0 2px var(--ember-heart),
    0 0 0 4px var(--bronze),
    0 30px 70px rgba(0, 0, 0, 0.85),
    0 0 60px rgba(226, 67, 16, 0.45);
}
.winner-glow-ring {
  position: absolute;
  border-radius: 50%;
  border: 2px solid var(--ember-flame);
  top: 50%; left: 50%;
  animation: winnerRing 2s ease-out infinite;
  box-shadow: 0 0 24px rgba(226, 67, 16, 0.55);
}
.winner-glow-ring.r1 { width: 340px; height: 340px; }
.winner-glow-ring.r2 { width: 340px; height: 340px; animation-delay: 0.8s; border-color: var(--ember-gold); }

.winner-info { color: var(--text-bright); }
.winner-title {
  font-family: var(--font-display);
  font-size: 1.5rem;
  font-weight: var(--fw-black, 900);
  margin: 0 0 6px;
  letter-spacing: 0.3px;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.7);
}
.winner-price {
  font-family: var(--font-display);
  font-size: 1.6rem;
  font-weight: 900;
  color: var(--ember-gold);
  margin: 0;
  text-shadow: 0 0 16px rgba(255, 201, 121, 0.55);
}
.winner-hint {
  font-family: var(--font-ui);
  font-size: 0.78rem;
  color: var(--text-ash);
  margin: 0;
  letter-spacing: 0.5px;
}

/* Winner pop transition */
.winner-pop-enter-active { transition: all 0.4s var(--ease-forge); }
.winner-pop-leave-active { transition: all 0.3s var(--ease-smoke); }
.winner-pop-enter-from { opacity: 0; transform: translate(-50%, -50%) scale(0.7); }
.winner-pop-leave-to   { opacity: 0; transform: translate(-50%, -50%) scale(0.88); }

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
