<script setup>
import { useRouter } from 'vue-router';

const router = useRouter();

const goHome = () => {
  router.push('/'); // Переход на главную страницу
};
</script>

<template>
  <div class="black-hole-container">
    <div class="black-hole-visuals">
      <div class="black-hole"></div>
      <div class="pulsing-glow"></div>
    </div>
    
    <div class="floating-content">
      <h1 class="text-404">404</h1>

      <button @click="goHome" class="home-button">
        <span class="button-text">Вернуться на главную</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.black-hole-container {
  /* Этот компонент займет весь экран */
  position: fixed;
  inset: 0;
  background: #000;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000; /* Убедимся, что он поверх всего */
}

.black-hole-visuals {
  position: absolute;
  inset: 0;
}

.black-hole {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 150vmax;
  height: 150vmax;
  background: radial-gradient(circle at center, #4c1d95 0%, #2e1065 15%, #000 30%);
  border-radius: 50%;
  transform: translate(-50%, -50%);
  animation:
    spin 40s linear infinite,
    suck 12s ease-in-out infinite alternate;
}

.pulsing-glow {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 160vmax;
  height: 160vmax;
  background: radial-gradient(circle at center, transparent 30%, #5b21b6 31%, transparent 33%);
  border-radius: 50%;
  transform: translate(-50%, -50%);
  animation:
    spin 50s linear infinite reverse,
    pulse 3s ease-in-out infinite;
}

.floating-content {
  position: relative;
  z-index: 10;
  width: 100%;
  height: 100%;
}

.text-404 {
  position: absolute;
  margin: 0;
  font-size: clamp(10rem, 20vw, 25rem); /* Адаптивный размер шрифта */
  color: #fff;
  font-weight: 900;
  text-shadow:
    0 0 15px rgba(255, 255, 255, 0.8),
    0 0 30px rgba(167, 139, 250, 0.7),
    0 0 50px rgba(139, 92, 246, 0.6);
  animation: move-and-spin 20s linear infinite;
  will-change: top, left, transform;
}

.home-button {
  position: absolute;
  bottom: 10vh;
  left: 50%;
  background: transparent;
  border: 2px solid rgba(255, 255, 255, 0.8);
  color: #fff;
  padding: 1rem 2rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  border-radius: 50px;
  text-transform: uppercase;
  letter-spacing: 2px;
  z-index: 20;
  transition: all 0.3s ease;
  animation: button-rotate 25s linear infinite;
}

.home-button:hover {
  transform: translateX(-50%) scale(1.1);
  border-color: #a78bfa;
  color: #a78bfa;
  box-shadow: 0 0 30px #a78bfa;
  animation-play-state: paused;
}

/* Анимации */

@keyframes spin {
  from { transform: translate(-50%, -50%) rotate(0deg); }
  to { transform: translate(-50%, -50%) rotate(360deg); }
}

@keyframes suck {
  from { opacity: 0.8; }
  to { opacity: 1; }
}

@keyframes pulse {
  0% { opacity: 0.7; transform: translate(-50%, -50%) scale(1); }
  50% { opacity: 1; transform: translate(-50%, -50%) scale(1.02); }
  100% { opacity: 0.7; transform: translate(-50%, -50%) scale(1); }
}

@keyframes move-and-spin {
  0% {
    top: 5%;
    left: 5%;
    transform: rotate(0deg);
  }
  25% {
    top: 5%;
    left: calc(100% - 25vw - 5%); /* 25vw - примерная ширина текста */
    transform: rotate(90deg);
  }
  50% {
    top: calc(100% - clamp(10rem, 20vw, 25rem) - 5%);
    left: calc(100% - 25vw - 5%);
    transform: rotate(180deg);
  }
  75% {
    top: calc(100% - clamp(10rem, 20vw, 25rem) - 5%);
    left: 5%;
    transform: rotate(270deg);
  }
  100% {
    top: 5%;
    left: 5%;
    transform: rotate(360deg);
  }
}

@keyframes button-rotate {
  from { transform: translateX(-50%) rotate(0deg); }
  to { transform: translateX(-50%) rotate(360deg); }
}

</style>