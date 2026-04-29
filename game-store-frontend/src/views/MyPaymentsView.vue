<script setup>
/**
 * Pay/A — история крипто-платежей юзера.
 * /profile/payments
 */
import { ref, onMounted, computed } from 'vue';
import { RouterLink } from 'vue-router';
import { useHead } from '@vueuse/head';
import api from '../api/axios';

useHead({
  title: 'Мои платежи — GameStore',
  meta: [{ name: 'robots', content: 'noindex, nofollow' }],
});

const payments = ref([]);
const loading = ref(true);

const formatDate = (s) => {
  if (!s) return '—';
  return new Date(s).toLocaleString('ru-RU', {
    day: 'numeric', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
};

const statusLabel = (s) => {
  switch (s) {
    case 'pending':   return 'Ожидание';
    case 'confirmed': return 'Подтверждён';
    case 'expired':   return 'Истёк';
    case 'failed':    return 'Ошибка';
    default:          return s;
  }
};

const linkFor = (p) => {
  if (p.status === 'confirmed') return { name: 'payment-success', params: { id: p.id } };
  if (p.status === 'expired' || p.status === 'failed') return { name: 'payment-expired', params: { id: p.id } };
  return { name: 'payment', params: { id: p.id } };
};

const tronscanUrl = (hash) => hash ? `https://tronscan.org/#/transaction/${hash}` : null;

onMounted(async () => {
  try {
    const { data } = await api.get('/payments');
    payments.value = data.data || [];
  } catch (e) {
    console.warn('[MyPayments] fetch failed', e);
  } finally {
    loading.value = false;
  }
});

const hasItems = computed(() => payments.value.length > 0);
</script>

<template>
  <div class="my-payments-view">
    <div class="payments-shell">

      <header class="payments-hero">
        <div class="hero-eyebrow">⚒ Хроника</div>
        <h1 class="hero-title">Мои платежи</h1>
        <p class="hero-sub">Все твои крипто-платежи — текущие и завершённые.</p>
      </header>

      <div v-if="loading" class="loading">Загрузка…</div>

      <div v-else-if="!hasItems" class="empty">
        <div class="empty-sigil">◈</div>
        <h3>Платежей пока нет</h3>
        <p>Когда ты совершишь первый крипто-платёж, он появится здесь.</p>
        <RouterLink to="/catalog" class="empty-link">К каталогу →</RouterLink>
      </div>

      <ul v-else class="payments-list">
        <li
          v-for="p in payments"
          :key="p.id"
          class="payment-row"
          :class="`status-${p.status}`"
        >
          <RouterLink :to="linkFor(p)" class="payment-row-link">
            <div class="row-status">
              <span class="status-dot"></span>
              <span class="status-label">{{ statusLabel(p.status) }}</span>
            </div>
            <div class="row-main">
              <div class="row-amount">
                {{ Number(p.amount_crypto).toFixed(6) }} USDT
                <span class="row-equiv">≈ {{ Number(p.amount_rub).toFixed(2) }} ₽</span>
              </div>
              <div v-if="p.transaction_hash" class="row-hash mono">
                <a
                  :href="tronscanUrl(p.transaction_hash)"
                  target="_blank"
                  rel="noopener"
                  @click.stop
                >
                  {{ p.transaction_hash.slice(0, 10) }}…{{ p.transaction_hash.slice(-6) }}
                </a>
              </div>
            </div>
            <div class="row-date">{{ formatDate(p.created_at) }}</div>
          </RouterLink>
        </li>
      </ul>

    </div>
  </div>
</template>

<style scoped>
.my-payments-view {
  min-height: calc(100vh - 73px);
  padding: 32px 16px 64px;
}
.payments-shell {
  max-width: 760px;
  margin: 0 auto;
}

.payments-hero {
  padding: 20px 24px;
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-md);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  margin-bottom: 18px;
}
.hero-eyebrow {
  font-size: 11px;
  letter-spacing: 0.25em;
  text-transform: uppercase;
  color: var(--iron-warm);
  margin-bottom: 4px;
}
.hero-title {
  font-size: 24px;
  margin: 0 0 4px;
  color: var(--text-bright);
}
.hero-sub {
  margin: 0;
  color: var(--text-muted);
  font-size: 13px;
}

.loading, .empty {
  padding: 48px 16px;
  text-align: center;
  color: var(--text-muted);
}
.empty-sigil {
  font-size: 40px;
  color: var(--iron-warm);
  margin-bottom: 12px;
  opacity: 0.6;
}
.empty h3 { color: var(--text-bright); margin: 0 0 8px; }
.empty p { margin: 0 0 16px; }
.empty-link {
  color: var(--iron-warm);
  text-decoration: none;
  font-size: 13px;
  letter-spacing: 0.05em;
}
.empty-link:hover { color: var(--text-bright); }

.payments-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.payment-row {
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-sm);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  transition: all var(--dur-fast) var(--ease-smoke);
}
.payment-row:hover {
  border-color: var(--iron-warm);
  box-shadow: var(--glow-ember-soft);
}
.payment-row-link {
  display: grid;
  grid-template-columns: 130px 1fr auto;
  align-items: center;
  gap: 16px;
  padding: 14px 18px;
  text-decoration: none;
  color: inherit;
}
.row-status {
  display: flex;
  align-items: center;
  gap: 8px;
}
.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--text-muted);
}
.status-label {
  font-size: 11px;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--text-muted);
}
.payment-row.status-pending .status-dot {
  background: #ffba78;
  box-shadow: 0 0 8px rgba(255, 186, 120, 0.6);
  animation: blink 1.5s ease-in-out infinite;
}
@keyframes blink { 50% { opacity: 0.4; } }
.payment-row.status-pending .status-label { color: #ffba78; }
.payment-row.status-confirmed .status-dot { background: #6cbf6c; box-shadow: 0 0 8px rgba(108, 191, 108, 0.5); }
.payment-row.status-confirmed .status-label { color: #8edb8e; }
.payment-row.status-expired .status-dot,
.payment-row.status-failed .status-dot { background: #b8341a; }
.payment-row.status-expired .status-label,
.payment-row.status-failed .status-label { color: #ff8a4c; }

.row-main { display: flex; flex-direction: column; gap: 2px; }
.row-amount {
  font-size: 14px;
  color: var(--text-bright);
  font-weight: 500;
}
.row-equiv {
  font-size: 12px;
  color: var(--text-muted);
  font-weight: 400;
  margin-left: 8px;
}
.row-hash a {
  font-size: 11px;
  color: #ffba78;
  text-decoration: none;
}
.row-hash a:hover { text-decoration: underline; }
.row-date {
  font-size: 11px;
  color: var(--text-muted);
  white-space: nowrap;
}

.mono { font-family: 'SF Mono', Monaco, Consolas, monospace; }

@media (max-width: 600px) {
  .payment-row-link {
    grid-template-columns: 1fr;
    gap: 6px;
  }
  .row-date { text-align: left; }
}
</style>
