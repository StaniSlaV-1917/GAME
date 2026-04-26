<script setup>
import { ref, onMounted, computed, watch, onUnmounted } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { useHead } from '@vueuse/head';
import api from '../api/axios';
import { useCartStore } from '../stores/cart';
import { useAuthStore } from '../stores/auth';
import { resolveMediaUrl } from '../utils/media';

useHead({
  title: 'Добыча — GameStore',
  meta: [
    { name: 'description', content: 'Твоя добыча в GameStore. Проверь выбранные игры и оформи заказ.' },
    { name: 'robots', content: 'noindex, nofollow' },
  ],
});

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
  try {
    if (authStore.isLoggedIn) {
      await cartStore.loadFromServer();
    }

    if (cartStore.items.length === 0) {
      cart.value = { items: [] };
    } else {
      cart.value = {
        items: cartStore.items.map(item => ({
          ...item,
          sum: item.price * item.quantity
        })),
        total: cartStore.totalPrice
      };
    }
  } catch (error) {
    console.error('Failed to load cart:', error);
    globalError.value = 'Ошибка загрузки добычи.';
    cart.value = null;
  } finally {
    loading.value = false;
  }
};

const updateQuantity = async (itemId, qty) => {
  if (qty < 1) return;
  try {
    await cartStore.updateItemQuantity(itemId, qty);
    const ci = cart.value?.items.find(i => i.id === itemId);
    if (ci) {
      ci.quantity = qty;
      ci.sum = ci.price * qty;
    }
  } catch (error) {
    console.error('Failed to update quantity:', error);
    globalError.value = 'Ошибка обновления количества.';
  }
};

const removeItem = async (itemId) => {
  removingId.value = itemId;
  try {
    await cartStore.removeItem(itemId);
    if (cart.value) {
      cart.value.items = cart.value.items.filter(i => i.id !== itemId);
    }
  } catch (error) {
    console.error('Failed to remove item:', error);
    globalError.value = 'Ошибка удаления.';
  } finally {
    removingId.value = null;
  }
};

