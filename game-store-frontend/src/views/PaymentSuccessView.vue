<script setup>
/**
 * Pay/A — заглушка успешной оплаты.
 *
 * Показывается после confirmed-перехода с PaymentView.
 * MVP: товара нет, юзер видит «спасибо за оплату, тут будет ключ когда
 * добавим товары». Хэш транзакции и сумма выводятся для референса.
 */
import { ref, computed, onMounted } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import { useHead } from '@vueuse/head';
import api from '../api/axios';

const route = useRoute();

useHead({
  title: 'Оплата успешна — GameStore',
  meta: [{ name: 'robots', content: 'noindex, nofollow' }],
});

const payment = ref(null);
const loading = ref(true);

const tronscanUrl = computed(() => {
  return payment.value?.transaction_hash
    ? `https://tronscan.org/#/transaction/${payment.value.transaction_hash}`
    : null;
});

const formattedDate = computed(() => {
  if (!payment.value?.confirmed_at) return '';
  return new Date(payment.value.confirmed_at).toLocaleString('ru-RU', {
    day: 'numeric', month: 'long', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
});

const cartItems = computed(() => payment.value?.metadata?.cart || []);

onMounted(async () => {
  try {
    const { data } = await api.get(`/payments/${route.params.id}`);
    payment.value = data;
  } catch (e) {
    console.warn('[PaymentSuccess] fetch failed', e);
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="success-view">
    <div class="success-shell">

      <div v-if="loading" class="loading">Загрузка…</div>

      <template v-else-if="payment">
        <!-- Sigil + title -->
        <div class="success-sigil" aria-hidden="true">🜨</div>
        <h1 class="success-title">Оплата прошла успешно</h1>
        <p class="success-sub">Кузница приняла твоё подношение.</p>

        <!-- Receipt -->
        <div class="receipt">
          <div class="receipt-row">
            <span class="receipt-label">Сумма</span>
            <span class="receipt-value">
              {{ payment.amount_crypto.toFixed(6) }} USDT
              <span class="receipt-equiv">≈ {{ payment.amount_rub.toFixed(2) }} ₽</span>
            </span>
          </div>

          <div v-if="payment.transaction_hash" class="receipt-row">
            <span class="receipt-label">Хэш транзакции</span>
            <span class="receipt-value mono">
              <a :href="tronscanUrl" target="_blank" rel="noopener" class="tx-link">
                {{ payment.transaction_hash.slice(0, 16) }}…{{ payment.transaction_hash.slice(-6) }}
              </a>
            </span>
          </div>

          <div class="receipt-row">
            <span class="receipt-label">Дата</span>
            <span class="receipt-value">{{ formattedDate }}</span>
          </div>

          <div v-if="cartItems.length" class="receipt-row items-row">
            <span class="receipt-label">Покупка</span>
            <ul class="receipt-items">
              <li v-for="(it, i) in cartItems" :key="i">
                {{ it.title }} × {{ it.quantity }}
              </li>
            </ul>
          </div>
        </div>

        <!-- Stub message about goods -->
        <div class="stub-block">
          <div class="stub-eyebrow">Тестовая среда</div>
          <p>
            Товар пока не выдаётся — система оплаты на стадии тестирования.
            Когда добавим инвентарь ключей, здесь появится <strong>«ваш ключ: XXX-XXX-XXX»</strong>
            с инструкцией активации.
          </p>
        </div>

        <!-- Actions -->
        <div class="success-actions">
          <RouterLink to="/catalog" class="action-btn primary">Вернуться в каталог</RouterLink>
          <RouterLink :to="{ name: 'my-payments' }" class="action-btn ghost">Мои платежи</RouterLink>
        </div>
      </template>

      <div v-else class="loading">Платёж не найден.</div>
    </div>
  </div>
</template>

<style scoped>
.success-view {
  min-height: calc(100vh - 73px);
  padding: 48px 16px 64px;
  display: flex;
  align-items: flex-start;
  justify-content: center;
}
.success-shell {
  max-width: 600px;
  width: 100%;
  text-align: center;
}
.loading {
  padding: 64px;
  color: var(--text-muted);
}

.success-sigil {
  font-size: 64px;
  color: var(--iron-warm);
  margin-bottom: 16px;
  filter: drop-shadow(0 0 20px rgba(226, 67, 16, 0.5));
  animation: sigil-glow 3s ease-in-out infinite;
}
@keyframes sigil-glow {
  0%, 100% { filter: drop-shadow(0 0 20px rgba(226, 67, 16, 0.5)); }
  50% { filter: drop-shadow(0 0 32px rgba(226, 67, 16, 0.85)); }
}

.success-title {
  font-size: 28px;
  margin: 0 0 8px;
  color: var(--text-bright);
  font-family: var(--font-display, inherit);
  letter-spacing: 0.02em;
}
.success-sub {
  margin: 0 0 32px;
  color: var(--text-parchment);
  font-style: italic;
}

.receipt {
  text-align: left;
  margin-bottom: 24px;
  padding: 22px 26px;
  border: 1px solid var(--iron-warm);
  border-radius: var(--r-md);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  box-shadow: var(--inset-iron-top), 0 8px 24px rgba(0,0,0,0.4);
}
.receipt-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 10px 0;
  border-bottom: 1px solid var(--iron-dark);
  gap: 16px;
}
.receipt-row:last-child { border-bottom: none; }
.receipt-row.items-row {
  flex-direction: column;
  align-items: stretch;
  gap: 6px;
}
.receipt-label {
  font-size: 11px;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: var(--iron-warm);
}
.receipt-value {
  font-size: 14px;
  color: var(--text-bright);
  text-align: right;
  word-break: break-all;
}
.receipt-equiv {
  display: block;
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 2px;
}
.mono { font-family: 'SF Mono', Monaco, Consolas, monospace; }
.tx-link {
  color: #ffba78;
  text-decoration: none;
  border-bottom: 1px dashed;
}
.tx-link:hover { color: var(--text-bright); }

.receipt-items {
  list-style: none;
  margin: 0;
  padding: 0;
  font-size: 13px;
  color: var(--text-parchment);
}
.receipt-items li {
  padding: 4px 0;
}
.receipt-items li::before {
  content: '⚒ ';
  color: var(--iron-warm);
  margin-right: 4px;
}

.stub-block {
  margin-bottom: 24px;
  padding: 16px 20px;
  border: 1px dashed var(--iron-dark);
  border-radius: var(--r-sm);
  background: rgba(0,0,0,0.2);
  text-align: left;
}
.stub-eyebrow {
  font-size: 10px;
  letter-spacing: 0.25em;
  text-transform: uppercase;
  color: var(--text-muted);
  margin-bottom: 6px;
}
.stub-block p {
  margin: 0;
  font-size: 13px;
  line-height: 1.5;
  color: var(--text-parchment);
}
.stub-block strong { color: var(--text-bright); }

.success-actions {
  display: flex;
  justify-content: center;
  gap: 12px;
  flex-wrap: wrap;
}
.action-btn {
  padding: 11px 22px;
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-sm);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  color: var(--text-parchment);
  text-decoration: none;
  font-size: 13px;
  letter-spacing: 0.05em;
  transition: all var(--dur-fast) var(--ease-smoke);
}
.action-btn.primary {
  border-color: var(--iron-warm);
  background: linear-gradient(180deg, rgba(226, 67, 16, 0.18) 0%, rgba(0,0,0,0) 100%);
  color: var(--text-bright);
}
.action-btn:hover {
  border-color: var(--iron-warm);
  color: var(--text-bright);
  transform: translateY(-1px);
  box-shadow: 0 0 10px rgba(226, 67, 16, 0.4);
}
</style>
