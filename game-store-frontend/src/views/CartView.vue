<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import api from '../api/axios';
import { useCartStore } from '../stores/cart';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const cartStore = useCartStore();
const authStore = useAuthStore();

const loading = ref(true);
const ordering = ref(false);
const globalError = ref('');
const successMessage = ref('');
const cart = ref(null);
const removingId = ref(null);

const loadCart = async () => {
  loading.value = true;
  globalError.value = '';
  if (cartStore.items.length === 0) {
    cart.value = { items: [] };
    loading.value = false;
    return;
  }
  try {
    const game_ids = cartStore.items.map(i => i.id);
    const { data } = await api.post('/cart/sync', { game_ids });
    const synced = data.items.map(s => {
      const local = cartStore.items.find(l => l.id === s.id);
      return { ...s, quantity: local ? local.quantity : 1 };
    });
    cart.value = { ...data, items: synced };
  } catch {
    globalError.value = 'Ошибка загрузки корзины.';
    cart.value = null;
  } finally {
    loading.value = false;
  }
};

const updateQuantity = (itemId, qty) => {
  if (qty < 1) return;
  const local = cartStore.items.find(i => i.id === itemId);
  if (local) local.quantity = qty;
  const ci = cart.value?.items.find(i => i.id === itemId);
  if (ci) ci.quantity = qty;
};

const removeItem = (itemId) => {
  removingId.value = itemId;
  setTimeout(() => {
    cartStore.removeItem(itemId);
    if (cart.value) cart.value.items = cart.value.items.filter(i => i.id !== itemId);
    removingId.value = null;
  }, 320);
};

const makeOrder = async () => {
  if (!authStore.isLoggedIn) { router.push('/login'); return; }
  if (!cartItems.value?.length) { globalError.value = 'Корзина пуста.'; return; }
  ordering.value = true;
  globalError.value = '';
  try {
    const { data } = await api.post('/orders', {
      items: cartItems.value.map(i => ({ game_id: i.id, quantity: i.quantity }))
    });
    successMessage.value = data.message || 'Заказ успешно оформлен!';
    cartStore.clearCart();
    cart.value = null;
  } catch (e) {
    globalError.value = e.response?.data?.message || 'Ошибка при оформлении заказа.';
  } finally { ordering.value = false; }
};

const cartItems = computed(() => cart.value?.items || []);
const cartTotal = computed(() =>
  cartItems.value.reduce((t, i) => t + i.price * i.quantity, 0)
);
const cartCount = computed(() =>
  cartItems.value.reduce((t, i) => t + i.quantity, 0)
);

onMounted(loadCart);
</script>

