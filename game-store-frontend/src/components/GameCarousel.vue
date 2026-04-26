<template>
  <div class="carousel-wrapper">
    <!-- Winner overlay (затемнение всего экрана) -->
    <div class="overlay" :class="{ active: isOverlayVisible }" @click="resetRoulette"></div>

    <div class="carousel-container">
      <!-- Header -->
      <div class="carousel-header">
        <div class="carousel-titles">
          <span class="carousel-eyebrow">
            <span class="eb-spike" aria-hidden="true"></span>
            Рулетка клинков
            <span class="eb-spike" aria-hidden="true"></span>
          </span>
          <h2 class="carousel-title">Ковать или купить?</h2>
        </div>
      </div>

      <!-- Сцена: track в кованой раме с заклёпками, по бокам — стрелки -->
      <div
        class="carousel-stage"
        :class="{ spinning: isSpinning }"
        @mouseenter="pauseAuto"
        @mouseleave="resumeAuto"
        @touchstart.passive="onTouchStart"
        @touchend.passive="onTouchEnd"
      >
        <span class="stage-rivet stage-rivet--tl" aria-hidden="true"></span>
        <span class="stage-rivet stage-rivet--tr" aria-hidden="true"></span>
        <span class="stage-rivet stage-rivet--bl" aria-hidden="true"></span>
        <span class="stage-rivet stage-rivet--br" aria-hidden="true"></span>
        <div class="stage-glow" aria-hidden="true"></div>

        <!-- Стрелка назад -->
        <button
          class="nav-arrow nav-arrow--left"
          v-if="!winnerGame && !isSpinning && games.length"
          @click="shift(-1)"
          aria-label="Назад"
        >
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </button>

        <div class="carousel-viewport">
          <div v-if="!games.length" class="loading-placeholder">
            <div class="loading-dots">
              <span></span><span></span><span></span>
            </div>
            <p>Раздуваем горн…</p>
          </div>

          <!-- Track с 3D-перспективой -->
          <TransitionGroup v-else tag="div" name="carousel" class="carousel-track">
            <router-link
              v-for="card in visibleCards"
              :key="card.game.id + '_' + card.offset"
              :to="{ name: 'game', params: { id: card.game.id } }"
              class="card-link"
              :style="card.dynamicStyle"
              :class="{ 'is-center': card.isCenter, 'is-side': !card.isCenter, [`off-${card.offset}`]: true }"
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
                </div>
                <div class="card-info">
                  <p class="card-title">{{ card.game.title }}</p>
                  <p class="card-price">{{ Number(card.game.price).toFixed(0) }} ₽</p>
                </div>
              </div>
            </router-link>
          </TransitionGroup>

          <!-- Искры горна во время вращения -->
          <div class="spin-sparks" v-if="isSpinning" aria-hidden="true">
            <span v-for="i in 14" :key="`sp${i}`" class="spark" :style="{ '--i': i }"></span>
          </div>
        </div>

        <!-- Стрелка вперёд -->
        <button
          class="nav-arrow nav-arrow--right"
          v-if="!winnerGame && !isSpinning && games.length"
          @click="shift(1)"
          aria-label="Вперёд"
        >
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>

      <!-- Pagination dots — маленькие шипы под сценой -->
      <div class="pagination-dots" v-if="games.length && games.length <= 12 && !winnerGame">
        <button
          v-for="(_, i) in games.length"
          :key="i"
          class="dot"
          :class="{ active: getDotIndex(i) }"
          @click="goTo(i)"
          :aria-label="`Игра ${i + 1}`"
        ></button>
      </div>

      <!-- Управление: кнопка "Испытать удачу" видна только когда нет победителя.
           Кнопки победителя переехали внутрь spotlight'а (см. ниже). -->
      <div class="carousel-controls">
        <button
          v-if="!winnerGame"
          class="roulette-btn"
          @click="startRoulette"
          :disabled="isSpinning || !games.length"
          :class="{ spinning: isSpinning }"
        >
          <span class="rb-label">{{ isSpinning ? 'Огонь раздут…' : 'Испытать удачу' }}</span>
        </button>
      </div>
    </div>

    <!-- Подсветка победителя: лучи вокруг картинки + кнопки в одном блоке -->
    <Transition name="winner-pop">
      <div
        class="winner-spotlight"
        v-if="winnerGame && isOverlayVisible"
        @click.self="resetRoulette"
      >
        <div class="winner-card" @click.stop>
          <div class="winner-badge">КОВАНЫЙ ПОБЕДИТЕЛЬ</div>
          <div class="winner-img-wrap">
            <!-- Лучи горна теперь живут вокруг картинки, а не на весь экран —
                 не уходят в инфинити вниз и не перекрывают кнопки -->
            <svg class="winner-rays" viewBox="-100 -100 200 200" aria-hidden="true">
              <g class="rays-spin">
                <g v-for="i in 12" :key="`r${i}`" :transform="`rotate(${i * 30})`">
                  <path d="M 0 -42 L 6 -98 L -6 -98 Z" class="ray" />
                </g>
              </g>
            </svg>
            <span class="wc-rivet wc-rivet--tl" aria-hidden="true"></span>
            <span class="wc-rivet wc-rivet--tr" aria-hidden="true"></span>
            <span class="wc-rivet wc-rivet--bl" aria-hidden="true"></span>
            <span class="wc-rivet wc-rivet--br" aria-hidden="true"></span>
            <img :src="resolveImageUrl(winnerGame.image)" :alt="winnerGame.title" class="winner-img" loading="lazy" />
          </div>
          <div class="winner-info">
            <h3 class="winner-title">{{ winnerGame.title }}</h3>
            <p class="winner-price">{{ Number(winnerGame.price).toFixed(0) }} ₽</p>
          </div>
          <!-- Кнопки победителя — внутри spotlight, на одном слое с картинкой -->
          <div class="winner-actions">
            <router-link :to="{ name: 'game', params: { id: winnerGame.id } }" class="winner-btn-buy">
              <span class="wbb-label">Взять за {{ Number(winnerGame.price).toFixed(0) }} ₽</span>
            </router-link>
            <button class="winner-btn-reset" @click="resetRoulette">Крутить ещё</button>
          </div>
          <p class="winner-hint">Клик мимо — закрыть</p>
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
const isPaused = ref(false);

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
    const sign = offset === 0 ? 0 : offset / Math.abs(offset);

    // 3D-глубина: чем дальше от центра, тем сильнее поворот Y и меньше масштаб.
    // Вместо дорогого blur — saturate + opacity + translate3d.
    const scale = offset === 0 ? 1.1 : 1 - distance * 0.13;
    const rotateY = offset === 0 ? 0 : -sign * (14 + distance * 6);
    const translateZ = offset === 0 ? 0 : -distance * 40;
    const translateX = offset === 0 ? 0 : sign * distance * 6;
    const opacity = offset === 0 ? 1 : Math.max(0.4, 1 - distance * 0.2);
    const saturate = offset === 0 ? 1.08 : Math.max(0.55, 1 - distance * 0.14);
    const brightness = offset === 0 ? 1 : Math.max(0.7, 1 - distance * 0.08);

    cards.push({
      game: games.value[realIndex],
      realIndex,
      offset,
      isCenter: offset === 0,
      dynamicStyle: {
        transform: `translate3d(${translateX}px, 0, ${translateZ}px) scale(${scale}) rotateY(${rotateY}deg)`,
        opacity,
        filter: `saturate(${saturate}) brightness(${brightness})`,
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

// ── Auto-scroll: 3.5s (а не 2.2s — даём время прочитать) ──
const AUTO_MS = 3500;

const startAutoScroll = () => {
  if (autoTimer || !games.value.length) return;
  autoTimer = setInterval(() => {
    if (isSpinning.value || winnerGame.value || isPaused.value) return;
    centerIndex.value++;
  }, AUTO_MS);
};

const stopAutoScroll = () => {
  if (autoTimer) { clearInterval(autoTimer); autoTimer = null; }
};

const pauseAuto = () => { isPaused.value = true; };
const resumeAuto = () => { isPaused.value = false; };

const handleSideCardClick = (offset) => {
  if (isSpinning.value || winnerGame.value) return;
  stopAutoScroll();
  centerIndex.value += offset;
  startAutoScroll();
};

// ── Клавиатура ──
const onKeyDown = (e) => {
  if (winnerGame.value || isSpinning.value || !games.value.length) return;
  if (e.key === 'ArrowLeft')  { e.preventDefault(); shift(-1); }
  if (e.key === 'ArrowRight') { e.preventDefault(); shift(1); }
};

// ── Свайп ──
let touchStartX = 0;
let touchStartY = 0;
const onTouchStart = (e) => {
  touchStartX = e.changedTouches[0].clientX;
  touchStartY = e.changedTouches[0].clientY;
};
const onTouchEnd = (e) => {
  if (winnerGame.value || isSpinning.value) return;
  const dx = e.changedTouches[0].clientX - touchStartX;
  const dy = e.changedTouches[0].clientY - touchStartY;
  // Работает только на горизонтальный жест, достаточно энергичный.
  if (Math.abs(dx) < 50 || Math.abs(dy) > Math.abs(dx) * 0.8) return;
  shift(dx > 0 ? -1 : 1);
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
      const base = 2400 / steps;
      let mult;
      if (progress < 0.22)       mult = 0.4;
      else if (progress < 0.7)   mult = 1;
      else {
        const t = (progress - 0.7) / 0.3;
        mult = 1 + (1 - Math.pow(1 - t, 3)) * 6.2;
      }
      spinTimer = setTimeout(spin, base * mult);
    } else {
      const winnerReal = ((centerIndex.value % len) + len) % len;
      setTimeout(() => {
        winnerGame.value = games.value[winnerReal];
        isSpinning.value = false;
        setTimeout(() => { isOverlayVisible.value = true; }, 450);
      }, 280);
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

const init = async () => {
  await loadGames();
  startAutoScroll();
  window.addEventListener('keydown', onKeyDown);
};

onMounted(init);
onActivated(() => {
  window.addEventListener('keydown', onKeyDown);
  if (!autoTimer && games.value.length) startAutoScroll();
});
onUnmounted(() => {
  stopAutoScroll();
  if (spinTimer) clearTimeout(spinTimer);
  window.removeEventListener('keydown', onKeyDown);
});
onDeactivated(() => {
  stopAutoScroll();
  window.removeEventListener('keydown', onKeyDown);
});
</script>

<style scoped>
/* ==========================================================
   GAME CAROUSEL · Ashenforge (v2 smooth)
   Рулетка клинков — каменная сцена с 3D-перспективой,
   центральная карта как раскалённый клинок, 12 лучей горна
   за победителем. Без blur — только translate3d + saturate.
   ========================================================== */
@keyframes curGlowPulse {
  0%, 100% { opacity: 0.45; transform: translate(-50%, -50%) scale(1); }
  50%      { opacity: 0.7;  transform: translate(-50%, -50%) scale(1.1); }
}
@keyframes loadingDot {
  0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
  40%           { transform: scale(1); opacity: 1; }
}
@keyframes spark-fly {
  0%   { transform: rotate(var(--angle)) translate(0, 0) scale(0.8); opacity: 0; }
  18%  { transform: rotate(var(--angle)) translate(0, -24px) scale(1); opacity: 1; }
  100% { transform: rotate(var(--angle)) translate(0, -190px) scale(0.18); opacity: 0; }
}
@keyframes rays-turn {
  to { transform: rotate(360deg); }
}
@keyframes ray-flicker {
  0%, 100% { opacity: 0.85; }
  50%      { opacity: 0.45; }
}

/* ===== WRAPPER ===== */
.carousel-wrapper { position: relative; width: 100%; overflow: hidden; }

/* ===== OVERLAY ===== */
.overlay {
  position: fixed; inset: 0;
  background: rgba(8, 6, 10, 0);
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
  justify-content: center;
}
.carousel-titles { text-align: center; }
.carousel-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  font-family: var(--font-ui);
  font-size: 0.78rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 3px;
  color: var(--bronze);
  margin-bottom: 12px;
}
.eb-spike {
  width: 0; height: 0;
  border-left: 5px solid transparent;
  border-right: 5px solid transparent;
  border-top: 7px solid var(--bronze);
  filter: drop-shadow(0 0 3px rgba(199, 154, 94, 0.55));
}
.carousel-title {
  font-family: var(--font-display);
  font-size: clamp(1.9rem, 3.4vw, 2.6rem);
  font-weight: var(--fw-black, 900);
  color: var(--text-bright);
  margin: 0;
  letter-spacing: 0.3px;
  text-shadow: 0 2px 12px rgba(0, 0, 0, 0.55);
}

/* ===== STAGE ===== */
.carousel-stage {
  position: relative;
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px clamp(48px, 7vw, 90px);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  /* Каменная рама — лёгкий шов по контуру через inset shadow */
  box-shadow:
    inset 0 0 0 1px var(--iron-dark),
    inset 0 1px 0 rgba(255, 201, 121, 0.08),
    inset 0 -1px 0 rgba(0, 0, 0, 0.5);
  transition: filter 0.4s var(--ease-smoke);
}
.carousel-stage.spinning { filter: brightness(0.88) saturate(1.2); }

/* Заклёпки по углам сцены */
.stage-rivet {
  position: absolute;
  width: 9px; height: 9px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%,
    var(--brass) 0%,
    var(--bronze) 50%,
    var(--iron-void) 100%);
  box-shadow:
    inset -1px -1px 2px rgba(0, 0, 0, 0.7),
    0 0 4px rgba(199, 154, 94, 0.45);
  z-index: 20;
  pointer-events: none;
}
.stage-rivet--tl { top: 14px; left: 14px; }
.stage-rivet--tr { top: 14px; right: 14px; }
.stage-rivet--bl { bottom: 14px; left: 14px; }
.stage-rivet--br { bottom: 14px; right: 14px; }

/* Тёплый центральный блик — горн под сценой */
.stage-glow {
  position: absolute;
  left: 50%; top: 50%;
  width: 70%; height: 90%;
  transform: translate(-50%, -50%);
  background: radial-gradient(ellipse at center,
    rgba(226, 67, 16, 0.18) 0%,
    transparent 60%);
  pointer-events: none;
  z-index: 0;
  animation: curGlowPulse 5s var(--ease-smoke) infinite;
}

/* ===== NAV ARROWS (в боках сцены) ===== */
.nav-arrow {
  position: relative;
  flex-shrink: 0;
  width: 48px; height: 56px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--bronze-dark);
  background: linear-gradient(180deg,
    var(--ash-stone) 0%,
    var(--ash-coal) 100%);
  color: var(--bronze);
  cursor: pointer;
  box-shadow: var(--inset-iron-top), var(--shadow-cast);
  clip-path: polygon(20% 0%, 100% 0%, 100% 80%, 80% 100%, 0% 100%, 0% 20%);
  transition:
    color 0.22s var(--ease-smoke),
    border-color 0.22s var(--ease-smoke),
    box-shadow 0.22s var(--ease-smoke),
    transform 0.2s var(--ease-forge);
  z-index: 10;
}
.nav-arrow:hover {
  color: var(--ember-gold);
  border-color: var(--ember-flame);
  box-shadow: var(--inset-iron-top), var(--shadow-cast), 0 0 14px rgba(226, 67, 16, 0.35);
  transform: translateY(-2px);
}
.nav-arrow:active { transform: translateY(0); }
.nav-arrow--left  { margin-right: 4px; }
.nav-arrow--right { margin-left: 4px; }

/* ===== VIEWPORT ===== */
.carousel-viewport {
  position: relative;
  flex: 1;
  min-height: 440px;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1;
}

/* ===== TRACK (3D) ===== */
.carousel-track {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 18px;
  padding: 20px 0;
  width: 100%;
  perspective: 1500px;
  perspective-origin: 50% 50%;
  transform-style: preserve-3d;
}

/* ===== CARD LINK ===== */
.card-link {
  display: block;
  text-decoration: none;
  flex-shrink: 0;
  transform-style: preserve-3d;
  transform-origin: center center;
  backface-visibility: hidden;
  will-change: transform, opacity, filter;
  /* Плавное движение: 0.55s с easing, которое "садится" без резкого хлопка */
  transition:
    transform 0.55s cubic-bezier(0.22, 0.8, 0.3, 1),
    opacity 0.55s cubic-bezier(0.22, 0.8, 0.3, 1),
    filter 0.55s cubic-bezier(0.22, 0.8, 0.3, 1);
}
.card-link.is-side { cursor: pointer; }
.card-link.is-side:hover { filter: saturate(1) brightness(1.08) !important; }
.card-link.is-center { cursor: default; }

/* ===== GAME CARD · каменная плита с clip-path ===== */
.game-card {
  width: 224px;
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
  width: 252px;
  border-color: var(--ember-heart);
  box-shadow:
    var(--inset-iron-top),
    0 22px 60px rgba(8, 6, 10, 0.75),
    0 0 32px rgba(226, 67, 16, 0.42);
}

/* Card image */
.card-img-wrap { position: relative; overflow: hidden; }
.card-img {
  width: 100%;
  height: 300px;
  object-fit: cover;
  display: block;
  transition: transform 0.5s var(--ease-smoke);
  filter: saturate(0.92);
}
.card-link.is-center .card-img { height: 332px; filter: saturate(1.06); }
.card-link.is-side:hover .card-img { transform: scale(1.05); }

.card-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top,
    var(--ash-obsidian) 0%,
    rgba(18, 16, 13, 0.18) 55%,
    transparent 100%);
  pointer-events: none;
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
  font-size: 0.94rem;
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
  font-size: 1rem;
  font-weight: 700;
  color: var(--ember-gold);
  margin: 0;
  white-space: nowrap;
  flex-shrink: 0;
  text-shadow: 0 0 8px rgba(255, 201, 121, 0.35);
}

