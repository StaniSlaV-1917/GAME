<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter, useRoute, RouterLink } from 'vue-router';
import { useHead } from '@vueuse/head';
import { useAuthStore } from '../stores/auth';
import { useToast } from '../composables/useToast';
import { warmupPing } from '../utils/warmup';

// Будим Fly-машину перед auth-запросом
onMounted(() => warmupPing());

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

const loginWithPassword = async () => {
  if (isLoading.value) return;
  error.value = '';
  if (!passwordForm.login.trim()) { error.value = 'Введите email или номер телефона.'; return; }
  if (!passwordForm.password)     { error.value = 'Введите пароль.'; return; }

  isLoading.value = true;
  try {
    await authStore.login(passwordForm);
    toast.success('Врата открыты. Добро пожаловать в оплот.');
    await router.push(getRedirectTarget());
  } catch (err) {
    error.value = err.message || 'Неверный логин или пароль.';
    toast.error(error.value);
  } finally {
    isLoading.value = false;
  }
};

const sendCode = async () => {
  if (isLoading.value) return;
  error.value = '';
  if (!codeForm.email.trim()) { error.value = 'Введите email.'; return; }

  isLoading.value = true;
  try {
    await authStore.sendLoginCode(codeForm.email);
    step.value = 'code';
    toast.info('Знак отправлен на ' + codeForm.email);
  } catch (err) {
    error.value = err.message || 'Не удалось отправить код. Проверьте email.';
    toast.error(error.value);
  } finally {
    isLoading.value = false;
  }
};

const loginWithCode = async () => {
  if (isLoading.value) return;
  error.value = '';
  if (!codeForm.code.trim()) { error.value = 'Введите код из письма.'; return; }

  isLoading.value = true;
  try {
    await authStore.loginWithCode(codeForm);
    toast.success('Знак подтверждён. Врата открыты.');
    await router.push(getRedirectTarget());
  } catch (err) {
    error.value = err.message || 'Неверный код. Попробуйте снова.';
    toast.error(error.value);
  } finally {
    isLoading.value = false;
  }
};

const sendResetCode = async () => {
  if (isLoading.value) return;
  error.value = '';
  if (!forgotForm.email.trim()) { error.value = 'Введите ваш email.'; return; }

  isLoading.value = true;
  try {
    await authStore.sendPasswordResetCode(forgotForm.email);
    step.value = 'code';
    toast.info('Ключ сброса отправлен на ' + forgotForm.email);
  } catch (err) {
    error.value = err.message || 'Не удалось отправить код. Проверьте email.';
    toast.error(error.value);
  } finally {
    isLoading.value = false;
  }
};

