<template>
  <div class="carousel-wrapper">
    <div class="overlay" :class="{ active: isOverlayVisible }" @click="resetRoulette"></div>

    <div class="carousel-container">
      <h2 class="carousel-title">Что купить сегодня?</h2>

      <div class="carousel-viewport">
        <div v-if="!games.length" class="loading-placeholder">
          Загрузка игр...
        </div>

        <TransitionGroup v-else tag="div" name="carousel" class="carousel-track">
          <router-link
            v-for="card in visibleCards"
            :key="card.game.id"
            :to="{ name: 'game', params: { id: card.game.id } }"
            class="game-card-link"
            :style="card.dynamicStyle"
            :class="{ 'is-center': card.isCenter }"
            @click.prevent="card.isCenter ? null : handleSideCardClick(card.offset)"
          >
            <div class="game-card">
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
          </router-link>
        </TransitionGroup>
      </div>

      <div class="winner-spotlight" v-if="winnerGame">
        <router-link :to="{ name: 'game', params: { id: winnerGame.id } }" class="game-card-link">
          <div class="game-card winner">
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
        </router-link>
      </div>

      <div class="carousel-controls">
        <button
          v-if="!winnerGame"
          @click="startRoulette"
          :disabled="isSpinning || !games.length"
          class="roulette-button"
        >
          {{ isSpinning ? 'Крутится...' : 'Испытать удачу!' }}
        </button>
        <button
          v-if="winnerGame"
          @click="resetRoulette"
          class="roulette-button reset-button"
        >
          Продолжить
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, onActivated, onDeactivated } from 'vue';
import { RouterLink } from 'vue-router';
import axios from '@/api/axios';

// --- Reactive State --- //

const games = ref([]); // Массив всех загруженных игр
const centerIndex = ref(0); // Индекс игры, которая в данный момент находится в центре карусели
const isSpinning = ref(false); // Флаг, активна ли анимация прокрутки рулетки
const winnerGame = ref(null); // Объект игры-победителя, отображается после остановки рулетки
const isOverlayVisible = ref(false); // Флаг для отображения темного фона

// --- Constants --- //

const VISIBLE = 7; // Количество видимых карточек в карусели. Желательно нечетное число.
const centerSlot = Math.floor(VISIBLE / 2); // Индекс центрального слота (например, 2 для 5 видимых карточек)

// --- Timers --- //

let autoTimer = null; // ID таймера для автоматической прокрутки
let spinTimer = null; // ID таймера для анимации рулетки

// --- Image Path Helper --- //

// Формирует полный URL для изображения
const resolveImageUrl = (imagePath) => {
  if (!imagePath) return '/img/noimage.png';
  if (imagePath.includes('/')) return `http://localhost:8000${imagePath}`;
  return `/img/${imagePath}`;
};

// --- Computed Properties --- //

/**
 * @description Вычисляемое свойство для генерации видимых карточек.
 * Формирует массив из `VISIBLE` карточек, рассчитывая их динамические стили
 * (размытие, масштаб, прозрачность) в зависимости от их положения относительно центра.
 */
const visibleCards = computed(() => {
  const len = games.value.length;
  if (!len) return [];

  const cards = [];
  for (let slot = 0; slot < VISIBLE; slot++) {
    const offset = slot - centerSlot;
    const vIndex = centerIndex.value + offset;
    const realIndex = ((vIndex % len) + len) % len;
    const distance = Math.abs(offset);

    // Более выраженные значения
    const blurValue = distance * 1.2;
    const scaleValue = offset === 0 ? 1.15 : 1 - (distance * 0.18);
    const opacityValue = offset === 0 ? 1 : 1 - (distance * 0.18);

    cards.push({
      game: games.value[realIndex],
      realIndex,
      offset,
      isCenter: offset === 0,
      dynamicStyle: {
        filter: `blur(${blurValue}px)`,
        transform: `scale(${scaleValue})`,
        opacity: opacityValue,
        zIndex: offset === 0 ? 999 : VISIBLE - distance,
      },
    });
  }
  return cards;
});

// --- Carousel Logic --- //

/**
 * @description Запускает автоматическую прокрутку карусели с заданным интервалом.
 * Не запускается, если рулетка крутится или уже есть победитель.
 */