/* ===== TransitionGroup FLIP — move (плавная перестановка DOM) ===== */
.carousel-move,
.carousel-enter-active,
.carousel-leave-active { transition: all 0.5s cubic-bezier(0.22, 0.8, 0.3, 1); }
.carousel-enter-from,
.carousel-leave-to { opacity: 0; transform: scale(0.55); }
.carousel-leave-active { position: absolute; }

/* ===== SPIN SPARKS — искры горна ===== */
.spin-sparks {
  position: absolute;
  top: 50%; left: 50%;
  pointer-events: none;
  z-index: 5;
}
.spark {
  position: absolute;
  top: 0; left: 0;
  width: 5px; height: 5px;
  margin: -2.5px 0 0 -2.5px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%,
    var(--ember-gold) 0%,
    var(--ember-glow) 50%,
    var(--ember-flame) 100%);
  box-shadow: 0 0 6px rgba(255, 201, 121, 0.7);
  --angle: calc(var(--i) * 25deg);
  transform-origin: center;
  animation: spark-fly 1.4s cubic-bezier(0.4, 0, 0.3, 1) infinite;
  animation-delay: calc(var(--i) * 90ms);
  opacity: 0;
}

/* ===== PAGINATION DOTS — маленькие шипы-ромбы ===== */
.pagination-dots {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin: 18px 0 26px;
}
.dot {
  width: 9px; height: 9px;
  border: 1px solid var(--iron-mid);
  background: var(--ash-coal);
  cursor: pointer;
  padding: 0;
  clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%);
  transition: all 0.25s var(--ease-smoke);
}
.dot:hover { border-color: var(--bronze); background: var(--ash-stone); }
.dot.active {
  background: var(--grad-ember);
  border-color: var(--ember-heart);
  width: 28px;
  box-shadow: 0 0 10px rgba(226, 67, 16, 0.55);
}

