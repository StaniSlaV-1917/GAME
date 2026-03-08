<template>
  <div class="game-carousel-container">
    <h2 class="carousel-title">Что купить сегодня?</h2>
    <div class="game-carousel" v-if="games.length">
      <div class="carousel-wrapper" :style="{ transform: `translateX(${translateValue}px)` }">
        <div
          v-for="(game, index) in games"
          :key="game.id"
          class="game-card"
          :class="{
            'active': index === activeIndex,
            'prev': index === prevIndex,
            'next': index === nextIndex
          }"
        >
          <img :src="game.cover_image_url" :alt="game.title" />
          <div class="game-info">
            <h3>{{ game.title }}</h3>
            <p>{{ game.price }} руб.</p>
          </div>
        </div>
      </div>
    </div>
    <div v-else class="loading">Загрузка игр...</div>

    <button @click="startRoulette" class="roulette-button" :disabled="isSpinning">
      {{ isSpinning ? 'Крутится...' : 'Испытать удачу!' }}
    </button>

    <canvas v-if="showConfetti" class="confetti-container"></canvas>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, nextTick } from 'vue';
import axios from '@/api/axios';
import confetti from 'canvas-confetti';

const games = ref([]);
const activeIndex = ref(0);
const isSpinning = ref(false);
const showConfetti = ref(false);
let carouselInterval = null;

const fetchGames = async () => {
  try {
    const response = await axios.get('/games');
    games.value = response.data;
    // Duplicate games to create a seamless loop
    games.value = [...games.value, ...games.value, ...games.value];
  } catch (error) {
    console.error('Ошибка при загрузке игр:', error);
  }
};

const nextSlide = () => {
  activeIndex.value = (activeIndex.value + 1) % games.value.length;
};

const startCarousel = () => {
  carouselInterval = setInterval(nextSlide, 3000);
};

const startRoulette = () => {
  if (isSpinning.value) return;

  isSpinning.value = true;
  clearInterval(carouselInterval);

  let spinDuration = 5000; // 5 seconds
  let spinInterval = 50;
  let spins = spinDuration / spinInterval;
  let currentSpin = 0;

  const spin = () => {
    if (currentSpin < spins) {
      nextSlide();
      currentSpin++;
      const easing = 1 - (currentSpin / spins);
      spinInterval = 50 + (200 * easing); // Slow down
      setTimeout(spin, spinInterval);
    } else {
      isSpinning.value = false;
      showWinner();
      startCarousel(); // Restart auto-scroll
    }
  };

  spin();
};

const showWinner = () => {
  const winnerGame = games.value[activeIndex.value];
  console.log('Победитель:', winnerGame.title);

  // Trigger confetti
  showConfetti.value = true;
  nextTick(() => {
      const canvas = document.querySelector('.confetti-container');
      if (canvas) {
        const myConfetti = confetti.create(canvas, {
          resize: true,
          useWorker: true,
        });
        myConfetti({
          particleCount: 150,
          spread: 60,
        });
      }
  });


  setTimeout(() => {
    showConfetti.value = false;
    // Here you can add logic to show a special offer modal
    alert(`Поздравляем! Вы выиграли ${winnerGame.title} по специальной цене!`);
  }, 2000);
};

onMounted(() => {
  fetchGames();
  startCarousel();
});

onUnmounted(() => {
  clearInterval(carouselInterval);
});

const prevIndex = computed(() => (activeIndex.value - 1 + games.value.length) % games.value.length);
const nextIndex = computed(() => (activeIndex.value + 1) % games.value.length);
const translateValue = computed(() => {
    if (!games.value.length) return 0;
    const cardWidth = 300; // Adjust based on your card width + margin
    return -activeIndex.value * cardWidth + (window.innerWidth / 2) - (cardWidth / 2);
});

</script>

<style scoped>
.game-carousel-container {
  padding: 2rem 0;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.carousel-title {
  font-size: 2.5rem;
  margin-bottom: 2rem;
  color: #fff;
}

.game-carousel {
  position: relative;
  width: 100%;
  height: 450px;
  display: flex;
  align-items: center;
}

.carousel-wrapper {
  display: flex;
  transition: transform 0.5s ease;
}

.game-card {
  width: 280px;
  margin: 0 10px;
  border-radius: 10px;
  overflow: hidden;
  transition: transform 0.5s ease, filter 0.5s ease;
  transform: scale(0.8);
  filter: blur(3px);
  background: #202020;
}

.game-card.active {
  transform: scale(1);
  filter: blur(0);
  z-index: 10;
}

.game-card.prev,
.game-card.next {
  transform: scale(0.9);
  filter: blur(1px);
}

.game-card img {
  width: 100%;
  height: 350px;
  object-fit: cover;
}

.game-info {
  padding: 1rem;
}

.game-info h3 {
  font-size: 1.2rem;
  margin: 0;
}

.game-info p {
  font-size: 1rem;
  color: #4caf50;
  margin: 0.5rem 0 0;
}

.roulette-button {
  margin-top: 2rem;
  padding: 1rem 2rem;
  font-size: 1.2rem;
  cursor: pointer;
  background-color: #4caf50;
  color: white;
  border: none;
  border-radius: 5px;
  transition: background-color 0.3s;
}

.roulette-button:disabled {
  background-color: #666;
  cursor: not-allowed;
}

.roulette-button:hover:not(:disabled) {
  background-color: #45a049;
}

.loading {
    color: white;
    font-size: 1.5rem;
}

.confetti-container {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 100;
}
</style>