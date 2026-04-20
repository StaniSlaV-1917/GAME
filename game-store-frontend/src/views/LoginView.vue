<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue';
import { useRouter, useRoute, RouterLink } from 'vue-router';
import { useHead } from '@vueuse/head';
import { useAuthStore } from '../stores/auth';
import { useToast } from '../composables/useToast';

useHead({
  title: 'Вход в аккаунт — GameStore',
  meta: [
    { name: 'description', content: 'Войдите в ваш аккаунт GameStore для доступа к покупкам и профилю.' },
    { property: 'og:title', content: 'Вход в аккаунт — GameStore' },
  ]
});

const router    = useRouter();
const route     = useRoute();
const authStore = useAuthStore();
const toast     = useToast();

// 'password', 'code' или 'forgot'
const authMode = ref('password');

const passwordForm = reactive({ login: '', password: '' });
const codeForm     = reactive({ email: '', code: '' });
const forgotForm   = reactive({ email: '', code: '', password: '', password_confirmation: '' });

const isLoading = ref(false);
const error     = ref('');
const step      = ref('email');   // для беспарольного и forgot: 'email' | 'code'
const wrapperEl = ref(null);

// Куда перейти после входа (поддержка ?redirect=)
function getRedirectTarget() {
  const q = route.query.redirect;
  return (q && typeof q === 'string' && q.startsWith('/')) ? q : { name: 'profile' };
}

// ─── Вход по паролю ───────────────────────────────────
const loginWithPassword = async () => {
  if (isLoading.value) return;
  error.value = '';

  if (!passwordForm.login.trim()) {
    error.value = 'Введите email или номер телефона.';
    return;
  }
  if (!passwordForm.password) {
    error.value = 'Введите пароль.';
    return;
  }

  isLoading.value = true;
  try {
    await authStore.login(passwordForm);
    toast.success('Вы успешно вошли в аккаунт!');
    await router.push(getRedirectTarget());
  } catch (err) {
    error.value = err.message || 'Неверный логин или пароль.';
    toast.error(error.value);
  } finally {
    isLoading.value = false;
  }
};

// ─── Беспарольный вход: отправка кода ─────────────────
const sendCode = async () => {
  if (isLoading.value) return;
  error.value = '';

  if (!codeForm.email.trim()) {
    error.value = 'Введите email.';
    return;
  }

  isLoading.value = true;
  try {
    await authStore.sendLoginCode(codeForm.email);
    step.value = 'code';
    toast.info('Код отправлен на ' + codeForm.email);
  } catch (err) {
    error.value = err.message || 'Не удалось отправить код. Проверьте email.';
    toast.error(error.value);
  } finally {
    isLoading.value = false;
  }
};

// ─── Беспарольный вход: проверка кода ─────────────────
const loginWithCode = async () => {
  if (isLoading.value) return;
  error.value = '';

  if (!codeForm.code.trim()) {
    error.value = 'Введите код из письма.';
    return;
  }

  isLoading.value = true;
  try {
    await authStore.loginWithCode(codeForm);
    toast.success('Вы успешно вошли в аккаунт!');
    await router.push(getRedirectTarget());
  } catch (err) {
    error.value = err.message || 'Неверный код. Попробуйте снова.';
    toast.error(error.value);
  } finally {
    isLoading.value = false;
  }
};

// ─── Сброс пароля: шаг 1 — отправка кода ─────────────
const sendResetCode = async () => {
  if (isLoading.value) return;
  error.value = '';

  if (!forgotForm.email.trim()) {
    error.value = 'Введите ваш email.';
    return;
  }

  isLoading.value = true;
  try {
    await authStore.sendPasswordResetCode(forgotForm.email);
    step.value = 'code';
    toast.info('Код сброса пароля отправлен на ' + forgotForm.email);
  } catch (err) {
    error.value = err.message || 'Не удалось отправить код. Проверьте email.';
    toast.error(error.value);
  } finally {
    isLoading.value = false;
  }
};

