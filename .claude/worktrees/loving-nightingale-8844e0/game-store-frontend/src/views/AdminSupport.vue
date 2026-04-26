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
/* ── Filters ─────────────────────────────────────────────────────────────── */
.filters-row {
  display: flex;
  gap: 14px;
  align-items: center;
  margin-bottom: 20px;
  flex-wrap: wrap;
}
.search-input {
  flex: 1;
  min-width: 200px;
  padding: 9px 14px;
  border-radius: 10px;
  border: 1px solid rgba(255,255,255,0.1);
  background: rgba(255,255,255,0.04);
  color: #e2e8f0;
  font-size: 0.88rem;
  outline: none;
  transition: border-color 0.2s;
}
.search-input:focus { border-color: rgba(99,102,241,0.5); }
.search-input::placeholder { color: #475569; }

.status-filters {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}
.filter-btn {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 6px 12px;
  border-radius: 8px;
  border: 1px solid rgba(255,255,255,0.08);
  background: rgba(255,255,255,0.03);
  color: #64748b;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.filter-btn:hover { background: rgba(255,255,255,0.07); color: #94a3b8; }
.filter-btn.active {
  background: rgba(255,255,255,0.1);
  border-color: rgba(255,255,255,0.18);
  color: #e2e8f0;
}
.filter-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
}
.filter-count {
  background: rgba(255,255,255,0.08);
  border-radius: 999px;
  padding: 0 6px;
  font-size: 0.72rem;
  color: #94a3b8;
}

/* ── Header badges ───────────────────────────────────────────────────────── */
.header-badges {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-shrink: 0;
}
.badge {
  padding: 5px 14px;
  border-radius: 999px;
  font-size: 0.8rem;
  font-weight: 700;
}
.badge-new      { background: rgba(59,130,246,0.15); color: #60a5fa; border: 1px solid rgba(59,130,246,0.3); }
.badge-progress { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }

/* ── Ticket list ─────────────────────────────────────────────────────────── */
.tickets-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.ticket-card {
  background: #0d1117;
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 14px;
  padding: 18px 20px;
  position: relative;
  transition: border-color 0.2s;
}
.ticket-card:hover { border-color: rgba(255,255,255,0.14); }

/* Left accent bar by status */
.ticket-card::before {
  content: '';
  position: absolute;
  left: 0; top: 12px; bottom: 12px;
  width: 3px;
  border-radius: 0 3px 3px 0;
}
.ticket-new::before         { background: #3b82f6; }
.ticket-in_progress::before { background: #f59e0b; }
.ticket-resolved::before    { background: #22c55e; }
.ticket-rejected::before    { background: #ef4444; }

/* ── Card header ─────────────────────────────────────────────────────────── */
.ticket-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}
.ticket-meta { display: flex; align-items: center; gap: 10px; }
.ticket-id {
  font-size: 0.75rem;
  font-weight: 700;
  color: #475569;
  background: rgba(255,255,255,0.05);
  padding: 2px 8px;
  border-radius: 6px;
  border: 1px solid rgba(255,255,255,0.07);
}
.ticket-path {
  font-size: 0.82rem;
  font-weight: 600;
  color: #60a5fa;
}
.ticket-head-right { display: flex; align-items: center; gap: 10px; }
.ticket-date { font-size: 0.75rem; color: #334155; }
.del-btn {
  width: 28px; height: 28px;
  border-radius: 7px;
  border: 1px solid rgba(255,255,255,0.06);
  background: rgba(239,68,68,0.06);
  color: #475569;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.2s;
}
.del-btn:hover { background: rgba(239,68,68,0.18); color: #fca5a5; border-color: rgba(239,68,68,0.3); }

/* ── Sender row ──────────────────────────────────────────────────────────── */
.ticket-sender {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}
.sender-avatar {
  width: 34px; height: 34px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: #fff;
  font-size: 0.85rem;
  font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.sender-info { flex: 1; min-width: 0; }
.sender-name  { display: block; font-size: 0.875rem; font-weight: 600; color: #e2e8f0; }
.sender-email {
  display: block;
  font-size: 0.78rem;
  color: #60a5fa;
  text-decoration: none;
}
.sender-email:hover { text-decoration: underline; }

/* Status select */
.ticket-status-wrap { flex-shrink: 0; }
.status-select {
  padding: 6px 10px;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 600;
  border: 1px solid rgba(255,255,255,0.12);
  background: rgba(255,255,255,0.05);
  color: #e2e8f0;
  cursor: pointer;
  outline: none;
  transition: all 0.2s;
}
.status-select:focus  { border-color: rgba(99,102,241,0.5); }
.sel-new         { border-color: rgba(59,130,246,0.4);  color: #60a5fa;  background: rgba(59,130,246,0.1); }
.sel-in_progress { border-color: rgba(245,158,11,0.4);  color: #fbbf24;  background: rgba(245,158,11,0.1); }
.sel-resolved    { border-color: rgba(34,197,94,0.4);   color: #4ade80;  background: rgba(34,197,94,0.1); }
.sel-rejected    { border-color: rgba(239,68,68,0.4);   color: #f87171;  background: rgba(239,68,68,0.1); }

/* ── Message body ────────────────────────────────────────────────────────── */
.ticket-body {
  font-size: 0.875rem;
  color: #94a3b8;
  line-height: 1.65;
  white-space: pre-wrap;
  word-break: break-word;
  background: rgba(255,255,255,0.02);
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: 9px;
  padding: 12px 14px;
  margin-bottom: 12px;
}

/* ── Admin note ──────────────────────────────────────────────────────────── */
.ticket-note-wrap { display: flex; flex-direction: column; gap: 5px; }
.note-label {
  font-size: 0.74rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1.5px;
  color: #334155;
}
.note-textarea {
  background: rgba(255,255,255,0.02);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 8px;
  padding: 8px 12px;
  color: #94a3b8;
  font-size: 0.83rem;
  font-family: inherit;
  resize: vertical;
  outline: none;
  transition: border-color 0.2s;
}
.note-textarea:focus { border-color: rgba(99,102,241,0.4); }
.note-textarea::placeholder { color: #1e2d40; }

/* ── Save indicators ─────────────────────────────────────────────────────── */
.saving-indicator, .saved-indicator {
  font-size: 0.75rem;
  margin-top: 6px;
  text-align: right;
}
.saving-indicator { color: #f59e0b; }
.saved-indicator  { color: #4ade80; }

/* ── Page header ─────────────────────────────────────────────────────────── */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
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
