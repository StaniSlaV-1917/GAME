<template>
  <div class="admin-page-container">
    <router-link to="/admin" class="admin-back-button">← Назад в админ-панель</router-link>
    <div class="page-header">
      <div>
        <h1 class="admin-page-title">Редактор главной страницы</h1>
        <p class="admin-page-subtitle">Управляйте контентом на главной странице сайта.</p>
      </div>
    </div>

    <!-- Редактор новостей -->
    <div class="admin-section">
      <h2 class="admin-section-title">Блок новостей</h2>

      <div v-if="error" class="admin-error">{{ error }}</div>
      <div v-if="loading" class="admin-loading">Загрузка данных...</div>

      <div v-else-if="!newsPosts.length" class="admin-empty">Новости не найдены.</div>

      <div v-else class="admin-table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Заголовок</th>
              <th>Отрывок</th>
              <th>Автор</th>
              <th>Дата</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="post in newsPosts" :key="post.id">
              <td>{{ post.title }}</td>
              <td>{{ post.excerpt }}</td>
              <td>{{ post.author }}</td>
              <td>{{ post.date }}</td>
              <td class="action-buttons">
                 <div class="button-group">
                    <button class="admin-button admin-button-secondary" @click="editPost(post)">Изменить</button>
                    <button class="admin-button admin-button-danger" @click="deletePost(post)">Удалить</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
       <button class="admin-button admin-button-primary" @click="addPost" style="margin-top: 1rem;">+ Добавить новость</button>
    </div>

     <!-- TODO: В будущем здесь можно будет добавлять редактор для других секций -->

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../api/axios'; // импортируем наш настроенный axios

const newsPosts = ref([]);
const loading = ref(false);
const error = ref('');

// Загрузка данных с бэкенда
const loadNews = async () => {
  loading.value = true;
  error.value = '';
  try {
    // Делаем запрос к новому эндпоинту
    const response = await api.get('/admin/home/editor');
    // Сохраняем полученные новости в state
    newsPosts.value = response.data.news;
  } catch (err) {
    console.error('Ошибка загрузки данных для редактора:', err);
    error.value = 'Не удалось загрузить данные для редактора.';
  }
  loading.value = false;
};

// Вызываем загрузку данных при монтировании компонента
onMounted(loadNews);

// --- Функции-заглушки для кнопок --- 
const addPost = () => {
    alert('Функционал добавления новости в разработке.');
}

const editPost = (post) => {
    alert(`Редактирование поста: ${post.title} (в разработке)`);
}

const deletePost = (post) => {
    if(confirm(`Вы уверены что хотите удалить новость "${post.title}"?`)){
        alert(`Новость "${post.title}" удалена (в разработке)`);
    }
}

</script>

<style scoped>
@import '../assets/admin.css';
</style>