const startAutoScroll = () => {
  if (autoTimer || !games.value.length) return;
  // Интервал автоматической прокрутки в миллисекундах.
  autoTimer = setInterval(() => {
    if (isSpinning.value || winnerGame.value) return;
    centerIndex.value++;
  }, 2000); // <-- Можете изменить этот параметр (например, на 5000 для более медленной прокрутки)
};

/**
 * @description Останавливает автоматическую прокрутку.
 */
const stopAutoScroll = () => {
  if (autoTimer) {
    clearInterval(autoTimer);
    autoTimer = null;
  }
};

/**
 * @description Обрабатывает клик по боковой карточке, смещая ее в центр.
 */
const handleSideCardClick = (offset) => {
  if (isSpinning.value || winnerGame.value) return;
  stopAutoScroll();
  centerIndex.value += offset;
  startAutoScroll();
};

/**
 * @description Основная функция запуска рулетки.
 */
const startRoulette = () => {
  if (isSpinning.value || !games.value.length) return;

  stopAutoScroll();
  isSpinning.value = true;
  winnerGame.value = null;
  isOverlayVisible.value = false;

  const len = games.value.length;

  const extraRounds = 4;
  const randomOffset = Math.floor(Math.random() * len);

  const currentReal = ((centerIndex.value % len) + len) % len;
  const targetReal = randomOffset;
  const steps = extraRounds * len + ((targetReal - currentReal + len) % len);

  const totalDurationMs = 2000;          // общая длительность как ты и хочешь
  const tailDurationMs = 2000;          // хвост замедления ~2 секунды
  const fastPhaseDurationMs = totalDurationMs - tailDurationMs;

  let currentStep = 0;

  const spin = () => {
    centerIndex.value++;
    currentStep++;

    if (currentStep < steps) {
      const progress = currentStep / steps;

      // 1) базовое время шага так, чтобы вся анимация уместилась в totalDurationMs
      const baseStepMs = totalDurationMs / steps;

      // 2) разгон: первые 20% прогресса — быстрее
      let speedMultiplier;
      if (progress < 0.2) {
        // быстрое начало (меньше задержка -> быстрее крутится)
        speedMultiplier = 0.5;          // можешь сделать 0.3 для еще быстрее
      } else if (progress < 0.7) {
        // средняя фаза примерно равномерная
        speedMultiplier = 1;
      } else {
        // 3) хвост: замедляемся — увеличиваем задержку
        const tailProgress = (progress - 0.7) / 0.3; // от 0 до 1 на хвосте
        // easing на хвосте — плавное замедление
        const easeOut = 1 - Math.pow(1 - tailProgress, 3);
        speedMultiplier = 1 + easeOut * 5; // чем выше число, тем сильнее замедление
      }

      const delay = baseStepMs * speedMultiplier;

      spinTimer = setTimeout(spin, delay);
    } else {
      const winnerReal = ((centerIndex.value % len) + len) % len;
      const winningGameData = games.value[winnerReal];

      setTimeout(() => {
        winnerGame.value = winningGameData;
        setTimeout(() => {
          isOverlayVisible.value = true;
        }, 700);
      }, 300);
    }
  };

  spin();
};


/**
 * @description Сбрасывает состояние рулетки после показа победителя.
 * Вызывается по клику на фон или на кнопку "Продолжить".
 */
const resetRoulette = () => {
  if (!winnerGame.value) return;
  isOverlayVisible.value = false;
  
  // Задержка перед тем, как убрать победителя и возобновить авто-прокрутку.
  // Дает время для плавного исчезновения фона.
  setTimeout(() => {
    winnerGame.value = null;
    isSpinning.value = false;
    startAutoScroll();
  }, 400); // <-- Должно быть равно времени анимации .overlay
};


// --- Lifecycle Hooks --- //

const loadGamesAndScroll = async () => {
    await loadGames();
    startAutoScroll();
}

const loadGames = async () => {
  try {
    const { data } = await axios.get('/games');
    if (data && data.length) {
        // Проверяем, изменились ли данные, чтобы избежать ненужных перерисовок
        if (JSON.stringify(games.value) !== JSON.stringify(data)) {
            games.value = data;
        }
    }
  } catch (e) {
    console.error('Ошибка при загрузке игр:', e);
  }
};

onMounted(loadGamesAndScroll);
onUnmounted(() => {
  stopAutoScroll();
  if (spinTimer) clearTimeout(spinTimer);
});
onActivated(loadGamesAndScroll);
onDeactivated(stopAutoScroll);
</script>

