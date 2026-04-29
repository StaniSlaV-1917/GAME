<template>
  <div class="admin-page-container">
    <router-link to="/admin" class="admin-back-button">← Назад в админ-панель</router-link>
    <div class="page-header">
      <div>
        <h1 class="admin-page-title">История заказов</h1>
        <p class="admin-page-subtitle">Отслеживайте и управляйте всеми заказами клиентов.</p>
      </div>
      <div class="actions">
        <button class="admin-button admin-button-secondary" @click="exportOrdersReport">Экспорт (CSV)</button>
      </div>
    </div>

    <div v-if="error" class="admin-error">{{ error }}</div>
    <div v-if="loading" class="admin-loading">Загрузка данных...</div>

    <div v-else-if="!orders.length" class="admin-empty">
      Заказов пока не поступало.
    </div>

    <div v-else class="admin-table-wrapper">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Заказ ID</th>
            <th>Клиент</th>
            <th>Дата</th>
            <th>Сумма ₽</th>
            <th>Статус</th>
            <th>Поз.</th>
            <th>Оплата</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="order in orders" :key="order.id">
            <td class="order-id">#{{ order.id }}</td>
            <td>
              <div>{{ order.user?.fullname || 'N/A' }}</div>
              <div class="user-email">{{ order.user?.email || 'N/A' }}</div>
            </td>
            <td>{{ new Date(order.order_date).toLocaleString('ru-RU') }}</td>
            <td>{{ Number(order.total).toFixed(2) }} ₽</td>
            <td>
              <select class="table-select" :class="`status-${order.status}`" v-model="order.status" @change="updateStatus(order, $event.target.value)">
                <option v-for="s in statusOptions" :key="s" :value="s">{{ statusLabel(s) }}</option>
              </select>
            </td>
            <td>{{ order.items.length }}</td>
            <!-- Pay/A.3 — колонка с инфо о крипто-платеже.
                 Старые заказы (до Pay/A) → latest_payment === null → "—".
                 Новые заказы → блок с валютой, суммой, статусом и tx hash. -->
            <td class="payment-cell">
              <template v-if="order.latest_payment">
                <div class="pay-line">
                  <span class="pay-curr">{{ currencyLabel(order.latest_payment.crypto_currency) }}</span>
                  <span class="pay-status" :class="`pst-${effectivePayStatus(order.latest_payment)}`">
                    {{ payStatusLabel(effectivePayStatus(order.latest_payment)) }}
                  </span>
                </div>
                <div class="pay-amount mono">
                  {{ Number(order.latest_payment.amount_crypto).toFixed(6) }}
                  {{ currencyUnit(order.latest_payment.crypto_currency) }}
                </div>
                <a
                  v-if="order.latest_payment.transaction_hash"
                  :href="explorerUrl(order.latest_payment)"
                  target="_blank" rel="noopener"
                  class="pay-tx mono"
                >
                  {{ order.latest_payment.transaction_hash.slice(0, 10) }}…
                </a>
              </template>
              <span v-else class="pay-empty">—</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="toastVisible" class="admin-toast">{{ toastText }}</div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../api/axios';

const orders = ref([]);
const loading = ref(false);
const error = ref('');
const statusOptions = ['created', 'paid', 'shipped', 'completed', 'cancelled'];

const toastText = ref('');
const toastVisible = ref(false);

const showToast = (text) => {
  toastText.value = text;
  toastVisible.value = true;
  setTimeout(() => { toastVisible.value = false; }, 2500);
};

const loadOrders = async () => {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await api.get('/admin/orders');
    orders.value = data;
  } catch (e) {
    console.error(e);
    error.value = 'Не удалось загрузить заказы.';
  } finally {
    loading.value = false;
  }
};