const makeOrder = async () => {
  if (!authStore.isLoggedIn) { router.push('/login'); return; }
  if (!cartItems.value?.length) { globalError.value = 'Добыча пуста.'; return; }
  ordering.value = true;
  globalError.value = '';
  try {
    const { data } = await api.post('/orders', {
      items: cartItems.value.map(i => ({ game_id: i.id, quantity: i.quantity }))
    });
    successMessage.value = data.message || 'Поход успешен! Ключи отправлены на почту.';
    await cartStore.clearCart();
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

const pluralItem = (n) => {
  const m = n % 100;
  if (m >= 11 && m <= 14) return 'предметов';
  switch (n % 10) {
    case 1: return 'предмет';
    case 2: case 3: case 4: return 'предмета';
    default: return 'предметов';
  }
};

// Animated price counter
const displayTotal = ref(0);
let animFrame = null;

const animateTotal = (target) => {
  if (animFrame) cancelAnimationFrame(animFrame);
  const start = displayTotal.value;
  const diff = target - start;
  const duration = 500;
  const startTime = performance.now();
  const step = (now) => {
    const t = Math.min((now - startTime) / duration, 1);
    const eased = 1 - Math.pow(1 - t, 3);
    displayTotal.value = Math.round(start + diff * eased);
    if (t < 1) animFrame = requestAnimationFrame(step);
  };
  animFrame = requestAnimationFrame(step);
};

watch(cartTotal, (val) => animateTotal(val), { immediate: true });

onMounted(loadCart);
onUnmounted(() => { if (animFrame) cancelAnimationFrame(animFrame); });
</script>

<template>
  <div class="cart-root">
    <!-- Ambient ember particles -->
    <div class="cart-embers" aria-hidden="true">
      <span v-for="n in 8" :key="n" class="ember" :style="{ '--i': n }"></span>
    </div>

    <div class="cart-inner">
      <!-- Header -->
      <div class="cart-header">
        <div class="cart-title-block">
          <div class="cart-eyebrow">
            <span class="eyebrow-spike"></span>
            <span>Трофеи воина</span>
            <span class="eyebrow-spike right"></span>
          </div>
          <h1 class="cart-title">Добыча</h1>
        </div>
        <RouterLink to="/catalog" class="continue-link">
          <span class="cl-arrow">←</span>
          <span>Вернуться в оружейную</span>
        </RouterLink>
      </div>

      <!-- Error -->
      <Transition name="fade">
        <div v-if="globalError" class="alert-box alert-error">
          <span class="alert-icon">⚠</span>
          <span>{{ globalError }}</span>
        </div>
      </Transition>

      <!-- Loading -->
      <div v-if="loading" class="empty-state">
        <div class="loading-ring"></div>
        <p>Собираем твою добычу…</p>
      </div>

      <!-- Success -->
      <Transition name="pop">
        <div v-if="successMessage" class="success-state">
          <div class="success-sigil">
            <div class="success-rune">⚔</div>
            <div class="success-rune-glow"></div>
          </div>
          <h2 class="success-title">Победа!</h2>
          <p class="success-sub">{{ successMessage }}</p>
          <div class="success-actions">
            <RouterLink to="/profile" class="success-btn primary">
              <span class="btn-icon">◈</span>
              <span>Мои походы</span>
            </RouterLink>
            <RouterLink to="/catalog" class="success-btn secondary">
              <span>В оружейную</span>
            </RouterLink>
          </div>
        </div>
      </Transition>

      <!-- Empty -->
      <div v-if="!loading && !successMessage && cartItems.length === 0" class="empty-state">
        <div class="empty-icon-wrap">
          <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="empty-icon">
            <circle cx="9" cy="21" r="1"/>
            <circle cx="20" cy="21" r="1"/>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
          </svg>
          <div class="empty-icon-glow"></div>
        </div>
        <h2 class="empty-title">Пока без добычи</h2>
        <p class="empty-sub">Найди в оружейной достойное оружие — оно появится здесь</p>
        <RouterLink to="/catalog" class="empty-cta">
          <span class="btn-icon">⚔</span>
          <span>В оружейную</span>
        </RouterLink>
      </div>

      <!-- Cart layout -->
      <div v-if="!loading && !successMessage && cartItems.length > 0" class="cart-layout">

        <!-- Items list -->
        <div class="items-col">
          <div class="items-header">
            <span class="items-count">
              <span class="ic-val">{{ cartCount }}</span>
              <span class="ic-label">{{ pluralItem(cartCount) }} в добыче</span>
            </span>
          </div>

          <TransitionGroup name="item" tag="div" class="items-list">
            <div
              v-for="item in cartItems" :key="item.id"
              class="cart-item" :class="{ removing: removingId === item.id }"
            >
              <span class="rivet rivet-tl" aria-hidden="true"></span>
              <span class="rivet rivet-tr" aria-hidden="true"></span>
              <span class="rivet rivet-bl" aria-hidden="true"></span>
              <span class="rivet rivet-br" aria-hidden="true"></span>

              <RouterLink :to="`/games/${item.id}`" class="item-img-link">
                <img
                  :src="resolveMediaUrl(item.image)"
                  :alt="item.title" class="item-img"
                  loading="lazy"
                />
                <div class="item-img-overlay"></div>
              </RouterLink>

              <div class="item-info">
                <RouterLink :to="`/games/${item.id}`" class="item-title">{{ item.title }}</RouterLink>
                <div class="item-meta">
                  <span class="item-tag"><span class="tag-dot"></span>{{ item.platform }}</span>
                  <span v-if="item.genre" class="item-tag">{{ item.genre }}</span>
                </div>
                <div class="item-price-row">
                  <span class="item-unit-price">{{ Number(item.price).toFixed(0) }}₽ × {{ item.quantity }}</span>
                  <span class="item-total-price">{{ Number(item.price * item.quantity).toFixed(0) }}₽</span>
                </div>
              </div>

              <div class="qty-controls">
                <button class="qty-btn" @click="updateQuantity(item.id, item.quantity - 1)" :disabled="item.quantity <= 1" aria-label="Уменьшить">−</button>
                <span class="qty-val">{{ item.quantity }}</span>
                <button class="qty-btn" @click="updateQuantity(item.id, item.quantity + 1)" aria-label="Увеличить">+</button>
              </div>

              <button class="remove-btn" @click="removeItem(item.id)" title="Убрать из добычи" aria-label="Удалить">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
          </TransitionGroup>
        </div>

        <!-- Summary -->
        <aside class="summary-card">
          <span class="rivet rivet-tl" aria-hidden="true"></span>
          <span class="rivet rivet-tr" aria-hidden="true"></span>
          <span class="rivet rivet-bl" aria-hidden="true"></span>
          <span class="rivet rivet-br" aria-hidden="true"></span>

          <div class="summary-head">
            <div class="summary-eyebrow">
              <span class="eyebrow-spike"></span>
              <span>Подсчёт</span>
            </div>
            <h2 class="summary-title">Итог похода</h2>
          </div>

          <div class="summary-rows">
            <div class="sum-row">
              <span>Товары ({{ cartCount }})</span>
              <span>{{ displayTotal }}₽</span>
            </div>
            <div class="sum-row">
              <span>Доставка</span>
              <span class="free-delivery">⚡ Мгновенно</span>
            </div>
          </div>

          <div class="summary-divider"></div>

          <div class="sum-row total-row">
            <span class="total-label">К оплате</span>
            <span class="total-price">
              <span class="tp-val">{{ displayTotal }}</span>
              <span class="tp-unit">₽</span>
            </span>
          </div>

          <button
            class="checkout-btn" @click="makeOrder"
            :disabled="ordering || loading"
          >
            <span v-if="ordering" class="btn-spinner"></span>
            <template v-else>
              <span class="btn-icon">⚔</span>
              <span>Оформить поход</span>
            </template>
          </button>

          <div class="summary-guarantees">
            <div class="sg-item">
              <span class="sg-icon">◈</span>
              <span>Безопасная оплата</span>
            </div>
            <div class="sg-item">
              <span class="sg-icon">✉</span>
              <span>Ключ на e-mail сразу</span>
            </div>
            <div class="sg-item">
              <span class="sg-icon">⚑</span>
              <span>Лицензия навсегда</span>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ============================================================
   ASHENFORGE · CartView
   ============================================================ */

@keyframes emberRise {
  0%   { opacity: 0; transform: translate(var(--x, 0), 0) scale(0.5); }
  15%  { opacity: 1; }
  100% { opacity: 0; transform: translate(calc(var(--x, 0) + 30px), -200px) scale(1.3); }
}
@keyframes spin { to { transform: rotate(360deg); } }
@keyframes floatIcon {
  0%, 100% { transform: translateY(0); }
  50%      { transform: translateY(-10px); }
}
@keyframes successPop {
  0%   { transform: scale(0) rotate(-180deg); opacity: 0; }
  60%  { transform: scale(1.2) rotate(20deg); opacity: 1; }
  100% { transform: scale(1) rotate(0); opacity: 1; }
}

.cart-root {
  min-height: 100vh;
  position: relative;
  color: var(--text-bone);
  overflow: hidden;
}

/* Ember particles bg */
.cart-embers {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 0;
  overflow: hidden;
}
.ember {
  position: absolute;
  bottom: 0;
  left: calc((var(--i) * 13%) + 5%);
  width: 3px; height: 3px;
  border-radius: 50%;
  background: radial-gradient(circle, var(--ember-gold), var(--ember-flame));
  box-shadow: 0 0 6px rgba(255, 122, 43, 0.7);
  animation: emberRise 9s ease-out infinite;
  animation-delay: calc(var(--i) * -1s);
  --x: calc((var(--i) * 4px) - 16px);
  opacity: 0;
}

.cart-inner {
  position: relative;
  z-index: 1;
  max-width: var(--content-max);
  margin: 0 auto;
  padding: 48px 24px 80px;
}

/* ==========================================================
   HEADER
   ========================================================== */
.cart-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--sp-10);
  flex-wrap: wrap;
  gap: 16px;
}
.cart-title-block { display: flex; flex-direction: column; gap: 10px; }

