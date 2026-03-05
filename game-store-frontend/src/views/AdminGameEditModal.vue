
<template>
  <div class="modal-overlay" @click.self="close">
    <div class="modal-content">
      <h2 class="modal-title">{{ isEditing ? 'Редактировать игру' : 'Добавить новую игру' }}</h2>
      <form @submit.prevent="handleSubmit">

        <!-- Основные поля -->
        <h3 class="subsection-title">Основные данные</h3>
        <div class="form-grid">
          <div class="form-group">
            <label for="title">Название</label>
            <input id="title" v-model="form.title" type="text" required>
          </div>
          <div class="form-group">
            <label for="price">Цена (₽)</label>
            <input id="price" v-model.number="form.price" type="number" step="0.01" required>
          </div>
          <div class="form-group">
            <label for="platform">Платформа</label>
            <input id="platform" v-model="form.platform" type="text">
          </div>
          <div class="form-group">
            <label for="genre">Жанр</label>
            <input id="genre" v-model="form.genre" type="text">
          </div>
          <div class="form-group">
            <label for="release_year">Год выхода</label>
            <input id="release_year" v-model.number="form.release_year" type="number">
          </div>
          <div class="form-group">
            <label for="trailer_url">URL трейлера (YouTube)</label>
            <input id="trailer_url" v-model="form.trailer_url" type="text" placeholder="https://www.youtube.com/watch?v=...">
          </div>
        </div>

        <!-- Описание -->
        <div class="form-group">
            <label for="description">Описание</label>
            <textarea id="description" v-model="form.description" rows="4"></textarea>
        </div>

        <hr class="form-divider" />

        <!-- Обложка -->
        <h3 class="subsection-title">Обложка</h3>
        <div class="form-group">
          <label for="image_file">Загрузить новую обложку</label>
          <input id="image_file" type="file" @change="handleCoverFileChange" accept="image/*">
          <div v-if="coverPreviewUrl" class="image-preview-container">
             <p>Предпросмотр новой обложки:</p>
             <img :src="coverPreviewUrl" alt="Предпросмотр обложки" class="image-preview" />
          </div>
          <div v-else-if="isEditing && game.image" class="image-preview-container">
            <p>Текущая обложка:</p>
            <img :src="`http://localhost:8000/${game.image}`" alt="Текущая обложка" class="image-preview" />
          </div>
        </div>

        <hr class="form-divider" />

        <!-- Галерея -->
        <h3 class="subsection-title">Галерея изображений</h3>
        
        <!-- Существующая галерея -->
        <div v-if="isEditing && form.images && form.images.length" class="gallery-grid">
          <div v-for="image in form.images" :key="image.id" class="gallery-item">
            <img :src="`http://localhost:8000/${image.path}`" :alt="`Gallery image ${image.id}`"/>
            <button type="button" @click="requestImageDelete(image.id)" class="btn-delete-img">Удалить</button>
          </div>
        </div>
        <p v-else-if="isEditing">У этой игры пока нет галереи.</p>

        <!-- Загрузка новых изображений -->
        <div class="form-group" style="margin-top: 20px;">
          <label for="gallery_files">Добавить изображения в галерею</label>
          <input id="gallery_files" type="file" multiple @change="handleGalleryFilesChange" accept="image/*">
        </div>

        <!-- Предпросмотр новых изображений галереи -->
        <div v-if="newGalleryPreviews.length" class="gallery-grid">
            <div v-for="(preview, index) in newGalleryPreviews" :key="index" class="gallery-item new-preview">
                <img :src="preview.url" :alt="`Preview ${preview.name}`" />
                <span class="file-name">{{ preview.name }}</span>
                <button type="button" @click="removeNewGalleryFile(index)" class="btn-delete-img">Отменить</button>
            </div>
        </div>

        <!-- Кнопки -->
        <div class="form-actions">
          <button type="submit" class="btn-save">{{ isEditing ? 'Сохранить изменения' : 'Создать игру' }}</button>
          <button type="button" @click="close" class="btn-cancel">Отмена</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';

const props = defineProps({
  game: Object,
  isEditing: Boolean
});

const emit = defineEmits(['close', 'save', 'delete-image']);

// Реактивная форма для всех текстовых полей
const form = ref({});

// Отдельные ref для файлов
const coverImageFile = ref(null);
const newGalleryFiles = ref([]); // Массив объектов { file, url, name }

// Инициализация данных формы при открытии модалки
watch(() => props.game, (newGame) => {
  if (props.isEditing && newGame) {
    form.value = { ...newGame };
  } else {
    form.value = {
        title: '', price: 0, platform: 'PC', genre: '', release_year: new Date().getFullYear(),
        description: '', trailer_url: '', images: []
    };
  }
  // Сбрасываем файлы при каждой смене пропсов
  coverImageFile.value = null;
  newGalleryFiles.value = [];
}, { immediate: true, deep: true });

