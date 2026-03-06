<template>
  <div class="admin-page-container">
    <router-link to="/admin" class="admin-back-button">← Назад в админ-панель</router-link>
    <div class="page-header">
      <div>
        <h1 class="admin-page-title">Управление играми</h1>
        <p class="admin-page-subtitle">Найдено игр: {{ filteredGames.length }}</p>
      </div>
      <div class="actions">
        <div class="admin-search-wrapper">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="search-icon"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          <input type="text" v-model="searchQuery" placeholder="Поиск по названию, жанру..." class="admin-search-input">
        </div>
        <button class="admin-button admin-button-primary" @click="openAddModal">+ Добавить игру</button>
      </div>
    </div>

    <div v-if="error" class="admin-error">{{ error }}</div>
    <div v-if="loading" class="admin-loading">Загрузка данных...</div>

    <div v-else-if="!games.length" class="admin-empty">
      Игры пока не добавлены. Нажмите "Добавить игру", чтобы начать.
    </div>
    <div v-else-if="!filteredGames.length" class="admin-empty">
      Игры по вашему запросу не найдены.
    </div>

    <div v-else class="admin-table-wrapper">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Название</th>
            <th>Платформа / Жанр</th>
            <th>Цена</th>
            <th>Статус</th>
            <th>Год</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="game in filteredGames" :key="game.id">
            <td>#{{ game.id }}</td>
            <td>
              <div style="font-weight: 600; color: #e5e7eb;">{{ game.title }}</div>
            </td>
            <td>
              <div>{{ game.platform }}</div>
              <div style="font-size: 0.8rem; color: #9ca3af;">{{ game.genre }}</div>
            </td>
            <td>
              <div :class="{ 'old-price': game.discount_percent }">{{ Number(game.price).toFixed(2) }} ₽</div>
              <div v-if="game.old_price && game.discount_percent" class="old-price-sub">
                 <s>{{ Number(game.old_price).toFixed(2) }} ₽</s>
              </div>
            </td>
            <td>
                <span v-if="game.discount_percent" class="status-badge discount">-{{ game.discount_percent }}%</span>
                <span v-if="game.is_featured" class="status-badge featured">Хит</span>
                <span v-if="game.is_new" class="status-badge new">Новинка</span>
            </td>
            <td>{{ game.release_year ?? '—' }}</td>
            <td class="action-buttons">
              <div class="button-group">
                <button class="admin-button admin-button-secondary" @click="openEditModal(game)">Изменить</button>
                <button class="admin-button admin-button-danger" @click="handleDelete(game.id, game.title)">Удалить</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <AdminGameEditModal 
      v-if="isModalOpen" 
      :is-editing="isEditing" 
      :game="selectedGame" 
      @close="closeModal" 
      @save="handleSaveGame"
      @delete-image="handleDeleteImage"
    />
    <div v-if="toastVisible" class="admin-toast">{{ toastText }}</div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../api/axios';
import AdminGameEditModal from './AdminGameEditModal.vue';

const games = ref([]);
const loading = ref(false);
const error = ref('');
const searchQuery = ref('');

const isModalOpen = ref(false);
const isEditing = ref(false);
const selectedGame = ref(null);

const toastText = ref('');
const toastVisible = ref(false);

const showToast = (text) => {
  toastText.value = text;
  toastVisible.value = true;
  setTimeout(() => { toastVisible.value = false; }, 3000);
};

const loadGames = async () => {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await api.get('/admin/games');
    games.value = data;
  } catch (e) {
    console.error(e);
    error.value = 'Не удалось загрузить игры. Пожалуйста, попробуйте обновить страницу.';
  } finally {
    loading.value = false;
  }
};

const filteredGames = computed(() => {
  if (!searchQuery.value) return games.value;
  const q = searchQuery.value.toLowerCase();
  return games.value.filter(game => 
    game.title.toLowerCase().includes(q) ||
    (game.genre && game.genre.toLowerCase().includes(q)) ||
    (game.platform && game.platform.toLowerCase().includes(q))
  );
});

const openAddModal = () => {
  isEditing.value = false;
  selectedGame.value = null;
  isModalOpen.value = true;
};

const openEditModal = (game) => {
  isEditing.value = true;
  const gameToEdit = games.value.find(g => g.id === game.id);
  selectedGame.value = JSON.parse(JSON.stringify(gameToEdit));
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
  selectedGame.value = null;
};

