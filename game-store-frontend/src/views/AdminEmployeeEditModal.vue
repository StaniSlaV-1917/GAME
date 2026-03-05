<template>
  <div v-if="modelValue" class="modal-backdrop" @click.self="closeModal">
    <div class="modal-content">
      <header class="modal-header">
        <h2 class="modal-title">{{ isEditMode ? 'Редактировать сотрудника' : 'Добавить сотрудника' }}</h2>
        <button class="modal-close-button" @click="closeModal">×</button>
      </header>
      <main class="modal-body">
        <form @submit.prevent="saveEmployee">
          <div class="form-group">
            <label for="fullname">ФИО</label>
            <input type="text" id="fullname" class="form-input" v-model="form.fullname" required>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" class="form-input" v-model="form.email" required>
          </div>
          <div class="form-group" v-if="!isEditMode">
            <label for="password">Пароль</label>
            <input type="password" id="password" class="form-input" v-model="form.password" required>
          </div>
          <div class="form-group">
            <label for="role">Роль</label>
            <select id="role" class="form-input" v-model="form.role">
              <option value="manager">Менеджер</option>
              <option value="admin">Администратор</option>
            </select>
          </div>
        </form>
      </main>
      <footer class="modal-footer">
        <button class="admin-button admin-button-secondary" @click="closeModal">Отмена</button>
        <button class="admin-button admin-button-primary" @click="saveEmployee">Сохранить</button>
      </footer>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import api from '../api/axios';

const props = defineProps({
  modelValue: Boolean,
  employee: Object,
});

const emit = defineEmits(['update:modelValue', 'saved']);

const form = ref({});

const isEditMode = computed(() => !!props.employee);

watch(() => props.employee, (newEmployee) => {
  form.value = newEmployee ? { ...newEmployee } : { fullname: '', email: '', password: '', role: 'manager' };
}, { immediate: true });

const closeModal = () => {
  emit('update:modelValue', false);
};

const saveEmployee = async () => {
  const url = isEditMode.value ? `/admin/employees/${props.employee.id}` : '/admin/employees';
  const method = isEditMode.value ? 'put' : 'post';

  try {
    const response = await api[method](url, form.value);
    emit('saved', response.data);
    closeModal();
  } catch (error) {
    console.error('Ошибка сохранения сотрудника:', error);
    // Optionally, show an error message to the user within the modal
    alert('Ошибка при сохранении данных.');
  }
};
</script>

<style scoped>
@import '../assets/admin.css';

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  color: #d1d5db;
  font-weight: 500;
}

.form-input {
  width: 100%;
  padding: 10px 14px;
  border-radius: 8px;
  border: 1px solid #374151;
  background-color: #1f2937;
  color: #e5e7eb;
  font-size: 1rem;
  outline: none;
  transition: all 0.2s ease;
}

.form-input:focus {
  border-color: #3b82f6;
  background-color: #111827;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}
</style>
