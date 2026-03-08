<template>
  <div class="carousel-container">
    <h2 class="carousel-title">Что купить сегодня?</h2>
    <div class="carousel-viewport">
      <div
        class="carousel-track"
        :style="trackStyle"
        ref="track"
        @transitionend="handleTransitionEnd"
      >
        <div
          v-for="(game, index) in displayedGames"
          :key="index"
          class="game-card"
          :class="{ 'active': index === activeCardIndex, 'winner': index === winnerIndex }"
        >
          <div class="card-content">
            <img :src="game.cover_image_url" :alt="game.title" class="game-image" />
            <div class="game-info">
              <h3>{{ game.title }}</h3>
              <p>{{ game.price }} руб.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="carousel-controls">
      <button @click="startRoulette" :disabled="isSpinning" class="roulette-button">
        {{ isSpinning ? 'Крутится...' : 'Испытать удачу!' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick } from 'vue';
import axios from '@/api/axios';

const games = ref([]);
const displayedGames = ref([]);
const isSpinning = ref(false);
const winnerIndex = ref(null);
const track = ref(null);

const CARD_WIDTH = 320; // Ширина карточки + отступы
const VISIBLE_CARDS = 5;
const CLONES = 10; // Количество клонов для "бесконечной" прокрутки

const state = reactive({
  currentIndex: CLONES, // Начинаем с первого "настоящего" элемента
  currentOffset: 0,
  transitionDuration: '0s',
});

const activeCardIndex = computed(() => {
  // Этот индекс будет постоянно указывать на центральную карту в видимой области
  return state.currentIndex + Math.floor(VISIBLE_CARDS / 2) - 3;
});

const trackStyle = computed(() => ({
  transform: `translateX(${state.currentOffset}px)`,
  transition: `transform ${state.transitionDuration} cubic-bezier(0.22, 0.61, 0.36, 1)`,
}));


const fetchGames = async () => {
  try {
    const response = await axios.get('/games');
    const originalGames = response.data;
    if (originalGames.length > 0) {
      // Создаем клоны для "бесконечной" прокрутки
      const clonesStart = originalGames.slice(-CLONES);
      const clonesEnd = originalGames.slice(0, CLONES);
      games.value = [...clonesStart, ...originalGames, ...clonesEnd];
      displayedGames.value = games.value;
      await nextTick();
      centerOnInitialCard();
    }
  } catch (error) {
    console.error('Ошибка при загрузке игр:', error);
  }
};

const centerOnInitialCard = () => {
  state.transitionDuration = '0s';
  const initialOffset = -state.currentIndex * CARD_WIDTH + (window.innerWidth / 2) - (CARD_WIDTH / 2);
  state.currentOffset = initialOffset;
};

const startRoulette = () => {
  if (isSpinning.value) return;

  isSpinning.value = true;
  winnerIndex.value = null;

  // Рассчитываем случайное количество шагов для прокрутки
  const totalGames = games.value.length - (2 * CLONES);
  const randomSpins = Math.floor(Math.random() * totalGames) + totalGames * 2; // Прокрутка минимум 2 круга
  const targetIndex = state.currentIndex + randomSpins;

  // Рассчитываем конечное смещение
  const finalOffset = -targetIndex * CARD_WIDTH + (window.innerWidth / 2) - (CARD_WIDTH / 2);

  // Устанавливаем длительность анимации
  state.transitionDuration = '6s'; // Длительность вращения
  state.currentOffset = finalOffset;
  state.currentIndex = targetIndex;
};


const handleTransitionEnd = () => {
  // Если мы остановились на клоне, "перепрыгиваем" на реальный элемент
  const totalOriginalGames = games.value.length - 2 * CLONES;
  if (state.currentIndex >= totalOriginalGames + CLONES) {
    state.transitionDuration = '0s';
    state.currentIndex -= totalOriginalGames;
    state.currentOffset = -state.currentIndex * CARD_WIDTH + (window.innerWidth / 2) - (CARD_WIDTH / 2);
  } else if (state.currentIndex < CLONES) {
      state.transitionDuration = '0s';
    state.currentIndex += totalOriginalGames;
    state.currentOffset = -state.currentIndex * CARD_WIDTH + (window.innerWidth / 2) - (CARD_WIDTH / 2);
  }

  if (isSpinning.value) {
    isSpinning.value = false;
    showWinner();
  }
};

const showWinner = () => {
  const winnerGameIndex = state.currentIndex % (games.value.length - 2 * CLONES) + CLONES;
  winnerIndex.value = winnerGameIndex;
  
  const winnerGame = games.value[winnerGameIndex];

  setTimeout(() => {
    alert(`Поздравляем! Вы выиграли ${winnerGame.title} по специальной цене!`);
    winnerIndex.value = null;
  }, 3000); // Показываем свечение 3 секунды
};


onMounted(() => {
  fetchGames();
  window.addEventListener('resize', centerOnInitialCard);
});

onUnmounted(() => {
  window.removeEventListener('resize', centerOnInitialCard);
});
</script>

<style scoped>
.carousel-container {
  width: 100%;
  padding: 3rem 0;
  background: #121212;
  overflow: hidden;
  position: relative;
}

.carousel-title {
  text-align: center;
  font-size: 2.5rem;
  margin-bottom: 2rem;
  color: #fff;
}

.carousel-viewport {
  width: 100%;
  position: relative;
}

.carousel-track {
  display: flex;
  will-change: transform;
}

.game-card {
  width: 300px;
  margin: 0 10px;
  flex-shrink: 0;
  border-radius: 15px;
  transition: transform 0.5s ease, opacity 0.5s ease;
  transform: scale(0.85);
  opacity: 0.5;
  background-color: #1a1a1a;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}

.game-card.active {
  transform: scale(1);
  opacity: 1;
}

.game-card.winner .card-content {
  box-shadow: 0 0 25px 5px #ffdf00, 0 0 35px 12px #ffbf00;
  border-radius: 15px;
}

.card-content {
    transition: box-shadow 0.4s ease-in-out;
}

.game-image {
  width: 100%;
  height: 380px;
  object-fit: cover;
  border-top-left-radius: 15px;
  border-top-right-radius: 15px;
  display: block;
}

.game-info {
  padding: 1.2rem;
  text-align: left;
}

.game-info h3 {
  font-size: 1.3rem;
  margin: 0 0 0.5rem 0;
  color: #fff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.game-info p {
  font-size: 1.1rem;
  color: #4caf50;
  margin: 0;
}

.carousel-controls {
  text-align: center;
  margin-top: 2.5rem;
}

.roulette-button {
  padding: 1rem 2.5rem;
  font-size: 1.3rem;
  font-weight: bold;
  cursor: pointer;
  background: linear-gradient(45deg, #4caf50, #2e7d32);
  color: white;
  border: none;
  border-radius: 50px;
  transition: all 0.3s ease;
  box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
}

.roulette-button:disabled {
  background: #666;
  cursor: not-allowed;
  box-shadow: none;
}

.roulette-button:hover:not(:disabled) {
  transform: translateY(-3px);
  box-shadow: 0 8px 20px rgba(76, 175, 80, 0.6);
}
</style>
