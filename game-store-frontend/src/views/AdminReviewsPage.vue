<template>
  <div class="admin-page-container">
    <router-link to="/admin" class="admin-back-button">← Назад в админ-панель</router-link>
    <div class="page-header">
      <div>
        <h1 class="admin-page-title">Модерация отзывов</h1>
        <p class="admin-page-subtitle">Управляйте комментариями и отзывами пользователей к играм.</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="admin-filters-card">
      <div class="filter-item">
        <label for="status-filter">Статус</label>
        <select id="status-filter" class="filter-select" v-model="filters.status">
          <option value="">Любой</option>
          <option value="pending">На модерации</option>
          <option value="approved">Одобрен</option>
          <option value="rejected">Отклонен</option>
        </select>
      </div>
      <div class="filter-item">
        <label for="game-id-filter">ID игры</label>
        <input id="game-id-filter" type="number" class="filter-input" v-model.number="filters.game_id" placeholder="ID игры" />
      </div>
      <div class="filter-item">
        <label for="user-id-filter">ID пользователя</label>
        <input id="user-id-filter" type="number" class="filter-input" v-model.number="filters.user_id" placeholder="ID пользователя" />
      </div>
      <div class="filter-item search-filter">
        <label for="search-filter">Поиск</label>
        <input id="search-filter" type="text" class="filter-input" v-model="filters.search" placeholder="Текст в заголовке или отзыве..." />
      </div>
    </div>

    <div v-if="error" class="admin-error">{{ error }}</div>
    <div v-if="loading" class="admin-loading">Загрузка отзывов...</div>

    <div v-else-if="!reviews.length" class="admin-empty">
      Отзывы, соответствующие фильтрам, не найдены.
    </div>

    <!-- Reviews Table -->
    <div v-else class="admin-table-wrapper">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Пользователь / Игра</th>
            <th>Отзыв</th>
            <th>Рейтинг</th>
            <th>Статус</th>
            <th>Дата</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="review in reviews" :key="review.id">
            <td>#{{ review.id }}</td>
            <td>
              <div>{{ review.user?.fullname || 'Аноним' }}</div>
              <div class="sub-text">{{ review.game?.title || 'Игра удалена' }}</div>
            </td>
            <td class="review-text">
              <div style="font-weight: 600;">{{ review.title }}</div>
              <p>{{ review.body }}</p>
            </td>
            <td>
                <span class="rating-badge">{{ review.rating }} ★</span>
            </td>
            <td>
              <span class="status-pill" :class="`status-${review.status}`">{{ statusLabel(review.status) }}</span>
            </td>
            <td class="sub-text">{{ new Date(review.created_at).toLocaleString() }}</td>
            <td class="action-buttons">
                <div class="button-group">
                    <button v-if="review.status !== 'approved'" class="admin-button admin-button-primary" @click="updateStatus(review, 'approved')">Одобрить</button>
                    <button v-if="review.status !== 'rejected'" class="admin-button admin-button-secondary" @click="updateStatus(review, 'rejected')">Отклонить</button>
                    <button class="admin-button admin-button-danger" @click="deleteReview(review)">Удалить</button>
                </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <!-- Pagination -->
    <div v-if="meta && meta.last_page > 1" class="pagination-controls">
        <button class="admin-button" @click="changePage(meta.current_page - 1)" :disabled="meta.current_page === 1">Назад</button>
        <span>Страница {{ meta.current_page }} из {{ meta.last_page }}</span>
        <button class="admin-button" @click="changePage(meta.current_page + 1)" :disabled="meta.current_page === meta.last_page">Вперед</button>
    </div>

    <div v-if="toastVisible" class="admin-toast">{{ toastText }}</div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import api from '../api/axios';

const reviews = ref([]);
const meta = ref(null);
const loading = ref(false);
const error = ref('');

const filters = ref({
  status: '',
  search: '',
  game_id: '',
  user_id: '',
  page: 1,
  per_page: 15,
});

const toastText = ref('');
const toastVisible = ref(false);

const showToast = (text) => {
  toastText.value = text;
  toastVisible.value = true;
  setTimeout(() => { toastVisible.value = false; }, 2500);
};

const statusLabel = (status) => ({
    pending: 'На модерации',
    approved: 'Одобрен',
    rejected: 'Отклонен',
}[status] || status);