<template>
  <div class="cart-root">
    <!-- Ambient blobs -->
    <div class="cart-blobs">
      <div class="cb cb1"></div>
      <div class="cb cb2"></div>
    </div>

    <div class="cart-inner">
      <!-- Header -->
      <div class="cart-header">
        <h1 class="cart-title">🛒 Корзина</h1>
        <RouterLink to="/catalog" class="continue-link">← Продолжить покупки</RouterLink>
      </div>

      <!-- Error -->
      <Transition name="fade">
        <div v-if="globalError" class="alert-box alert-error">⚠️ {{ globalError }}</div>
      </Transition>

      <!-- Loading -->
      <div v-if="loading" class="empty-state">
        <div class="loading-ring"></div>
        <p>Загружаем корзину...</p>
      </div>

      <!-- Success -->
      <Transition name="pop">
        <div v-if="successMessage" class="success-state">
          <div class="success-icon">🎉</div>
          <h2>Заказ оформлен!</h2>
          <p>{{ successMessage }}</p>
          <div class="success-actions">
            <RouterLink to="/profile" class="success-btn primary">Мои заказы</RouterLink>
            <RouterLink to="/catalog" class="success-btn secondary">В каталог</RouterLink>
          </div>
        </div>
      </Transition>

      <!-- Empty -->
      <div v-if="!loading && !successMessage && cartItems.length === 0" class="empty-state">
        <div class="empty-icon">🛒</div>
        <h2 class="empty-title">Корзина пуста</h2>
        <p class="empty-sub">Добавьте игры из каталога, чтобы оформить заказ</p>
        <RouterLink to="/catalog" class="empty-cta">Перейти в каталог</RouterLink>
      </div>

      <!-- Cart layout -->
      <div v-if="!loading && !successMessage && cartItems.length > 0" class="cart-layout">

        <!-- Items list -->
        <div class="items-col">
          <div class="items-header">
            <span class="items-count">{{ cartCount }} {{ cartCount === 1 ? 'товар' : cartCount < 5 ? 'товара' : 'товаров' }}</span>
          </div>

          <TransitionGroup name="item" tag="div" class="items-list">
            <div
              v-for="item in cartItems" :key="item.id"
              class="cart-item" :class="{ removing: removingId === item.id }"
            >
              <!-- Image -->
              <RouterLink :to="`/games/${item.id}`" class="item-img-link">
                <img
                  :src="item.image ? `/img/${item.image}` : '/img/noimage.png'"
                  :alt="item.title" class="item-img"
                />
                <div class="item-img-overlay"></div>
              </RouterLink>

              <!-- Info -->
              <div class="item-info">
                <RouterLink :to="`/games/${item.id}`" class="item-title">{{ item.title }}</RouterLink>
                <div class="item-meta">
                  <span class="item-tag">{{ item.platform }}</span>
                  <span v-if="item.genre" class="item-tag">{{ item.genre }}</span>
                </div>
                <div class="item-price-row">
                  <span class="item-unit-price">{{ Number(item.price).toFixed(0) }} ₽ × {{ item.quantity }}</span>
                  <span class="item-total-price">{{ Number(item.price * item.quantity).toFixed(0) }} ₽</span>
                </div>
              </div>

              <!-- Qty controls -->
              <div class="qty-controls">
                <button class="qty-btn" @click="updateQuantity(item.id, item.quantity - 1)" :disabled="item.quantity <= 1">−</button>
                <span class="qty-val">{{ item.quantity }}</span>
                <button class="qty-btn" @click="updateQuantity(item.id, item.quantity + 1)">+</button>
              </div>

              <!-- Remove -->
              <button class="remove-btn" @click="removeItem(item.id)" title="Удалить">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
          </TransitionGroup>
        </div>

        <!-- Summary -->
        <aside class="summary-card">
          <h2 class="summary-title">Итог заказа</h2>

          <div class="summary-rows">
            <div class="sum-row">
              <span>Товары ({{ cartCount }})</span>
              <span>{{ Number(cartTotal).toFixed(0) }} ₽</span>
            </div>
            <div class="sum-row">
              <span>Доставка</span>
              <span class="free-delivery">⚡ Мгновенно</span>
            </div>
          </div>

          <div class="summary-divider"></div>

          <div class="sum-row total-row">
            <span>К оплате</span>
            <span class="total-price">{{ Number(cartTotal).toFixed(0) }} ₽</span>
          </div>

          <button
            class="checkout-btn" @click="makeOrder"
            :disabled="ordering || loading"
          >
            <span v-if="ordering" class="btn-spinner"></span>
            <span v-else>Оформить заказ</span>
          </button>

          <div class="summary-guarantees">
            <div class="sg-item">🔒 Безопасная оплата</div>
            <div class="sg-item">⚡ Ключ на e-mail сразу</div>
            <div class="sg-item">🎯 Лицензия навсегда</div>
          </div>
        </aside>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ─── Root ─── */
