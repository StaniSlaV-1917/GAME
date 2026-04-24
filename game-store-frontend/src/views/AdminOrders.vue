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
            <th>Сумма</th>
            <th>Статус</th>
            <th>Кол-во позиций</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="order in orders" :key="order.id">
            <td class="order-id">#{{ order.id }}</td>
            <td>
              <div>{{ order.user?.fullname || 'N/A' }}</div>
              <div class="user-email">{{ order.user?.email || 'N/A' }}</div>
            </td>
            <td>{{ new Date(order.order_date).toLocaleString() }}</td>
            <td>{{ Number(order.total).toFixed(2) }} ₽</td>
            <td>
              <select class="table-select" :class="`status-${order.status}`" v-model="order.status" @change="updateStatus(order, $event.target.value)">
                <option v-for="s in statusOptions" :key="s" :value="s">{{ statusLabel(s) }}</option>
              </select>
            </td>
            <td>{{ order.items.length }}</td>
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
</style>
