<template>
  <div class="admin-page-container">
    <router-link to="/admin" class="admin-back-button">← Назад в админ-панель</router-link>
    <div class="page-header">
      <div>
        <h1 class="admin-page-title">Управление пользователями</h1>
        <p class="admin-page-subtitle">
          Просмотр данных, назначение ролей, модерация (бан/заморозка/удаление).
          <strong>Личные данные пользователей не редактируются админом</strong>
          — пользователи правят их сами.
        </p>
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
            <th>ФИО / Username</th>
            <th>Email / Телефон</th>
            <th>Роль</th>
            <th>Статус</th>
            <th>Регистрация</th>
            <th>Модерация</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.id" :class="{ 'row-banned': user.banned_at, 'row-frozen': user.frozen_at }">
            <td>#{{ user.id }}</td>
            <td>
              <div class="user-name-cell">
                <span class="user-fullname">{{ user.fullname || '—' }}</span>
                <span v-if="user.username" class="user-username">@{{ user.username }}</span>
                <span v-else class="user-username muted">username не задан</span>
              </div>
            </td>
            <td>
              <div class="contact-info">
                <span>{{ user.email || '—' }}</span>
                <span class="phone">{{ user.phone || '—' }}</span>
              </div>
            </td>
            <td>
              <span class="role-badge" :class="`role-${user.role}`">
                {{ roleLabels[user.role] || user.role }}
              </span>
            </td>
            <td>
              <span v-if="user.banned_at" class="status-badge status-banned" :title="user.ban_reason">
                🚫 Забанен
              </span>
              <span v-else-if="user.frozen_at" class="status-badge status-frozen" :title="user.freeze_reason">
                ❄ Заморожен
              </span>
              <span v-else class="status-badge status-active">✓ Активен</span>
            </td>
            <td class="reg-date-cell">{{ formatDate(user.reg_date) }}</td>
            <td class="action-buttons">
              <button
                class="admin-button admin-button-secondary"
                :disabled="user.banned_at !== null"
                :title="user.banned_at ? 'Сначала разбаньте' : 'Сменить роль'"
                @click="openRoleModal(user)"
              >Роль</button>

              <button
                v-if="!user.banned_at"
                class="admin-button admin-button-danger"
                :disabled="user.role === 'admin'"
                :title="user.role === 'admin' ? 'Бан админа невозможен' : ''"
                @click="openBanModal(user)"
              >Забанить</button>
              <button
                v-else
                class="admin-button admin-button-secondary"
                @click="unbanUser(user)"
              >Разбанить</button>

              <button
                v-if="!user.frozen_at && !user.banned_at"
                class="admin-button admin-button-warning"
                :disabled="user.role === 'admin'"
                @click="openFreezeModal(user)"
              >Заморозить</button>
              <button
                v-else-if="user.frozen_at"
                class="admin-button admin-button-secondary"
                @click="unfreezeUser(user)"
              >Разморозить</button>

              <button
                class="admin-button admin-button-danger"
                :disabled="user.role === 'admin'"
                @click="confirmDelete(user)"
              >Удалить</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ═══ Модалка причины бана / заморозки ═══ -->
    <div v-if="modActionModal.open" class="modal-backdrop" @click.self="closeModActionModal">
      <div class="modal-content mod-action-modal">
        <h2 class="modal-title">
          {{ modActionModal.action === 'ban' ? '🚫 Забанить' : '❄ Заморозить' }} пользователя
        </h2>
        <p class="modal-subtitle">
          {{ modActionModal.user?.fullname || modActionModal.user?.email }} (#{{ modActionModal.user?.id }})
        </p>
        <div class="field">
          <label>Причина <span class="field-hint">3-1000 символов, увидит пользователь</span></label>
          <textarea
            v-model="modActionModal.reason"
            rows="4"
            placeholder="Например: спам в комментариях, оскорбления, реклама..."
            maxlength="1000"
          ></textarea>
        </div>
        <div v-if="modActionModal.error" class="msg-banner error">✗ {{ modActionModal.error }}</div>
        <div class="modal-actions">
          <button class="admin-button admin-button-secondary" @click="closeModActionModal">Отмена</button>
          <button
            class="admin-button admin-button-danger"
            :disabled="modActionModal.sending || modActionModal.reason.trim().length < 3"
            @click="submitModAction"
          >
            <span v-if="modActionModal.sending">Применяю...</span>
            <span v-else>{{ modActionModal.action === 'ban' ? 'Забанить' : 'Заморозить' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ═══ Confirm-модалка удаления ═══ -->
    <div v-if="deleteModal.open" class="modal-backdrop" @click.self="deleteModal.open = false">
      <div class="modal-content mod-action-modal">
        <h2 class="modal-title">⚠ Удалить пользователя?</h2>
        <p class="modal-subtitle">
          {{ deleteModal.user?.fullname || deleteModal.user?.email }} (#{{ deleteModal.user?.id }})
        </p>
        <p class="modal-warning">
          Это <strong>необратимое действие</strong>. Все заказы, отзывы и посты пользователя
          будут затронуты согласно правилам каскада БД.
        </p>
        <div class="modal-actions">
          <button class="admin-button admin-button-secondary" @click="deleteModal.open = false">Отмена</button>
          <button
            class="admin-button admin-button-danger"
            :disabled="deleteModal.sending"
            @click="submitDelete"
          >
            <span v-if="deleteModal.sending">Удаляю...</span>
            <span v-else>Удалить навсегда</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ═══ Модалка смены роли ═══ -->
    <div v-if="roleModal.open" class="modal-backdrop" @click.self="roleModal.open = false">
      <div class="modal-content mod-action-modal">
        <h2 class="modal-title">⚙ Сменить роль</h2>
        <p class="modal-subtitle">
          {{ roleModal.user?.fullname || roleModal.user?.email }} (#{{ roleModal.user?.id }})
        </p>

        <div class="role-current">
          Текущая роль:
          <span class="role-badge" :class="`role-${roleModal.user?.role}`">
            {{ roleLabels[roleModal.user?.role] || roleModal.user?.role }}
          </span>
        </div>

        <div class="field">
          <label>Новая роль</label>
          <div class="role-options">
            <label
              v-for="r in roles"
              :key="r"
              class="role-option"
              :class="{ active: roleModal.newRole === r, disabled: r === 'admin' && currentUserRole !== 'admin' }"
            >
              <input
                type="radio"
                :value="r"
                v-model="roleModal.newRole"
                :disabled="r === 'admin' && currentUserRole !== 'admin'"
              />
              <span class="role-option-icon">{{ roleIcons[r] }}</span>
              <span class="role-option-body">
                <strong>{{ roleLabels[r] }}</strong>
                <span class="role-option-desc">{{ roleDescriptions[r] }}</span>
              </span>
            </label>
          </div>
        </div>

        <p v-if="roleModal.newRole === 'admin' && currentUserRole !== 'admin'" class="modal-warning">
          Только админы могут назначать других админами.
        </p>

        <div v-if="roleModal.error" class="msg-banner error">✗ {{ roleModal.error }}</div>

        <div class="modal-actions">
          <button class="admin-button admin-button-secondary" @click="roleModal.open = false">Отмена</button>
          <button
            class="admin-button admin-button-primary"
            :disabled="roleModal.sending || roleModal.newRole === roleModal.user?.role"
            @click="submitRoleChange"
          >
            <span v-if="roleModal.sending">Меняю...</span>
            <span v-else>Сменить</span>
          </button>
        </div>
      </div>
    </div>

    <div v-if="toastVisible" class="admin-toast">{{ toastText }}</div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, reactive } from 'vue';
import { useAuthStore } from '../stores/auth';
import api from '../api/axios';

const authStore = useAuthStore();
const currentUserRole = computed(() => authStore.user?.role);

const users = ref([]);
const loading = ref(false);
const error = ref('');
const roles = ['user', 'manager', 'admin'];

// Локализация ролей в Ashenforge-стилистике
const roleLabels = {
  user:    'Воин',
  manager: 'Кузнец',
  admin:   'Старейшина',
};
const roleIcons = {
  user:    '⚔',
  manager: '🔨',
  admin:   '👑',
};
const roleDescriptions = {
  user:    'Обычный пользователь — покупки, отзывы, посты',
  manager: 'Управление контентом — игры, новости, заказы',
  admin:   'Полный доступ — модерация + админ-панель',
};

const toastText = ref('');
const toastVisible = ref(false);

// Модалка ban/freeze (с указанием причины)
const modActionModal = reactive({
  open: false,
  action: 'ban',     // 'ban' | 'freeze'
  user: null,
  reason: '',
  sending: false,
  error: '',
});

// Confirm-модалка удаления
const deleteModal = reactive({
  open: false,
  user: null,
  sending: false,
});

// Модалка смены роли
const roleModal = reactive({
  open: false,
  user: null,
  newRole: 'user',
  sending: false,
  error: '',
});

const showToast = (text) => {
  toastText.value = text;
  toastVisible.value = true;
  setTimeout(() => { toastVisible.value = false; }, 2500);
};

const formatDate = (s) => s ? new Date(s).toLocaleDateString('ru-RU') : '—';

const loadUsers = async () => {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await api.get('/admin/users');
    users.value = data;
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

// ═══ Смена роли ═══

const openRoleModal = (user) => {
  roleModal.open = true;
  roleModal.user = user;
  roleModal.newRole = user.role;
  roleModal.error = '';
  roleModal.sending = false;
};

const submitRoleChange = async () => {
  if (roleModal.sending) return;
  if (roleModal.newRole === roleModal.user.role) {
    roleModal.error = 'Выбрана та же роль что текущая.';
    return;
  }
  if (roleModal.newRole === 'admin' && currentUserRole.value !== 'admin') {
    roleModal.error = 'Только админы могут назначать других админами.';
    return;
  }
  roleModal.sending = true;
  roleModal.error = '';
  try {
    const { data } = await api.put(`/admin/users/${roleModal.user.id}/role`, {
      role: roleModal.newRole,
    });
    // Обновляем юзера в списке
    const idx = users.value.findIndex(u => u.id === roleModal.user.id);
    if (idx >= 0 && data.user) Object.assign(users.value[idx], data.user);
    showToast(`Роль изменена на «${roleLabels[roleModal.newRole]}»`);
    roleModal.open = false;
  } catch (e) {
    roleModal.error = e.response?.data?.message || 'Ошибка смены роли.';
  } finally {
    roleModal.sending = false;
  }
};

// ═══ Бан ═══

const openBanModal = (user) => {
  modActionModal.open = true;
  modActionModal.action = 'ban';
  modActionModal.user = user;
  modActionModal.reason = '';
  modActionModal.error = '';
  modActionModal.sending = false;
};

const openFreezeModal = (user) => {
  modActionModal.open = true;
  modActionModal.action = 'freeze';
  modActionModal.user = user;
  modActionModal.reason = '';
  modActionModal.error = '';
  modActionModal.sending = false;
};

const closeModActionModal = () => {
  modActionModal.open = false;
};

const submitModAction = async () => {
  if (modActionModal.sending) return;
  if (modActionModal.reason.trim().length < 3) {
    modActionModal.error = 'Причина должна быть минимум 3 символа.';
    return;
  }
  modActionModal.sending = true;
  modActionModal.error = '';
  try {
    const action = modActionModal.action; // 'ban' | 'freeze'
    const { data } = await api.post(
      `/admin/users/${modActionModal.user.id}/${action}`,
      { reason: modActionModal.reason }
    );
    // Обновим пользователя в списке
    const idx = users.value.findIndex(u => u.id === modActionModal.user.id);
    if (idx >= 0 && data.user) Object.assign(users.value[idx], data.user);
    showToast(data.message || 'Готово');
    modActionModal.open = false;
  } catch (e) {
    modActionModal.error = e.response?.data?.message || 'Ошибка применения';
  } finally {
    modActionModal.sending = false;
  }
};

const unbanUser = async (user) => {
  if (!confirm(`Снять бан с ${user.fullname || user.email}?`)) return;
  try {
    const { data } = await api.post(`/admin/users/${user.id}/unban`);
    const idx = users.value.findIndex(u => u.id === user.id);
    if (idx >= 0 && data.user) Object.assign(users.value[idx], data.user);
    showToast(data.message);
  } catch (e) {
    showToast(e.response?.data?.message || 'Ошибка');
  }
};

const unfreezeUser = async (user) => {
  if (!confirm(`Снять заморозку с ${user.fullname || user.email}?`)) return;
  try {
    const { data } = await api.post(`/admin/users/${user.id}/unfreeze`);
    const idx = users.value.findIndex(u => u.id === user.id);
    if (idx >= 0 && data.user) Object.assign(users.value[idx], data.user);
    showToast(data.message);
  } catch (e) {
    showToast(e.response?.data?.message || 'Ошибка');
  }
};

// ═══ Удаление ═══

const confirmDelete = (user) => {
  deleteModal.open = true;
  deleteModal.user = user;
  deleteModal.sending = false;
};

const submitDelete = async () => {
  if (deleteModal.sending) return;
  deleteModal.sending = true;
  try {
    await api.delete(`/admin/users/${deleteModal.user.id}`);
    users.value = users.value.filter(u => u.id !== deleteModal.user.id);
    showToast('Пользователь удалён');
    deleteModal.open = false;
  } catch (e) {
    showToast(e.response?.data?.message || 'Ошибка удаления');
  } finally {
    deleteModal.sending = false;
  }
};

onMounted(loadUsers);
</script>

<style>
@import '../assets/admin.css';

/* ═══ Таблица ═══ */
.user-name-cell {
  display: flex; flex-direction: column; gap: 2px;
  min-width: 140px;
}
.user-fullname {
  font-weight: 600;
  color: var(--text-bone);
}
.user-username {
  font-family: var(--font-ui);
  font-size: 0.82rem;
  color: var(--ember-spark);
  letter-spacing: 0.3px;
}
.user-username.muted {
  color: var(--text-smoke);
  font-style: italic;
  font-weight: 400;
}

.contact-info { display: flex; flex-direction: column; gap: 3px; }
.contact-info .phone {
  font-family: var(--font-ui);
  font-size: 0.82rem;
  color: var(--text-ash);
  letter-spacing: 0.3px;
}

.reg-date-cell {
  font-size: 0.85rem;
  color: var(--text-parchment);
  white-space: nowrap;
}

/* ═══ Status badge ═══ */
.status-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 0.78rem;
  font-weight: 600;
  white-space: nowrap;
}
.status-active {
  background: rgba(127, 166, 61, 0.15);
  color: #a3c755;
  border: 1px solid rgba(127, 166, 61, 0.4);
}
.status-banned {
  background: rgba(226, 67, 16, 0.18);
  color: #ff8433;
  border: 1px solid rgba(226, 67, 16, 0.5);
  cursor: help;
}
.status-frozen {
  background: rgba(74, 115, 149, 0.18);
  color: #7db3d4;
  border: 1px solid rgba(74, 115, 149, 0.5);
  cursor: help;
}

/* Подсветка строки забаненного/замороженного */
.row-banned td { opacity: 0.65; }
.row-banned td:nth-child(5),
.row-banned td:nth-child(7) { opacity: 1; }
.row-frozen td { opacity: 0.85; }

/* Кнопки модерации */
.action-buttons {
  display: flex; flex-wrap: wrap; gap: 6px;
}
.action-buttons .admin-button {
  font-size: 0.78rem;
  padding: 6px 11px;
  white-space: nowrap;
}
.admin-button-warning {
  background: linear-gradient(180deg, rgba(226, 132, 44, 0.85), rgba(176, 30, 22, 0.7));
  color: #fff6df;
  border: 1px solid rgba(226, 132, 44, 0.6);
}
.admin-button-warning:hover:not(:disabled) {
  background: linear-gradient(180deg, rgba(255, 167, 88, 0.9), rgba(226, 67, 16, 0.85));
}

/* ═══ Модалка модерации ═══ */
.mod-action-modal {
  max-width: 520px;
  width: 100%;
}
.mod-action-modal .modal-title {
  font-family: var(--font-display);
  font-size: 1.5rem;
  margin: 0 0 8px;
  color: var(--text-bright);
}
.mod-action-modal .modal-subtitle {
  color: var(--text-parchment);
  margin: 0 0 24px;
  font-size: 0.95rem;
}
.mod-action-modal .field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 18px; }
.mod-action-modal .field label {
  font-family: var(--font-display);
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--text-bone);
  text-transform: uppercase;
  letter-spacing: 0.4px;
}
.mod-action-modal .field-hint {
  font-weight: 400;
  font-size: 0.74rem;
  color: var(--text-ash);
  text-transform: none;
  letter-spacing: 0;
  margin-left: 6px;
}
.mod-action-modal textarea {
  width: 100%;
  padding: 12px 14px;
  border: 2px solid var(--iron-mid);
  border-radius: 6px;
  background: linear-gradient(180deg, rgba(8, 6, 10, 0.7), rgba(18, 16, 13, 0.85));
  color: var(--text-bone);
  font-family: var(--font-body);
  font-size: 0.95rem;
  resize: vertical;
  outline: none;
}
.mod-action-modal textarea:focus {
  border-color: var(--ember-flame);
  box-shadow: 0 0 0 3px rgba(226, 67, 16, 0.18);
}
.mod-action-modal .modal-warning {
  background: rgba(226, 67, 16, 0.12);
  border: 1px solid rgba(226, 67, 16, 0.3);
  border-radius: 6px;
  padding: 12px 14px;
  margin: 0 0 20px;
  color: var(--text-bone);
  font-size: 0.92rem;
}
.mod-action-modal .modal-actions {
  display: flex; gap: 10px; justify-content: flex-end;
}

.msg-banner.error {
  background: rgba(226, 67, 16, 0.15);
  border: 1px solid rgba(226, 67, 16, 0.4);
  border-radius: 6px;
  padding: 10px 14px;
  color: #ff8433;
  font-size: 0.88rem;
  margin: 0 0 16px;
}

/* ═══ Role badge (отображение роли в таблице) ═══ */
.role-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 12px;
  border-radius: 12px;
  font-family: var(--font-display);
  font-size: 0.82rem;
  font-weight: 600;
  letter-spacing: 0.3px;
  text-transform: uppercase;
  white-space: nowrap;
  user-select: none;
}
.role-badge.role-user {
  background: rgba(199, 154, 94, 0.12);
  color: var(--brass);
  border: 1px solid rgba(199, 154, 94, 0.4);
}
.role-badge.role-manager {
  background: rgba(122, 28, 20, 0.18);
  color: #ff8433;
  border: 1px solid rgba(226, 67, 16, 0.5);
}
.role-badge.role-admin {
  background: linear-gradient(135deg, rgba(255, 132, 51, 0.2), rgba(255, 201, 121, 0.18));
  color: var(--ember-gold);
  border: 1px solid rgba(255, 167, 88, 0.6);
  box-shadow: 0 0 8px rgba(255, 132, 51, 0.25);
}

/* ═══ Модалка смены роли ═══ */
.role-current {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 14px;
  margin-bottom: 18px;
  background: rgba(8, 6, 10, 0.4);
  border: 1px solid var(--iron-mid);
  border-radius: 6px;
  font-family: var(--font-body);
  color: var(--text-parchment);
}
.role-options {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.role-option {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 16px;
  background: linear-gradient(180deg, rgba(8, 6, 10, 0.5), rgba(18, 16, 13, 0.7));
  border: 2px solid var(--iron-mid);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s var(--ease-smoke);
}
.role-option:hover:not(.disabled) {
  border-color: var(--bronze);
  background: linear-gradient(180deg, rgba(122, 28, 20, 0.15), rgba(8, 6, 10, 0.6));
}
.role-option.active {
  border-color: var(--ember-flame);
  background: linear-gradient(180deg, rgba(226, 67, 16, 0.18), rgba(122, 28, 20, 0.12));
  box-shadow: 0 0 0 3px rgba(226, 67, 16, 0.2);
}
.role-option.disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.role-option input[type="radio"] {
  /* Скрываем радио-инпут — кликабельность через label */
  position: absolute;
  opacity: 0;
  pointer-events: none;
}
.role-option-icon {
  font-size: 1.6rem;
  line-height: 1;
  width: 36px;
  text-align: center;
  flex-shrink: 0;
}
.role-option-body {
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex: 1;
}
.role-option-body strong {
  font-family: var(--font-display);
  font-size: 1rem;
  color: var(--text-bright);
  letter-spacing: 0.3px;
}
.role-option-desc {
  font-size: 0.82rem;
  color: var(--text-parchment);
}
</style>