/* ===== LOADING ===== */
.loading-placeholder {
  padding-top: 120px;
  font-family: var(--font-body);
  color: var(--text-ash);
}
.loading-dots { display: flex; justify-content: center; gap: 8px; margin-bottom: 16px; }
.loading-dots span {
  width: 11px; height: 11px;
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

.roulette-btn {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 15px 44px;
  /* Фиксируем ширину, чтобы при смене лейбла «Испытать удачу» ↔ «Огонь раздут…»
     кнопка не сжималась/растягивалась и центр оставался стабильным */
  min-width: 240px;
  font-family: var(--font-display);
  font-size: 1.04rem;
  font-weight: 700;
  letter-spacing: 1.6px;
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
  border-color: var(--ember-flame);
  /* ВАЖНО: НЕ используем curGlowPulse — он содержит translate(-50%, -50%)
     для центрирования .stage-glow и применённый к flex-кнопке смещает её влево-вверх.
     Вместо этого — пульсация box-shadow и filter, без transform. */
  animation: rouletteSpinPulse 1.4s var(--ease-smoke) infinite;
}
@keyframes rouletteSpinPulse {
  0%, 100% {
    box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.35), var(--glow-ember);
    filter: brightness(1);
  }
  50% {
    box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.35), var(--glow-ember-strong);
    filter: brightness(1.15);
  }
}
.rb-label { position: relative; z-index: 1; }

