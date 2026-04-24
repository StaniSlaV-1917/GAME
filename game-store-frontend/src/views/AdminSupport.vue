<template>
  <div class="admin-page-container">
    <router-link to="/admin" class="admin-back-button">← Назад в админ-панель</router-link>

    <div class="page-header">
      <div>
        <h1 class="admin-page-title">Обращения в поддержку</h1>
        <p class="admin-page-subtitle">Управляйте заявками пользователей — меняйте статусы и оставляйте заметки.</p>
      </div>
      <div class="header-badges">
        <span class="badge badge-new">{{ countByStatus('new') }} новых</span>
        <span class="badge badge-progress">{{ countByStatus('in_progress') }} в работе</span>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-row">
      <input
        v-model="search"
        class="search-input"
        placeholder="Поиск по email, имени, теме..."
        @input="onSearch"
      />
      <div class="status-filters">
        <button
          v-for="f in statusFilters" :key="f.value"
          class="filter-btn"
          :class="{ active: activeFilter === f.value }"
          @click="setFilter(f.value)"
        >
          <span class="filter-dot" :style="{ background: f.color }"></span>
          {{ f.label }}
          <span class="filter-count">{{ f.value === 'all' ? tickets.length : countByStatus(f.value) }}</span>
        </button>
      </div>
    </div>

    <div v-if="loading" class="admin-loading">Загрузка обращений...</div>
    <div v-else-if="error" class="admin-error">{{ error }}</div>
    <div v-else-if="!filtered.length" class="admin-empty">
      {{ activeFilter === 'all' ? 'Обращений пока нет.' : 'Нет обращений с этим статусом.' }}
    </div>

    <div v-else class="tickets-list">
      <div
        v-for="ticket in filtered"
        :key="ticket.id"
        class="ticket-card"
        :class="`ticket-${ticket.status}`"
      >
        <!-- Card header -->
        <div class="ticket-head">
          <div class="ticket-meta">
            <span class="ticket-id">#{{ ticket.id }}</span>
            <span class="ticket-path">{{ ticket.problem_path }}</span>
          </div>
          <div class="ticket-head-right">
            <span class="ticket-date">{{ formatDate(ticket.created_at) }}</span>
            <button class="del-btn" @click="deleteTicket(ticket.id)" title="Удалить">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
            </button>
          </div>
        </div>

        <!-- Sender -->
        <div class="ticket-sender">
          <div class="sender-avatar">{{ (ticket.user_name || ticket.user_email)?.[0]?.toUpperCase() }}</div>
          <div class="sender-info">
            <span class="sender-name">{{ ticket.user_name || 'Аноним' }}</span>
            <a :href="`mailto:${ticket.user_email}`" class="sender-email">{{ ticket.user_email }}</a>
          </div>
          <div class="ticket-status-wrap">
            <select
              class="status-select"
              :class="`sel-${ticket.status}`"
              v-model="ticket.status"
              @change="saveStatus(ticket)"
            >
              <option v-for="(label, val) in STATUS_LABELS" :key="val" :value="val">{{ label }}</option>
            </select>
          </div>
        </div>

        <!-- Body -->
        <div class="ticket-body">{{ ticket.body }}</div>

        <!-- Admin note -->
        <div class="ticket-note-wrap">
          <label class="note-label">Заметка администратора</label>
          <textarea
            v-model="ticket.admin_note"
            class="note-textarea"
            placeholder="Добавьте внутреннюю заметку..."
            rows="2"
            @blur="saveStatus(ticket)"
          ></textarea>
        </div>

        <!-- Saving indicator -->
        <div v-if="savingId === ticket.id" class="saving-indicator">Сохранение...</div>
        <div v-if="savedId === ticket.id" class="saved-indicator">✓ Сохранено</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../api/axios';
import { useToast } from '../composables/useToast';

const { success, error: toastError } = useToast();

