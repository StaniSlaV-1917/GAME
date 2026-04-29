import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

/**
 * Режим сайта — единый домен с двумя контекстами просмотра.
 * - 'shop'  : каталог, корзина, оплата (магазин игр)
 * - 'forum' : посты, комменты, лента, чаты (соц-составляющая v2)
 *
 * Mode НЕ меняет URL-структуру и не двигает страницы — он только переключает
 * состав главного nav в шапке и активирует «целевую» точку логотипа.
 * Любая страница доступна напрямую по URL независимо от mode.
 *
 * Хранится в localStorage под ключом 'gsMode' — при возврате юзера
 * вспоминается его последний выбор.
 */

const VALID = ['shop', 'forum'];

export const useModeStore = defineStore('mode', () => {
  const stored = localStorage.getItem('gsMode');
  const current = ref(VALID.includes(stored) ? stored : 'shop');

  const isShop  = computed(() => current.value === 'shop');
  const isForum = computed(() => current.value === 'forum');

  /**
   * Главные пункты nav в шапке для текущего режима.
   * Используется в App.vue для рендера <RouterLink>'ов.
   */
  const navItems = computed(() => {
    if (current.value === 'forum') {
      return [
        { to: '/feed',      label: 'Лента' },
        { to: '/posts',     label: 'Посты' },
        { to: '/messages',  label: 'Сообщения', requiresAuth: true },
        { to: '/mods',      label: 'Моды' },
        { to: '/community', label: 'Сообщество' },
      ];
    }
    return [
      { to: '/',        label: 'Главная' },
      { to: '/news',    label: 'Хроники' },
      { to: '/catalog', label: 'Оружейная' },
      { to: '/about',   label: 'О клане' },
    ];
  });

  /**
   * Куда ведёт клик по логотипу. shop → главная магазина, forum → лента.
   */
  const homeRoute = computed(() => current.value === 'forum' ? '/feed' : '/');

  function setMode(mode) {
    if (!VALID.includes(mode)) return;
    current.value = mode;
    localStorage.setItem('gsMode', mode);
  }

  function toggle() {
    setMode(current.value === 'shop' ? 'forum' : 'shop');
  }

  return { current, isShop, isForum, navItems, homeRoute, setMode, toggle };
});
