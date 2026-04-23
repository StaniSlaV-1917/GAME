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
            <label for="rating">Рейтинг (0-10)</label>
            <input id="rating" v-model.number="form.rating" type="number" step="0.1">
          </div>
        </div>

        <!-- Описание -->
        <div class="form-group">
            <label for="description">Описание</label>
            <textarea id="description" v-model="form.description" rows="4"></textarea>
        </div>

        <hr class="form-divider" />

        <!-- Системные требования -->
        <h3 class="subsection-title">Системные требования</h3>
        <div class="form-grid">
            <div class="form-group">
                <label for="os_requirements">ОС</label>
                <input id="os_requirements" v-model="form.os_requirements" type="text" placeholder="Windows 10, 11 (64-bit)">
            </div>
            <div class="form-group">
                <label for="processor_requirements">Процессор</label>
                <input id="processor_requirements" v-model="form.processor_requirements" type="text" placeholder="Intel Core i5-4670">
            </div>
            <div class="form-group">
                <label for="ram_requirements">Оперативная память</label>
                <input id="ram_requirements" v-model="form.ram_requirements" type="text" placeholder="8 GB ОЗУ">
            </div>
            <div class="form-group">
                <label for="graphics_requirements">Видеокарта</label>
                <input id="graphics_requirements" v-model="form.graphics_requirements" type="text" placeholder="GTX 1050, DirectX 12">
            </div>
            <div class="form-group">
                <label for="storage_requirements">Место на диске</label>
                <input id="storage_requirements" v-model="form.storage_requirements" type="text" placeholder="16 GB">
            </div>
        </div>

        <hr class="form-divider" />

        <!-- Трейлер и ссылки -->
        <h3 class="subsection-title">Медиа и ссылки</h3>
        <div class="form-group">
            <label for="trailer_url">URL трейлера (YouTube)</label>
            <input id="trailer_url" v-model="form.trailer_url" type="url" placeholder="https://www.youtube.com/watch?v=...">
        </div>

        <div class="form-group">
            <label for="stopgame_url_code">Код страницы на StopGame</label>
            <input id="stopgame_url_code" v-model="form.stopgame_url_code" type="text" placeholder="izumrudnyy_gorod">
        </div>
        

        <hr class="form-divider" />

        <!-- Цены и скидки -->
        <h3 class="subsection-title">Цены и скидки</h3>
        <div class="form-grid">
            <div class="form-group">
                <label for="old_price">Старая цена (₽)</label>
                <input id="old_price" v-model.number="form.old_price" type="number" step="0.01">
            </div>
            <div class="form-group">
                <label for="discount_percent">Процент скидки (%)</label>
                <input id="discount_percent" v-model.number="form.discount_percent" type="number">
            </div>
        </div>

        <!-- Статусы -->
        <h3 class="subsection-title">Статусы</h3>
        <div class="form-grid-flags">
            <div class="form-group-checkbox">
                <input id="is_featured" v-model="form.is_featured" type="checkbox">
                <label for="is_featured">Хит продаж</label>
            </div>
            <div class="form-group-checkbox">
                <input id="is_new" v-model="form.is_new" type="checkbox">
                <label for="is_new">Новинка</label>
            </div>
        </div>

        <hr class="form-divider" />

        <!-- Обложка -->
        <h3 class="subsection-title">Обложка</h3>
        <div class="form-group">
          <label for="image">URL текущей обложки</label>
           <input id="image" v-model="form.image" type="text" placeholder="/images/image.jpg">
           <div v-if="isEditing && form.image" class="image-preview-container">
            <p>Текущая обложка:</p>
            <img :src="resolveMediaUrl(form.image)" alt="Текущая обложка" class="image-preview" />
          </div>
        </div>

        <hr class="form-divider" />

        <!-- Галерея -->
        <h3 class="subsection-title">Галерея изображений</h3>
        <div v-if="isEditing && form.images && form.images.length" class="gallery-grid">
          <div v-for="image in form.images" :key="image.id" class="gallery-item">
            <img :src="resolveMediaUrl(image.path)" :alt="`Gallery image ${image.id}`"/>
            <button type="button" @click="requestImageDelete(image)" class="btn-delete-img">Удалить</button>
          </div>
        </div>
        <p v-else-if="isEditing">У этой игры пока нет галереи.</p>
        <div class="form-group" style="margin-top: 20px;">
          <label for="gallery_files">Добавить изображения в галерею</label>
          <input id="gallery_files" type="file" multiple @change="handleGalleryFilesChange" accept="image/*">
        </div>
        <div v-if="newGalleryFiles.length" class="gallery-grid">
            <div v-for="(preview, index) in newGalleryFiles" :key="index" class="gallery-item new-preview">
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
import { ref, watch } from 'vue';
import { resolveMediaUrl } from '../utils/media';

