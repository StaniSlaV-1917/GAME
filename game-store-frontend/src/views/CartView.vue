<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api/axios';
import { useCartStore } from '../stores/cart';

const router = useRouter();
const cartStore = useCartStore();
const loading = ref(true);
const globalError = ref('');
const successMessage = ref('');

const cart = ref(null);
const updatingItemId = ref(null);
const removingItemId = ref(null);

const loadCart = async () => {
  loading.value = true;
  globalError.value = '';
  // Если в локальном хранилище нет товаров, не нужно делать запрос к серверу
  if (cartStore.items.length === 0) {
    cart.value = { items: [] };
    loading.value = false;
    return;
  }
  try {
    const game_ids = cartStore.items.map(item => item.id);
    const { data } = await api.post('/cart/sync', { game_ids });
    
    // Совмещаем данные с сервера (цена, название) с локальными данными (количество)
    const syncedItems = data.items.map(serverItem => {
        const localItem = cartStore.items.find(li => li.id === serverItem.id);
        return {
            ...serverItem,
            quantity: localItem ? localItem.quantity : 1,
        };
    });

    cart.value = { ...data, items: syncedItems };

  } catch (e) {
    globalError.value = 'Ошибка загрузки корзины.';
    cart.value = null;
  } finally {
    loading.value = false;
  }
};

const updateQuantity = (itemId, newQuantity) => {
  if (newQuantity < 1) return;
  
  // Обновляем количество в центральном хранилище Pinia
  const localItem = cartStore.items.find(i => i.id === itemId);
  if (localItem) {
    localItem.quantity = newQuantity;
  }

  // Обновляем количество в локальном состоянии компонента для мгновенного отклика UI
  const cartItem = cart.value.items.find(i => i.id === itemId);
  if (cartItem) {
    cartItem.quantity = newQuantity;
  }
};

const removeItem = (itemId) => {
  // Удаляем из центрального хранилища Pinia
  cartStore.removeItem(itemId);

  // Удаляем из локального состояния компонента для мгновенного отклика UI
  if (cart.value && cart.value.items) {
    cart.value.items = cart.value.items.filter(i => i.id !== itemId);
  }
};

// КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ
const makeOrder = async () => {
  if (!cartItems.value || cartItems.value.length === 0) {
      globalError.value = "Ваша корзина пуста.";
      return;
  }

  loading.value = true;
  globalError.value = '';
  successMessage.value = '';
  
  try {
    // Готовим данные с актуальным составом корзины для отправки
    const orderPayload = {
        items: cartItems.value.map(item => ({
            game_id: item.id,
            quantity: item.quantity,
        })),
    };

    // Отправляем данные на сервер
    const { data } = await api.post('/orders', orderPayload);
    
    successMessage.value = data.message || 'Ваш заказ успешно оформлен!';
    cartStore.clearCart();
    cart.value = null;

  } catch (e) {
    globalError.value = e.response?.data?.message || 'Ошибка при оформлении заказа. Проверьте, авторизованы ли вы.';
  } finally {
    loading.value = false;
  }
};

const cartItems = computed(() => cart.value?.items || []);
const cartTotal = computed(() => {
  if (!cart.value || !cart.value.items) return 0;
  return cart.value.items.reduce((total, item) => total + item.price * item.quantity, 0);
});

onMounted(loadCart);
</script>

<template>
  <main class="cart-page-container">
    <h1>Корзина</h1>

    <!-- Глобальные сообщения -->
    <div v-if="globalError" class="status-indicator error-message">{{ globalError }}</div>
    <div v-if="loading && !cartItems.length" class="status-indicator">Загрузка...</div>
    
    <div v-else-if="successMessage" class="status-indicator success-message">
      <p>{{ successMessage }}</p>
      <button @click="router.push('/catalog')" class="action-btn">В каталог</button>
    </div>
    
    <div v-else-if="!cartItems.length" class="status-indicator cart-empty">
      <p>Ваша корзина пуста</p>
      <button @click="router.push('/catalog')" class="action-btn">Выбрать игры</button>
    </div>

    <div v-else class="cart-layout">
      <div class="cart-items-list">
        <div 
          v-for="item in cartItems" 
          :key="item.id" 
          class="cart-item-card" 
          :class="{ 'is-processing': removingItemId === item.id || updatingItemId === item.id }"
        >
          <img :src="item.image ? `/img/${item.image}` : '/img/noimage.png'" :alt="item.title" class="item-image" />
          
          <div class="item-details">
            <router-link :to="`/games/${item.id}`" class="item-title">{{ item.title }}</router-link>
            <p class="item-meta">{{ item.platform }} / {{ item.genre }}</p>
          </div>

          <div class="item-quantity-controls">
            <button @click="updateQuantity(item.id, item.quantity - 1)" :disabled="item.quantity <= 1 || updatingItemId === item.id">-</button>
            <span>{{ updatingItemId === item.id ? '...' : item.quantity }}</span>
            <button @click="updateQuantity(item.id, item.quantity + 1)" :disabled="updatingItemId === item.id">+</button>
          </div>

          <div class="item-price">
            {{ Number(item.price * item.quantity).toFixed(0) }} ₽
          </div>

          <button class="item-remove-btn" @click="removeItem(item.id)" :disabled="removingItemId === item.id">
            &times;
          </button>
        </div>
      </div>

      <aside class="cart-summary-card">
        <h2>Итог заказа</h2>
        <div class="summary-row">
          <span>Товары ({{ cartItems.length }})</span>
          <span>{{ Number(cartTotal).toFixed(0) }} ₽</span>
        </div>
        <div class="summary-row total">
          <span>К оплате</span>
          <span>{{ Number(cartTotal).toFixed(0) }} ₽</span>
        </div>
        <button class="checkout-btn" @click="makeOrder" :disabled="loading || updatingItemId || removingItemId">
          {{ loading ? 'Оформление...' : 'Оформить заказ' }}
        </button>
      </aside>
    </div>
  </main>
