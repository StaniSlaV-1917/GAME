<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { useHead } from '@vueuse/head';
import { useAuthStore } from '../stores/auth';
import { useToast } from '../composables/useToast';
import { warmupPing } from '../utils/warmup';

// Будим Fly-машину перед регистрацией
onMounted(() => warmupPing());

useHead({
  title: 'Регистрация в GameStore',
  meta: [
    { name: 'description', content: 'Создайте аккаунт в GameStore, чтобы покупать игры, отслеживать заказы и получать эксклюзивные предложения. Регистрация занимает меньше минуты.' },
    { property: 'og:title', content: 'Регистрация в GameStore' },
    { property: 'og:description', content: 'Создайте аккаунт в GameStore, чтобы покупать игры, отслеживать заказы и получать эксклюзивные предложения. Регистрация занимает меньше минуты.' },
  ]
});

const router = useRouter();
const authStore = useAuthStore();
const toast = useToast();

const form = ref({ fullname: '', email: '', phone: '', password: '', password_confirmation: '' });
const isLoading = ref(false);
const error = ref('');
const wrapperEl = ref(null);

const handleFocus = (v) => wrapperEl.value?.classList.toggle('is-focused', v);

const submit = async () => {
  if (isLoading.value) return;
  error.value = '';
  if (form.value.password !== form.value.password_confirmation) {
    error.value = 'Пароли не совпадают'; return;
  }
  if (form.value.password.length < 6) {
    error.value = 'Пароль должен быть не менее 6 символов'; return;
  }

  isLoading.value = true;
  try {
    await authStore.register(form.value);
    toast.success('Имя вписано в свиток. Добро пожаловать в ряды.');
    router.push({ name: 'profile' });
  } catch (e) {
    error.value = e.response?.data?.message || 'Произошла ошибка при регистрации.';
    toast.error(error.value);
    console.error(e);
  } finally {
    isLoading.value = false;
  }
};
</script>

<template>
  <div class="forge-auth">
    <div class="fa-bg" aria-hidden="true">
      <div class="fa-glow fa-glow-1"></div>
      <div class="fa-glow fa-glow-2"></div>
      <div class="fa-grid"></div>
    </div>

    <div class="fa-slab" ref="wrapperEl">
      <span class="slab-rivet slab-rivet--tl" aria-hidden="true"></span>
      <span class="slab-rivet slab-rivet--tr" aria-hidden="true"></span>
      <span class="slab-rivet slab-rivet--bl" aria-hidden="true"></span>
      <span class="slab-rivet slab-rivet--br" aria-hidden="true"></span>
      <div class="slab-iron-top" aria-hidden="true"></div>
      <div class="slab-iron-bottom" aria-hidden="true"></div>

      <div class="slab-inner">
        <div class="slab-head">
          <span class="tribal-eyebrow">
            <span class="eyebrow-spike"></span>
            Кодекс ополчения
            <span class="eyebrow-spike"></span>
          </span>
          <h1 class="slab-title">Вступить в ряды</h1>
          <p class="slab-sub">Назовите имя, ковач впишет его в свиток оплота</p>
        </div>

        <Transition name="err-fade">
          <div v-if="error" class="err-banner" role="alert">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-5a.75.75 0 01.75.75v2.5a.75.75 0 01-1.5 0v-2.5A.75.75 0 0110 8z" clip-rule="evenodd"/>
            </svg>
            <p>{{ error }}</p>
          </div>
        </Transition>

        <form @submit.prevent="submit" class="forge-form" novalidate>
          <div class="field">
            <label for="fullname">Полное имя</label>
            <input
              id="fullname"
              v-model="form.fullname"
              placeholder="Иванов Иван"
              required type="text"
              autocomplete="name"
              @focus="handleFocus(true)" @blur="handleFocus(false)"
            />
          </div>
          <div class="field">
            <label for="email">Email</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              placeholder="you@example.com"
              required
              autocomplete="email"
              @focus="handleFocus(true)" @blur="handleFocus(false)"
            />
          </div>
          <div class="field">
            <label for="phone">Телефон</label>
            <input
              id="phone"
              v-model="form.phone"
              placeholder="79991234567"
              required type="tel"
              autocomplete="tel"
              @focus="handleFocus(true)" @blur="handleFocus(false)"
            />
          </div>
          <div class="field">
            <label for="password">Ключ-пароль</label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              placeholder="Минимум 6 символов"
              required
              autocomplete="new-password"
              @focus="handleFocus(true)" @blur="handleFocus(false)"
            />
          </div>
          <div class="field">
            <label for="password_confirmation">Повторите ключ</label>
            <input
              id="password_confirmation"
              v-model="form.password_confirmation"
              type="password"
              placeholder="••••••••"
              required
              autocomplete="new-password"
              @focus="handleFocus(true)" @blur="handleFocus(false)"
            />
          </div>
          <button type="submit" class="forge-btn" :disabled="isLoading">
            <span v-if="isLoading" class="btn-spinner" aria-hidden="true"></span>
            <span class="forge-btn-label">{{ isLoading ? 'Куём…' : 'Вписать в свиток' }}</span>
          </button>
        </form>

        <div class="slab-foot">
          <p>Уже есть аккаунт? <RouterLink to="/login" class="forge-link">Войти в оплот</RouterLink></p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Те же стили, что и в LoginView — единая визуальная форма каменной плиты */
.forge-auth {
  position: relative;
  min-height: calc(100vh - 180px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 64px 20px 80px;
  overflow: hidden;
}

.fa-bg { position: absolute; inset: 0; pointer-events: none; z-index: 0; }
.fa-glow { position: absolute; border-radius: 50%; filter: blur(100px); }
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

.fa-slab {
  position: relative;
  z-index: 2;
  width: 100%;
  max-width: 480px;
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

.slab-iron-top, .slab-iron-bottom {
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

.forge-form { display: flex; flex-direction: column; gap: 16px; }

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

.err-fade-enter-active, .err-fade-leave-active { transition: all 0.22s ease; }
.err-fade-enter-from, .err-fade-leave-to { opacity: 0; transform: translateY(-6px); }

@media (max-width: 768px) {
  .fa-slab { padding: 38px 30px 28px; }
  .slab-title { font-size: 1.7rem; }
}
@media (max-width: 480px) {
  .fa-slab { padding: 30px 22px 22px; }
  .slab-title { font-size: 1.5rem; }
  .field input { font-size: 16px; }
}
@media (max-width: 380px) {
  .fa-slab { padding: 24px 16px 18px; }
  .slab-title { font-size: 1.3rem; }
}
</style>
