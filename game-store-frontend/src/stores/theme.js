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
  const stored = localStorage.getItem('gsTheme');
  const current = ref(VALID.includes(stored) ? stored : 'dark');

  const apply = () => {
    document.documentElement.setAttribute('data-theme', current.value);
  };

  const setTheme = (theme) => {
    if (!VALID.includes(theme)) return;
    current.value = theme;
    localStorage.setItem('gsTheme', theme);
    apply();
  };

  // Совместимость со старым API (toggle / isDark) — мало ли где остался
  const isDark = computed(() => current.value === 'dark' || current.value === 'legacy');
  const toggle = () => setTheme(current.value === 'light' ? 'dark' : 'light');

  apply();

  return { current, isDark, setTheme, toggle };
});