.winner-actions { display: flex; gap: 14px; flex-wrap: wrap; justify-content: center; }

.winner-btn-buy {
  position: relative;
  display: inline-flex;
  align-items: center;
  padding: 15px 38px;
  font-family: var(--font-display);
  font-size: 1.04rem;
  font-weight: 700;
  letter-spacing: 1.6px;
  text-transform: uppercase;
  text-decoration: none;
  color: var(--text-bright);
  border: 1px solid var(--ember-heart);
  background: var(--grad-ember);
  box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.35), var(--glow-ember);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.65);
  clip-path: var(--clip-forged-sm);
  overflow: hidden;
  transition: transform 0.2s var(--ease-forge), box-shadow 0.2s var(--ease-smoke);
}
.winner-btn-buy::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg,
    transparent 0%, rgba(255, 201, 121, 0.4) 50%, transparent 100%);
  transform: translateX(-120%);
  transition: transform 0.6s var(--ease-smoke);
  pointer-events: none;
}
.winner-btn-buy:hover {
  transform: translateY(-3px);
  box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.35), var(--glow-ember-strong);
}
.winner-btn-buy:hover::after { transform: translateX(120%); }
.wbb-label { position: relative; z-index: 1; }

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

/* ===== WINNER SPOTLIGHT — лучи горна вокруг картинки + плита ===== */
.winner-spotlight {
  position: fixed;
  inset: 0;                         /* во весь экран — клик мимо карточки = закрытие */
  z-index: 110;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: auto;             /* клики ловим */
  padding: 20px;
}