.cart-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-family: var(--font-tribal);
  font-size: 0.76rem;
  color: var(--brass);
  letter-spacing: var(--ls-widest);
  text-transform: uppercase;
}
.eyebrow-spike {
  width: 0; height: 0;
  border-top: 5px solid transparent;
  border-bottom: 5px solid transparent;
  border-right: 8px solid var(--ember-heart);
  filter: drop-shadow(0 0 4px rgba(194, 40, 26, 0.5));
}
.eyebrow-spike.right {
  border-right: none;
  border-left: 8px solid var(--ember-heart);
}

.cart-title {
  font-family: var(--font-display);
  font-size: clamp(2rem, 4vw, 2.8rem);
  font-weight: var(--fw-black);
  color: var(--text-bright);
  margin: 0;
  letter-spacing: var(--ls-tight);
  text-shadow:
    0 2px 0 rgba(0, 0, 0, 0.6),
    0 0 24px rgba(226, 67, 16, 0.2);
}

.continue-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  color: var(--text-parchment);
  font-family: var(--font-display);
  font-size: 0.85rem;
  font-weight: var(--fw-semibold);
  text-transform: uppercase;
  letter-spacing: var(--ls-wide);
  text-decoration: none;
  border: 1px solid var(--iron-mid);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  border-radius: var(--r-xs);
  transition: all var(--dur-fast) var(--ease-smoke);
  box-shadow: var(--inset-iron-top);
}
.continue-link:hover {
  color: var(--ember-spark);
  border-color: var(--iron-warm);
  box-shadow: var(--glow-ember-soft), var(--inset-iron-top);
}
.cl-arrow { transition: transform var(--dur-fast); color: var(--brass); }
.continue-link:hover .cl-arrow { transform: translateX(-3px); }

