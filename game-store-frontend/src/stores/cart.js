import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';

export const useCartStore = defineStore('cart', () => {
  // STATE
  const items = ref([]);

  // HYDRATION: Load cart from localStorage on startup
  const storedCart = localStorage.getItem('gameStoreCart');
  if (storedCart) {
    items.value = JSON.parse(storedCart);
  }

  // GETTERS
  const itemCount = computed(() => items.value.length);
  const totalPrice = computed(() => 
    items.value.reduce((total, item) => total + (item.price || 0), 0)
  );
  const getItemById = (itemId) => {
    return items.value.find(item => item.id === itemId);
  }

  // ACTIONS
  function addItem(item) {
    if (!item || !item.id) {
      console.error('Невозможно добавить невалидный товар в корзину');
      return;
    }
    const existingItem = getItemById(item.id);
    if (!existingItem) {
      items.value.push({ ...item, quantity: 1 });
    }
    // Если товар уже существует, мы просто подтверждаем его наличие, не увеличивая количество
  }

  function removeItem(itemId) {
    const index = items.value.findIndex(item => item.id === itemId);
    if (index !== -1) {
      items.value.splice(index, 1);
    }
  }

  function clearCart() {
    items.value = [];
  }

  // PERSISTENCE: Save to localStorage whenever the cart changes
  watch(items, (newItems) => {
    localStorage.setItem('gameStoreCart', JSON.stringify(newItems));
  }, { deep: true });

  return {
    items,
    itemCount,
    totalPrice,
    getItemById,
    addItem,
    removeItem,
    clearCart,
  };
});