/* Лучи теперь живут ВОКРУГ картинки в .winner-img-wrap (см. ниже),
   а не на весь экран. Селектор остаётся для совместимости стилей <svg>. */
.winner-rays {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 480px;
  height: 480px;
  transform: translate(-50%, -50%);
  pointer-events: none;
  z-index: 0;                       /* за картинкой */
}
.rays-spin {
  transform-origin: 50% 50%;
  transform-box: view-box;
  animation: rays-turn 40s linear infinite;
}
.ray {
  fill: var(--ember-glow);
  opacity: 0.65;
  filter: drop-shadow(0 0 12px rgba(226, 67, 16, 0.55));
  animation: ray-flicker 3.5s var(--ease-smoke) infinite;
}
.ray:nth-child(even) { animation-delay: 1.2s; opacity: 0.45; }

.winner-card {
  position: relative;
  z-index: 2;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  text-align: center;
  max-width: min(540px, 95vw);
}

/* Кнопки победителя — внутри spotlight'а, под картинкой и над лучами */
.winner-actions {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
  justify-content: center;
  margin-top: 4px;
}

.winner-badge {
  font-family: var(--font-display);
  font-size: 0.85rem;
  font-weight: var(--fw-black, 900);
  letter-spacing: 4px;
  text-transform: uppercase;
  color: var(--ember-gold);
  background: rgba(8, 6, 10, 0.78);
  border: 1px solid var(--ember-heart);
  padding: 7px 28px;
  box-shadow: 0 0 18px rgba(226, 67, 16, 0.45);
  text-shadow: 0 0 8px rgba(255, 201, 121, 0.6);
}