</template>

<style scoped>
.cart-page-container { max-width: 1200px; margin: 24px auto; padding: 0 24px; color: #e5e7eb; }
h1 { font-size: 2rem; font-weight: 700; color: #fff; margin-bottom: 24px; }

.status-indicator {
  background: #111827; 
  border: 1px solid #1f2937;
  border-radius: 12px; 
  padding: 40px;
  margin-bottom: 24px;
  text-align: center;
  color: #9ca3af;
}
.status-indicator p { margin: 0 0 16px; font-size: 1.1rem; }
.error-message { background: #3a1a1a; border-color: #ef4444; color: #fecaca; }
.success-message { background: #162a22; border-color: #22c55e; color: #a7f3d0; }

.action-btn {
  background: #3b82f6; color: #fff; border: none; padding: 10px 20px;
  border-radius: 8px; font-size: 1rem; cursor: pointer; transition: background .2s;
}
.action-btn:hover { background: #2563eb; }

.cart-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: flex-start; }

.cart-items-list { display: flex; flex-direction: column; gap: 16px; }

@media (max-width: 992px) {
  .cart-layout { grid-template-columns: 1fr; }
  .cart-summary-card { position: static; } 
}

.cart-item-card {
  display: grid;
  grid-template-columns: 100px 1fr auto auto auto;
  align-items: center;
  gap: 16px;
  background: #111827; 
  border: 1px solid #1f2937;
  padding: 16px;
  border-radius: 12px;
  transition: opacity .2s, border-color .2s;
}
.cart-item-card:hover { border-color: #374151; }
.cart-item-card.is-processing { opacity: 0.5; pointer-events: none; }

.item-image { width: 100px; height: 65px; object-fit: cover; border-radius: 8px; }

.item-details { display: flex; flex-direction: column; gap: 4px; }
.item-title { font-size: 1.1rem; font-weight: 600; color: #fff; text-decoration: none; }
.item-title:hover { color: #3b82f6; }
.item-meta { font-size: 0.85rem; color: #6b7280; }

.item-price { font-size: 1.1rem; font-weight: 600; color: #e5e7eb; white-space: nowrap; }

.item-quantity-controls {
  display: flex; align-items: center; gap: 8px; background: #1f2937; border-radius: 8px;
}
.item-quantity-controls button {
  background: transparent; color: #9ca3af; border: none; font-size: 1.2rem; cursor: pointer;
  width: 32px; height: 32px; transition: color .2s;
}
.item-quantity-controls button:hover:enabled { color: #fff; }
.item-quantity-controls button:disabled { color: #4b5563; cursor: not-allowed; }
.item-quantity-controls span { font-weight: 600; font-size: 1rem; min-width: 20px; text-align: center; }

.item-remove-btn {
  background: #374151; color: #9ca3af; width: 32px; height: 32px;
  border: none; border-radius: 50%; font-size: 1.5rem; line-height: 1;
  cursor: pointer; transition: all .2s;
}
.item-remove-btn:hover:enabled { background: #ef4444; color: #fff; }

.cart-summary-card {
  background: #111827; 
  border: 1px solid #1f2937;
  border-radius: 12px;
  padding: 24px;
  position: sticky;
  top: 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.cart-summary-card h2 { font-size: 1.4rem; color: #fff; margin: 0; }

.summary-row {
  display: flex; justify-content: space-between; align-items: center;
  font-size: 1rem; color: #9ca3af;
}
.summary-row.total {
  font-size: 1.2rem; font-weight: 600; color: #fff;
  border-top: 1px solid #374151; padding-top: 16px;
}

.checkout-btn {
  background: #22c55e; color: #fff; border: none; padding: 12px; 
  border-radius: 8px; font-size: 1.1rem; font-weight: 600;
  cursor: pointer; transition: all .2s;
}
.checkout-btn:hover:enabled { background: #16a34a; transform: scale(1.02); }
.checkout-btn:disabled { background: #1e462d; color: #537a65; cursor: not-allowed; }

@media (max-width: 768px) {
  .cart-item-card {
    grid-template-columns: 80px 1fr auto;
    grid-template-rows: auto auto;
    padding: 12px;
  }
  .item-image { grid-row: 1 / 3; }
  .item-details { grid-column: 2 / 4; }
  .item-quantity-controls { grid-column: 2; grid-row: 2; justify-self: start; }
  .item-price { grid-column: 3; grid-row: 2; justify-self: end; }
  .item-remove-btn { position: absolute; top: 12px; right: 12px; background: none; }
}

</style>