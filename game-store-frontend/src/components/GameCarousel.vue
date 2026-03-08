<template>
  <div class="carousel-wrapper">
    <div class="overlay" :class="{ active: !!winnerGame }"></div>

    <div class="carousel-container">
      <h2 class="carousel-title">Что купить сегодня?</h2>

      <div class="carousel-viewport">
        <div v-if="!games.length" class="loading-placeholder">
          Загрузка игр...
        </div>

        <div v-else class="carousel-track">
          <div
            v-for="(card, idx) in visibleCards"
            :key="card.game.id + '-' + idx"
            class="game-card"
            :class="{ active: idx === centerSlot }"
          >
            <div class="card-content">
              <img
                :src="resolveImageUrl(card.game.image)"
                :alt="card.game.title"
                class="game-image"
              />
            <div class="game-info">
              <h3>{{ card.game.title }}</h3>
              <p>{{ Number(card.game.price).toFixed(0) }} ₽</p>
            </div>
            </div>
          </div>
        </div>
      </div>

      <div class="winner-spotlight" v-if="winnerGame">
        <div class="game-card active winner">
          <div class="card-content">
            <img
              :src="resolveImageUrl(winnerGame.image)"
              :alt="winnerGame.title"
              class="game-image"
            />
            <div class="game-info">
              <h3>{{ winnerGame.title }}</h3>
              <p>{{ Number(winnerGame.price).toFixed(0) }} ₽</p>
            </div>
          </div>
        </div>
      </div>

      <div class="carousel-controls">
        <button
          @click="startRoulette"
          :disabled="isSpinning || !games.length"
          class="roulette-button"
        >
          {{ isSpinning ? 'Крутится...' : 'Испытать удачу!' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from '@/api/axios';

const games = ref([]);
const centerIndex = ref(0);
const isSpinning = ref(false);
const winnerGame = ref(null);

const VISIBLE = 5;
const centerSlot = Math.floor(VISIBLE / 2);

let autoTimer = null;
let spinTimer = null;

const resolveImageUrl = (imagePath) => {
  if (!imagePath) return '/img/noimage.png';
  if (imagePath.includes('/')) return `http://localhost:8000${imagePath}`;
  return `/img/${imagePath}`;
};

const visibleCards = computed(() => {
  const len = games.value.length;
  if (!len) return [];

  const cards = [];
  for (let slot = 0; slot < VISIBLE; slot++) {
    const offset = slot - centerSlot;
    const vIndex = centerIndex.value + offset;
    const realIndex = ((vIndex % len) + len) % len;
    cards.push({
      game: games.value[realIndex],
      realIndex,
    });
  }
  return cards;
});

// Плавный пассивный шаг
const startAutoScroll = () => {
  if (autoTimer || !games.value.length) return;
  autoTimer = setInterval(() => {
    if (isSpinning.value) return;
    centerIndex.value += 1;
  }, 3000); // каждые 3 секунды мягкий переход
};

const stopAutoScroll = () => {
  if (autoTimer) {
    clearInterval(autoTimer);
    autoTimer = null;
  }
};

const startRoulette = () => {
  if (isSpinning.value || !games.value.length) return;

  stopAutoScroll();
  isSpinning.value = true;
  winnerGame.value = null;

  const len = games.value.length;
  const extraRounds = 3;
  const randomOffset = Math.floor(Math.random() * len);

  const currentReal = ((centerIndex.value % len) + len) % len;
  const targetReal = randomOffset;
  const delta =
    extraRounds * len + ((targetReal - currentReal + len) % len);

  const steps = delta;
  const totalDurationMs = 5000; // ~5 секунд
  const intervalMs = totalDurationMs / steps;

  let moved = 0;

  if (spinTimer) {
    clearInterval(spinTimer);
    spinTimer = null;
  }

  spinTimer = setInterval(() => {
    centerIndex.value += 1;
    moved += 1;
    if (moved >= steps) {
      clearInterval(spinTimer);
      spinTimer = null;

      const winnerReal = ((centerIndex.value % len) + len) % len;
      winnerGame.value = games.value[winnerReal];

      setTimeout(() => {
        winnerGame.value = null;
        isSpinning.value = false;
        startAutoScroll();
      }, 4000);
    }
  }, Math.max(18, intervalMs)); // чуть медленнее, плавнее
};

const fetchGames = async () => {
  try {
    const { data } = await axios.get('/games');
    if (!data.length) return;
    games.value = data;
    centerIndex.value = 0;
    startAutoScroll();
  } catch (e) {
    console.error('Ошибка при загрузке игр:', e);
  }
};

onMounted(() => {
  fetchGames();
});

onUnmounted(() => {
  stopAutoScroll();
  if (spinTimer) clearInterval(spinTimer);
});
</script>

<style scoped>
.carousel-wrapper {
  position: relative;
  width: 100%;
  overflow: hidden;
}

.carousel-container {
  width: 100%;
  padding: 3rem 0;
  background: radial-gradient(circle at top, #1f2937 0, #020617 55%, #000 100%);
  text-align: center;
}

.carousel-title {
  font-size: 2.5rem;
  margin-bottom: 2.5rem;
  color: #fff;
  letter-spacing: 0.05em;
}

.carousel-viewport {
  width: 100%;
  position: relative;
  min-height: 450px;
}

.loading-placeholder {
  color: #9ca3af;
  font-size: 1.5rem;
  padding-top: 150px;
}

.carousel-track {
  display: flex;
  justify-content: center;
  gap: 24px;
  transition: transform 0.45s cubic-bezier(0.3, 0.7, 0.2, 1);
}

/* Карточки */
.game-card {
  width: 260px;
  flex-shrink: 0;
  border-radius: 18px;
  background-color: #0f172a;
  transform: scale(0.8);
  opacity: 0.3;
  filter: blur(2.2px);
  transition:
    transform 0.45s cubic-bezier(0.3, 0.7, 0.2, 1),
    opacity 0.45s cubic-bezier(0.3, 0.7, 0.2, 1),
    filter 0.45s cubic-bezier(0.3, 0.7, 0.2, 1),
    box-shadow 0.45s cubic-bezier(0.3, 0.7, 0.2, 1);
}

.game-card.active {
  transform: scale(1.06);
  opacity: 1;
  filter: blur(0);
  box-shadow: 0 18px 40px rgba(0, 0, 0, 0.6);
}

.card-content {
  border-radius: 18px;
  overflow: hidden;
}

.game-image {
  width: 100%;
  height: 340px;
  object-fit: cover;
  display: block;
}

.game-info {
  padding: 1.2rem;
  text-align: left;
}

.game-info h3 {
  font-size: 1.1rem;
  margin: 0 0 0.5rem 0;
  color: #fff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.game-info p {
  font-size: 1.05rem;
  color: #4ade80;
  margin: 0;
  font-weight: 500;
}

/* Оверлей и победитель */
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0);
  transition: background-color 0.8s ease-in-out;
  z-index: 10;
  pointer-events: none;
}

.overlay.active {
  background-color: rgba(0, 0, 0, 0.85);
}

.winner-spotlight {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 20;
  pointer-events: none;
}

.game-card.winner {
  transform: scale(1.12);
  filter: blur(0);
  opacity: 1;
}

.game-card.winner .card-content {
  border-radius: 18px;
  box-shadow:
    0 0 25px 8px rgba(250, 204, 21, 0.8),
    0 0 50px 20px rgba(251, 191, 36, 0.6);
}

/* Кнопка */
.carousel-controls {
  position: relative;
  z-index: 5;
  margin-top: 2.5rem;
}

.roulette-button {
  padding: 1rem 2.8rem;
  font-size: 1.3rem;
  font-weight: 700;
  cursor: pointer;
  background: linear-gradient(135deg, #22c55e, #15803d);
  color: white;
  border: none;
  border-radius: 999px;
  transition: all 0.25s ease;
  box-shadow: 0 8px 25px rgba(34, 197, 94, 0.5);
}

.roulette-button:disabled {
  background: #4b5563;
  cursor: not-allowed;
  box-shadow: none;
  transform: scale(1);
}

.roulette-button:hover:not(:disabled) {
  transform: translateY(-3px) scale(1.03);
  box-shadow: 0 12px 35px rgba(34, 197, 94, 0.65);
}
</style>