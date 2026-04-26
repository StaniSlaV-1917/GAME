<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useHead } from '@vueuse/head';

useHead({
  title: '404 — Страница не найдена | GameStore',
  meta: [
    { name: 'description', content: 'Запрошенная страница не найдена. Вернитесь на главную страницу GameStore.' },
    { name: 'robots', content: 'noindex, follow' },
  ],
});

const router = useRouter();

const goHome = () => {
  router.push('/');
};

// --- Инициализация particles.js ---
const initParticles = () => {
  if (window.particlesJS) {
    window.particlesJS('particles-js', {
      "particles": {
        "number": { "value": 80, "density": { "enable": true, "value_area": 800 } },
        "color": { "value": "#ffffff" },
        "shape": { "type": "circle" },
        "opacity": { "value": 0.5, "random": true, "anim": { "enable": true, "speed": 0.4, "opacity_min": 0.1, "sync": false } },
        "size": { "value": 2.5, "random": true },
        "move": { "enable": true, "speed": 0.5, "direction": "none", "random": true, "straight": false, "out_mode": "out" }
      },
      "interactivity": {
        "detect_on": "canvas",
        "events": { "onhover": { "enable": true, "mode": "bubble" }, "resize": true },
        "modes": {
          "bubble": { "distance": 250, "size": 6, "duration": 2, "opacity": 0.8 }
        }
      },
      "retina_detect": true
    });
  }
}

onMounted(() => {
  // Даем DOM время на отрисовку перед инициализацией
  setTimeout(initParticles, 100);
});

onUnmounted(() => {
  if (window.pJSDom && window.pJSDom[0]) {
      window.pJSDom[0].pJS.fn.vendors.destroypJS();
      window.pJSDom = [];
  }
});
</script>

<template>
  <div class="not-found-container">
    <div id="particles-js"></div>
    
    <div class="content-wrapper">
      <h1 class="text-404">404</h1>
      <p class="message">Кажется, вы попали в неизведанный сектор космоса.</p>
      <button @click="goHome" class="home-button">
        <span>Вернуться на базу</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
#particles-js {
  position: absolute;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  z-index: 1;
}

.not-found-container {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  /* Используем похожий фон, как на странице логина */
  background: #030712 radial-gradient(ellipse at center, rgba(76, 29, 149, 0.4) 0%, transparent 70%);
  overflow: hidden;
  z-index: 2000;
}

.content-wrapper {
  position: relative;
  z-index: 2;
  animation: fade-in 1.5s ease-out;
}

.text-404 {
  font-size: clamp(10rem, 25vw, 18rem); /* Адаптивный размер */
  color: #fff;
  font-weight: 800;
  margin: 0;
  line-height: 1;
  text-shadow:
    0 0 10px rgba(255, 255, 255, 0.6),
    0 0 25px rgba(167, 139, 250, 0.5),
    0 0 50px rgba(139, 92, 246, 0.4);
  animation: gentle-float 6s ease-in-out infinite;
}

.message {
  font-size: 1.2rem;
  color: #9ca3af;
  margin: -1rem 0 2.5rem;
  text-shadow: 0 2px 5px rgba(0,0,0,0.5);
}

.home-button {
  background: transparent;
  border: 2px solid rgba(255, 255, 255, 0.7);
  color: #fff;
  padding: 1rem 2rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  border-radius: 50px;
  text-transform: uppercase;
  letter-spacing: 1.5px;
  transition: all 0.3s ease;
}

.home-button:hover {
  transform: scale(1.05);
  border-color: #a78bfa;
  color: #a78bfa;
  box-shadow: 0 0 25px rgba(167, 139, 250, 0.5);
}

/* Анимации */
@keyframes fade-in {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes gentle-float {
  0% { transform: translateY(0px); }
  50% { transform: translateY(-15px); }
  100% { transform: translateY(0px); }
}
</style>