const props = defineProps({ game: Object, isEditing: Boolean });
const emit = defineEmits(['close', 'save', 'delete-image']);

const form = ref({});
const newGalleryFiles = ref([]); // Массив объектов { file, url, name }

const getInitialForm = () => ({
    title: '',
    genre: '',
    platform: 'PC',
    price: 0,
    old_price: null,
    discount_percent: null,
    rating: null,
    release_year: new Date().getFullYear(),
    description: '',
    os_requirements: '',
    processor_requirements: '',
    ram_requirements: '',
    graphics_requirements: '',
    storage_requirements: '',
    image: '',
    stopgame_url_code: '',
    trailer_url: '',
    is_featured: false,
    is_new: false,
    images: [],
});

// Watch for price changes to calculate discount
watch(() => form.value.price, (newPrice) => {
    if (form.value.old_price && newPrice) {
        const discount = Math.round(((form.value.old_price - newPrice) / form.value.old_price) * 100);
        form.value.discount_percent = discount > 0 ? discount : null;
    }
});

// Watch for old_price changes to calculate discount
watch(() => form.value.old_price, (newOldPrice) => {
    if (newOldPrice && form.value.price) {
        const discount = Math.round(((newOldPrice - form.value.price) / newOldPrice) * 100);
        form.value.discount_percent = discount > 0 ? discount : null;
    } else if (!newOldPrice) {
        form.value.discount_percent = null;
    }
});

// Watch for discount_percent changes to calculate old_price
watch(() => form.value.discount_percent, (newDiscount) => {
    if (newDiscount && form.value.price) {
        const oldPrice = Math.round(form.value.price / (1 - newDiscount / 100));
        form.value.old_price = oldPrice > form.value.price ? oldPrice : null;
    } else if (!newDiscount) {
        form.value.old_price = null;
    }
});

watch(() => props.game, (newGame) => {
  if (props.isEditing && newGame) {
    form.value = { ...getInitialForm(), ...newGame };
  } else {
    form.value = getInitialForm();
  }
  newGalleryFiles.value = [];
}, { immediate: true, deep: true });

const handleGalleryFilesChange = (event) => {
  const files = Array.from(event.target.files);
  files.forEach(file => {
      newGalleryFiles.value.push({ file: file, url: URL.createObjectURL(file), name: file.name });
  });
  event.target.value = null;
};

const removeNewGalleryFile = (index) => {
    URL.revokeObjectURL(newGalleryFiles.value[index].url);
    newGalleryFiles.value.splice(index, 1);
};

const handleSubmit = () => {
  // Создаем чистый объект данных для отправки
  const gameData = { ...form.value };

  // Преобразуем булевы значения в 0 или 1 для бэкенда
  gameData.is_featured = gameData.is_featured ? 1 : 0;
  gameData.is_new = gameData.is_new ? 1 : 0;

  // Удаляем реактивные и ненужные для бэкенда свойства
  delete gameData.images;
  delete gameData.average_review_rating;
  delete gameData.reviews_count;

  const galleryFormData = new FormData();
  if (newGalleryFiles.value.length > 0) {
    newGalleryFiles.value.forEach(fileObj => {
        galleryFormData.append('gallery[]', fileObj.file);
    });
  }

  emit('save', {
    gameData,
    gameId: props.game?.id,
    galleryFormData: newGalleryFiles.value.length > 0 ? galleryFormData : null
  });

  close();
};

const requestImageDelete = (image) => {
    if (confirm('Вы уверены, что хотите удалить это изображение?')) {
        emit('delete-image', { gameId: props.game.id, imageId: image.id });
    }
};

const close = () => { emit('close'); };

</script>

<style scoped>
.modal-overlay {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(0,0,0,0.75);
  backdrop-filter: blur(6px);
  display: flex; justify-content: center; align-items: center;
  z-index: 1000;
  animation: fadeIn 0.18s ease;
}
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

.modal-content {
  background: rgba(10,15,30,0.97);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 20px;
  padding: 32px;
  width: 90%; max-width: 780px; max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 32px 80px rgba(0,0,0,0.6), 0 0 0 1px rgba(255,255,255,0.05);
  scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.1) transparent;
  animation: slideUp 0.22s ease;
}
@keyframes slideUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
.modal-content::-webkit-scrollbar { width: 4px; }
.modal-content::-webkit-scrollbar-track { background: transparent; }
.modal-content::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }

