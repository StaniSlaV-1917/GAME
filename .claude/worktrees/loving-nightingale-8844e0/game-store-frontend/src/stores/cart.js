import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';
import api from '../api/axios';
import { useAuthStore } from './auth';

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

  function getItemById(itemId) {
    return items.value.find(item => item.id === itemId);
  }

  async function addItem(itemToAdd) {
    const authStore = useAuthStore();

    // Если пользователь авторизован, отправляем на сервер
    if (authStore.isLoggedIn) {
      try {
        await api.post('/cart/add', { game_id: itemToAdd.id });
        // Загружаем обновленную корзину с сервера
        await loadFromServer();
        return;
      } catch (error) {
        console.error('Failed to add item to server cart:', error);
        // Если сервер недоступен, сохраняем локально
      }
    }

    // Локальное сохранение (для неавторизованных или при ошибке сервера)
    const existingItem = getItemById(itemToAdd.id);
    if (existingItem) {
      existingItem.quantity += 1;
    } else {
      items.value.push({ ...itemToAdd, quantity: 1 });
    }
  }

  async function removeItem(itemId) {
    const authStore = useAuthStore();

    // Если пользователь авторизован, отправляем на сервер
    if (authStore.isLoggedIn) {
      try {
        await api.post('/cart/remove', { game_id: itemId });
        // Загружаем обновленную корзину с сервера
        await loadFromServer();
        return;
      } catch (error) {
        console.error('Failed to remove item from server cart:', error);
      }
    }

    // Локальное удаление
    const index = items.value.findIndex(item => item.id === itemId);
    if (index !== -1) {
      items.value.splice(index, 1);
    }
  }

  async function updateItemQuantity(itemId, quantity) {
    const authStore = useAuthStore();

    // Если пользователь авторизован, отправляем на сервер
    if (authStore.isLoggedIn) {
      try {
        await api.post('/cart/update', { game_id: itemId, quantity });
        // Загружаем обновленную корзину с сервера
        await loadFromServer();
        return;
      } catch (error) {
        console.error('Failed to update item quantity on server:', error);
      }
    }

    // Локальное обновление
    const item = getItemById(itemId);
    if (item) {
        if (quantity > 0) {
            item.quantity = quantity;
        } else {
            removeItem(itemId);
        }
    }
  }

  async function clearCart() {
    const authStore = useAuthStore();

    // Если пользователь авторизован, отправляем на сервер
    if (authStore.isLoggedIn) {
      try {
        await api.post('/cart/clear');
        // Загружаем обновленную корзину с сервера
        await loadFromServer();
        return;
      } catch (error) {
        console.error('Failed to clear server cart:', error);
      }
    }

    // Локальная очистка
    items.value = [];
  }

  async function loadFromServer() {
    const authStore = useAuthStore();
    if (!authStore.isLoggedIn) return;

    try {
      const { data } = await api.get('/cart');
      items.value = data.items || [];
    } catch (error) {
      console.error('Failed to load cart from server:', error);
    }
  }

  return {
    items,
    itemCount,
    totalPrice,
    getItemById,
    addItem,
    removeItem,
    updateItemQuantity,
    clearCart,
    loadFromServer,
  };
});
