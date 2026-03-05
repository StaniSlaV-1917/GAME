<template>
  <div class="admin-page">
    <h1 class="page-title">Управление новостями</h1>

    <div class="actions-bar">
      <button @click="openAddModal" class="btn btn-primary">Добавить новость</button>
    </div>

    <div v-if="loading" class="loading-indicator">Загрузка...</div>
    <div v-else-if="error" class="error-message">{{ error }}</div>

    <div v-else class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Заголовок</th>
            <th>Дата публикации</th>
            <th>Действия</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="article in news" :key="article.id">
            <td>{{ article.id }}</td>
            <td>{{ article.title }}</td>
            <td>{{ formatDate(article.published_at) }}</td>
            <td class="actions-cell">
              <button @click="openEditModal(article)" class="btn btn-secondary">Редактировать</button>
              <button @click="handleDelete(article.id, article.title)" class="btn btn-danger">Удалить</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <AdminNewsEditModal 
      v-if="isModalOpen"
      :article="selectedArticle"
      :isEditing="isEditing"
      @close="closeModal"
      @save="handleSave"
    />

    <div v-if="toastVisible" class="admin-toast">{{ toastText }}</div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../api/axios';
import AdminNewsEditModal from './AdminNewsEditModal.vue';

const news = ref([]);
const loading = ref(true);
const error = ref('');

const isModalOpen = ref(false);
const isEditing = ref(false);
const selectedArticle = ref(null);

const toastText = ref('');
const toastVisible = ref(false);

const showToast = (text) => {
  toastText.value = text;
  toastVisible.value = true;
  setTimeout(() => { toastVisible.value = false; }, 2500);
};

onMounted(() => {
  loadNews();
});

const loadNews = async () => {
  loading.value = true;
  error.value = '';
  try {
    // Загружаем новости с сервера, сортируем по дате публикации
    const { data } = await api.get('/news?_sort=published_at&_order=desc');
    news.value = data;
  } catch (e) {
    error.value = 'Ошибка при загрузке новостей.';
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const handleSave = async (articleData) => {
  try {
    if (isEditing.value) {
      // Режим редактирования - PUT
      const { data: updatedArticle } = await api.put(`/news/${articleData.id}`, articleData);
      const index = news.value.findIndex(a => a.id === updatedArticle.id);
      if (index !== -1) {
        news.value[index] = updatedArticle;
      }
      showToast(`Новость "${updatedArticle.title}" обновлена`);
    } else {
      // Режим создания - POST
      const payload = {
        ...articleData,
        published_at: new Date().toISOString(),
      };
      const { data: newArticle } = await api.post('/news', payload);
      news.value.unshift(newArticle);
      showToast(`Новость "${newArticle.title}" создана`);
    }
  } catch (e) {
    console.error(e);
    showToast('Ошибка при сохранении новости');
  } finally {
    closeModal();
  }
};

const handleDelete = async (articleId, articleTitle) => {
  if (!confirm(`Вы уверены, что хотите удалить новость "${articleTitle}"?`)) {
    return;
  }
  try {
    await api.delete(`/news/${articleId}`);
    const index = news.value.findIndex(a => a.id === articleId);
    if (index !== -1) {
      news.value.splice(index, 1);
    }
    showToast('Новость успешно удалена');
  } catch (e) {
    console.error(e);
    showToast('Ошибка при удалении новости');
  }
};

const openAddModal = () => {
  isEditing.value = false;
  selectedArticle.value = null;
  isModalOpen.value = true;
};

const openEditModal = (article) => {
  isEditing.value = true;
  // Глубокое копирование, чтобы избежать прямого изменения оригинала
  selectedArticle.value = JSON.parse(JSON.stringify(article));
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
  selectedArticle.value = null;
};

const formatDate = (dateString) => {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleDateString('ru-RU', {
    year: 'numeric', month: 'long', day: 'numeric'
  });
};
</script>

<style scoped>
/* Стили полностью заимствованы из AdminGames для консистентности */
.admin-page { padding: 2rem; max-width: 1200px; margin: 0 auto;}
.page-title { color: #fff; margin-bottom: 1.5rem; }
.actions-bar { margin-bottom: 1.5rem; }
.loading-indicator, .error-message { color: #fff; text-align: center; padding: 2rem; }
.error-message { color: #ef4444; }

.table-container { background-color: #1f2937; border-radius: 8px; overflow: hidden; }
.data-table { width: 100%; border-collapse: collapse; }

thead tr { background-color: #374151; }
th { padding: 12px 15px; text-align: left; font-weight: 600; color: #d1d5db; }
tbody tr { border-bottom: 1px solid #374151; }
tbody tr:last-child { border: none; }
td { padding: 12px 15px; color: #9ca3af; }
td.actions-cell { display: flex; gap: 10px; }

.btn { padding: 8px 12px; border: none; border-radius: 6px; cursor: pointer; transition: background-color 0.2s; font-weight: 500; }
.btn-primary { background-color: #3b82f6; color: white; }
.btn-primary:hover { background-color: #2563eb; }
.btn-secondary { background-color: #6b7280; color: white; }
.btn-secondary:hover { background-color: #4b5563; }
.btn-danger { background-color: #ef4444; color: white; }
.btn-danger:hover { background-color: #dc2626; }

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
  from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); }
}

</style>
