import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';

export const useCartStore = defineStore('cart', () => {
  const items = ref(JSON.parse(localStorage.getItem('gameStoreCart') || '[]'));

  watch(items, (newCartState) => {
    localStorage.setItem('gameStoreCart', JSON.stringify(newCartState));
  }, { deep: true });

  const itemCount = computed(() =>
    items.value.reduce((total, item) => total + item.quantity, 0)
  );

  const totalPrice = computed(() =>
    items.value.reduce((total, item) => total + (item.price * item.quantity), 0)
  );

  // ВОЗВРАЩАЮ УДАЛЕННУЮ ФУНКЦИЮ
  function getItemById(itemId) {
    return items.value.find(item => item.id === itemId);
  }

  function addItem(itemToAdd) {
    const existingItem = getItemById(itemToAdd.id);
    if (existingItem) {
      existingItem.quantity += 1;
    } else {
      items.value.push({ ...itemToAdd, quantity: 1 });
    }
  }

  function removeItem(itemId) {
    const index = items.value.findIndex(item => item.id === itemId);
    if (index !== -1) {
      items.value.splice(index, 1);
    }
  }

  function updateItemQuantity(itemId, quantity) {
    const item = getItemById(itemId);
    if (item) {
        if (quantity > 0) {
            item.quantity = quantity;
        } else {
            removeItem(itemId);
        }
    }
  }

  function clearCart() {
    items.value = [];
  }

  return {
    items,
    itemCount,
    totalPrice,
    getItemById, // <-- И ДОБАВЛЯЮ ЕЕ В СПИСОК ВОЗВРАЩАЕМОГО
    addItem,
    removeItem,
    updateItemQuantity,
    clearCart,
  };
});