const exportOrdersReport = async () => {
  try {
    const response = await api.get('/admin/reports/orders', { responseType: 'blob' });
    const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `orders_report_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
    link.remove();
  } catch (e) {
    console.error(e);
    showToast('Ошибка при экспорте отчёта');
  }
};

const statusLabel = (status) => {
  const map = {
    created: 'Создан',
    paid: 'Оплачен',
    shipped: 'Отправлен',
    completed: 'Завершён',
    cancelled: 'Отменён',
  };
  return map[status] || status;
};

// Pay/A.3 — helpers для колонки «Оплата»
const currencyLabel = (code) => {
  switch (code) {
    case 'USDT_TRC20': return 'USDT TRC-20';
    case 'TRX':        return 'TRX';
    case 'USDT_BEP20': return 'USDT BEP-20';
    default:           return code || '—';
  }
};
const currencyUnit = (code) => code === 'TRX' ? 'TRX' : 'USDT';

const payStatusLabel = (s) => {
  const m = {
    pending:   'ожид.',
    confirmed: 'оплачено',
    expired:   'истёк',
    failed:    'ошибка',
  };
  return m[s] || s;
};

// Pending с истёкшим expires_at показываем как expired визуально
const effectivePayStatus = (p) => {
  if (!p) return null;
  if (p.status === 'pending' && p.expires_at && new Date(p.expires_at) < new Date()) {
    return 'expired';
  }
  return p.status;
};

const explorerUrl = (p) => {
  if (!p?.transaction_hash) return null;
  return p.crypto_currency === 'USDT_BEP20'
    ? `https://bscscan.com/tx/${p.transaction_hash}`
    : `https://tronscan.org/#/transaction/${p.transaction_hash}`;
};

const updateStatus = async (order, newStatus) => {
  try {
    const { data } = await api.put(`/admin/orders/${order.id}/status`, { status: newStatus });
    order.status = data.order.status;
    showToast(`Статус заказа #${order.id} обновлён`);
  } catch (e) {
    console.error(e);
    showToast('Ошибка обновления статуса');
  }
};

onMounted(loadOrders);
</script>

<style>
@import '../assets/admin.css';

/* AdminOrders — локальные добавки. Admin-toast/общие стили — из admin.css */
.order-id {
  font-family: var(--font-display);
  font-weight: 700;
  color: var(--ember-gold);
  text-shadow: 0 0 6px rgba(255, 201, 121, 0.3);
}

.user-email {
  font-family: var(--font-ui);
  font-size: 0.8rem;
  color: var(--text-ash);
}

.table-select {
  min-width: 148px;
  padding: 7px 12px;
  border: 1px solid var(--iron-mid);
  background: linear-gradient(180deg, rgba(8, 6, 10, 0.7), rgba(18, 16, 13, 0.85));
  color: var(--text-bone);
  font-family: var(--font-ui);
  font-size: 0.88rem;
  outline: none;
  box-shadow: var(--inset-iron-top);
  transition: border-color 0.2s var(--ease-smoke), box-shadow 0.2s var(--ease-smoke);
}

.table-select:focus {
  border-color: var(--ember-flame);
  box-shadow: var(--inset-iron-top), 0 0 0 3px rgba(226, 67, 16, 0.15);
}

/* Статусы заказов — ember-палитра */
.table-select.status-created   { color: var(--text-bone); }
.table-select.status-paid      { color: var(--ember-spark); }
.table-select.status-shipped   { color: var(--ember-glow); }
.table-select.status-completed { color: var(--ember-gold); }
.table-select.status-cancelled { color: #ffb4a8; }

/* Pay/A.3 — payment-колонка: компактный stack валюта/сумма/tx */
.payment-cell {
  min-width: 180px;
  font-size: 11px;
  line-height: 1.4;
}
.pay-line {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
  margin-bottom: 2px;
}
.pay-curr {
  font-size: 11px;
  letter-spacing: 0.05em;
  color: var(--text-bright);
  white-space: nowrap;
}
.pay-status {
  font-size: 10px;
  padding: 1px 6px;
  border-radius: 8px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  border: 1px solid currentColor;
}
.pst-pending   { color: #ffba78; background: rgba(255, 186, 120, 0.1); }
.pst-confirmed { color: #8edb8e; background: rgba(108, 191, 108, 0.12); }
.pst-expired,
.pst-failed    { color: #ff8a4c; background: rgba(184, 52, 26, 0.12); }
.pay-amount {
  color: var(--text-parchment);
  font-size: 11px;
  margin-bottom: 2px;
}
.pay-tx {
  display: inline-block;
  color: #ffba78;
  text-decoration: none;
  font-size: 10px;
}
.pay-tx:hover { text-decoration: underline; }
.pay-empty {
  color: var(--text-muted);
  font-size: 16px;
}
.mono { font-family: 'SF Mono', Monaco, Consolas, monospace; }
</style>