/* ==========================================================
   ALERT
   ========================================================== */
.alert-box {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 20px;
  border-radius: var(--r-xs);
  margin-bottom: var(--sp-5);
  font-family: var(--font-body);
  font-size: 0.95rem;
  font-style: italic;
  border-left: 3px solid;
}
.alert-error {
  background: linear-gradient(180deg, rgba(90, 20, 18, 0.25) 0%, rgba(42, 10, 8, 0.15) 100%);
  border: 1px solid var(--ember-deep);
  border-left-color: var(--ember-heart);
  color: var(--text-parchment);
}
.alert-icon { color: var(--ember-flame); font-size: 1.15rem; font-style: normal; }

/* ==========================================================
   EMPTY / LOADING STATE
   ========================================================== */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 55vh;
  gap: 20px;
  text-align: center;
  padding: 40px 20px;
}
.loading-ring {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  border: 3px solid var(--iron-dark);
  border-top-color: var(--ember-flame);
  animation: spin 0.8s linear infinite;
}

.empty-icon-wrap {
  position: relative;
  display: grid;
  place-items: center;
  animation: floatIcon 3s ease-in-out infinite;
}
.empty-icon {
  position: relative;
  z-index: 1;
  color: var(--bronze);
}
.empty-icon-glow {
  position: absolute;
  inset: -16px;
  background: radial-gradient(circle, rgba(255, 122, 43, 0.2) 0%, transparent 70%);
  filter: blur(10px);
  animation: emberPulse 3s ease-in-out infinite;
}

.empty-title {
  font-family: var(--font-display);
  font-size: 1.8rem;
  font-weight: var(--fw-bold);
  color: var(--text-bright);
  margin: 0;
  letter-spacing: var(--ls-wide);
}
.empty-sub {
  font-family: var(--font-body);
  font-style: italic;
  color: var(--text-ash);
  margin: 0;
  font-size: 1rem;
  max-width: 420px;
}
.empty-cta {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 14px 32px;
  font-family: var(--font-display);
  font-size: 0.95rem;
  font-weight: var(--fw-bold);
  text-transform: uppercase;
  letter-spacing: var(--ls-wide);
  background: var(--grad-ember);
  border: 1px solid var(--ember-heart);
  border-radius: var(--r-xs);
  color: var(--text-bright);
  text-decoration: none;
  transition: all var(--dur-med) var(--ease-smoke);
  box-shadow:
    var(--inset-iron-top),
    inset 0 -2px 4px rgba(0, 0, 0, 0.3),
    var(--glow-ember-soft);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.7);
  margin-top: 8px;
}
.empty-cta:hover {
  filter: brightness(1.15);
  box-shadow: var(--inset-iron-top), inset 0 -2px 4px rgba(0, 0, 0, 0.3), var(--glow-ember-strong);
  transform: translateY(-2px);
}

