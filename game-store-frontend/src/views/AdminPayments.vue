<script setup>
/**
 * Pay/A — админский view всех крипто-платежей.
 *
 * Возможности:
 *   • Stats-полоска: total / confirmed / pending / expired / sum_rub
 *   • Фильтры: status / currency / search (по юзеру)
 *   • Пагинация (per_page=20)
 *   • Линки на TronScan / BscScan по транзакциям
 *
 * URL: /admin/payments
 */
import { ref, onMounted, computed, watch } from 'vue';
import { RouterLink } from 'vue-router';
import api from '../api/axios';

const items = ref([]);
const stats = ref({
  total_count: 0,
  confirmed_count: 0,
  pending_count: 0,
  expired_count: 0,
  confirmed_sum_rub: 0,
});
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(false);
const error = ref('');

// Фильтры
const filters = ref({
  status: '',
  currency: '',
  search: '',
  per_page: 20,
});
const page = ref(1);

let searchDebounce = null;

const load = async () => {
  loading.value = true;
  error.value = '';
  try {
    const params = {
      page: page.value,
      per_page: filters.value.per_page,
    };
    if (filters.value.status) params.status = filters.value.status;
    if (filters.value.currency) params.currency = filters.value.currency;
    if (filters.value.search) params.search = filters.value.search;

    const { data } = await api.get('/admin/payments', { params });
    items.value = data.data || [];
    meta.value = data.meta || meta.value;
    stats.value = data.stats || stats.value;
  } catch (e) {
    error.value = 'Не удалось загрузить платежи. ' + (e?.response?.data?.message || '');
  } finally {
    loading.value = false;
  }
};

onMounted(load);

// При смене status/currency сразу перезагружаем
watch(() => [filters.value.status, filters.value.currency, filters.value.per_page], () => {
  page.value = 1;
  load();
});

// Search — с debounce'ом
watch(() => filters.value.search, (val) => {
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(() => {
    page.value = 1;
    load();
  }, 350);
});

const goPage = (n) => {
  if (n < 1 || n > meta.value.last_page) return;
  page.value = n;
  load();
};

const explorerUrl = (p) => {
  if (!p?.transaction_hash) return null;
  return p.crypto_currency === 'USDT_BEP20'
    ? `https://bscscan.com/tx/${p.transaction_hash}`
    : `https://tronscan.org/#/transaction/${p.transaction_hash}`;
};

const currencyLabel = (code) => {
  switch (code) {
    case 'USDT_TRC20': return 'USDT TRC-20';
    case 'TRX':        return 'TRX';
    case 'USDT_BEP20': return 'USDT BEP-20';
    default:           return code || '—';
  }
};
const currencyUnit = (code) => code === 'TRX' ? 'TRX' : 'USDT';

const statusLabel = (s) => {
  switch (s) {
    case 'pending':   return 'Ожидание';
    case 'confirmed': return 'Подтверждён';
    case 'expired':   return 'Истёк';
    case 'failed':    return 'Ошибка';
    default:          return s;
  }
};

