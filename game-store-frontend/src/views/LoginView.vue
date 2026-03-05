<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const form = ref({ login: '', password: '' });
const isLoading = ref(false);
const error = ref('');
const wrapperEl = ref(null);

// --- Логика для управления CSS-классами при фокусе и отправке ---
const handleFocus = (isFocused) => {
  if (!wrapperEl.value) return;
  wrapperEl.value.classList.toggle('is-focused', isFocused);
};

const submit = async () => {
  if (isLoading.value) return;
  isLoading.value = true;
  error.value = '';

  if (wrapperEl.value) {
    wrapperEl.value.classList.add('is-submitting');
    setTimeout(() => wrapperEl.value?.classList.remove('is-submitting'), 500);
  }

  try {
    await authStore.login(form.value);
    router.push({ name: 'profile' });
  } catch (e) {
    error.value = 'Неверный логин или пароль. Попробуйте снова.';
    console.error(e);
  } finally {
    isLoading.value = false;
  }
};

// --- Инициализация particles.js с новым эффектом "пузыря" ---
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
  initParticles();
});

onUnmounted(() => {
  // Уничтожаем инстанс particles.js при уходе со страницы
  if (window.pJSDom && window.pJSDom[0]) {
      window.pJSDom[0].pJS.fn.vendors.destroypJS();
  }
});
</script>

<template>
  <div class="auth-page-wrapper" ref="wrapperEl">
    <div id="particles-js"></div>

    <div class="auth-card">
      <div class="card-header">
        <h1 class="title">С возвращением!</h1>
        <p class="subtitle">Войдите в свой аккаунт, чтобы продолжить</p>
      </div>
      <div v-if="error" class="error-banner"><p>{{ error }}</p></div>
      <form @submit.prevent="submit" class="auth-form">
        <div class="input-group">
          <label for="login">Логин</label>
          <input id="login" v-model="form.login" placeholder="Ваш email или телефон" required type="text" @focus="handleFocus(true)" @blur="handleFocus(false)" />
        </div>
        <div class="input-group">
          <label for="password">Пароль</label>
          <input id="password" v-model="form.password" type="password" placeholder="••••••••" required @focus="handleFocus(true)" @blur="handleFocus(false)" />
        </div>
        <button type="submit" class="submit-button" :disabled="isLoading">{{ isLoading ? 'Проверка...' : 'Войти' }}</button>
      </form>
      <div class="card-footer"><p>Еще нет аккаунта? <RouterLink to="/register" class="link">Создать сейчас</RouterLink></p></div>
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
  z-index: 2; /* Частицы над градиентом, под карточкой */
}

.auth-page-wrapper {
  --bg-size: 300px;
  --bg-blur: 80px;
  --bg-color-1: #4c1d95;
  --bg-color-2: #be185d;

  display: flex;
  justify-content: center;
  align-items: center;
  min-height: calc(100vh - 150px);
  padding: 40px 20px;
  position: relative;
  overflow: hidden;
  /* Статичный фон */
  background: #030712 radial-gradient(ellipse at center, rgba(76, 29, 149, 0.4) 0%, transparent 70%);
}

/* Старый эффект свечения удален */

.auth-page-wrapper.is-focused {
  --bg-size: 400px;
}

.auth-page-wrapper.is-submitting {
  --bg-size: max(120vw, 120vh);
  --bg-blur: 100px;
  --bg-color-1: #7e22ce;
  --bg-color-2: #3b82f6;
}

.auth-card {
  position: relative;
  z-index: 3; /* Карточка над всем */
  width: 100%;
  max-width: 420px;
  padding: 30px 35px;
  background: rgba(17, 24, 39, 0.7);
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
}

.card-header { text-align: center; margin-bottom: 25px; }
.title { font-size: 2rem; font-weight: 700; color: #fff; margin: 0 0 10px; }
.subtitle { font-size: 1rem; color: #9ca3af; margin: 0; }

.error-banner { background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.5); color: #fca5a5; border-radius: 8px; margin-bottom: 20px; padding: 12px 16px; text-align: center; font-size: 0.9em; }
.auth-form { display: flex; flex-direction: column; gap: 20px; }
.input-group { display: flex; flex-direction: column; }
.input-group label { margin-bottom: 8px; font-weight: 500; font-size: 0.9rem; color: #d1d5db; }
.input-group input { padding: 12px 15px; border-radius: 8px; border: 1px solid #374151; background: rgba(31, 41, 55, 0.5); color: #e5e7eb; font-size: 1rem; outline: none; transition: border-color 0.2s, box-shadow 0.2s; }
.input-group input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
.submit-button { margin-top: 10px; padding: 14px 0; border-radius: 8px; border: none; cursor: pointer; background: linear-gradient(90deg, #3b82f6, #6366f1); color: #fff; font-weight: 600; font-size: 1rem; box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3); transition: all 0.2s ease; }
.submit-button:hover:not(:disabled) { transform: translateY(-2px); }
.submit-button:disabled { background: #4b5563; cursor: not-allowed; }
.card-footer { margin-top: 25px; text-align: center; font-size: 0.9rem; color: #9ca3af; }
.link { color: #3b82f6; font-weight: 500; text-decoration: none; }
.link:hover { text-decoration: underline; }

</style>