/* ==========================================================
   SUCCESS
   ========================================================== */
.success-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 55vh;
  gap: 20px;
  text-align: center;
  padding: 40px 20px;
}
.success-sigil {
  position: relative;
  display: grid;
  place-items: center;
  width: 100px;
  height: 100px;
  animation: successPop 0.7s var(--ease-forge);
}
.success-rune {
  position: relative;
  z-index: 2;
  font-family: var(--font-display);
  font-size: 3.5rem;
  color: var(--ember-gold);
  filter: drop-shadow(0 0 14px rgba(255, 201, 121, 0.9));
}
.success-rune-glow {
  position: absolute;
  inset: -20px;
  background: radial-gradient(circle, rgba(255, 122, 43, 0.5) 0%, rgba(226, 67, 16, 0.2) 50%, transparent 75%);
  filter: blur(12px);
  animation: emberPulse 2s ease-in-out infinite;
}
.success-title {
  font-family: var(--font-display);
  font-size: 2.2rem;
  font-weight: var(--fw-black);
  color: var(--text-bright);
  margin: 0;
  letter-spacing: var(--ls-wide);
  text-transform: uppercase;
  background: var(--grad-ember-text);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  filter: drop-shadow(0 0 16px rgba(226, 67, 16, 0.4));
}
.success-sub {
  font-family: var(--font-body);
  font-style: italic;
  color: var(--text-parchment);
  font-size: 1.05rem;
  margin: 0;
  max-width: 480px;
}
.success-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  justify-content: center;
  margin-top: 8px;
}
.success-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 26px;
  border-radius: var(--r-xs);
  font-family: var(--font-display);
  font-size: 0.88rem;
  font-weight: var(--fw-bold);
  text-transform: uppercase;
  letter-spacing: var(--ls-wide);
  text-decoration: none;
  transition: all var(--dur-fast) var(--ease-smoke);
}
.success-btn.primary {
  background: var(--grad-ember);
  border: 1px solid var(--ember-heart);
  color: var(--text-bright);
  box-shadow:
    var(--inset-iron-top),
    inset 0 -2px 3px rgba(0, 0, 0, 0.3),
    var(--glow-ember-soft);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.7);
}
.success-btn.secondary {
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  color: var(--text-parchment);
  box-shadow: var(--inset-iron-top);
}
.success-btn:hover { transform: translateY(-2px); filter: brightness(1.1); }
.success-btn .btn-icon { font-size: 1rem; }

/* ==========================================================
   CART LAYOUT
   ========================================================== */
.cart-layout {
  display: grid;
  grid-template-columns: 1fr 360px;
  gap: 24px;
  align-items: start;
}

/* Items */
.items-col { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
.items-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 4px;
}
.items-count {
  display: inline-flex;
  align-items: baseline;
  gap: 6px;
}
.ic-val {
  font-family: var(--font-display);
  font-size: 1.3rem;
  font-weight: var(--fw-black);
  color: var(--ember-gold);
  text-shadow: 0 0 8px rgba(255, 201, 121, 0.4);
}
.ic-label {
  font-family: var(--font-tribal);
  font-size: 0.76rem;
  color: var(--brass);
  letter-spacing: var(--ls-widest);
  text-transform: uppercase;
}

.items-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.cart-item {
  position: relative;
  display: grid;
  grid-template-columns: 120px 1fr auto auto;
  align-items: center;
  gap: 20px;
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  padding: 16px;
  transition: all 0.32s var(--ease-smoke);
  clip-path: var(--clip-forged-sm);
  box-shadow:
    inset 0 0 0 1px var(--iron-mid),
    inset 0 0 0 3px var(--iron-void),
    var(--shadow-subtle);
}
.cart-item:hover {
  box-shadow:
    inset 0 0 0 1px var(--iron-warm),
    inset 0 0 0 3px var(--iron-void),
    var(--shadow-cast);
  transform: translateY(-2px);
}
.cart-item.removing { opacity: 0; transform: translateX(30px) scale(0.96); }