const confirmResetPassword = async () => {
  if (isLoading.value) return;
  error.value = '';

  if (!forgotForm.code.trim())       { error.value = 'Введите код из письма.'; return; }
  if (!forgotForm.password)          { error.value = 'Введите новый пароль.'; return; }
  if (forgotForm.password.length < 8) {
    error.value = 'Пароль должен содержать не менее 8 символов.'; return;
  }
  if (forgotForm.password !== forgotForm.password_confirmation) {
    error.value = 'Пароли не совпадают.'; return;
  }

  isLoading.value = true;
  try {
    await authStore.resetPassword({
      email:                 forgotForm.email,
      code:                  forgotForm.code,
      password:              forgotForm.password,
      password_confirmation: forgotForm.password_confirmation,
    });
    toast.success('Ключ перекован. Теперь вы можете войти.');
    authMode.value = 'password';
    step.value = 'email';
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

const handleFocus = (v) => wrapperEl.value?.classList.toggle('is-focused', v);
</script>

<template>
  <div class="forge-auth">
    <!-- фоновое свечение горна + каменная сетка -->
    <div class="fa-bg" aria-hidden="true">
      <div class="fa-glow fa-glow-1"></div>
      <div class="fa-glow fa-glow-2"></div>
      <div class="fa-grid"></div>
    </div>

    <!-- центральная каменная плита -->
    <div class="fa-slab" ref="wrapperEl">
      <span class="slab-rivet slab-rivet--tl" aria-hidden="true"></span>
      <span class="slab-rivet slab-rivet--tr" aria-hidden="true"></span>
      <span class="slab-rivet slab-rivet--bl" aria-hidden="true"></span>
      <span class="slab-rivet slab-rivet--br" aria-hidden="true"></span>
      <div class="slab-iron-top" aria-hidden="true"></div>
      <div class="slab-iron-bottom" aria-hidden="true"></div>

      <div class="slab-inner">
        <!-- переключатель режимов -->
        <div class="mode-switcher" role="tablist">
          <button
            role="tab"
            :aria-selected="authMode === 'password'"
            @click="authMode = 'password'; error = ''"
            :class="{ active: authMode === 'password' }"
          >По ключу</button>
          <button
            role="tab"
            :aria-selected="authMode === 'code'"
            @click="authMode = 'code'; error = ''; step = 'email'"
            :class="{ active: authMode === 'code' }"
          >По знаку</button>
          <button
            role="tab"
            :aria-selected="authMode === 'forgot'"
            @click="authMode = 'forgot'; error = ''; step = 'email'"
            :class="{ active: authMode === 'forgot' }"
          >Забыт ключ</button>
        </div>

        <!-- ── по паролю ── -->
        <div v-if="authMode === 'password'">
          <div class="slab-head">
            <span class="tribal-eyebrow">
              <span class="eyebrow-spike"></span>
              Врата оплота
              <span class="eyebrow-spike"></span>
            </span>
            <h1 class="slab-title">Вход в аккаунт</h1>
            <p class="slab-sub">Назовите имя и ключ, чтобы войти внутрь</p>
          </div>

          <Transition name="err-fade">
            <div v-if="error" class="err-banner" role="alert">
              <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-5a.75.75 0 01.75.75v2.5a.75.75 0 01-1.5 0v-2.5A.75.75 0 0110 8z" clip-rule="evenodd"/>
              </svg>
              <p>{{ error }}</p>
            </div>
          </Transition>

          <form @submit.prevent="loginWithPassword" class="forge-form" novalidate>
            <div class="field">
              <label for="login">Email или телефон</label>
              <input
                id="login"
                v-model="passwordForm.login"
                placeholder="Ваш email или телефон"
                autocomplete="username"
                @focus="handleFocus(true)" @blur="handleFocus(false)"
              />
            </div>
            <div class="field">
              <label for="password">Ключ-пароль</label>
              <input
                id="password"
                v-model="passwordForm.password"
                type="password"
                placeholder="••••••••"
                autocomplete="current-password"
                @focus="handleFocus(true)" @blur="handleFocus(false)"
              />
            </div>
            <button type="submit" class="forge-btn" :disabled="isLoading">
              <span v-if="isLoading" class="btn-spinner" aria-hidden="true"></span>
              <span class="forge-btn-label">{{ isLoading ? 'Куём…' : 'Войти в оплот' }}</span>
            </button>
          </form>
        </div>

        <!-- ── по коду ── -->
        <div v-if="authMode === 'code'">
          <div class="slab-head">
            <span class="tribal-eyebrow">
              <span class="eyebrow-spike"></span>
              Знак-символ
              <span class="eyebrow-spike"></span>
            </span>
            <h1 class="slab-title">Вход без пароля</h1>
            <p class="slab-sub">
              {{ step === 'email'
                ? 'Назовите email — пошлём вам огненный знак'
                : 'Знак отправлен на ' + codeForm.email }}
            </p>
          </div>

          <Transition name="err-fade">
            <div v-if="error" class="err-banner" role="alert">
              <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-5a.75.75 0 01.75.75v2.5a.75.75 0 01-1.5 0v-2.5A.75.75 0 0110 8z" clip-rule="evenodd"/>
              </svg>
              <p>{{ error }}</p>
            </div>
          </Transition>

          <form v-if="step === 'email'" @submit.prevent="sendCode" class="forge-form" novalidate>
            <div class="field">
              <label for="email-code">Email</label>
              <input
                id="email-code"
                v-model="codeForm.email"
                type="email"
                placeholder="Ваш email"
                autocomplete="email"
                @focus="handleFocus(true)" @blur="handleFocus(false)"
              />
            </div>
            <button type="submit" class="forge-btn" :disabled="isLoading">
              <span v-if="isLoading" class="btn-spinner" aria-hidden="true"></span>
              <span class="forge-btn-label">{{ isLoading ? 'Отправка…' : 'Прислать знак' }}</span>
            </button>
          </form>

          <form v-if="step === 'code'" @submit.prevent="loginWithCode" class="forge-form" novalidate>
            <div class="field">
              <label for="code">Знак из письма</label>
              <input
                id="code"
                v-model="codeForm.code"
                type="text"
                placeholder="••••••"
                inputmode="numeric"
                autocomplete="one-time-code"
                @focus="handleFocus(true)" @blur="handleFocus(false)"
              />
            </div>
            <button type="submit" class="forge-btn" :disabled="isLoading">
              <span v-if="isLoading" class="btn-spinner" aria-hidden="true"></span>
              <span class="forge-btn-label">{{ isLoading ? 'Проверка…' : 'Войти' }}</span>
            </button>
            <button type="button" class="link-back" @click="step = 'email'; error = ''">
              ← Поменять email
            </button>
          </form>
        </div>

        <!-- ── сброс ── -->
        <div v-if="authMode === 'forgot'">
          <div class="slab-head">
            <span class="tribal-eyebrow">
              <span class="eyebrow-spike"></span>
              Перековка ключа
              <span class="eyebrow-spike"></span>
            </span>
            <h1 class="slab-title">Сброс пароля</h1>
            <p class="slab-sub">
              {{ step === 'email'
                ? 'Назовите email — пошлём код для перековки'
                : 'Введите код из письма и новый ключ-пароль' }}
            </p>
          </div>

          <Transition name="err-fade">
            <div v-if="error" class="err-banner" role="alert">
              <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-5a.75.75 0 01.75.75v2.5a.75.75 0 01-1.5 0v-2.5A.75.75 0 0110 8z" clip-rule="evenodd"/>
              </svg>
              <p>{{ error }}</p>
            </div>
          </Transition>

          <form v-if="step === 'email'" @submit.prevent="sendResetCode" class="forge-form" novalidate>
            <div class="field">
              <label for="forgot-email">Email</label>
              <input
                id="forgot-email"
                v-model="forgotForm.email"
                type="email"
                placeholder="Ваш email"
                autocomplete="email"
                @focus="handleFocus(true)" @blur="handleFocus(false)"
              />
            </div>
            <button type="submit" class="forge-btn" :disabled="isLoading">
              <span v-if="isLoading" class="btn-spinner" aria-hidden="true"></span>
              <span class="forge-btn-label">{{ isLoading ? 'Отправка…' : 'Прислать код' }}</span>
            </button>
          </form>

          <form v-if="step === 'code'" @submit.prevent="confirmResetPassword" class="forge-form" novalidate>
            <div class="field">
              <label for="reset-code">Код из письма</label>
              <input
                id="reset-code"
                v-model="forgotForm.code"
                type="text"
                placeholder="••••••"
                inputmode="numeric"
                autocomplete="one-time-code"
                @focus="handleFocus(true)" @blur="handleFocus(false)"
              />
            </div>
            <div class="field">
              <label for="new-password">Новый ключ-пароль</label>
              <input
                id="new-password"
                v-model="forgotForm.password"
                type="password"
                placeholder="Минимум 8 символов"
                autocomplete="new-password"
                @focus="handleFocus(true)" @blur="handleFocus(false)"
              />
            </div>
            <div class="field">
              <label for="new-password-confirm">Повторите ключ</label>
              <input
                id="new-password-confirm"
                v-model="forgotForm.password_confirmation"
                type="password"
                placeholder="••••••••"
                autocomplete="new-password"
                @focus="handleFocus(true)" @blur="handleFocus(false)"
              />
            </div>
            <button type="submit" class="forge-btn" :disabled="isLoading">
              <span v-if="isLoading" class="btn-spinner" aria-hidden="true"></span>
              <span class="forge-btn-label">{{ isLoading ? 'Куём…' : 'Перековать ключ' }}</span>
            </button>
            <button type="button" class="link-back" @click="step = 'email'; error = ''">
              ← Поменять email
            </button>
          </form>
        </div>

        <div class="slab-foot">
          <p>Ещё нет аккаунта? <RouterLink to="/register" class="forge-link">Вступить в ряды</RouterLink></p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ==========================================================
   FORGE AUTH · каменная плита над горном
   используется LoginView и RegisterView (общий визуал)
   ========================================================== */
.forge-auth {
  position: relative;
  min-height: calc(100vh - 180px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 64px 20px 80px;
  overflow: hidden;
}

/* ── фон: ember-свечение снизу + угольная сетка ── */
.fa-bg {
  position: absolute; inset: 0;
  pointer-events: none;
  z-index: 0;
}
.fa-glow {
  position: absolute;
  border-radius: 50%;
  filter: blur(100px);
  opacity: 0.55;
}
.fa-glow-1 {
  width: 620px; height: 620px;
  background: radial-gradient(circle, var(--ember-glow) 0%, transparent 70%);
  bottom: -180px; left: 50%;
  transform: translateX(-50%);
  opacity: 0.35;
}
.fa-glow-2 {
  width: 420px; height: 420px;
  background: radial-gradient(circle, var(--ember-heart) 0%, transparent 70%);
  top: -140px; right: -80px;
  opacity: 0.25;
}
.fa-grid {
  position: absolute; inset: 0;
  background-image:
    linear-gradient(rgba(122, 93, 72, 0.06) 1px, transparent 1px),
    linear-gradient(90deg, rgba(122, 93, 72, 0.06) 1px, transparent 1px);
  background-size: 48px 48px;
  mask-image: radial-gradient(ellipse at center, black 25%, transparent 75%);
  -webkit-mask-image: radial-gradient(ellipse at center, black 25%, transparent 75%);
}

/* ── каменная плита с заклёпками ── */
.fa-slab {
  position: relative;
  z-index: 2;
  width: 100%;
  max-width: 460px;
  background:
    linear-gradient(180deg,
      var(--ash-ironrust) 0%,
      var(--ash-stone) 45%,
      var(--ash-coal) 100%);
  clip-path: var(--clip-forged-md);
  padding: 38px 40px 32px;
  box-shadow:
    inset 0 0 0 1px var(--iron-mid),
    inset 0 0 0 3px var(--iron-void),
    var(--shadow-deep),
    var(--inset-forge);
  transition: box-shadow 0.4s var(--ease-smoke);
}
.fa-slab.is-focused {
  box-shadow:
    inset 0 0 0 1px var(--bronze),
    inset 0 0 0 3px var(--iron-void),
    var(--shadow-deep),
    0 0 48px rgba(226, 67, 16, 0.22);
}

/* Декоративные металлические полосы сверху/снизу (bevels) */
.slab-iron-top,
.slab-iron-bottom {
  position: absolute;
  left: 12px; right: 12px;
  height: 2px;
  background: linear-gradient(90deg,
    transparent 0%,
    var(--bronze) 30%,
    var(--brass) 50%,
    var(--bronze) 70%,
    transparent 100%);
  opacity: 0.5;
}
.slab-iron-top { top: 6px; }
.slab-iron-bottom { bottom: 6px; }

/* Заклёпки в углах */
.slab-rivet {
  position: absolute;
  width: 10px; height: 10px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%,
    var(--brass) 0%,
    var(--bronze) 45%,
    var(--iron-void) 100%);
  box-shadow:
    inset -1px -1px 2px rgba(0, 0, 0, 0.7),
    inset 1px 1px 1px rgba(255, 201, 121, 0.35),
    0 0 5px rgba(199, 154, 94, 0.45);
}
.slab-rivet--tl { top: 14px; left: 14px; }
.slab-rivet--tr { top: 14px; right: 14px; }
.slab-rivet--bl { bottom: 14px; left: 14px; }
.slab-rivet--br { bottom: 14px; right: 14px; }

.slab-inner { position: relative; z-index: 2; }

/* ── переключатель режимов (tabs) ── */
.mode-switcher {
  display: flex;
  background: rgba(8, 6, 10, 0.55);
  border: 1px solid var(--iron-dark);
  padding: 4px;
  gap: 4px;
  margin-bottom: 26px;
  box-shadow: var(--inset-iron-top);
}
.mode-switcher button {
  flex: 1;
  padding: 10px 10px;
  border: none;
  background: transparent;
  color: var(--text-ash);
  font-family: var(--font-ui);
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  cursor: pointer;
  transition: color 0.2s var(--ease-smoke), background 0.2s var(--ease-smoke);
}
.mode-switcher button:not(.active):hover {
  color: var(--text-parchment);
  background: rgba(122, 93, 72, 0.08);
}
.mode-switcher button.active {
  background: linear-gradient(180deg, var(--ash-forge) 0%, var(--ash-bloodrock) 100%);
  color: var(--text-bright);
  box-shadow:
    inset 0 0 0 1px var(--bronze-dark),
    inset 0 1px 0 rgba(255, 201, 121, 0.18),
    0 0 12px rgba(226, 67, 16, 0.3);
}

/* ── заголовок ── */
.slab-head {
  text-align: center;
  margin-bottom: 24px;
}
.tribal-eyebrow {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  font-family: var(--font-ui);
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: var(--bronze);
  margin-bottom: 14px;
}
.eyebrow-spike {
  width: 0; height: 0;
  border-left: 4px solid transparent;
  border-right: 4px solid transparent;
  border-top: 6px solid var(--bronze);
  filter: drop-shadow(0 0 3px rgba(199, 154, 94, 0.5));
}
.slab-title {
  font-family: var(--font-display);
  font-weight: var(--fw-black, 900);
  font-size: clamp(1.7rem, 3vw, 2.2rem);
  color: var(--text-bright);
  line-height: 1.1;
  margin: 0 0 10px;
  text-shadow: 0 2px 12px rgba(0, 0, 0, 0.6);
}
.slab-sub {
  font-family: var(--font-body);
  font-size: 0.92rem;
  color: var(--text-parchment);
  line-height: 1.55;
  margin: 0;
}

/* ── ошибка ── */
.err-banner {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  background: linear-gradient(180deg, rgba(138, 31, 24, 0.25), rgba(90, 20, 18, 0.35));
  border: 1px solid rgba(194, 40, 26, 0.45);
  color: #ffb4a8;
  padding: 11px 14px;
  margin-bottom: 18px;
  font-size: 0.87rem;
  box-shadow: var(--inset-iron-top);
}
.err-banner svg { flex-shrink: 0; margin-top: 1px; color: var(--ember-heart); }
.err-banner p { margin: 0; line-height: 1.4; }

/* ── форма ── */
.forge-form {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.field { display: flex; flex-direction: column; }
.field label {
  margin-bottom: 8px;
  font-family: var(--font-ui);
  font-weight: 700;
  font-size: 0.74rem;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  color: var(--bronze);
}
.field input {
  padding: 13px 15px;
  border: 1px solid var(--iron-mid);
  background:
    linear-gradient(180deg,
      rgba(8, 6, 10, 0.75) 0%,
      rgba(18, 16, 13, 0.85) 100%);
  color: var(--text-bone);
  font-family: var(--font-body);
  font-size: 0.98rem;
  outline: none;
  transition: border-color 0.2s var(--ease-smoke), box-shadow 0.2s var(--ease-smoke);
  box-shadow: var(--inset-iron-top);
}
.field input::placeholder { color: var(--text-void); }
.field input:focus {
  border-color: var(--ember-flame);
  box-shadow:
    var(--inset-iron-top),
    0 0 0 3px rgba(226, 67, 16, 0.15),
    0 0 14px rgba(255, 122, 43, 0.25);
}

/* ── кнопка submit (кованая) ── */
.forge-btn {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-top: 8px;
  padding: 14px 22px;
  border: 1px solid var(--ember-heart);
  background: var(--grad-ember);
  color: var(--text-bright);
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1rem;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  cursor: pointer;
  overflow: hidden;
  box-shadow:
    var(--inset-iron-top),
    inset 0 -2px 3px rgba(0, 0, 0, 0.35),
    var(--glow-ember);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.65);
  transition: transform 0.18s var(--ease-forge), box-shadow 0.2s var(--ease-smoke);
}
.forge-btn::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg,
    transparent 0%,
    rgba(255, 201, 121, 0.4) 50%,
    transparent 100%);
  transform: translateX(-120%);
  transition: transform 0.6s var(--ease-smoke);
  pointer-events: none;
}
.forge-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow:
    var(--inset-iron-top),
    inset 0 -2px 3px rgba(0, 0, 0, 0.35),
    var(--glow-ember-strong);
}
.forge-btn:hover:not(:disabled)::after { transform: translateX(120%); }
.forge-btn:active:not(:disabled) { transform: translateY(0); }
.forge-btn:disabled {
  background: var(--ash-stone);
  border-color: var(--iron-mid);
  color: var(--text-smoke);
  box-shadow: var(--inset-iron-top);
  cursor: not-allowed;
  text-shadow: none;
}
.forge-btn-label { position: relative; z-index: 1; }

