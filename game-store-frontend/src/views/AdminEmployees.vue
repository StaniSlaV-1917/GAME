<template>
  <div class="admin-page-container">
    <router-link to="/admin" class="admin-back-button">← Назад в админ-панель</router-link>
    <div class="page-header">
      <div>
        <h1 class="admin-page-title">Управление персоналом</h1>
        <p class="admin-page-subtitle">Добавление, изменение и удаление учетных записей сотрудников.</p>
      </div>
      <div class="actions">
        <button class="admin-button admin-button-primary" @click="openCreateModal">+ Добавить сотрудника</button>
      </div>
    </div>

    <div v-if="error" class="admin-error">{{ error }}</div>
    <div v-if="loading" class="admin-loading">Загрузка данных...</div>

    <div v-else-if="!employees.length" class="admin-empty">Сотрудники не найдены.</div>

    <!-- Employees Table -->
    <div v-else class="admin-table-wrapper">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>ФИО</th>
            <th>Email</th>
            <th>Роль</th>
            <th>Дата добавления</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="employee in employees" :key="employee.id">
            <td>#{{ employee.id }}</td>
            <td>{{ employee.fullname }}</td>
            <td>{{ employee.email }}</td>
            <td>
              <span class="role-badge" :class="`role-${employee.role}`">{{ employee.role }}</span>
            </td>
            <td>{{ new Date(employee.created_at).toLocaleDateString() }}</td>
            <td class="action-buttons">
              <div class="button-group">
                <button class="admin-button admin-button-secondary" @click="openEditModal(employee)">Изменить</button>
                <button class="admin-button admin-button-danger" @click="deleteEmployee(employee)">Удалить</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Edit/Create Modal -->
    <AdminEmployeeEditModal 
      v-model="isModalOpen" 
      :employee="selectedEmployee" 
      @saved="handleEmployeeSaved" 
    />

    <div v-if="toastVisible" class="admin-toast">{{ toastText }}</div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../api/axios';
import AdminEmployeeEditModal from './AdminEmployeeEditModal.vue';

const employees = ref([]);
const loading = ref(false);
const error = ref('');

const isModalOpen = ref(false);
const selectedEmployee = ref(null);

const toastText = ref('');
const toastVisible = ref(false);

const showToast = (text) => {
  toastText.value = text;
  toastVisible.value = true;
  setTimeout(() => { toastVisible.value = false; }, 2500);
};

const loadEmployees = async () => {
  loading.value = true;
  error.value = '';
  try {
    const response = await api.get('/admin/employees');
    employees.value = response.data;
  } catch (err) {
    console.error('Ошибка загрузки сотрудников:', err);
    error.value = 'Не удалось загрузить список сотрудников.';
  }
  loading.value = false;
};

const openCreateModal = () => {
  selectedEmployee.value = null;
  isModalOpen.value = true;
};

const openEditModal = (employee) => {
  selectedEmployee.value = { ...employee };
  isModalOpen.value = true;
};

const handleEmployeeSaved = (savedEmployee) => {
  const index = employees.value.findIndex(e => e.id === savedEmployee.id);
  if (index !== -1) {
    employees.value[index] = savedEmployee;
    showToast('Сотрудник успешно обновлен.');
  } else {
    employees.value.push(savedEmployee);
    showToast('Новый сотрудник добавлен.');
  }
};

const deleteEmployee = async (employee) => {
  if (!confirm(`Вы уверены, что хотите удалить сотрудника ${employee.fullname}?`)) return;

  try {
    await api.delete(`/admin/employees/${employee.id}`);
    employees.value = employees.value.filter(e => e.id !== employee.id);
    showToast('Сотрудник удален.');
  } catch (err) {
    console.error('Ошибка удаления сотрудника:', err);
    showToast('Ошибка при удалении сотрудника.');
  }
};

onMounted(loadEmployees);
</script>

<style>
@import '../assets/admin.css';

/* AdminEmployees — роли в ember/bronze палитре */
.role-badge {
  display: inline-block;
  font-family: var(--font-ui);
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 1.8px;
  text-transform: uppercase;
  padding: 4px 12px;
  background: rgba(8, 6, 10, 0.55);
  border: 1px solid currentColor;
}

.role-badge.role-admin   { color: var(--ember-gold); }
.role-badge.role-manager { color: var(--bronze); }
</style>