.cart-root { min-height: 100vh; position: relative; color: #e5e7eb; overflow: hidden; }
.cart-blobs { position: fixed; inset: 0; pointer-events: none; z-index: 0; }
.cb { position: absolute; border-radius: 50%; filter: blur(100px); opacity: 0.12; }
.cb1 { width: 500px; height: 500px; background: #3b82f6; top: -10%; left: -10%; }
.cb2 { width: 400px; height: 400px; background: #6366f1; bottom: 5%; right: -5%; }

.cart-inner { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; padding: 36px 24px 80px; }

/* ─── Header ─── */
.cart-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; flex-wrap: wrap; gap: 12px; }
.cart-title { font-size: clamp(1.6rem, 3vw, 2.2rem); font-weight: 800; color: #fff; margin: 0; }
.continue-link { color: #6b7280; text-decoration: none; font-size: 0.9rem; transition: color 0.2s; }
.continue-link:hover { color: #93c5fd; }

/* ─── Alert ─── */
.alert-box { padding: 14px 20px; border-radius: 12px; margin-bottom: 20px; font-size: 0.95rem; }
.alert-error { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.35); color: #fca5a5; }

/* ─── Loading / Empty ─── */
.empty-state {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  min-height: 50vh; gap: 18px; text-align: center;
}
.loading-ring {
  width: 52px; height: 52px; border-radius: 50%;
  border: 3px solid rgba(59,130,246,0.15); border-top-color: #3b82f6;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.empty-icon { font-size: 4rem; animation: floatIcon 3s ease-in-out infinite; }
@keyframes floatIcon { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
.empty-title { font-size: 1.6rem; font-weight: 700; color: #fff; margin: 0; }
.empty-sub { color: #6b7280; margin: 0; font-size: 0.95rem; }
.empty-cta {
  padding: 13px 32px; background: linear-gradient(135deg, #3b82f6, #6366f1);
  border-radius: 12px; color: #fff; font-weight: 700; text-decoration: none;
  transition: all 0.22s; box-shadow: 0 6px 20px rgba(59,130,246,0.35);
}
.empty-cta:hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(59,130,246,0.5); }

/* ─── Success ─── */
.success-state {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  min-height: 50vh; gap: 18px; text-align: center;
}
.success-icon { font-size: 4rem; animation: popIn 0.6s cubic-bezier(.34,1.56,.64,1); }
@keyframes popIn { from { transform: scale(0); opacity: 0; } to { transform: scale(1); opacity: 1; } }
.success-state h2 { font-size: 1.8rem; font-weight: 800; color: #fff; margin: 0; }
.success-state p { color: #9ca3af; margin: 0; }
.success-actions { display: flex; gap: 14px; flex-wrap: wrap; justify-content: center; }
.success-btn { padding: 12px 28px; border-radius: 12px; text-decoration: none; font-weight: 700; transition: all 0.22s; }
.success-btn.primary { background: linear-gradient(135deg, #3b82f6, #6366f1); color: #fff; }
.success-btn.secondary { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); color: #d1d5db; }
.success-btn:hover { transform: translateY(-2px); filter: brightness(1.1); }

/* ─── Cart Layout ─── */
.cart-layout { display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start; }

/* ─── Items ─── */
.items-col { display: flex; flex-direction: column; gap: 16px; }
.items-header { display: flex; align-items: center; justify-content: space-between; padding: 0 4px; }
.items-count { font-size: 0.9rem; color: #6b7280; }
.items-list { display: flex; flex-direction: column; gap: 14px; }

.cart-item {
  display: grid; grid-template-columns: 108px 1fr auto auto;
  align-items: center; gap: 18px;
  background: rgba(15,23,42,0.75); backdrop-filter: blur(16px);
  border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 16px;
  transition: all 0.32s ease;
}
.cart-item:hover { border-color: rgba(59,130,246,0.3); }
.cart-item.removing { opacity: 0; transform: translateX(30px) scale(0.96); }

.item-img-link { position: relative; border-radius: 10px; overflow: hidden; display: block; }
.item-img { width: 108px; height: 72px; object-fit: cover; display: block; transition: transform 0.3s; }
.item-img-link:hover .item-img { transform: scale(1.04); }
.item-img-overlay { position: absolute; inset: 0; background: linear-gradient(to right, transparent 60%, rgba(15,23,42,0.4)); }

.item-info { min-width: 0; display: flex; flex-direction: column; gap: 7px; }
.item-title { font-size: 1rem; font-weight: 700; color: #f1f5f9; text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: color 0.2s; }
.item-title:hover { color: #60a5fa; }
.item-meta { display: flex; flex-wrap: wrap; gap: 6px; }
.item-tag { background: rgba(255,255,255,0.07); color: #9ca3af; padding: 3px 10px; border-radius: 6px; font-size: 0.75rem; }
.item-price-row { display: flex; gap: 12px; align-items: baseline; }
.item-unit-price { font-size: 0.83rem; color: #6b7280; }
.item-total-price { font-size: 1.05rem; font-weight: 700; color: #4ade80; }

/* Qty controls */
.qty-controls { display: flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 4px; }
.qty-btn { width: 30px; height: 30px; border-radius: 7px; border: none; background: transparent; color: #9ca3af; font-size: 1.1rem; cursor: pointer; display: grid; place-items: center; transition: all 0.18s; }
.qty-btn:hover:not(:disabled) { background: rgba(59,130,246,0.2); color: #fff; }
.qty-btn:disabled { opacity: 0.3; cursor: not-allowed; }
.qty-val { font-weight: 700; font-size: 0.95rem; color: #e5e7eb; min-width: 22px; text-align: center; }

/* Remove btn */
.remove-btn {
  width: 34px; height: 34px; border-radius: 9px; border: 1px solid rgba(255,255,255,0.08);
  background: rgba(255,255,255,0.04); color: #6b7280; cursor: pointer;
  display: grid; place-items: center; transition: all 0.2s;
}
.remove-btn:hover { background: rgba(239,68,68,0.15); border-color: rgba(239,68,68,0.3); color: #fca5a5; }

/* Item transition */
.item-enter-active { transition: all 0.38s ease; }
.item-leave-active { transition: all 0.32s ease; position: absolute; width: 100%; }
.item-enter-from { opacity: 0; transform: translateY(-16px); }
.item-leave-to { opacity: 0; transform: translateX(30px) scale(0.97); }

/* ─── Summary Card ─── */
.summary-card {
  background: rgba(15,23,42,0.8); backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.1); border-radius: 20px;
  padding: 28px; position: sticky; top: 88px;
  display: flex; flex-direction: column; gap: 18px;
}
.summary-title { font-size: 1.2rem; font-weight: 700; color: #fff; margin: 0; }
.summary-rows { display: flex; flex-direction: column; gap: 12px; }
.sum-row { display: flex; justify-content: space-between; align-items: center; font-size: 0.92rem; color: #9ca3af; }
.free-delivery { color: #4ade80; font-weight: 600; }
.summary-divider { height: 1px; background: rgba(255,255,255,0.08); }
.total-row { font-size: 1.1rem; font-weight: 700; color: #fff; }
.total-price { color: #4ade80; font-size: 1.5rem; font-weight: 800; }

.checkout-btn {
  width: 100%; padding: 15px; border: none; border-radius: 14px; cursor: pointer;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: #fff; font-size: 1.05rem; font-weight: 700;
  display: flex; align-items: center; justify-content: center; gap: 10px;
  transition: all 0.25s ease; box-shadow: 0 6px 24px rgba(59,130,246,0.4);
}
.checkout-btn:hover:not(:disabled) { transform: translateY(-3px); box-shadow: 0 10px 32px rgba(59,130,246,0.55); }
.checkout-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
.btn-spinner {
  width: 20px; height: 20px; border-radius: 50%;
  border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff;
  animation: spin 0.7s linear infinite;
}

.summary-guarantees { display: flex; flex-direction: column; gap: 9px; }
.sg-item { font-size: 0.8rem; color: #6b7280; display: flex; align-items: center; gap: 8px; }

/* ─── Alerts Transition ─── */
.fade-enter-active, .fade-leave-active { transition: all 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(-8px); }
.pop-enter-active { transition: all 0.5s cubic-bezier(.34,1.56,.64,1); }
.pop-enter-from { opacity: 0; transform: scale(0.88); }

/* ─── Responsive ─── */
@media (max-width: 900px) { .cart-layout { grid-template-columns: 1fr; } .summary-card { position: static; } }
@media (max-width: 640px) {
  .cart-item { grid-template-columns: 80px 1fr; grid-template-rows: auto auto; }
  .item-img { width: 80px; height: 56px; }
  .qty-controls { grid-column: 2; }
  .remove-btn { grid-column: 1; grid-row: 2; justify-self: start; }
}
</style>