.btn-spinner {
  width: 14px; height: 14px;
  border: 2px solid rgba(255, 248, 234, 0.3);
  border-top-color: var(--text-bright);
  border-radius: 50%;
  animation: forgeSpin 0.8s linear infinite;
  flex-shrink: 0;
}
@keyframes forgeSpin { to { transform: rotate(360deg); } }

/* ── линка "назад" ── */
.link-back {
  background: none;
  border: none;
  color: var(--text-ash);
  font-family: var(--font-ui);
  font-size: 0.82rem;
  cursor: pointer;
  padding: 4px 0;
  text-align: center;
  margin-top: -4px;
  transition: color 0.15s var(--ease-smoke);
}
.link-back:hover { color: var(--ember-spark); }

/* ── подвал ── */
.slab-foot {
  margin-top: 26px;
  padding-top: 18px;
  border-top: 1px dashed var(--iron-dark);
  text-align: center;
  font-family: var(--font-body);
  font-size: 0.9rem;
  color: var(--text-ash);
}
.slab-foot p { margin: 0; }
.forge-link {
  color: var(--ember-spark);
  font-weight: 600;
  text-decoration: none;
  border-bottom: 1px solid rgba(255, 167, 88, 0.35);
  transition: color 0.2s var(--ease-smoke), border-color 0.2s var(--ease-smoke);
}
.forge-link:hover {
  color: var(--ember-gold);
  border-bottom-color: var(--ember-gold);
}

/* ── transitions ── */
.err-fade-enter-active, .err-fade-leave-active { transition: all 0.22s ease; }
.err-fade-enter-from, .err-fade-leave-to { opacity: 0; transform: translateY(-6px); }

@media (max-width: 768px) {
  .fa-slab { padding: 38px 30px 28px; }
  .slab-title { font-size: 1.7rem; }
}
@media (max-width: 480px) {
  .fa-slab { padding: 30px 22px 22px; }
  .mode-switcher button { font-size: 0.72rem; padding: 9px 6px; letter-spacing: 0.8px; }
  .slab-title { font-size: 1.5rem; }
  .field input { font-size: 16px; /* iOS: убирает auto-zoom при focus */ }
}
@media (max-width: 380px) {
  .fa-slab { padding: 24px 16px 18px; }
  .slab-title { font-size: 1.3rem; }
}
</style>
