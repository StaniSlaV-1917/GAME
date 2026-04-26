<template>
  <div class="admin-page">
    <h1 class="page-title">Управление новостями</h1>

    <div class="actions-bar">
      <RouterLink to="/admin" class="btn btn-secondary">Назад в админку</RouterLink>
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
import { RouterLink } from 'vue-router';
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
    const { data } = await api.get('/admin/news');
    news.value = data.sort((a, b) => new Date(b.published_at) - new Date(a.published_at));
  } catch (e) {
    error.value = 'Ошибка при загрузке новостей.';
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const handleSave = async (articleData) => {
  const formData = new FormData();
  formData.append('title', articleData.title);
  formData.append('content', articleData.content);
  formData.append('published_at', articleData.published_at);
  
  // Только если был выбран новый файл
  if (articleData.image && typeof articleData.image !== 'string') {
    formData.append('image', articleData.image);
  }

  try {
    if (isEditing.value) {
      formData.append('_method', 'PUT'); // Трюк для Laravel
      const { data: updatedArticle } = await api.post(`/admin/news/${articleData.id}`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      const index = news.value.findIndex(a => a.id === updatedArticle.id);
      if (index !== -1) {
        news.value[index] = updatedArticle;
      }
      showToast(`Новость "${updatedArticle.title}" обновлена`);
    } else {
      const { data: newArticle } = await api.post('/admin/news', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      news.value.unshift(newArticle);
      showToast(`Новость "${newArticle.title}" создана`);
    }
  } catch (e) {
    console.error(e);
    // Отображение ошибок валидации от Laravel
    if (e.response && e.response.status === 422) {
        const errors = e.response.data.errors;
        const errorMessages = Object.values(errors).flat().join('\n');
        showToast(`Ошибка валидации:\n${errorMessages}`);
    } else {
        showToast('Ошибка при сохранении новости');
    }
  } finally {
    closeModal();
    loadNews(); // Перезагружаем список, чтобы все было в актуальном состоянии
  }
};

const handleDelete = async (articleId, articleTitle) => {
  if (!confirm(`Вы уверены, что хотите удалить новость "${articleTitle}"?`)) {
    return;
  }
  try {
    await api.delete(`/admin/news/${articleId}`);
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
  selectedArticle.value = article;
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
  selectedArticle.value = null;
};

const formatDate = (dateString) => {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleString('ru-RU', {
    year: 'numeric', month: 'long', day: 'numeric',    hour: '2-digit', minute: '2-digit'
  });
};
</script>

<style scoped>
/* Стили полностью заимствованы из AdminGames для консистентности */
.admin-page { padding: 2rem; max-width: 1200px; margin: 0 auto;}
.page-title { color: #fff; margin-bottom: 1.5rem; }
.actions-bar { margin-bottom: 1.5rem; display: flex; gap: 1rem; }
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

.btn { padding: 8px 12px; border: none; border-radius: 6px; cursor: pointer; transition: background-color 0.2s; font-weight: 500; text-decoration: none; display: inline-block; }
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
  white-space: pre-wrap; /* Для переноса строк в сообщениях об ошибках */
}

@keyframes toast-fade-in {
  from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); }
}

</style>
