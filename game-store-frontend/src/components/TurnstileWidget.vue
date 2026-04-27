<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { useThemeStore } from '../stores/theme';

/**
 * TurnstileWidget — обёртка над Cloudflare Turnstile для Vue 3.
 *
 * Лениво подгружает скрипт challenges.cloudflare.com/turnstile/v0/api.js
 * один раз на страницу, рендерит виджет в свой div, и эмитит событие
 * 'verified' с токеном когда Cloudflare решит что юзер не бот.
 *
 * Использование:
 *   <TurnstileWidget @verified="onTurnstileToken" ref="turnstileRef" />
 *   ...
 *   <button :disabled="!turnstileToken" @click="submit">Зарегистрироваться</button>
 *
 *   // После submit, сбросить:
 *   turnstileRef.value?.reset();
 *   turnstileToken.value = null;
 *
 * Тема (light/dark) автоматически следует за themeStore. Юзер переключил
 * сайт на Светлую — виджет тоже стал светлым.
 */

const props = defineProps({
  // Site key — публичный, попадает в JS-бандл, видим клиентам. Берём из
  // VITE_TURNSTILE_SITE_KEY (см. GitHub Actions workflow).
  siteKey: {
    type: String,
    default: () => import.meta.env.VITE_TURNSTILE_SITE_KEY || '',
  },
  // 'auto' = Cloudflare сам подберёт по prefers-color-scheme.
  // Можно передать 'light' / 'dark' если хочется фиксировать.
  theme: {
    type: String,
    default: null,   // null → берём из themeStore автоматически
  },
});

const emit = defineEmits([
  'verified',   // (token: string) — успешная проверка
  'expired',    // токен истёк (через ~5 мин), нужно решать заново
  'error',      // (errorCode?: string) — ошибка проверки
]);

const widgetEl = ref(null);
const widgetId = ref(null);
const themeStore = useThemeStore();

const SCRIPT_SRC = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';

/**
 * Загружает Turnstile API-скрипт один раз. Если уже на странице — резолвит
 * сразу. Если другой компонент уже грузит — ждёт его завершения.
 */
function ensureScript() {
  return new Promise((resolve, reject) => {
    if (window.turnstile) return resolve();

    const existing = document.querySelector(`script[src="${SCRIPT_SRC}"]`);
    if (existing) {
      existing.addEventListener('load', () => resolve());
      existing.addEventListener('error', () => reject(new Error('Turnstile script load failed')));
      return;
    }

    const script = document.createElement('script');
    script.src = SCRIPT_SRC;
    script.async = true;
    script.defer = true;
    script.onload = () => resolve();
    script.onerror = () => reject(new Error('Turnstile script load failed'));
    document.head.appendChild(script);
  });
}

function effectiveTheme() {
  if (props.theme) return props.theme;
  // Mapping наших трёх тем на CF Turnstile: Turnstile поддерживает
  // light/dark/auto. dark = light → 'dark', legacy → 'dark', light → 'light'.
  return themeStore.current === 'light' ? 'light' : 'dark';
}

async function renderWidget() {
  if (!props.siteKey) {
    console.warn('TurnstileWidget: site key пустой. Виджет не отрисуется. Проверьте VITE_TURNSTILE_SITE_KEY в build env.');
    return;
  }

  try {
    await ensureScript();
    if (!window.turnstile || !widgetEl.value) return;

    widgetId.value = window.turnstile.render(widgetEl.value, {
      sitekey: props.siteKey,
      theme: effectiveTheme(),
      action: 'register',
      callback: (token) => emit('verified', token),
      'expired-callback': () => emit('expired'),
      'error-callback': (code) => emit('error', code),
    });
  } catch (e) {
    console.error('TurnstileWidget render error:', e);
    emit('error', e.message || 'load_failed');
  }
}

function reset() {
  if (widgetId.value && window.turnstile) {
    window.turnstile.reset(widgetId.value);
  }
}

onMounted(() => {
  renderWidget();
});

onUnmounted(() => {
  if (widgetId.value && window.turnstile) {
    try {
      window.turnstile.remove(widgetId.value);
    } catch (e) {
      // ignore — DOM мог уже не существовать
    }
  }
});

// Если юзер переключил тему сайта — перерендерим виджет в новой теме
watch(() => themeStore.current, () => {
  if (widgetId.value && window.turnstile) {
    try {
      window.turnstile.remove(widgetId.value);
    } catch (e) {}
    widgetId.value = null;
    renderWidget();
  }
});

defineExpose({ reset });
</script>

<template>
  <div ref="widgetEl" class="turnstile-widget"></div>
</template>

<style scoped>
.turnstile-widget {
  /* Стандартный размер Turnstile-виджета — ~300x65. Просто центрируем
     в форме, специальной стилизации внутри не делаем (внутри iframe от CF). */
  display: flex;
  justify-content: center;
  min-height: 65px;
  margin: 8px 0;
}
</style>
