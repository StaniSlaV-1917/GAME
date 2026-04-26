import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useThemeStore = defineStore('theme', () => {
  const isDark = ref(localStorage.getItem('gsTheme') !== 'light');

  const apply = () => {
    document.documentElement.setAttribute('data-theme', isDark.value ? 'dark' : 'light');
  };

  const toggle = () => {
    isDark.value = !isDark.value;
    localStorage.setItem('gsTheme', isDark.value ? 'dark' : 'light');
    apply();
  };

  apply();

  return { isDark, toggle };
});
