import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

/**
 * Темы:
 * - 'dark'   — Ночь Кузницы (текущая Ордынская тёмная, дефолт)
 * - 'light'  — День Осады (тёплый песок и терракота)
 * - 'legacy' — Древний свод (старая тёмно-синяя до Ashenforge)
 */
const VALID = ['dark', 'light', 'legacy'];

export const useThemeStore = defineStore('theme', () => {
  // localStorage может бросить (incognito с заблокированным storage,
  // SecurityError в редких embedded-режимах). Не даём убить инициализацию.
  let stored = null;
  try { stored = localStorage.getItem('gsTheme'); } catch (_) { /* ignore */ }
  const current = ref(VALID.includes(stored) ? stored : 'dark');

  const apply = () => {
    try {
      document.documentElement.setAttribute('data-theme', current.value);
    } catch (e) {
      // setAttribute практически не бросает, но пусть будет страховка
      console.error('[theme.apply]', e);
    }
  };

  const setTheme = (theme) => {
    if (!VALID.includes(theme)) return;
    current.value = theme;
    try { localStorage.setItem('gsTheme', theme); } catch (_) { /* ignore — incognito quota */ }
    apply();
  };

  // Совместимость со старым API (toggle / isDark) — мало ли где остался
  const isDark = computed(() => current.value === 'dark' || current.value === 'legacy');
  const toggle = () => setTheme(current.value === 'light' ? 'dark' : 'light');

  /**
   * Цикл по всем трём темам по порядку: dark → light → legacy → dark…
   * Используется на мобиле где dropdown слишком громоздкий — один клик
   * по иконке темы переключает на следующую тему.
   */
  const cycle = () => {
    const idx = VALID.indexOf(current.value);
    const next = VALID[(idx + 1) % VALID.length];
    setTheme(next);
  };

  apply();

  return { current, isDark, setTheme, toggle, cycle };
});
