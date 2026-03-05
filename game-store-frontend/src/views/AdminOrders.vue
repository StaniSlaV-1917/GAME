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

.order-id {
  font-weight: 600;
  color: #a5b4fc;
}

.user-email {
  font-size: 0.8rem;
  color: #9ca3af;
}

.table-select {
  min-width: 140px;
  padding: 6px 10px;
  border-radius: 6px;
  border: 1px solid #374151;
  background-color: #1f2937;
  color: #e5e7eb;
  font-size: 0.9rem;
  outline: none;
  transition: all 0.2s ease;
}

.table-select:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
}

/* Status-specific colors */
.table-select.status-created { color: #f3f4f6; }
.table-select.status-paid { color: #86efac; }
.table-select.status-shipped { color: #93c5fd; }
.table-select.status-completed { color: #6ee7b7; }
.table-select.status-cancelled { color: #fca5a5; }

.admin-toast {
  position: fixed;
  right: 20px;
  bottom: 20px;
  background: #1f2937;
  border: 1px solid #374151;
  color: #e5e7eb;
  padding: 12px 18px;
  border-radius: 8px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
  z-index: 1300;
  font-size: 0.95rem;
  animation: toast-fade-in 0.3s ease-out;
}

@keyframes toast-fade-in {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}
</style>
