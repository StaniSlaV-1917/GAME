
<template>
  <div class="modal-overlay" @click.self="close">
    <div class="modal-content">
      <h2 class="modal-title">{{ isEditing ? 'Редактировать игру' : 'Добавить новую игру' }}</h2>
      <form @submit.prevent="handleSubmit">
        <!-- Основные поля -->
        <div class="form-grid">
          <div class="form-group">
            <label for="title">Название</label>
            <input id="title" v-model="editableGame.title" type="text" required>
          </div>
          <div class="form-group">
            <label for="price">Цена (₽)</label>
            <input id="price" v-model.number="editableGame.price" type="number" step="0.01" required>
          </div>
          <div class="form-group">
            <label for="platform">Платформа</label>
            <input id="platform" v-model="editableGame.platform" type="text" required>
          </div>
          <div class="form-group">
            <label for="genre">Жанр</label>
            <input id="genre" v-model="editableGame.genre" type="text" required>
          </div>
          <div class="form-group">
            <label for="release_year">Год выхода</label>
            <input id="release_year" v-model.number="editableGame.release_year" type="number">
          </div>
           <div class="form-group">
            <label for="image">URL изображения</label>
            <input id="image" v-model="editableGame.image" type="text">
          </div>
        </div>

        <!-- Поля для описания -->
        <div class="form-group">
            <label for="description">Описание</label>
            <textarea id="description" v-model="editableGame.description" rows="4"></textarea>
        </div>

        <hr class="form-divider" />
        
        <!-- Новые поля для дополнительного контента -->
        <h3 class="subsection-title">Дополнительный контент</h3>
        <div class="form-grid">
            <div class="form-group">
                <label for="developer">Разработчик</label>
                <input id="developer" v-model="editableGame.developer" type="text">
            </div>
            <div class="form-group">
                <label for="publisher">Издатель</label>
                <input id="publisher" v-model="editableGame.publisher" type="text">
            </div>
        </div>
        <div class="form-group">
            <label for="trailerUrl">URL трейлера (YouTube embed)</label>
            <input id="trailerUrl" v-model="editableGame.trailerUrl" type="text">
        </div>

        <!-- Управление скриншотами -->
        <div class="form-group">
          <label>URL скриншотов</label>
          <div v-for="(screenshot, index) in editableGame.screenshots" :key="index" class="screenshot-input">
            <input v-model="editableGame.screenshots[index]" type="text" placeholder="https://example.com/image.jpg">
            <button type="button" @click="removeScreenshot(index)" class="btn-remove-shot">-</button>
          </div>
          <button type="button" @click="addScreenshot" class="btn-add-shot">Добавить скриншот</button>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-save">{{ isEditing ? 'Сохранить изменения' : 'Создать игру' }}</button>
          <button type="button" @click="close" class="btn-cancel">Отмена</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  game: Object,
  isEditing: Boolean
});

const emit = defineEmits(['close', 'save']);

const getInitialGameData = () => ({
  title: '',
  price: 0,
  platform: 'PC',
  genre: '',
  release_year: new Date().getFullYear(),
  description: '',
  image: '',
  developer: '',
  publisher: '',
  trailerUrl: '',
  screenshots: []
});

const editableGame = ref(getInitialGameData());

// Используем watch для реакции на изменение входных данных
watch(() => props.game, (newGame) => {
  if (props.isEditing && newGame) {
    // При редактировании копируем данные из props
    editableGame.value = {
      ...getInitialGameData(), // Гарантируем наличие всех полей
      ...newGame,
      screenshots: Array.isArray(newGame.screenshots) ? [...newGame.screenshots] : []
    };
  } else {
    // При создании новой игры используем пустые данные
    editableGame.value = getInitialGameData();
  }
}, {
  immediate: true, // Запускает watch сразу при создании компонента
  deep: true // Необходимо для отслеживания изменений внутри объекта
});

// Функции для управления скриншотами
const addScreenshot = () => {
  if (!Array.isArray(editableGame.value.screenshots)) {
    editableGame.value.screenshots = [];
  }
  editableGame.value.screenshots.push('');
};

const removeScreenshot = (index) => {
  editableGame.value.screenshots.splice(index, 1);
};

const handleSubmit = () => {
  // Убираем пустые строки из скриншотов перед сохранением
  if (editableGame.value.screenshots) {
      editableGame.value.screenshots = editableGame.value.screenshots.filter(url => url && url.trim() !== '');
  }
  emit('save', editableGame.value);
  close();
};

const close = () => {
  emit('close');
};
</script>

<style scoped>
/* Общие стили модального окна */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal-content {
    background-color: #2a3a50;
    padding: 25px;
    border-radius: 10px;
    width: 90%;
    max-width: 650px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 5px 20px rgba(0,0,0,0.4);
}

.modal-title {
    margin: 0 0 20px;
    color: #fff;
    font-size: 1.6rem;
    font-weight: 600;
    border-bottom: 1px solid #4a5a70;
    padding-bottom: 15px;
}

.subsection-title {
    color: #a0c3ff;
    font-size: 1.2rem;
    margin: 20px 0 15px;
    font-weight: 500;
}

.form-divider {
    border: none;
    border-top: 1px solid #4a5a70;
    margin: 25px 0;
}

/* Стили формы */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.form-group {
    display: flex;
    flex-direction: column;
    margin-bottom: 15px;
}

.form-group label {
    margin-bottom: 8px;
    color: #c0d0e0;
    font-size: 0.9rem;
    font-weight: 500;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #4a5a70;
    border-radius: 5px;
    background-color: #1e2a3a;
    color: #e0e0e0;
    font-size: 1rem;
    transition: border-color 0.2s;
}
.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #3b82f6;
}

textarea {
    resize: vertical;
}

/* Стили для управления скриншотами */
.screenshot-input {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}
.btn-remove-shot {
    background-color: #ef4444;
    color: white;
    border: none;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    font-weight: bold;
    cursor: pointer;
    flex-shrink: 0;
    transition: background-color 0.2s;
}
.btn-remove-shot:hover { background-color: #dc2626; }

.btn-add-shot {
    background-color: #22c55e;
    color: white;
    border: none;
    border-radius: 5px;
    padding: 8px 12px;
    font-size: 0.9rem;
    cursor: pointer;
    margin-top: 5px;
    transition: background-color 0.2s;
}
.btn-add-shot:hover { background-color: #16a34a; }


/* Кнопки действий */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #4a5a70;
}

.btn-save, .btn-cancel {
    padding: 12px 24px;
    border-radius: 6px;
    border: none;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-save {
    background-color: #3b82f6;
    color: white;
}
.btn-save:hover { background-color: #2563eb; }

.btn-cancel {
    background-color: #4b5563;
    color: #e5e7eb;
}
.btn-cancel:hover { background-color: #5b6573; }

</style>