.modal-title {
  margin: 0 0 28px;
  color: #fff;
  font-size: 1.5rem; font-weight: 800;
  padding-bottom: 20px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
  background: linear-gradient(135deg, #fff, #94a3b8);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}

.subsection-title {
  font-size: 0.72rem; font-weight: 700;
  letter-spacing: 2px; text-transform: uppercase;
  color: #3b82f6;
  margin: 28px 0 16px;
  padding-bottom: 10px;
  border-bottom: 1px solid rgba(59,130,246,0.2);
}

.form-divider { border: none; border-top: 1px solid rgba(255,255,255,0.06); margin: 24px 0; }

.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; }
.form-grid-flags { display: flex; gap: 24px; align-items: center; margin: 16px 0; flex-wrap: wrap; }

.form-group { display: flex; flex-direction: column; margin-bottom: 14px; }
.form-group label {
  margin-bottom: 7px;
  color: #94a3b8; font-size: 0.82rem; font-weight: 600;
  letter-spacing: 0.5px; text-transform: uppercase;
}
.form-group input,
.form-group textarea,
.form-group select {
  width: 100%; padding: 11px 14px;
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 10px;
  background: rgba(255,255,255,0.04);
  color: #e5e7eb; font-size: 0.95rem;
  transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
  outline: none;
  box-sizing: border-box;
}
.form-group input::placeholder, .form-group textarea::placeholder { color: #4b5563; }
.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
  border-color: #3b82f6;
  background: rgba(59,130,246,0.06);
  box-shadow: 0 0 0 3px rgba(59,130,246,0.18);
}
.form-group input[type="number"] { -moz-appearance: textfield; }
.form-group input[type="number"]::-webkit-outer-spin-button,
.form-group input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.form-group input[type="file"] {
  padding: 10px;
  color: #9ca3af;
  cursor: pointer;
}
.form-group select option { background: #0f172a; }
.form-group textarea { resize: vertical; min-height: 100px; }

.form-group-checkbox {
  display: flex; align-items: center; gap: 10px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 10px;
  padding: 12px 16px;
  cursor: pointer;
  transition: border-color 0.2s, background 0.2s;
}
.form-group-checkbox:hover { border-color: rgba(59,130,246,0.3); background: rgba(59,130,246,0.05); }
.form-group-checkbox label { margin: 0; color: #d1d5db; font-size: 0.95rem; font-weight: 500; cursor: pointer; text-transform: none; letter-spacing: 0; }
.form-group-checkbox input { width: 17px; height: 17px; accent-color: #3b82f6; cursor: pointer; flex-shrink: 0; }

.form-actions {
  display: flex; justify-content: flex-end; gap: 12px;
  margin-top: 32px; padding-top: 24px;
  border-top: 1px solid rgba(255,255,255,0.07);
}
.btn-save {
  padding: 12px 28px; border-radius: 10px; border: none;
  font-size: 0.95rem; font-weight: 700; cursor: pointer;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: #fff;
  box-shadow: 0 4px 16px rgba(59,130,246,0.35);
  transition: all 0.2s;
}
.btn-save:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(59,130,246,0.5); }
.btn-cancel {
  padding: 12px 24px; border-radius: 10px;
  border: 1px solid rgba(255,255,255,0.12);
  background: rgba(255,255,255,0.05);
  color: #9ca3af; font-size: 0.95rem; font-weight: 600; cursor: pointer;
  transition: all 0.2s;
}
.btn-cancel:hover { border-color: rgba(255,255,255,0.25); color: #e5e7eb; background: rgba(255,255,255,0.08); }

.image-preview-container { margin-top: 12px; }
.image-preview { max-width: 140px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1); }

.gallery-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
  gap: 12px; margin-top: 12px;
}
.gallery-item {
  position: relative; border-radius: 10px; overflow: hidden;
  aspect-ratio: 16/10;
  border: 1px solid rgba(255,255,255,0.08);
}
.gallery-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
.gallery-item .btn-delete-img {
  position: absolute; top: 6px; right: 6px;
  background: rgba(239,68,68,0.85); color: #fff; border: none;
  border-radius: 6px; width: 26px; height: 26px;
  font-size: 12px; display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: background 0.2s; opacity: 0;
}
.gallery-item:hover .btn-delete-img { opacity: 1; }
.gallery-item.new-preview .file-name {
  position: absolute; bottom: 0; left: 0; right: 0;
  background: rgba(0,0,0,0.7); color: #fff; font-size: 10px;
  padding: 4px 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.gallery-item.new-preview .btn-delete-img { opacity: 1; }
</style>
