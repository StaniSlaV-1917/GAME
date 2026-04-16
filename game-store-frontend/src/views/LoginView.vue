<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { useHead } from '@vueuse/head';
import { useAuthStore } from '../stores/auth';

// SEO 
useHead({
  title: 'Вход в аккаунт - GameStore',
  meta: [
    { name: 'description', content: 'Войдите в ваш аккаунт GameStore, чтобы получить доступ к покупкам, избранному и персональным предложениям. Быстрый и безопасный вход.' },
    { property: 'og:title', content: 'Вход в аккаунт - GameStore' },
    { property: 'og:description', content: 'Войдите в ваш аккаунт GameStore, чтобы получить доступ к покупкам, избранному и персональным предложениям. Быстрый и безопасный вход.' },
    { property: 'og:image', content: 'https://gamestore.zyro.com/images/logo.svg' },
    { property: 'og:url', content: window.location.href }
  ]
});

const router = useRouter();
const authStore = useAuthStore();

// 'password' или 'code'
const authMode = ref('password'); 

const passwordForm = reactive({ login: '', password: '' });
const codeForm = reactive({ email: '', code: '' });

const isLoading = ref(false);
const error = ref('');
const step = ref('email'); // для беспарольного входа: 'email' или 'code'
const wrapperEl = ref(null); // для CSS эффектов

// --- Логика для входа по паролю ---
const loginWithPassword = async () => {
  if (isLoading.value) return;
  isLoading.value = true;
  error.value = '';

  try {
    await authStore.login(passwordForm);
    router.push({ name: 'profile' });
  } catch (err) {
    error.value = err.message || 'Неверный логин или пароль.';
    console.error(err);
  } finally {
    isLoading.value = false;
  }
};

// --- Логика для входа по коду ---
const sendCode = async () => {
  if (isLoading.value) return;
  isLoading.value = true;
  error.value = '';

  try {
    await authStore.sendLoginCode(codeForm.email);
    step.value = 'code';
  } catch (err) {
    error.value = 'Не удалось отправить код. Проверьте email и попробуйте снова.';
    console.error(err);
  } finally {
    isLoading.value = false;
  }
};

const loginWithCode = async () => {
  if (isLoading.value) return;
  isLoading.value = true;
  error.value = '';

  try {
    await authStore.loginWithCode(codeForm);
    router.push({ name: 'profile' });
  } catch (err) {
    error.value = err.message || 'Неверный код. Попробуйте снова.';
    console.error(err);
  } finally {
    isLoading.value = false;
  }
};

// --- Управление UI и анимациями ---
const handleFocus = (isFocused) => {
  if (!wrapperEl.value) return;
  wrapperEl.value.classList.toggle('is-focused', isFocused);
};

const initParticles = () => {
  if (window.particlesJS) {
    window.particlesJS('particles-js', {
      "particles": { "number": { "value": 80, "density": { "enable": true, "value_area": 800 } }, "color": { "value": "#ffffff" }, "shape": { "type": "circle" }, "opacity": { "value": 0.5, "random": true, "anim": { "enable": true, "speed": 0.4, "opacity_min": 0.1, "sync": false } }, "size": { "value": 2.5, "random": true }, "move": { "enable": true, "speed": 0.5, "direction": "none", "random": true, "straight": false, "out_mode": "out" } },
      "interactivity": { "detect_on": "canvas", "events": { "onhover": { "enable": true, "mode": "bubble" }, "resize": true }, "modes": { "bubble": { "distance": 250, "size": 6, "duration": 2, "opacity": 0.8 } } },
      "retina_detect": true
    });
  }
}

onMounted(initParticles);
onUnmounted(() => {
  if (window.pJSDom && window.pJSDom[0]) {
    window.pJSDom[0].pJS.fn.vendors.destroypJS();
  }
});

</script>