/* Rivets */
.rivet {
  position: absolute;
  width: 7px; height: 7px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%, var(--brass) 0%, var(--bronze) 45%, var(--iron-void) 100%);
  box-shadow: inset -1px -1px 2px rgba(0, 0, 0, 0.7), inset 1px 1px 1px rgba(255, 201, 121, 0.35);
  pointer-events: none;
  z-index: 2;
}
.cart-item .rivet-tl { top: 10px; left: 10px; }
.cart-item .rivet-tr { top: 10px; right: 10px; }
.cart-item .rivet-bl { bottom: 10px; left: 10px; }
.cart-item .rivet-br { bottom: 10px; right: 10px; }

/* Image */
.item-img-link {
  position: relative;
  display: block;
  overflow: hidden;
  border: 1px solid var(--iron-mid);
  background: var(--ash-void);
  clip-path: var(--clip-forged-sm);
}
.item-img {
  width: 120px;
  height: 80px;
  object-fit: cover;
  display: block;
  transition: transform var(--dur-slow) var(--ease-smoke);
}
.item-img-link:hover .item-img { transform: scale(1.08); }
.item-img-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to right, transparent 55%, rgba(8, 6, 10, 0.35));
  pointer-events: none;
}

/* Info */
.item-info {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.item-title {
  font-family: var(--font-display);
  font-size: 1.02rem;
  font-weight: var(--fw-bold);
  color: var(--text-bright);
  text-decoration: none;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  transition: color var(--dur-fast);
  letter-spacing: var(--ls-wide);
}
.item-title:hover { color: var(--ember-spark); }

.item-meta { display: flex; flex-wrap: wrap; gap: 6px; }
.item-tag {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: var(--ash-coal);
  border: 1px solid var(--iron-dark);
  color: var(--text-ash);
  padding: 3px 10px;
  border-radius: var(--r-xs);
  font-family: var(--font-display);
  font-size: 0.7rem;
  font-weight: var(--fw-semibold);
  text-transform: uppercase;
  letter-spacing: var(--ls-wide);
}
.tag-dot {
  width: 4px; height: 4px;
  border-radius: 50%;
  background: var(--ember-glow);
  box-shadow: 0 0 4px rgba(255, 122, 43, 0.8);
}

.item-price-row {
  display: flex;
  gap: 14px;
  align-items: baseline;
  margin-top: 2px;
}
.item-unit-price {
  font-family: var(--font-body);
  font-size: 0.85rem;
  font-style: italic;
  color: var(--text-smoke);
}
.item-total-price {
  font-family: var(--font-display);
  font-size: 1.1rem;
  font-weight: var(--fw-black);
  color: var(--ember-gold);
  text-shadow: 0 0 6px rgba(255, 201, 121, 0.3);
}

/* Qty controls */
.qty-controls {
  display: flex;
  align-items: center;
  gap: 4px;
  background: var(--ash-obsidian);
  border: 1px solid var(--iron-mid);
  border-radius: var(--r-xs);
  padding: 4px;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.4);
}
.qty-btn {
  width: 30px; height: 30px;
  border-radius: var(--r-xs);
  border: none;
  background: transparent;
  color: var(--text-parchment);
  font-family: var(--font-display);
  font-size: 1.2rem;
  font-weight: var(--fw-bold);
  cursor: pointer;
  display: grid;
  place-items: center;
  transition: all var(--dur-fast) var(--ease-smoke);
}
.qty-btn:hover:not(:disabled) {
  background: rgba(226, 67, 16, 0.25);
  color: var(--ember-spark);
  box-shadow: var(--inset-forge);
}
.qty-btn:disabled { opacity: 0.3; cursor: not-allowed; }
.qty-val {
  font-family: var(--font-display);
  font-weight: var(--fw-black);
  font-size: 1rem;
  color: var(--text-bright);
  min-width: 24px;
  text-align: center;
}

