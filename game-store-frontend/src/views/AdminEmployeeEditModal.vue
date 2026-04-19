<template>
  <div v-if="modelValue" class="modal-backdrop" @click.self="closeModal">
    <div class="modal-content">
      <header class="modal-header">
        <div class="modal-header-left">
          <div class="modal-icon">{{ isEditMode ? '✏️' : '👤' }}</div>
          <h2 class="modal-title">{{ isEditMode ? 'Редактировать сотрудника' : 'Добавить сотрудника' }}</h2>
        </div>
        <button class="modal-close-btn" @click="closeModal">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </header>

      <main class="modal-body">
        <form @submit.prevent="saveEmployee">
          <div class="form-group">
            <label for="fullname">ФИО</label>
            <input type="text" id="fullname" v-model="form.fullname" placeholder="Иванов Иван Иванович" required>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" v-model="form.email" placeholder="example@gamestore.ru" required>
          </div>
          <div v-if="!isEditMode" class="form-group">
            <label for="password">Пароль</label>
            <input type="password" id="password" v-model="form.password" placeholder="Минимум 8 символов" required>
          </div>
          <div class="form-group">
            <label for="role">Роль</label>
            <div class="role-selector">
              <div
                class="role-option"
                :class="{ active: form.role === 'manager' }"
                @click="form.role = 'manager'"
              >
                <span class="role-icon">🎧</span>
                <div>
                  <div class="role-name">Менеджер</div>
                  <div class="role-desc">Поддержка, заказы</div>
                </div>
              </div>
              <div
                class="role-option"
                :class="{ active: form.role === 'admin' }"
                @click="form.role = 'admin'"
              >
                <span class="role-icon">⚙️</span>
                <div>
                  <div class="role-name">Администратор</div>
                  <div class="role-desc">Полный доступ</div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </main>

      <footer class="modal-footer">
        <button class="btn-cancel" @click="closeModal">Отмена</button>
        <button class="btn-save" @click="saveEmployee">
          {{ isEditMode ? 'Сохранить изменения' : 'Добавить сотрудника' }}
        </button>
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
  form.value = newEmployee
    ? { ...newEmployee }
    : { fullname: '', email: '', password: '', role: 'manager' };
}, { immediate: true });

const closeModal = () => emit('update:modelValue', false);

const saveEmployee = async () => {
  const url = isEditMode.value ? `/admin/employees/${props.employee.id}` : '/admin/employees';
  const method = isEditMode.value ? 'put' : 'post';
  try {
    const response = await api[method](url, form.value);
    emit('saved', response.data);
    closeModal();
  } catch (error) {
    console.error('Ошибка сохранения сотрудника:', error);
    alert('Ошибка при сохранении данных.');
  }
};
</script>

<style scoped>
.modal-backdrop {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(0,0,0,0.75);
  backdrop-filter: blur(6px);
  display: flex; justify-content: center; align-items: center;
  z-index: 1000;
  animation: fadeIn 0.18s ease;
}
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

.modal-content {
  background: rgba(10,15,30,0.97);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 20px;
  width: 90%; max-width: 480px;
  box-shadow: 0 32px 80px rgba(0,0,0,0.6), 0 0 0 1px rgba(255,255,255,0.05);
  overflow: hidden;
  animation: slideUp 0.22s ease;
}
@keyframes slideUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }

.modal-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 24px 28px 20px;
  border-bottom: 1px solid rgba(255,255,255,0.07);
}
.modal-header-left { display: flex; align-items: center; gap: 12px; }
.modal-icon { font-size: 1.4rem; }
.modal-title {
  margin: 0; font-size: 1.2rem; font-weight: 800;
  background: linear-gradient(135deg, #fff, #94a3b8);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.modal-close-btn {
  width: 32px; height: 32px; border-radius: 8px; border: none;
  background: rgba(255,255,255,0.05); color: #6b7280;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.2s;
}
.modal-close-btn:hover { background: rgba(255,255,255,0.1); color: #e5e7eb; }

.modal-body { padding: 24px 28px; }

.form-group { margin-bottom: 18px; }
.form-group label {
  display: block; margin-bottom: 7px;
  color: #94a3b8; font-size: 0.78rem; font-weight: 700;
  letter-spacing: 0.5px; text-transform: uppercase;
}
.form-group input {
  width: 100%; padding: 11px 14px;
  border: 1px solid rgba(255,255,255,0.1); border-radius: 10px;
  background: rgba(255,255,255,0.04); color: #e5e7eb;
  font-size: 0.95rem; outline: none; box-sizing: border-box;
  transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}
.form-group input::placeholder { color: #4b5563; }
.form-group input:focus {
  border-color: #22c55e;
  background: rgba(34,197,94,0.06);
  box-shadow: 0 0 0 3px rgba(34,197,94,0.18);
}

.role-selector { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.role-option {
  display: flex; align-items: center; gap: 12px;
  padding: 14px 16px;
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 12px;
  background: rgba(255,255,255,0.03);
  cursor: pointer;
  transition: all 0.2s;
}
.role-option:hover { border-color: rgba(34,197,94,0.3); background: rgba(34,197,94,0.05); }
.role-option.active {
  border-color: #22c55e;
  background: rgba(34,197,94,0.1);
  box-shadow: 0 0 0 1px rgba(34,197,94,0.3);
}
.role-icon { font-size: 1.3rem; flex-shrink: 0; }
.role-name { font-size: 0.88rem; font-weight: 700; color: #f1f5f9; margin-bottom: 2px; }
.role-desc { font-size: 0.75rem; color: #6b7280; }

.modal-footer {
  display: flex; justify-content: flex-end; gap: 10px;
  padding: 20px 28px 24px;
  border-top: 1px solid rgba(255,255,255,0.07);
}
.btn-save {
  padding: 11px 24px; border-radius: 10px; border: none;
  font-size: 0.9rem; font-weight: 700; cursor: pointer;
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: #fff; box-shadow: 0 4px 16px rgba(34,197,94,0.3);
  transition: all 0.2s;
}
.btn-save:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(34,197,94,0.45); }
.btn-cancel {
  padding: 11px 20px; border-radius: 10px;
  border: 1px solid rgba(255,255,255,0.1);
  background: rgba(255,255,255,0.04); color: #9ca3af;
  font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.btn-cancel:hover { border-color: rgba(255,255,255,0.22); color: #e5e7eb; background: rgba(255,255,255,0.08); }
</style>