.winner-img-wrap {
  position: relative;
  width: 270px;
  /* Позволяем лучам выходить за рамки картинки */
  overflow: visible;
}
.wc-rivet {
  position: absolute;
  width: 10px; height: 10px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%,
    var(--brass) 0%,
    var(--bronze) 50%,
    var(--iron-void) 100%);
  box-shadow: inset -1px -1px 2px rgba(0, 0, 0, 0.7), 0 0 5px rgba(199, 154, 94, 0.55);
  z-index: 2;
}
.wc-rivet--tl { top: 10px; left: 10px; }
.wc-rivet--tr { top: 10px; right: 10px; }
.wc-rivet--bl { bottom: 10px; left: 10px; }
.wc-rivet--br { bottom: 10px; right: 10px; }
.winner-img {
  position: relative;
  z-index: 1;                       /* выше лучей (z-index: 0) */
  width: 270px;
  height: 372px;
  object-fit: cover;
  display: block;
  clip-path: var(--clip-forged-md);
  box-shadow:
    0 0 0 2px var(--ember-heart),
    0 0 0 4px var(--bronze),
    0 30px 70px rgba(0, 0, 0, 0.85),
    0 0 60px rgba(226, 67, 16, 0.55);
}

.winner-info { color: var(--text-bright); }
.winner-title {
  font-family: var(--font-display);
  font-size: 1.55rem;
  font-weight: var(--fw-black, 900);
  margin: 0 0 6px;
  letter-spacing: 0.3px;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.7);
}
.winner-price {
  font-family: var(--font-display);
  font-size: 1.65rem;
  font-weight: 900;
  color: var(--ember-gold);
  margin: 0;
  text-shadow: 0 0 16px rgba(255, 201, 121, 0.6);
}
.winner-hint {
  font-family: var(--font-ui);
  font-size: 0.78rem;
  color: var(--text-ash);
  margin: 0;
  letter-spacing: 0.5px;
}