/* Remove btn */
.remove-btn {
  width: 36px; height: 36px;
  border-radius: var(--r-xs);
  border: 1px solid var(--iron-mid);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  color: var(--text-ash);
  cursor: pointer;
  display: grid;
  place-items: center;
  transition: all var(--dur-fast) var(--ease-smoke);
  box-shadow: var(--inset-iron-top);
}
.remove-btn:hover {
  background: linear-gradient(180deg, var(--ash-bloodrock) 0%, var(--ash-stone) 100%);
  border-color: var(--ember-heart);
  color: var(--ember-flame);
  box-shadow: var(--glow-ember-soft), var(--inset-iron-top);
}

/* Item transition */
.item-enter-active { transition: all 0.4s var(--ease-forge); }
.item-leave-active { transition: all 0.35s var(--ease-smoke); position: absolute; width: 100%; }
.item-enter-from { opacity: 0; transform: translateY(-18px); }
.item-leave-to { opacity: 0; transform: translateX(30px) scale(0.96); }

/* ==========================================================
   SUMMARY CARD
   ========================================================== */
.summary-card {
  position: sticky;
  top: 88px;
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  padding: 28px 24px;
  display: flex;
  flex-direction: column;
  gap: 18px;
  clip-path: var(--clip-forged-md);
  box-shadow:
    inset 0 0 0 1px var(--iron-mid),
    inset 0 0 0 3px var(--iron-void),
    var(--shadow-lift),
    var(--inset-forge);
}

.summary-card .rivet {
  position: absolute;
  width: 8px; height: 8px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%, var(--brass) 0%, var(--bronze) 45%, var(--iron-void) 100%);
  box-shadow: inset -1px -1px 2px rgba(0, 0, 0, 0.7), inset 1px 1px 1px rgba(255, 201, 121, 0.35);
  z-index: 2;
}
.summary-card .rivet-tl { top: 12px; left: 12px; }
.summary-card .rivet-tr { top: 12px; right: 12px; }
.summary-card .rivet-bl { bottom: 12px; left: 12px; }
.summary-card .rivet-br { bottom: 12px; right: 12px; }

.summary-head { display: flex; flex-direction: column; gap: 6px; padding-bottom: 4px; }
.summary-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-family: var(--font-tribal);
  font-size: 0.72rem;
  color: var(--brass);
  letter-spacing: var(--ls-widest);
  text-transform: uppercase;
}
.summary-title {
  font-family: var(--font-display);
  font-size: 1.35rem;
  font-weight: var(--fw-bold);
  color: var(--text-bright);
  margin: 0;
  letter-spacing: var(--ls-wide);
  text-transform: uppercase;
}

.summary-rows { display: flex; flex-direction: column; gap: 12px; }
.sum-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-family: var(--font-body);
  font-size: 0.95rem;
  color: var(--text-parchment);
}
.free-delivery {
  color: var(--orc-emerald);
  font-family: var(--font-display);
  font-size: 0.82rem;
  font-weight: var(--fw-bold);
  text-transform: uppercase;
  letter-spacing: var(--ls-wide);
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.summary-divider {
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--iron-mid) 50%, transparent);
  position: relative;
}
.summary-divider::before {
  content: '';
  position: absolute;
  top: -4px;
  left: 50%;
  transform: translateX(-50%);
  width: 0; height: 0;
  border-left: 4px solid transparent;
  border-right: 4px solid transparent;
  border-bottom: 5px solid var(--ember-heart);
  filter: drop-shadow(0 0 3px rgba(194, 40, 26, 0.6));
}

.total-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  padding: 4px 0;
}
.total-label {
  font-family: var(--font-display);
  font-size: 0.95rem;
  color: var(--text-bright);
  text-transform: uppercase;
  letter-spacing: var(--ls-wide);
  font-weight: var(--fw-semibold);
}
.total-price {
  display: inline-flex;
  align-items: baseline;
  gap: 2px;
  font-family: var(--font-display);
  font-weight: var(--fw-black);
  color: var(--ember-gold);
  text-shadow: 0 0 16px rgba(255, 201, 121, 0.4);
}
.tp-val { font-size: 2rem; letter-spacing: var(--ls-tight); }
.tp-unit { font-size: 1.1rem; color: var(--brass); margin-left: 2px; }

