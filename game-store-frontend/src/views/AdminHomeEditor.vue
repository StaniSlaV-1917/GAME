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
      <div class="admin-table-wrapper">
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
import { ref } from 'vue';

// Эти данные должны приходить с бэкенда, пока используем заглушку
const newsPosts = ref([
  {
    id: 1,
    title: 'Elden Ring: Shadow of the Erdtree — первый взгляд',
    excerpt: 'Долгожданное дополнение наконец-то вышло. Разбираемся, что нового в землях Теней и стоит ли оно своих денег.',
    author: 'Game Informer',
    date: '24.06.2024',
    link: '#'
  },
  {
    id: 2,
    title: 'Анонсирована новая часть серии The Witcher',
    excerpt: 'CD Projekt RED неожиданно показали тизер следующей большой игры во вселенной Ведьмака. Кодовое имя — Polaris.',
    author: 'IGN',
    date: '22.06.2024',
    link: '#'
  },
]);

const addPost = () => {
    alert('Функционал добавления новости в разработке.');
    // Здесь будет логика для открытия модального окна или страницы для создания нового поста
}

const editPost = (post) => {
    alert(`Редактирование поста: ${post.title} (в разработке)`);
     // Логика для редактирования
}

const deletePost = (post) => {
    if(confirm(`Вы уверены что хотите удалить новость "${post.title}"?`)){
        alert(`Новость "${post.title}" удалена (в разработке)`);
         // Логика для удаления
    }
}

</script>

<style scoped>
@import '../assets/admin.css';
</style>