const tickets  = ref([]);
const loading  = ref(false);
const error    = ref('');
const search   = ref('');
const activeFilter = ref('all');
const savingId = ref(null);
const savedId  = ref(null);
let saveTimer  = null;

const STATUS_LABELS = {
  new:         'Новое',
  in_progress: 'В работе',
  resolved:    'Решено',
  rejected:    'Отклонено',
};

const statusFilters = [
  { value: 'all',         label: 'Все',       color: '#64748b' },
  { value: 'new',         label: 'Новые',     color: '#3b82f6' },
  { value: 'in_progress', label: 'В работе',  color: '#f59e0b' },
  { value: 'resolved',    label: 'Решённые',  color: '#22c55e' },
  { value: 'rejected',    label: 'Отклонённые',color: '#ef4444' },
];

const filtered = computed(() => {
  let list = tickets.value;
  if (activeFilter.value !== 'all') {
    list = list.filter(t => t.status === activeFilter.value);
  }
  if (search.value.trim()) {
    const q = search.value.toLowerCase();
    list = list.filter(t =>
      t.user_email?.toLowerCase().includes(q) ||
      t.user_name?.toLowerCase().includes(q) ||
      t.problem_path?.toLowerCase().includes(q) ||
      t.body?.toLowerCase().includes(q)
    );
  }
  return list;
});

const countByStatus = (status) => tickets.value.filter(t => t.status === status).length;

const formatDate = (iso) => {
  const d = new Date(iso);
  return d.toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const loadTickets = async () => {
  loading.value = true;
  error.value   = '';
  try {
    const { data } = await api.get('/admin/support');
    tickets.value  = data;
  } catch (e) {
    error.value = 'Не удалось загрузить обращения.';
  } finally {
    loading.value = false;
  }
};

const saveStatus = async (ticket) => {
  clearTimeout(saveTimer);
  saveTimer = setTimeout(async () => {
    savingId.value = ticket.id;
    savedId.value  = null;
    try {
      await api.put(`/admin/support/${ticket.id}`, {
        status:     ticket.status,
        admin_note: ticket.admin_note ?? '',
      });
      savedId.value = ticket.id;
      setTimeout(() => { if (savedId.value === ticket.id) savedId.value = null; }, 2000);
    } catch (e) {
      toastError('Не удалось сохранить изменения.');
    } finally {
      savingId.value = null;
    }
  }, 500);
};

const deleteTicket = async (id) => {
  if (!confirm('Удалить обращение #' + id + '?')) return;
  try {
    await api.delete(`/admin/support/${id}`);
    tickets.value = tickets.value.filter(t => t.id !== id);
    success('Обращение удалено.');
  } catch (e) {
    toastError('Не удалось удалить обращение.');
  }
};

const setFilter = (val) => { activeFilter.value = val; };
const onSearch = () => {}; // реактивно через computed

onMounted(loadTickets);
</script>

<style scoped>
/* AdminSupport · Ashenforge — стража поддержки */

/* ── Filters ── */
.filters-row {
  display: flex;
  gap: 14px;
  align-items: center;
  margin-bottom: 22px;
  flex-wrap: wrap;
}
.search-input {
  flex: 1;
  min-width: 220px;
  padding: 10px 14px;
  border: 1px solid var(--iron-mid);
  background: linear-gradient(180deg, rgba(8, 6, 10, 0.7), rgba(18, 16, 13, 0.85));
  color: var(--text-bone);
  font-family: var(--font-body);
  font-size: 0.92rem;
  outline: none;
  box-shadow: var(--inset-iron-top);
  transition: border-color 0.2s var(--ease-smoke), box-shadow 0.2s var(--ease-smoke);
}
.search-input:focus {
  border-color: var(--ember-flame);
  box-shadow: var(--inset-iron-top), 0 0 0 3px rgba(226, 67, 16, 0.14);
}
.search-input::placeholder { color: var(--text-void); }

.status-filters { display: flex; gap: 6px; flex-wrap: wrap; }
.filter-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 7px 13px;
  border: 1px solid var(--iron-dark);
  background: rgba(8, 6, 10, 0.45);
  color: var(--text-ash);
  font-family: var(--font-ui);
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.8px;
  text-transform: uppercase;
  cursor: pointer;
  box-shadow: var(--inset-iron-top);
  transition: all 0.2s var(--ease-smoke);
}
.filter-btn:hover {
  background: rgba(122, 93, 72, 0.12);
  color: var(--text-parchment);
}
.filter-btn.active {
  background: linear-gradient(180deg, var(--ash-forge) 0%, var(--ash-bloodrock) 100%);
  border-color: var(--bronze);
  color: var(--ember-gold);
}
.filter-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
}
.filter-count {
  background: rgba(8, 6, 10, 0.55);
  border: 1px solid var(--iron-dark);
  padding: 0 7px;
  font-size: 0.72rem;
  color: var(--text-parchment);
}