const handleSaveGame = async (payload) => {
    const { gameData, gameId, galleryFormData } = payload;
    let savedGame;

    // *** НАЧАЛО ДИАГНОСТИЧЕСКОГО КОДА ***
    console.log('Отправка данных на сервер:', JSON.parse(JSON.stringify(gameData)));
    // *** КОНЕЦ ДИАГНОСТИЧЕСКОГО КОДА ***

    try {
        if (isEditing.value) {
            const response = await api.put(`/admin/games/${gameId}`, gameData);
            savedGame = response.data;
            const index = games.value.findIndex(g => g.id === savedGame.id);
            if (index !== -1) {
                games.value[index] = savedGame;
            }
            showToast(`Игра "${savedGame.title}" успешно обновлена`);
        } else {
            const response = await api.post('/admin/games', gameData);
            savedGame = response.data;
            games.value.unshift(savedGame);
            showToast(`Игра "${savedGame.title}" успешно создана`);
        }

        if (galleryFormData && savedGame && galleryFormData.has('gallery[0]')) {
            await handleUploadGallery({ galleryFormData, gameId: savedGame.id });
            const freshGameData = await api.get(`/admin/games/${savedGame.id}`);
            const index = games.value.findIndex(g => g.id === savedGame.id);
            if (index !== -1) {
                games.value[index] = freshGameData.data;
            }
        }

    } catch (e) {
        console.error('Save/Upload Error:', e);
        const errorMessage = e.response?.data?.message || 'Произошла ошибка при сохранении игры';
        showToast(errorMessage);
    } finally {
        closeModal();
    }
};

const handleUploadGallery = async (payload) => {
    const { galleryFormData, gameId } = payload;
    if (!gameId) {
        showToast('Ошибка: ID игры не найден для загрузки галереи.');
        return;
    }
    try {
        await api.post(`/admin/games/${gameId}/gallery`, galleryFormData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        showToast('Галерея успешно обновлена!');
    } catch (e) {
        console.error('Ошибка при загрузке галереи:', e);
        showToast(e.response?.data?.message || 'Произошла ошибка при загрузке галереи');
    }
};

const handleDeleteImage = async (payload) => {
    const { gameId, imageId } = payload;
    try {
        await api.delete(`/admin/games/${gameId}/gallery/${imageId}`);
        showToast('Изображение удалено');
        const game = games.value.find(g => g.id === gameId);
        if (game) {
            game.images = game.images.filter(img => img.id !== imageId);
        }
    } catch (error) {
        console.error('Ошибка при удалении изображения:', error);
        showToast('Не удалось удалить изображение.');
    }
};

const handleDelete = async (gameId, gameTitle) => {
  if (!confirm(`Вы уверены, что хотите удалить игру "${gameTitle}"?`)) return;
  try {
    await api.delete(`/admin/games/${gameId}`);
    games.value = games.value.filter((g) => g.id !== gameId);
    showToast('Игра удалена');
  } catch (e) {
    console.error(e);
    showToast('Ошибка при удалении игры');
  }
};

onMounted(loadGames);
</script>

<style>
@import '../assets/admin.css';

.page-header .actions { display: flex; gap: 1rem; align-items: center; }
.admin-search-wrapper { position: relative; }
.admin-search-wrapper .search-icon { position: absolute; top: 50%; left: 14px; transform: translateY(-50%); color: #9ca3af; pointer-events: none; }
.admin-search-input { padding: 0.65rem 0.9rem 0.65rem 2.5rem; border-radius: 6px; border: 1px solid #4a5568; background-color: #2d3748; color: #e2e8f0; font-size: 0.95rem; min-width: 280px; transition: all 0.2s ease; }
.admin-search-input:focus { outline: none; border-color: #4299e1; background-color: #1a202c; box-shadow: 0 0 0 2px rgba(66, 153, 225, 0.3); }

.old-price { text-decoration: line-through; color: #9ca3af; }
.old-price-sub { font-size: 0.8rem; color: #9ca3af; }
.status-badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 600; margin-right: 6px; text-transform: uppercase; letter-spacing: 0.02em; }
.status-badge.discount { background-color: rgba(239, 68, 68, 0.1); color: #fca5a5; }
.status-badge.featured { background-color: rgba(234, 179, 8, 0.1); color: #fcd34d; }
.status-badge.new { background-color: rgba(59, 130, 246, 0.1); color: #93c5fd; }

.admin-toast { position: fixed; right: 20px; bottom: 20px; background: #1f2937; border: 1px solid #374151; color: #e5e7eb; padding: 12px 18px; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5); z-index: 1300; font-size: 0.95rem; animation: toast-fade-in 0.3s ease-out; }
@keyframes toast-fade-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
