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
/* AdminEmployeeEditModal · Ashenforge — каменный свиток с заклёпками */
.modal-backdrop {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(8, 6, 10, 0.82);
  backdrop-filter: blur(6px);
  display: flex; justify-content: center; align-items: center;
  z-index: 1000;
  animation: aeFade 0.22s var(--ease-smoke);
}
@keyframes aeFade { from { opacity: 0; } to { opacity: 1; } }

.modal-content {
  position: relative;
  background: linear-gradient(180deg,
    var(--ash-ironrust) 0%,
    var(--ash-stone) 40%,
    var(--ash-coal) 100%);
  border: 1px solid var(--bronze-dark);
  clip-path: var(--clip-forged-md);
  width: 92%; max-width: 500px;
  box-shadow:
    inset 0 0 0 1px var(--iron-mid),
    var(--shadow-deep),
    var(--inset-forge);
  overflow: hidden;
  animation: aeSlide 0.26s var(--ease-forge);
}
.modal-content::before {
  content: '';
  position: absolute;
  inset: 12px;
  pointer-events: none;
  background-image:
    radial-gradient(circle 5px at 0 0,       var(--brass) 2.5px, var(--bronze) 3.8px, transparent 5px),
    radial-gradient(circle 5px at 100% 0,    var(--brass) 2.5px, var(--bronze) 3.8px, transparent 5px),
    radial-gradient(circle 5px at 0 100%,    var(--brass) 2.5px, var(--bronze) 3.8px, transparent 5px),
    radial-gradient(circle 5px at 100% 100%, var(--brass) 2.5px, var(--bronze) 3.8px, transparent 5px);
  z-index: 3;
}
@keyframes aeSlide { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: none; } }

.modal-header {
  position: relative; z-index: 2;
  display: flex; align-items: center; justify-content: space-between;
  padding: 24px 30px 20px;
  border-bottom: 1px dashed var(--iron-dark);
}
.modal-header-left { display: flex; align-items: center; gap: 12px; }
.modal-icon {
  font-size: 1.4rem;
  color: var(--ember-gold);
  filter: drop-shadow(0 0 6px rgba(255, 201, 121, 0.5));
}
.modal-title {
  margin: 0;
  font-family: var(--font-display);
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--text-bright);
  letter-spacing: 0.3px;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
}
.modal-close-btn {
  width: 34px; height: 34px;
  border: 1px solid var(--iron-dark);
  background: rgba(8, 6, 10, 0.55);
  color: var(--text-parchment);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  box-shadow: var(--inset-iron-top);
  transition: all 0.22s var(--ease-smoke);
}
.modal-close-btn:hover {
  background: rgba(138, 31, 24, 0.35);
  color: var(--ember-gold);
  border-color: var(--ember-heart);
}

.modal-body { padding: 26px 30px; position: relative; z-index: 2; }

.form-group { margin-bottom: 20px; }
.form-group label {
  display: block;
  margin-bottom: 8px;
  font-family: var(--font-ui);
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  color: var(--bronze);
}
.form-group input {
  width: 100%;
  padding: 12px 15px;
  border: 1px solid var(--iron-mid);
  background: linear-gradient(180deg, rgba(8, 6, 10, 0.75), rgba(18, 16, 13, 0.85));
  color: var(--text-bone);
  font-family: var(--font-body);
  font-size: 0.96rem;
  outline: none;
  box-sizing: border-box;
  box-shadow: var(--inset-iron-top);
  transition: border-color 0.2s var(--ease-smoke), box-shadow 0.2s var(--ease-smoke);
}
.form-group input::placeholder { color: var(--text-void); }
.form-group input:focus {
  border-color: var(--ember-flame);
  box-shadow: var(--inset-iron-top), 0 0 0 3px rgba(226, 67, 16, 0.14);
}

.role-selector { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.role-option {
  display: flex; align-items: center; gap: 12px;
  padding: 14px 16px;
  border: 1px solid var(--iron-mid);
  background: linear-gradient(180deg, rgba(8, 6, 10, 0.55), rgba(18, 16, 13, 0.7));
  cursor: pointer;
  box-shadow: var(--inset-iron-top);
  transition: all 0.22s var(--ease-smoke);
}
.role-option:hover {
  border-color: var(--bronze-dark);
  background: linear-gradient(180deg, rgba(8, 6, 10, 0.65), rgba(18, 16, 13, 0.8));
}
.role-option.active {
  border-color: var(--ember-heart);
  background: linear-gradient(180deg, rgba(226, 67, 16, 0.18), rgba(138, 31, 24, 0.18));
  box-shadow: var(--inset-iron-top), 0 0 12px rgba(226, 67, 16, 0.3);
}
.role-icon { font-size: 1.3rem; flex-shrink: 0; color: var(--ember-gold); }
.role-name {
  font-family: var(--font-display);
  font-size: 0.92rem;
  font-weight: 700;
  color: var(--text-bright);
  margin-bottom: 3px;
  letter-spacing: 0.3px;
}
.role-desc {
  font-family: var(--font-body);
  font-size: 0.78rem;
  color: var(--text-parchment);
}

.modal-footer {
  position: relative; z-index: 2;
  display: flex; justify-content: flex-end; gap: 10px;
  padding: 20px 30px 26px;
  border-top: 1px dashed var(--iron-dark);
}
.btn-save {
  position: relative;
  padding: 12px 26px;
  border: 1px solid var(--ember-heart);
  background: var(--grad-ember);
  color: var(--text-bright);
  font-family: var(--font-display);
  font-size: 0.9rem;
  font-weight: 700;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  cursor: pointer;
  clip-path: var(--clip-forged-sm);
  box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.35), var(--glow-ember);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
  overflow: hidden;
  transition: transform 0.2s var(--ease-forge), box-shadow 0.2s var(--ease-smoke);
}
.btn-save::after {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(90deg, transparent, rgba(255, 201, 121, 0.4), transparent);
  transform: translateX(-120%);
  transition: transform 0.6s var(--ease-smoke);
  pointer-events: none;
}
.btn-save:hover {
  transform: translateY(-2px);
  box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.35), var(--glow-ember-strong);
}
.btn-save:hover::after { transform: translateX(120%); }

.btn-cancel {
  padding: 12px 22px;
  border: 1px solid var(--bronze-dark);
  background: transparent;
  color: var(--text-parchment);
  font-family: var(--font-ui);
  font-size: 0.84rem;
  font-weight: 700;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  cursor: pointer;
  box-shadow: var(--inset-iron-top);
  clip-path: var(--clip-forged-sm);
  transition: all 0.22s var(--ease-smoke);
}
.btn-cancel:hover {
  border-color: var(--bronze);
  color: var(--text-bright);
  background: rgba(122, 93, 72, 0.12);
}
</style>