/* ── Header badges ── */
.header-badges {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-shrink: 0;
}
.badge {
  padding: 5px 14px;
  font-family: var(--font-ui);
  font-size: 0.76rem;
  font-weight: 700;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  background: rgba(8, 6, 10, 0.55);
  border: 1px solid currentColor;
}
.badge-new      { color: var(--ember-spark); }
.badge-progress { color: var(--ember-gold); }

/* ── Ticket list ── */
.tickets-list { display: flex; flex-direction: column; gap: 14px; }

.ticket-card {
  position: relative;
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  padding: 20px 22px;
  box-shadow: var(--inset-iron-top), var(--shadow-cast);
  transition: border-color 0.2s var(--ease-smoke), box-shadow 0.2s var(--ease-smoke);
}
.ticket-card:hover {
  border-color: var(--bronze-dark);
  box-shadow: var(--inset-iron-top), var(--shadow-cast), 0 0 14px rgba(226, 67, 16, 0.2);
}

/* Левая полоса статуса — как клеймо */
.ticket-card::before {
  content: '';
  position: absolute;
  left: -1px; top: 10px; bottom: 10px;
  width: 4px;
}
.ticket-new::before         { background: linear-gradient(180deg, var(--ember-spark) 0%, var(--ember-glow) 100%); box-shadow: 0 0 8px rgba(255, 167, 88, 0.5); }
.ticket-in_progress::before { background: linear-gradient(180deg, var(--ember-gold) 0%, var(--ember-flame) 100%); box-shadow: 0 0 8px rgba(255, 201, 121, 0.5); }
.ticket-resolved::before    { background: linear-gradient(180deg, var(--bronze) 0%, var(--brass) 100%); }
.ticket-rejected::before    { background: linear-gradient(180deg, #8a1f18 0%, #5a1412 100%); }

/* ── Card header ── */
.ticket-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 14px;
  flex-wrap: wrap;
}
.ticket-meta { display: flex; align-items: center; gap: 10px; }
.ticket-id {
  font-family: var(--font-ui);
  font-size: 0.74rem;
  font-weight: 700;
  color: var(--text-ash);
  background: rgba(8, 6, 10, 0.55);
  padding: 3px 10px;
  border: 1px solid var(--iron-dark);
  letter-spacing: 0.5px;
}
.ticket-path {
  font-family: var(--font-ui);
  font-size: 0.84rem;
  font-weight: 700;
  color: var(--ember-spark);
  letter-spacing: 0.5px;
}
.ticket-head-right { display: flex; align-items: center; gap: 10px; }
.ticket-date {
  font-family: var(--font-ui);
  font-size: 0.75rem;
  color: var(--text-ash);
}
.del-btn {
  width: 30px; height: 30px;
  border: 1px solid var(--iron-dark);
  background: rgba(8, 6, 10, 0.45);
  color: var(--text-ash);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: var(--inset-iron-top);
  transition: all 0.2s var(--ease-smoke);
}
.del-btn:hover {
  background: rgba(138, 31, 24, 0.35);
  color: #ffb4a8;
  border-color: #c2281a;
}

/* ── Sender row ── */
.ticket-sender {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 14px;
}
.sender-avatar {
  width: 38px; height: 38px;
  clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
  background: linear-gradient(180deg, var(--ash-forge) 0%, var(--ash-bloodrock) 100%);
  border: 1px solid var(--bronze-dark);
  color: var(--ember-gold);
  font-family: var(--font-display);
  font-size: 0.9rem;
  font-weight: var(--fw-black, 900);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: var(--inset-iron-top), 0 0 8px rgba(199, 154, 94, 0.25);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
}
.sender-info { flex: 1; min-width: 0; }
.sender-name {
  display: block;
  font-family: var(--font-display);
  font-size: 0.92rem;
  font-weight: 700;
  color: var(--text-bright);
  letter-spacing: 0.3px;
}
.sender-email {
  display: block;
  font-family: var(--font-ui);
  font-size: 0.78rem;
  color: var(--ember-spark);
  text-decoration: none;
  transition: color 0.2s var(--ease-smoke);
}
.sender-email:hover { color: var(--ember-gold); }

/* Status select */
.ticket-status-wrap { flex-shrink: 0; }
.status-select {
  padding: 7px 12px;
  font-family: var(--font-ui);
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: uppercase;
  border: 1px solid var(--iron-mid);
  background: rgba(8, 6, 10, 0.5);
  color: var(--text-bone);
  cursor: pointer;
  outline: none;
  box-shadow: var(--inset-iron-top);
  transition: all 0.2s var(--ease-smoke);
}
.status-select:focus { border-color: var(--ember-flame); }
.sel-new         { border-color: var(--bronze); color: var(--ember-spark); }
.sel-in_progress { border-color: var(--ember-heart); color: var(--ember-gold); }
.sel-resolved    { border-color: var(--bronze); color: var(--brass); }
.sel-rejected    { border-color: #c2281a; color: #ffb4a8; }

/* ── Message body ── */
.ticket-body {
  font-family: var(--font-body);
  font-size: 0.92rem;
  color: var(--text-bone);
  line-height: 1.7;
  white-space: pre-wrap;
  word-break: break-word;
  background: rgba(8, 6, 10, 0.4);
  border: 1px solid var(--iron-dark);
  padding: 13px 16px;
  margin-bottom: 14px;
  box-shadow: var(--inset-iron-top);
}

/* ── Admin note ── */
.ticket-note-wrap { display: flex; flex-direction: column; gap: 6px; }
.note-label {
  font-family: var(--font-ui);
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1.8px;
  color: var(--bronze);
}
.note-textarea {
  background: rgba(8, 6, 10, 0.55);
  border: 1px solid var(--iron-dark);
  padding: 9px 13px;
  color: var(--text-bone);
  font-family: var(--font-body);
  font-size: 0.88rem;
  resize: vertical;
  outline: none;
  box-shadow: var(--inset-iron-top);
  transition: border-color 0.2s var(--ease-smoke);
}
.note-textarea:focus { border-color: var(--ember-flame); }
.note-textarea::placeholder { color: var(--text-void); }

/* ── Save indicators ── */
.saving-indicator, .saved-indicator {
  font-family: var(--font-ui);
  font-size: 0.75rem;
  margin-top: 6px;
  text-align: right;
  letter-spacing: 0.5px;
}
.saving-indicator { color: var(--ember-spark); }
.saved-indicator  { color: var(--ember-gold); }

/* ── Page header ── */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 26px;
  gap: 16px;
  flex-wrap: wrap;
}

@media (max-width: 640px) {
  .filters-row { flex-direction: column; align-items: stretch; }
  .status-filters { overflow-x: auto; flex-wrap: nowrap; padding-bottom: 4px; }
  .ticket-sender { flex-wrap: wrap; }
  .ticket-status-wrap { width: 100%; }
  .status-select { width: 100%; }
}
</style>