.checkout-btn {
  width: 100%;
  padding: 16px;
  border: 1px solid var(--ember-heart);
  cursor: pointer;
  background: var(--grad-ember);
  color: var(--text-bright);
  font-family: var(--font-display);
  font-size: 1rem;
  font-weight: var(--fw-bold);
  text-transform: uppercase;
  letter-spacing: var(--ls-wide);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  transition: all var(--dur-med) var(--ease-smoke);
  position: relative;
  overflow: hidden;
  border-radius: var(--r-xs);
  box-shadow:
    var(--inset-iron-top),
    inset 0 -2px 4px rgba(0, 0, 0, 0.35),
    var(--glow-ember-soft);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.7);
}
.checkout-btn::before {
  content: '';
  position: absolute;
  top: 0; left: -120%;
  width: 50%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 201, 121, 0.4), transparent);
  transform: skewX(-20deg);
  transition: left 0.7s var(--ease-smoke);
}
.checkout-btn:hover:not(:disabled) {
  filter: brightness(1.15);
  transform: translateY(-2px);
  box-shadow: var(--inset-iron-top), inset 0 -2px 4px rgba(0, 0, 0, 0.35), var(--glow-ember-strong);
}
.checkout-btn:hover:not(:disabled)::before { left: 120%; }
.checkout-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  transform: none;
}
.checkout-btn .btn-icon { font-size: 1.1rem; }
.btn-spinner {
  width: 20px; height: 20px;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: var(--text-bright);
  animation: spin 0.7s linear infinite;
}

/* Guarantees */
.summary-guarantees {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding-top: 4px;
  border-top: 1px dashed var(--iron-dark);
  padding-top: 14px;
}
.sg-item {
  font-family: var(--font-body);
  font-size: 0.86rem;
  color: var(--text-ash);
  display: flex;
  align-items: center;
  gap: 10px;
}
.sg-icon {
  color: var(--brass);
  font-size: 1rem;
  flex-shrink: 0;
}

/* ==========================================================
   TRANSITIONS
   ========================================================== */
.fade-enter-active, .fade-leave-active { transition: all var(--dur-med) var(--ease-smoke); }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(-8px); }
.pop-enter-active { transition: all 0.5s var(--ease-forge); }
.pop-enter-from { opacity: 0; transform: scale(0.88); }

/* ==========================================================
   RESPONSIVE
   ========================================================== */
@media (max-width: 1100px) {
  .cart-layout { gap: 24px; }
  .summary-card { padding: 22px; }
}
@media (max-width: 900px) {
  .cart-layout { grid-template-columns: 1fr; gap: 22px; }
  .summary-card { position: static; max-width: 480px; margin: 0 auto; width: 100%; }
}
@media (max-width: 768px) {
  .cart-page { padding: var(--sp-6) var(--sp-4) var(--sp-12); }
  .cart-title { font-size: clamp(1.6rem, 5vw, 2.2rem); }
}
@media (max-width: 640px) {
  .cart-item {
    grid-template-columns: 90px 1fr;
    grid-template-rows: auto auto auto;
    gap: 12px;
    padding: 14px;
  }
  .item-img { width: 90px; height: 60px; }
  .item-info { grid-column: 2; }
  .qty-controls { grid-column: 1 / -1; justify-self: start; grid-row: 3; }
  .remove-btn { grid-column: 2; grid-row: 3; justify-self: end; }
  .cart-header { flex-direction: column; align-items: flex-start; gap: 10px; }
  .continue-link { width: 100%; justify-content: center; }
  .summary-card { padding: 18px; }
}
@media (max-width: 480px) {
  .cart-page { padding: var(--sp-5) var(--sp-3) var(--sp-10); }
  .cart-item { grid-template-columns: 76px 1fr; padding: 12px; }
  .item-img { width: 76px; height: 50px; }
  .item-title { font-size: 0.92rem; }
  .item-platform { font-size: 0.74rem; }
  .summary-card { padding: 16px; }
}
</style>