<template>
  <div class="auth-page-wrapper" ref="wrapperEl">
    <div id="particles-js"></div>

    <div class="auth-card">
      <!-- Переключатель режимов -->
      <div class="mode-switcher">
        <button @click="authMode = 'password'" :class="{ active: authMode === 'password' }">По паролю</button>
        <button @click="authMode = 'code'" :class="{ active: authMode === 'code' }">По коду из Email</button>
      </div>

      <!-- Форма входа по паролю -->
      <div v-if="authMode === 'password'">
        <div class="card-header">
          <h1 class="title">Вход в аккаунт</h1>
          <p class="subtitle">Введите ваши данные для входа</p>
        </div>
        <div v-if="error" class="error-banner"><p>{{ error }}</p></div>
        <form @submit.prevent="loginWithPassword" class="auth-form">
          <div class="input-group">
            <label for="login">Email или телефон</label>
            <input id="login" v-model="passwordForm.login" placeholder="Ваш email или телефон" required @focus="handleFocus(true)" @blur="handleFocus(false)" />
          </div>
          <div class="input-group">
            <label for="password">Пароль</label>
            <input id="password" v-model="passwordForm.password" type="password" placeholder="••••••••" required @focus="handleFocus(true)" @blur="handleFocus(false)" />
          </div>
          <button type="submit" class="submit-button" :disabled="isLoading">{{ isLoading ? 'Вход...' : 'Войти' }}</button>
        </form>
      </div>

      <!-- Форма входа по коду -->
      <div v-if="authMode === 'code'">
        <div class="card-header">
          <h1 class="title">Вход без пароля</h1>
          <p class="subtitle">{{ step === 'email' ? 'Введите ваш email для получения кода' : 'Мы отправили код на ' + codeForm.email }}</p>
        </div>
        <div v-if="error" class="error-banner"><p>{{ error }}</p></div>
        <form v-if="step === 'email'" @submit.prevent="sendCode" class="auth-form">
          <div class="input-group">
            <label for="email-code">Email</label>
            <input id="email-code" v-model="codeForm.email" placeholder="Ваш email" required type="email" @focus="handleFocus(true)" @blur="handleFocus(false)" />
          </div>
          <button type="submit" class="submit-button" :disabled="isLoading">{{ isLoading ? 'Отправка...' : 'Отправить код' }}</button>
        </form>
        <form v-if="step === 'code'" @submit.prevent="loginWithCode" class="auth-form">
          <div class="input-group">
            <label for="code">Код</label>
            <input id="code" v-model="codeForm.code" type="text" placeholder="••••••" required @focus="handleFocus(true)" @blur="handleFocus(false)" />
          </div>
          <button type="submit" class="submit-button" :disabled="isLoading">{{ isLoading ? 'Проверка...' : 'Войти' }}</button>
        </form>
      </div>

      <div class="card-footer"><p>Еще нет аккаунта? <RouterLink to="/register" class="link">Создать сейчас</RouterLink></p></div>
    </div>
  </div>
</template>

<style scoped>
/* ... ваши старые стили остаются здесь ... */

/* Стили для переключателя */
.mode-switcher {
  display: flex;
  background-color: rgba(31, 41, 55, 0.7);
  border-radius: 10px;
  padding: 4px;
  margin-bottom: 25px;
}

.mode-switcher button {
  flex: 1;
  padding: 10px 15px;
  border: none;
  background-color: transparent;
  color: #9ca3af;
  font-size: 0.9rem;
  font-weight: 600;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.mode-switcher button.active {
  background-color: #3b82f6;
  color: #fff;
  box-shadow: 0 4px 10px rgba(59, 130, 246, 0.2);
}

/* Остальные стили без изменений */
#particles-js {
  position: absolute;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  z-index: 2;
}

.auth-page-wrapper { --bg-size: 300px; --bg-blur: 80px; --bg-color-1: #4c1d95; --bg-color-2: #be185d; display: flex; justify-content: center; align-items: center; min-height: calc(100vh - 150px); padding: 40px 20px; position: relative; overflow: hidden; background: #030712 radial-gradient(ellipse at center, rgba(76, 29, 149, 0.4) 0%, transparent 70%); }
.auth-page-wrapper.is-focused { --bg-size: 400px; }
.auth-page-wrapper.is-submitting { --bg-size: max(120vw, 120vh); --bg-blur: 100px; --bg-color-1: #7e22ce; --bg-color-2: #3b82f6; }

.auth-card { position: relative; z-index: 3; width: 100%; max-width: 420px; padding: 30px 35px; background: rgba(17, 24, 39, 0.7); border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.1); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5); }

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