// ─── Сброс пароля: шаг 2 — подтверждение кода и новый пароль ─
const confirmResetPassword = async () => {
  if (isLoading.value) return;
  error.value = '';

  if (!forgotForm.code.trim()) {
    error.value = 'Введите код из письма.';
    return;
  }
  if (!forgotForm.password) {
    error.value = 'Введите новый пароль.';
    return;
  }
  if (forgotForm.password.length < 8) {
    error.value = 'Пароль должен содержать не менее 8 символов.';
    return;
  }
  if (forgotForm.password !== forgotForm.password_confirmation) {
    error.value = 'Пароли не совпадают.';
    return;
  }

  isLoading.value = true;
  try {
    await authStore.resetPassword({
      email:                 forgotForm.email,
      code:                  forgotForm.code,
      password:              forgotForm.password,
      password_confirmation: forgotForm.password_confirmation,
    });
    toast.success('Пароль успешно изменён! Теперь вы можете войти.');
    authMode.value = 'password';
    step.value = 'email';
    // clear form
    forgotForm.email = '';
    forgotForm.code = '';
    forgotForm.password = '';
    forgotForm.password_confirmation = '';
  } catch (err) {
    error.value = err.message || 'Не удалось сбросить пароль. Попробуйте снова.';
    toast.error(error.value);
  } finally {
    isLoading.value = false;
  }
};

// ─── UI helpers ───────────────────────────────────────
const handleFocus = (v) => wrapperEl.value?.classList.toggle('is-focused', v);

const initParticles = () => {
  if (window.particlesJS) {
    window.particlesJS('particles-js', {
      particles: {
        number: { value: 80, density: { enable: true, value_area: 800 } },
        color:   { value: '#ffffff' },
        shape:   { type: 'circle' },
        opacity: { value: 0.5, random: true, anim: { enable: true, speed: 0.4, opacity_min: 0.1 } },
        size:    { value: 2.5, random: true },
        move:    { enable: true, speed: 0.5, direction: 'none', random: true, out_mode: 'out' },
      },
      interactivity: {
        detect_on: 'canvas',
        events:    { onhover: { enable: true, mode: 'bubble' }, resize: true },
        modes:     { bubble: { distance: 250, size: 6, duration: 2, opacity: 0.8 } },
      },
      retina_detect: true,
    });
  }
};

onMounted(initParticles);
onUnmounted(() => {
  if (window.pJSDom?.[0]) {
    window.pJSDom[0].pJS.fn.vendors.destroypJS();
    window.pJSDom = [];
  }
});
</script>