const formatDate = (s) => {
  if (!s) return '—';
  return new Date(s).toLocaleString('ru-RU', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
};

// Эффективный статус — pending с истёкшим expires_at показываем как expired
const effectiveStatus = (p) => {
  if (p.status === 'pending' && p.expires_at && new Date(p.expires_at) < new Date()) {
    return 'expired';
  }
  return p.status;
};
</script>

<template>
  <div class="admin-page-container">
    <RouterLink to="/admin" class="admin-back-button">← Назад в админ-панель</RouterLink>

    <div class="page-header">
      <div>
        <h1 class="admin-page-title">Сокровищница</h1>
        <p class="admin-page-subtitle">Все крипто-платежи — текущие и завершённые.</p>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-val">{{ stats.total_count.toLocaleString('ru-RU') }}</div>
        <div class="stat-label">Всего</div>
      </div>
      <div class="stat-card success">
        <div class="stat-val">{{ stats.confirmed_count.toLocaleString('ru-RU') }}</div>
        <div class="stat-label">Подтверждено</div>
      </div>
      <div class="stat-card warning">
        <div class="stat-val">{{ stats.pending_count.toLocaleString('ru-RU') }}</div>
        <div class="stat-label">В ожидании</div>
      </div>
      <div class="stat-card danger">
        <div class="stat-val">{{ stats.expired_count.toLocaleString('ru-RU') }}</div>
        <div class="stat-label">Истекло</div>
      </div>
      <div class="stat-card highlight">
        <div class="stat-val">{{ Number(stats.confirmed_sum_rub).toLocaleString('ru-RU', { maximumFractionDigits: 2 }) }} ₽</div>
        <div class="stat-label">В казне (подтв.)</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-row">
      <input
        v-model="filters.search"
        class="admin-search-input"
        placeholder="Поиск по юзеру (имя/username/email)…"
      />
      <select v-model="filters.status" class="admin-select">
        <option value="">Все статусы</option>
        <option value="pending">Ожидание</option>
        <option value="confirmed">Подтверждён</option>
        <option value="expired">Истёк</option>
        <option value="failed">Ошибка</option>
      </select>
      <select v-model="filters.currency" class="admin-select">
        <option value="">Все валюты</option>
        <option value="USDT_TRC20">USDT TRC-20</option>
        <option value="TRX">TRX</option>
        <option value="USDT_BEP20">USDT BEP-20</option>
      </select>
    </div>

    <div v-if="error" class="admin-error">{{ error }}</div>
    <div v-if="loading" class="admin-loading">Загрузка…</div>

    <div v-else-if="!items.length" class="admin-empty">
      Платежей с такими фильтрами нет.
    </div>

    <div v-else class="admin-table-wrapper">
      <table class="admin-table payments-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Юзер</th>
            <th>Валюта</th>
            <th>Сумма</th>
            <th>₽</th>
            <th>Статус</th>
            <th>Tx hash</th>
            <th>Создан</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in items" :key="p.id" :class="`row-status-${effectiveStatus(p)}`">
            <td class="tnum">#{{ p.id }}</td>
            <td>
              <div class="user-name">{{ p.user?.fullname || '—' }}</div>
              <div class="user-meta">@{{ p.user?.username || p.user?.email || 'no-handle' }}</div>
            </td>
            <td>
              <span class="currency-tag">{{ currencyLabel(p.crypto_currency) }}</span>
            </td>
            <td class="tnum amount-cell">
              {{ Number(p.amount_crypto).toFixed(6) }}
              <span class="unit">{{ currencyUnit(p.crypto_currency) }}</span>
            </td>
            <td class="tnum">{{ Number(p.amount_rub).toFixed(2) }}</td>
            <td>
              <span class="status-pill" :class="`status-${effectiveStatus(p)}`">
                {{ statusLabel(effectiveStatus(p)) }}
              </span>
            </td>
            <td class="tnum">
              <a v-if="p.transaction_hash" :href="explorerUrl(p)" target="_blank" rel="noopener" class="tx-link">
                {{ p.transaction_hash.slice(0, 8) }}…
              </a>
              <span v-else class="muted">—</span>
            </td>
            <td class="tnum date-cell">{{ formatDate(p.created_at) }}</td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div class="pagination" v-if="meta.last_page > 1">
        <button class="pg-btn" :disabled="page <= 1" @click="goPage(page - 1)">←</button>
        <span class="pg-info">Стр. {{ page }} из {{ meta.last_page }} · всего {{ meta.total }}</span>
        <button class="pg-btn" :disabled="page >= meta.last_page" @click="goPage(page + 1)">→</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import '../assets/admin.css';

.stats-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
.stat-card {
  padding: 16px 18px;
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-sm);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  text-align: left;
}
.stat-card.success { border-color: rgba(108, 191, 108, 0.4); }
.stat-card.warning { border-color: rgba(255, 186, 120, 0.4); }
.stat-card.danger  { border-color: rgba(184, 52, 26, 0.4); }
.stat-card.highlight {
  border-color: var(--iron-warm);
  background: linear-gradient(180deg, rgba(226, 67, 16, 0.15) 0%, rgba(0,0,0,0) 100%);
}
.stat-val {
  font-size: 22px;
  font-weight: 600;
  color: var(--text-bright);
  margin-bottom: 4px;
}
.stat-label {
  font-size: 11px;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: var(--text-muted);
}

.filters-row {
  display: flex;
  gap: 10px;
  margin-bottom: 18px;
  flex-wrap: wrap;
}
.filters-row input {
  flex: 1;
  min-width: 220px;
}
.admin-select {
  padding: 9px 14px;
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-sm);
  background: rgba(0,0,0,0.3);
  color: var(--text-bone);
  font-family: inherit;
  font-size: 13px;
}

.payments-table th, .payments-table td { padding: 10px 12px; }
.payments-table .tnum { font-family: 'SF Mono', Monaco, Consolas, monospace; }
.amount-cell .unit {
  font-size: 11px;
  color: var(--text-muted);
  margin-left: 4px;
}
.user-name { color: var(--text-bright); font-size: 13px; }
.user-meta {
  font-size: 11px;
  color: var(--text-muted);
  letter-spacing: 0.05em;
}
.currency-tag {
  font-size: 11px;
  padding: 3px 8px;
  border: 1px solid var(--iron-dark);
  border-radius: 4px;
  background: rgba(0,0,0,0.3);
  letter-spacing: 0.05em;
  white-space: nowrap;
}
.status-pill {
  display: inline-block;
  font-size: 11px;
  padding: 3px 10px;
  border-radius: 12px;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  border: 1px solid currentColor;
}
.status-pill.status-pending {
  color: #ffba78;
  background: rgba(255, 186, 120, 0.1);
}
.status-pill.status-confirmed {
  color: #8edb8e;
  background: rgba(108, 191, 108, 0.1);
}
.status-pill.status-expired,
.status-pill.status-failed {
  color: #ff8a4c;
  background: rgba(184, 52, 26, 0.12);
}

.tx-link {
  color: #ffba78;
  text-decoration: none;
  font-size: 12px;
}
.tx-link:hover { text-decoration: underline; }
.muted { color: var(--text-muted); }
.date-cell { font-size: 11px; color: var(--text-muted); white-space: nowrap; }

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 18px;
  padding: 14px;
}
.pg-btn {
  width: 36px;
  height: 36px;
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-sm);
  background: rgba(0,0,0,0.3);
  color: var(--text-bright);
  cursor: pointer;
  font-family: inherit;
  font-size: 16px;
  transition: all var(--dur-fast);
}
.pg-btn:hover:not(:disabled) {
  border-color: var(--iron-warm);
  color: var(--iron-warm);
}
.pg-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.pg-info {
  font-size: 12px;
  color: var(--text-muted);
  letter-spacing: 0.05em;
}
</style>
