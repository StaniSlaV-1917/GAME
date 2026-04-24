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

<style>
@import '../assets/admin.css';

/* AdminNews — использует свою структуру .admin-page + .table-container */
.admin-page {
  padding: 2rem 1.5rem;
  max-width: 1200px;
  margin: 0 auto;
  color: var(--text-bone);
}
.page-title {
  font-family: var(--font-display);
  font-size: clamp(1.8rem, 3.2vw, 2.4rem);
  font-weight: var(--fw-black, 900);
  color: var(--text-bright);
  margin: 0 0 1.5rem;
  letter-spacing: 0.3px;
  text-shadow: 0 2px 12px rgba(0, 0, 0, 0.5);
}
.actions-bar {
  margin-bottom: 1.5rem;
  display: flex;
  gap: 1rem;
}

.loading-indicator,
.error-message {
  text-align: center;
  padding: 3rem 2rem;
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  clip-path: var(--clip-forged-sm);
  color: var(--text-parchment);
  font-family: var(--font-body);
  box-shadow: var(--inset-iron-top);
}
.error-message {
  color: #ffb4a8;
  background: linear-gradient(180deg, rgba(138, 31, 24, 0.3), rgba(90, 20, 18, 0.4));
  border-color: rgba(194, 40, 26, 0.45);
}

.table-container {
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  clip-path: var(--clip-forged-sm);
  overflow: hidden;
  box-shadow: var(--inset-iron-top), var(--shadow-cast);
}
.data-table {
  width: 100%;
  border-collapse: collapse;
  font-family: var(--font-body);
  color: var(--text-bone);
}

thead tr {
  background: linear-gradient(180deg, var(--ash-bloodrock) 0%, var(--ash-ironrust) 100%);
}
th {
  padding: 14px 16px;
  text-align: left;
  font-family: var(--font-ui);
  font-size: 0.76rem;
  font-weight: 700;
  letter-spacing: 1.8px;
  text-transform: uppercase;
  color: var(--bronze);
  border-bottom: 1px solid var(--bronze-dark);
}
tbody tr {
  border-bottom: 1px dashed var(--iron-dark);
  transition: background-color 0.22s var(--ease-smoke);
}
tbody tr:last-child { border: none; }
tbody tr:hover {
  background: linear-gradient(90deg, transparent 0%, rgba(226, 67, 16, 0.1) 50%, transparent 100%);
}
td {
  padding: 13px 16px;
  color: var(--text-parchment);
  font-size: 0.94rem;
}
td.actions-cell { display: flex; gap: 10px; }

/* Кованые кнопки .btn-* */
.btn {
  position: relative;
  padding: 9px 16px;
  border: 1px solid transparent;
  cursor: pointer;
  font-family: var(--font-ui);
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 1.3px;
  text-transform: uppercase;
  text-decoration: none;
  display: inline-block;
  box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.3);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
  clip-path: var(--clip-forged-sm);
  transition: transform 0.18s var(--ease-forge);
}
.btn:hover { transform: translateY(-2px); }
.btn-primary {
  background: var(--grad-ember);
  color: var(--text-bright);
  border-color: var(--ember-heart);
  box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.35), var(--glow-ember);
}
.btn-primary:hover {
  box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.35), var(--glow-ember-strong);
}
.btn-secondary {
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  color: var(--text-parchment);
  border-color: var(--bronze-dark);
}
.btn-secondary:hover {
  color: var(--text-bright);
  border-color: var(--bronze);
  background: linear-gradient(180deg, var(--ash-ironrust) 0%, var(--ash-stone) 100%);
}
.btn-danger {
  background: linear-gradient(180deg, #8a1f18 0%, #5a1412 100%);
  color: var(--text-bright);
  border-color: #c2281a;
}
.btn-danger:hover {
  background: linear-gradient(180deg, #c2281a 0%, #8a1f18 100%);
}
</style>