const loadReviews = async () => {
  loading.value = true;
  error.value = '';
  const params = Object.fromEntries(Object.entries(filters.value).filter(([, v]) => v !== ''));
  try {
    const { data } = await api.get('/admin/reviews', { params });
    reviews.value = data.data || [];
    meta.value = { current_page: data.current_page, last_page: data.last_page, total: data.total };
  } catch (e) {
    console.error(e);
    error.value = 'Ошибка загрузки отзывов.';
  } finally {
    loading.value = false;
  }
};

const changePage = (page) => {
  if (page >= 1 && page <= meta.value.last_page) {
    filters.value.page = page;
    loadReviews();
  }
};

const updateStatus = async (review, status) => {
  try {
    await api.put(`/admin/reviews/${review.id}`, { status });
    review.status = status; // Optimistic update
    showToast(`Статус отзыва #${review.id} изменен на "${statusLabel(status)}"`);
  } catch (e) {
    console.error(e);
    showToast('Ошибка изменения статуса');
  }
};

const deleteReview = async (review) => {
  if (!confirm('Вы уверены, что хотите удалить этот отзыв?')) return;
  try {
    await api.delete(`/admin/reviews/${review.id}`);
    reviews.value = reviews.value.filter(r => r.id !== review.id);
    showToast(`Отзыв #${review.id} удален.`);
  } catch (e) {
    console.error(e);
    showToast('Ошибка удаления отзыва');
  }
};

watch(filters, loadReviews, { deep: true, immediate: true });

onMounted(loadReviews);

</script>

<style>
@import '../assets/admin.css';

/* AdminReviewsPage — фильтры и локальные бейджи */
.admin-filters-card {
  position: relative;
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  clip-path: var(--clip-forged-sm);
  padding: 1.5rem;
  margin-bottom: 2rem;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  box-shadow: var(--inset-iron-top);
}

.filter-item {
  display: flex;
  flex-direction: column;
}

.filter-item label {
  font-family: var(--font-ui);
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--bronze);
  margin-bottom: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 1.5px;
}

.filter-input, .filter-select {
  width: 100%;
  padding: 9px 14px;
  border: 1px solid var(--iron-mid);
  background: linear-gradient(180deg, rgba(8, 6, 10, 0.7), rgba(18, 16, 13, 0.85));
  color: var(--text-bone);
  font-family: var(--font-body);
  font-size: 0.92rem;
  outline: none;
  box-shadow: var(--inset-iron-top);
  transition: border-color 0.2s var(--ease-smoke), box-shadow 0.2s var(--ease-smoke);
}

.filter-input:focus, .filter-select:focus {
  border-color: var(--ember-flame);
  box-shadow: var(--inset-iron-top), 0 0 0 3px rgba(226, 67, 16, 0.14);
}

.search-filter { grid-column: 1 / -1; }
@media (min-width: 1024px) {
  .search-filter { grid-column: span 2; }
}

.review-text p {
    margin: 0;
    font-family: var(--font-body);
    font-size: 0.92rem;
    color: var(--text-bone);
    max-width: 48ch;
    line-height: 1.6;
    white-space: normal;
}

.sub-text {
    font-family: var(--font-ui);
    font-size: 0.8rem;
    color: var(--text-ash);
}

.rating-badge {
    background: rgba(8, 6, 10, 0.55);
    color: var(--ember-gold);
    border: 1px solid var(--bronze-dark);
    padding: 4px 10px;
    font-family: var(--font-display);
    font-weight: 700;
    font-size: 0.92rem;
    text-shadow: 0 0 6px rgba(255, 201, 121, 0.3);
}

.status-pill {
  display: inline-block;
  font-family: var(--font-ui);
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  padding: 4px 12px;
  background: rgba(8, 6, 10, 0.55);
  border: 1px solid currentColor;
}
.status-pill.status-pending  { color: var(--bronze); }
.status-pill.status-approved { color: var(--ember-gold); }
.status-pill.status-rejected { color: #ffb4a8; }

.button-group {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
}

.pagination-controls {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin-top: 2rem;
    color: var(--text-parchment);
    font-family: var(--font-ui);
    font-size: 0.88rem;
    letter-spacing: 0.5px;
}
</style>
