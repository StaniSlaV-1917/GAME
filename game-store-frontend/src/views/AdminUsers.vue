<template>
  <div class="admin-page-container">
    <router-link to="/admin" class="admin-back-button">← Назад в админ-панель</router-link>
    <div class="page-header">
      <div>
        <h1 class="admin-page-title">Управление пользователями</h1>
        <p class="admin-page-subtitle">Просмотр, редактирование и назначение ролей пользователям.</p>
      </div>
      <div class="actions">
        <button class="admin-button admin-button-secondary" @click="exportUsersReport">Экспорт (CSV)</button>
      </div>
    </div>

    <div v-if="error" class="admin-error">{{ error }}</div>
    <div v-if="loading" class="admin-loading">Загрузка данных...</div>

    <div v-else-if="!users.length" class="admin-empty">
      Пользователи не найдены.
    </div>

    <div v-else class="admin-table-wrapper">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>ФИО</th>
            <th>Email / Телефон</th>
            <th>Роль</th>
            <th>Дата регистрации</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.id">
            <td>#{{ user.id }}</td>
            <td>
              <input type="text" class="table-input" v-model="user.fullname" @change="markAsChanged(user)">
            </td>
            <td>
              <div class="contact-info">
                <span>{{ user.email }}</span>
                <span class="phone">{{ user.phone }}</span>
              </div>
            </td>
            <td>
              <select class="table-select" v-model="user.role" @change="changeRole(user)">
                <option v-for="r in roles" :key="r" :value="r" :disabled="r === 'admin' && currentUserRole !== 'admin'">
                  {{ r }}
                </option>
              </select>
            </td>
            <td>{{ new Date(user.reg_date).toLocaleDateString() }}</td>
            <td class="action-buttons">
              <button class="admin-button admin-button-primary" :disabled="!isChanged(user)" @click="saveUser(user)">
                Сохранить
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="toastVisible" class="admin-toast">{{ toastText }}</div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '../stores/auth';
import api from '../api/axios';

const authStore = useAuthStore();
const currentUserRole = computed(() => authStore.user?.role);

const users = ref([]);
const originalUsers = ref([]);
const changedUsers = ref(new Set());

const loading = ref(false);
const error = ref('');
const roles = ['user', 'manager', 'admin'];

const toastText = ref('');
const toastVisible = ref(false);

const showToast = (text) => {
  toastText.value = text;
  toastVisible.value = true;
  setTimeout(() => {
    toastVisible.value = false;
  }, 2500);
};

const loadUsers = async () => {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await api.get('/admin/users');
    users.value = data;
    originalUsers.value = JSON.parse(JSON.stringify(data)); // Deep copy
  } catch (e) {
    console.error(e);
    error.value = 'Не удалось загрузить пользователей. Пожалуйста, обновите страницу.';
  } finally {
    loading.value = false;
  }
};

const exportUsersReport = async () => {
  try {
    const response = await api.get('/admin/reports/users', { responseType: 'blob' });
    const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `users_report_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
    link.remove();
  } catch (e) {
    console.error(e);
    showToast('Ошибка при экспорте отчёта');
  }
};

const markAsChanged = (user) => {
  const originalUser = originalUsers.value.find(u => u.id === user.id);
  if (originalUser && originalUser.fullname !== user.fullname) {
    changedUsers.value.add(user.id);
  } else {
    changedUsers.value.delete(user.id);
  }
};

const isChanged = (user) => {
  return changedUsers.value.has(user.id);
};

const saveUser = async (user) => {
  try {
    await api.put(`/admin/users/${user.id}`, { fullname: user.fullname });
    const originalUser = originalUsers.value.find(u => u.id === user.id);
    if (originalUser) {
      originalUser.fullname = user.fullname;
    }
    changedUsers.value.delete(user.id);
    showToast(`Пользователь #${user.id} обновлён`);
  } catch (e) {
    console.error(e);
    showToast('Ошибка сохранения данных');
  }
};

const changeRole = async (user) => {
  try {
    const { data } = await api.put(`/admin/users/${user.id}/role`, { role: user.role });
    user.role = data.user.role;
    const originalUser = originalUsers.value.find(u => u.id === user.id);
    if (originalUser) {
      originalUser.role = data.user.role;
    }
    showToast(`Роль для #${user.id} изменена на ${user.role}`);
  } catch (e) {
    console.error(e);
    const originalUser = originalUsers.value.find(u => u.id === user.id);
    if (originalUser) {
      user.role = originalUser.role; // Revert on failure
    }
    showToast('Ошибка смены роли');
  }
};

onMounted(loadUsers);
</script>

<style>
@import '../assets/admin.css';

/* AdminUsers — поля внутри таблицы и контактные данные */
.table-input, .table-select {
  width: 100%;
  padding: 9px 13px;
  border: 1px solid var(--iron-mid);
  background: linear-gradient(180deg, rgba(8, 6, 10, 0.7), rgba(18, 16, 13, 0.85));
  color: var(--text-bone);
  font-family: var(--font-body);
  font-size: 0.92rem;
  outline: none;
  box-shadow: var(--inset-iron-top);
  transition: border-color 0.2s var(--ease-smoke), box-shadow 0.2s var(--ease-smoke);
}

.table-input:focus, .table-select:focus {
  border-color: var(--ember-flame);
  box-shadow: var(--inset-iron-top), 0 0 0 3px rgba(226, 67, 16, 0.14);
}

.contact-info {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.contact-info .phone {
  font-family: var(--font-ui);
  font-size: 0.82rem;
  color: var(--text-ash);
  letter-spacing: 0.3px;
}
</style>