// --- Обработчики файлов ---

const handleCoverFileChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    coverImageFile.value = file;
  }
};

const handleGalleryFilesChange = (event) => {
  const files = Array.from(event.target.files);
  files.forEach(file => {
      newGalleryFiles.value.push({
          file: file,
          url: URL.createObjectURL(file),
          name: file.name,
      });
  });
  // Очищаем input, чтобы можно было выбрать те же файлы снова
  event.target.value = null;
};

const removeNewGalleryFile = (index) => {
    // Освобождаем память от URL.createObjectURL
    URL.revokeObjectURL(newGalleryFiles.value[index].url);
    newGalleryFiles.value.splice(index, 1);
};

// --- URL для предпросмотра --- 

const coverPreviewUrl = computed(() => {
    return coverImageFile.value ? URL.createObjectURL(coverImageFile.value) : null;
});

const newGalleryPreviews = computed(() => {
    return newGalleryFiles.value;
});

// --- Отправка данных ---

const handleSubmit = () => {
  const formData = new FormData();

  // Добавляем все поля из формы
  for (const key in form.value) {
      if (key !== 'images' && form.value[key] !== null) { // Не добавляем массив картинок
        formData.append(key, form.value[key]);
      }
  }

  // Добавляем файл обложки, если он выбран
  if (coverImageFile.value) {
    formData.append('image_file', coverImageFile.value);
  }

  // Добавляем новые файлы галереи
  if (newGalleryFiles.value.length) {
      newGalleryFiles.value.forEach(fileObj => {
          formData.append('gallery_files[]', fileObj.file);
      });
  }

  // Для метода PUT в Laravel, если это редактирование
  if (props.isEditing) {
      formData.append('_method', 'POST'); // Используем POST как договорились в роутах
  }

  emit('save', { formData, gameId: props.game?.id });
  close();
};

const requestImageDelete = (imageId) => {
    if (confirm('Вы уверены, что хотите удалить это изображение?')) {
        emit('delete-image', imageId);
    }
};

const close = () => {
  emit('close');
};
</script>

<style scoped>
/* ... Оставляем все стили как были, но добавляем новые ... */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7); display: flex; justify-content: center; align-items: center; z-index: 1000;}
.modal-content { background-color: #2a3a50; padding: 25px; border-radius: 10px; width: 90%; max-width: 750px; max-height: 90vh; overflow-y: auto; box-shadow: 0 5px 20px rgba(0,0,0,0.4);}
.modal-title { margin: 0 0 20px; color: #fff; font-size: 1.6rem; font-weight: 600; border-bottom: 1px solid #4a5a70; padding-bottom: 15px;}
.subsection-title { color: #a0c3ff; font-size: 1.2rem; margin: 25px 0 15px; font-weight: 500; border-bottom: 1px solid #4a5a70; padding-bottom: 8px;}
.form-divider { border: none; border-top: 1px solid #4a5a70; margin: 25px 0;}
.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;}
.form-group { display: flex; flex-direction: column; margin-bottom: 15px;}
.form-group label { margin-bottom: 8px; color: #c0d0e0; font-size: 0.9rem; font-weight: 500;}
.form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #4a5a70; border-radius: 5px; background-color: #1e2a3a; color: #e0e0e0; font-size: 1rem; transition: border-color 0.2s;}
.form-group input:focus, .form-group textarea:focus { outline: none; border-color: #3b82f6;}
.form-actions { display: flex; justify-content: flex-end; gap: 15px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #4a5a70;}
.btn-save, .btn-cancel { padding: 12px 24px; border-radius: 6px; border: none; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.2s;}
.btn-save { background-color: #3b82f6; color: white;}
.btn-save:hover { background-color: #2563eb; }
.btn-cancel { background-color: #4b5563; color: #e5e7eb;}
.btn-cancel:hover { background-color: #5b6573; }

/* Стили для галереи */
.image-preview-container { margin-top: 15px; }
.image-preview { max-width: 150px; border-radius: 5px; border: 2px solid #4a5a70; }

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 15px;
    margin-top: 10px;
}

.gallery-item {
    position: relative;
    border-radius: 5px;
    overflow: hidden;
}

.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.gallery-item .btn-delete-img {
    position: absolute;
    top: 5px;
    right: 5px;
    background-color: rgba(239, 68, 68, 0.8);
    color: white;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    font-size: 12px;
    line-height: 24px;
    text-align: center;
    cursor: pointer;
    transition: background-color 0.2s;
    opacity: 0;
}

.gallery-item:hover .btn-delete-img {
    opacity: 1;
}

.gallery-item.new-preview .file-name {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    background: rgba(0,0,0,0.6);
    color: #fff;
    font-size: 11px;
    padding: 4px;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.gallery-item.new-preview .btn-delete-img {
    opacity: 1; /* Всегда показывать кнопку для новых превью */
}

</style>