<style scoped>
/* Плавное перемещение карточек при обычной прокрутке */
.carousel-move { transition: transform 1s cubic-bezier(0.55, 0, 0.1, 1); }

/* Анимация появления/исчезновения карточек на краях */
.carousel-enter-active, .carousel-leave-active { transition: all 0.7s cubic-bezier(0.55, 0, 0.1, 1); }
.carousel-enter-from, .carousel-leave-to { opacity: 0; transform: scale(0.5); }
.carousel-leave-active { position: absolute; }

/* Стили для ссылок-карточек */
.game-card-link {
  display: block;
  text-decoration: none;
  border-radius: 18px;
  transition:
    transform 0.35s cubic-bezier(0.25, 0.8, 0.25, 1),
    filter 0.35s cubic-bezier(0.25, 0.8, 0.25, 1),
    opacity 0.35s cubic-bezier(0.25, 0.8, 0.25, 1);
  will-change: transform, filter, opacity;
  cursor: pointer;
}
.game-card-link:not(.is-center):hover { transform: scale(0.9) !important; } /* Легкий эффект при наведении на боковые карты */
.game-card-link.is-center { cursor: default; } /* Отключаем курсор для центральной карты */

/* Основные контейнеры */
.carousel-wrapper { position: relative; width: 100%; overflow: hidden; }
.carousel-container { width: 100%; padding: 3rem 0; text-align: center; }
.carousel-title { font-size: 2.5rem; margin-bottom: 2.5rem; color: #fff; letter-spacing: 0.05em; }
.carousel-viewport { width: 100%; position: relative; min-height: 450px; }
.loading-placeholder { color: #9ca3af; font-size: 1.5rem; padding-top: 150px; }
.carousel-track { display: flex; justify-content: center; align-items: center; gap: 24px; }

/* Стили самой карточки */
.game-card {
  width: 260px;
  flex-shrink: 0;
  border-radius: 18px;
  background-color: #0f172a;
  transition: box-shadow 0.6s cubic-bezier(0.3, 0.7, 0.2, 1);
}

.game-card-link.is-center .game-card {
  box-shadow: 0 18px 40px rgba(0, 0, 0, 0.6);
}

.card-content { border-radius: 18px; overflow: hidden; }
.game-image { width: 100%; height: 340px; object-fit: cover; display: block; }
.game-info { padding: 1.2rem; text-align: left; }
.game-info h3 { font-size: 1.1rem; margin: 0 0 0.5rem 0; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.game-info p { font-size: 1.05rem; color: #4ade80; margin: 0; font-weight: 500; }

/* Оверлей и победитель */
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0);
  transition: background-color 0.8s ease-in-out;
  z-index: 10;
  pointer-events: none;
  cursor: pointer;
}
.overlay.active { background-color: rgba(0, 0, 0, 0.85); pointer-events: auto; }

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
}
.winner .card-content {
  border-radius: 18px;
  box-shadow: 0 0 25px 8px rgba(250, 204, 21, 0.8), 0 0 50px 20px rgba(251, 191, 36, 0.6);
}

/* Кнопки управления */
.carousel-controls {
  position: relative;
  z-index: 5;
  margin-top: 2.5rem;
  height: 60px; /* Резервируем высоту, чтобы избежать сдвига макета */
  display: flex;
  align-items: center;
  justify-content: center;
}

.roulette-button {
  padding: 1rem 2.8rem;
  font-size: 1.3rem;
  font-weight: 700;
  cursor: pointer;
  color: white;
  border: none;
  border-radius: 999px;
  transition: all 0.25s ease;
}
.roulette-button:not(.reset-button) {
  background: linear-gradient(135deg, #22c55e, #15803d);
  box-shadow: 0 8px 25px rgba(34, 197, 94, 0.5);
}
.roulette-button:disabled { background: #4b5563; cursor: not-allowed; box-shadow: none; transform: scale(1); }
.roulette-button:hover:not(:disabled) { transform: translateY(-3px) scale(1.03); }
.roulette-button:not(.reset-button):hover:not(:disabled) {
  box-shadow: 0 12px 35px rgba(34, 197, 94, 0.65);
}

.reset-button {
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
}
.reset-button:hover { 
  box-shadow: 0 12px 35px rgba(99, 102, 241, 0.55);
}
</style>