<template>
  <div class="auth-page-wrapper" ref="wrapperEl">
    <div id="particles-js"></div>

    <div class="auth-card">
      <!-- Переключатель режимов -->
      <div class="mode-switcher">
        <button
          @click="authMode = 'password'; error = ''"
          :class="{ active: authMode === 'password' }"
        >По паролю</button>
        <button
          @click="authMode = 'code'; error = ''; step = 'email'"
          :class="{ active: authMode === 'code' }"
        >По коду</button>
        <button
          @click="authMode = 'forgot'; error = ''; step = 'email'"
          :class="{ active: authMode === 'forgot' }"
        >Забыл пароль</button>
      </div>

      <!-- ── Вход по паролю ── -->
      <div v-if="authMode === 'password'">
        <div class="card-header">
          <h1 class="title">Вход в аккаунт</h1>
          <p class="subtitle">Введите ваши данные для входа</p>
        </div>

        <Transition name="err-fade">
          <div v-if="error" class="error-banner">
            <svg class="err-icon" viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-5a.75.75 0 01.75.75v2.5a.75.75 0 01-1.5 0v-2.5A.75.75 0 0110 8z" clip-rule="evenodd"/>
            </svg>
            <p>{{ error }}</p>
          </div>
        </Transition>

        <form @submit.prevent="loginWithPassword" class="auth-form" novalidate>
          <div class="input-group">
            <label for="login">Email или телефон</label>
            <input
              id="login"
              v-model="passwordForm.login"
              placeholder="Ваш email или телефон"
              autocomplete="username"
              @focus="handleFocus(true)"
              @blur="handleFocus(false)"
            />
          </div>
          <div class="input-group">
            <label for="password">Пароль</label>
            <input
              id="password"
              v-model="passwordForm.password"
              type="password"
              placeholder="••••••••"
              autocomplete="current-password"
              @focus="handleFocus(true)"
              @blur="handleFocus(false)"
            />
          </div>
          <button type="submit" class="submit-button" :disabled="isLoading">
            <span v-if="isLoading" class="btn-spinner"></span>
            {{ isLoading ? 'Вход...' : 'Войти' }}
          </button>
        </form>
      </div>

      <!-- ── Беспарольный вход ── -->
      <div v-if="authMode === 'code'">
        <div class="card-header">
          <h1 class="title">Вход без пароля</h1>
          <p class="subtitle">
            {{ step === 'email'
              ? 'Введите ваш email для получения кода'
              : 'Мы отправили код на ' + codeForm.email }}
          </p>
        </div>

        <Transition name="err-fade">
          <div v-if="error" class="error-banner">
            <svg class="err-icon" viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-5a.75.75 0 01.75.75v2.5a.75.75 0 01-1.5 0v-2.5A.75.75 0 0110 8z" clip-rule="evenodd"/>
            </svg>
            <p>{{ error }}</p>
          </div>
        </Transition>

        <form v-if="step === 'email'" @submit.prevent="sendCode" class="auth-form" novalidate>
          <div class="input-group">
            <label for="email-code">Email</label>
            <input
              id="email-code"
              v-model="codeForm.email"
              type="email"
              placeholder="Ваш email"
              autocomplete="email"
              @focus="handleFocus(true)"
              @blur="handleFocus(false)"
            />
          </div>
          <button type="submit" class="submit-button" :disabled="isLoading">
            <span v-if="isLoading" class="btn-spinner"></span>
            {{ isLoading ? 'Отправка...' : 'Отправить код' }}
          </button>
        </form>

        <form v-if="step === 'code'" @submit.prevent="loginWithCode" class="auth-form" novalidate>
          <div class="input-group">
            <label for="code">Код из письма</label>
            <input
              id="code"
              v-model="codeForm.code"
              type="text"
              placeholder="••••••"
              inputmode="numeric"
              autocomplete="one-time-code"
              @focus="handleFocus(true)"
              @blur="handleFocus(false)"
            />
          </div>
          <button type="submit" class="submit-button" :disabled="isLoading">
            <span v-if="isLoading" class="btn-spinner"></span>
            {{ isLoading ? 'Проверка...' : 'Войти' }}
          </button>
          <button type="button" class="back-link" @click="step = 'email'; error = ''">
            ← Изменить email
          </button>
        </form>
      </div>

      <!-- ── Сброс пароля ── -->
      <div v-if="authMode === 'forgot'">
        <div class="card-header">
          <h1 class="title">Сброс пароля</h1>
          <p class="subtitle">
            {{ step === 'email'
              ? 'Введите ваш email для получения кода'
              : 'Введите код из письма и новый пароль' }}
          </p>
        </div>

        <Transition name="err-fade">
          <div v-if="error" class="error-banner">
            <svg class="err-icon" viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-5a.75.75 0 01.75.75v2.5a.75.75 0 01-1.5 0v-2.5A.75.75 0 0110 8z" clip-rule="evenodd"/>
            </svg>
            <p>{{ error }}</p>
          </div>
        </Transition>

        <!-- Шаг 1: email -->
        <form v-if="step === 'email'" @submit.prevent="sendResetCode" class="auth-form" novalidate>
          <div class="input-group">
            <label for="forgot-email">Email</label>
            <input
              id="forgot-email"
              v-model="forgotForm.email"
              type="email"
              placeholder="Ваш email"
              autocomplete="email"
              @focus="handleFocus(true)"
              @blur="handleFocus(false)"
            />
          </div>
          <button type="submit" class="submit-button" :disabled="isLoading">
            <span v-if="isLoading" class="btn-spinner"></span>
            {{ isLoading ? 'Отправка...' : 'Получить код' }}
          </button>
        </form>

        <!-- Шаг 2: код + новый пароль -->
        <form v-if="step === 'code'" @submit.prevent="confirmResetPassword" class="auth-form" novalidate>
          <div class="input-group">
            <label for="reset-code">Код из письма</label>
            <input
              id="reset-code"
              v-model="forgotForm.code"
              type="text"
              placeholder="••••••"
              inputmode="numeric"
              autocomplete="one-time-code"
              @focus="handleFocus(true)"
              @blur="handleFocus(false)"
            />
          </div>
          <div class="input-group">
            <label for="new-password">Новый пароль</label>
            <input
              id="new-password"
              v-model="forgotForm.password"
              type="password"
              placeholder="Минимум 8 символов"
              autocomplete="new-password"
              @focus="handleFocus(true)"
              @blur="handleFocus(false)"
            />
          </div>
          <div class="input-group">
            <label for="new-password-confirm">Повторите пароль</label>
            <input
              id="new-password-confirm"
              v-model="forgotForm.password_confirmation"
              type="password"
              placeholder="••••••••"
              autocomplete="new-password"
              @focus="handleFocus(true)"
              @blur="handleFocus(false)"
            />
          </div>
          <button type="submit" class="submit-button" :disabled="isLoading">
            <span v-if="isLoading" class="btn-spinner"></span>
            {{ isLoading ? 'Сохранение...' : 'Сохранить пароль' }}
          </button>
          <button type="button" class="back-link" @click="step = 'email'; error = ''">
            ← Изменить email
          </button>
        </form>
      </div>

      <div class="card-footer">
        <p>Ещё нет аккаунта? <RouterLink to="/register" class="link">Создать сейчас</RouterLink></p>
      </div>
    </div>
  </div>