/* Winner pop transition */
.winner-pop-enter-active { transition: all 0.45s var(--ease-forge); }
.winner-pop-leave-active { transition: all 0.3s var(--ease-smoke); }
.winner-pop-enter-from { opacity: 0; transform: translate(-50%, -50%) scale(0.78); }
.winner-pop-leave-to   { opacity: 0; transform: translate(-50%, -50%) scale(0.9); }

/* ===== RESPONSIVE ===== */
@media (max-width: 1100px) {
  .game-card { width: 220px; }
  .game-card.center { width: 260px; }
}
@media (max-width: 900px) {
  .carousel-stage { padding: 20px clamp(30px, 5vw, 60px); }
  .nav-arrow { width: 42px; height: 48px; }
  .game-card { width: 200px; }
  .game-card.center { width: 230px; }
  .card-img { height: 260px; }
  .card-link.is-center .card-img { height: 290px; }
  .winner-img-wrap, .winner-img { width: 240px; }
  .winner-img { height: 330px; }
  .roulette-btn { padding: 13px 36px; font-size: 0.96rem; min-width: 220px; }
}
@media (max-width: 720px) {
  .carousel-stage { padding: 18px clamp(20px, 4vw, 40px); }
  .game-card { width: 180px; }
  .game-card.center { width: 210px; }
  .card-img { height: 240px; }
  .card-link.is-center .card-img { height: 270px; }
  .winner-card { padding: 18px; }
  .winner-title { font-size: 1.4rem; }
}
@media (max-width: 600px) {
  .carousel-stage { padding: 16px 12px; }
  .nav-arrow { display: none; } /* на мобилке — только свайп */
  .game-card { width: 170px; }
  .game-card.center { width: 200px; }
  .card-img { height: 230px; }
  .card-link.is-center .card-img { height: 260px; }
  .winner-img-wrap, .winner-img { width: 210px; }
  .winner-img { height: 290px; }
  .roulette-btn { padding: 12px 28px; font-size: 0.88rem; min-width: 200px; }
}
@media (max-width: 480px) {
  .game-card { width: 150px; }
  .game-card.center { width: 180px; }
  .card-img { height: 200px; }
  .card-link.is-center .card-img { height: 230px; }
  .winner-img-wrap, .winner-img { width: 180px; }
  .winner-img { height: 250px; }
  .winner-title { font-size: 1.2rem; }
  .roulette-btn { padding: 11px 22px; font-size: 0.82rem; min-width: 180px; letter-spacing: 1.2px; }
  .winner-actions { flex-direction: column; width: 100%; }
  .winner-btn-buy, .winner-btn-reset { width: 100%; justify-content: center; }
}

/* ===== reduced-motion ===== */
@media (prefers-reduced-motion: reduce) {
  .stage-glow, .roulette-btn.spinning, .spark, .rays-spin, .ray { animation: none; }
  .card-link, .carousel-move, .carousel-enter-active, .carousel-leave-active {
    transition-duration: 0.25s;
  }
}
</style>