</template>

<style scoped>
#particles-js {
  position: absolute;
  inset: 0;
  z-index: 2;
  pointer-events: none;
}

.auth-page-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: calc(100vh - 150px);
  padding: 40px 20px;
  position: relative;
  overflow: hidden;
  background: #030712 radial-gradient(ellipse at center, rgba(76, 29, 149, 0.4) 0%, transparent 70%);
}
.auth-page-wrapper.is-focused {
  --bg-size: 400px;
}

.auth-card {
  position: relative;
  z-index: 3;
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

/* Mode switcher */
.mode-switcher {
  display: flex;
  background-color: rgba(31, 41, 55, 0.7);
  border-radius: 10px;
  padding: 4px;
  margin-bottom: 25px;
  gap: 4px;
}
.mode-switcher button {
  flex: 1;
  padding: 10px 15px;
  border: none;
  background: transparent;
  color: #9ca3af;
  font-size: 0.88rem;
  font-weight: 600;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.25s ease;
}
.mode-switcher button.active {
  background-color: #3b82f6;
  color: #fff;
  box-shadow: 0 4px 10px rgba(59, 130, 246, 0.25);
}
.mode-switcher button:not(.active):hover {
  background: rgba(255, 255, 255, 0.06);
  color: #e5e7eb;
}

/* Header */
.card-header { text-align: center; margin-bottom: 25px; }
.title { font-size: 2rem; font-weight: 700; color: #fff; margin: 0 0 10px; }
.subtitle { font-size: 1rem; color: #9ca3af; margin: 0; }

/* Error banner */
.error-banner {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.4);
  color: #fca5a5;
  border-radius: 8px;
  margin-bottom: 20px;
  padding: 11px 14px;
  font-size: 0.875rem;
}
.err-icon { flex-shrink: 0; margin-top: 1px; }
.error-banner p { margin: 0; line-height: 1.4; }

/* Form */
.auth-form { display: flex; flex-direction: column; gap: 20px; }

.input-group { display: flex; flex-direction: column; }
.input-group label {
  margin-bottom: 8px;
  font-weight: 500;
  font-size: 0.875rem;
  color: #d1d5db;
}
.input-group input {
  padding: 12px 15px;
  border-radius: 8px;
  border: 1px solid #374151;
  background: rgba(31, 41, 55, 0.5);
  color: #e5e7eb;
  font-size: 1rem;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.input-group input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}
.input-group input::placeholder { color: #6b7280; }

/* Submit button */
.submit-button {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 10px;
  padding: 14px 0;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  background: linear-gradient(90deg, #3b82f6, #6366f1);
  color: #fff;
  font-weight: 600;
  font-size: 1rem;
  box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
  transition: all 0.2s ease;
}
.submit-button:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 12px 28px rgba(99, 102, 241, 0.4);
}
.submit-button:disabled {
  background: #4b5563;
  cursor: not-allowed;
  box-shadow: none;
}

/* Spinner */
.btn-spinner {
  width: 15px;
  height: 15px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
  flex-shrink: 0;
}
@keyframes spin { to { transform: rotate(360deg); } }

.back-link {
  background: none;
  border: none;
  color: #9ca3af;
  font-size: 0.875rem;
  cursor: pointer;
  padding: 4px 0;
  text-align: center;
  margin-top: -8px;
  transition: color 0.15s;
}
.back-link:hover { color: #e5e7eb; }

/* Footer */
.card-footer { margin-top: 25px; text-align: center; font-size: 0.9rem; color: #9ca3af; }
.link { color: #3b82f6; font-weight: 500; text-decoration: none; }
.link:hover { color: #60a5fa; text-decoration: underline; }

/* Error transition */
.err-fade-enter-active, .err-fade-leave-active { transition: all 0.22s ease; }
.err-fade-enter-from, .err-fade-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
