<template>
  <div class="modal-overlay" @click.self="close">
    <div class="modal-content">
      <h2 class="modal-title">{{ isEditing ? 'Редактировать новость' : 'Создать новость' }}</h2>
      <form @submit.prevent="handleSubmit">
        
        <div class="form-group">
          <label for="title">Заголовок</label>
          <input id="title" v-model="editableArticle.title" type="text" required>
        </div>

        <div class="form-group">
          <label for="content">Содержимое</label>
          <textarea id="content" v-model="editableArticle.content" rows="10" required></textarea>
        </div>

        <div class="form-group">
          <label for="image">Изображение (обложка)</label>
          <input id="image" type="file" @change="handleFileChange">
          <div v-if="imagePreview" class="image-preview">
            <p>Текущее изображение:</p>
            <img :src="imagePreview" alt="Image preview">
          </div>
        </div>

        <div class="form-group">
          <label for="published_at">Дата публикации</label>
          <input id="published_at" v-model="formattedDate" type="datetime-local">
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-save">{{ isEditing ? 'Сохранить изменения' : 'Создать' }}</button>
          <button type="button" @click="close" class="btn-cancel">Отмена</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';

const props = defineProps({
  article: Object,
  isEditing: Boolean
});

const emit = defineEmits(['close', 'save']);

const getInitialArticleData = () => ({
  id: null,
  title: '',
  content: '',
  image: null,
  published_at: new Date().toISOString().slice(0, 16)
});

const editableArticle = ref(getInitialArticleData());
const imageFile = ref(null);
const imagePreview = ref(null);

// Используем computed property для форматирования даты для input[type=datetime-local]
const formattedDate = computed({
  get() {
    if (!editableArticle.value.published_at) return '';
    return editableArticle.value.published_at.slice(0, 16);
  },
  set(value) {
    editableArticle.value.published_at = value;
  }
});

watch(() => props.article, (newArticle) => {
  if (props.isEditing && newArticle) {
    editableArticle.value = { ...newArticle };
    imagePreview.value = newArticle.image ? newArticle.image : null;
  } else {
    editableArticle.value = getInitialArticleData();
    imagePreview.value = null;
  }
  imageFile.value = null;
}, { immediate: true });

const handleFileChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    imageFile.value = file;
    // Для предпросмотра
    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const handleSubmit = () => {
  const finalData = {
    ...editableArticle.value,
    image: imageFile.value, // Прикрепляем файл
    published_at: editableArticle.value.published_at ? new Date(editableArticle.value.published_at).toISOString() : new Date().toISOString()
  };
  
  emit('save', finalData);
};

const close = () => {
  emit('close');
};

</script>

<style scoped>
/* Стили упрощены и адаптированы для новостей */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7); display: flex; justify-content: center; align-items: center; z-index: 1000; }
.modal-content { background-color: #2a3a50; padding: 25px; border-radius: 10px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; }
.modal-title { margin: 0 0 20px; color: #fff; font-size: 1.6rem; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; color: #c0d0e0; }
.form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #4a5a70; border-radius: 5px; background-color: #1e2a3a; color: #e0e0e0; }
.image-preview { margin-top: 15px; }
.image-preview p { margin-bottom: 10px; color: #c0d0e0; }
.image-preview img { max-width: 100%; max-height: 200px; border-radius: 5px; }
.form-actions { display: flex; justify-content: flex-end; gap: 15px; margin-top: 25px; padding-top: 20px; border-top: 1px solid #4a5a70; }
.btn-save { background-color: #3b82f6; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; }
.btn-cancel { background-color: #4b5563; color: #e5e7eb; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; }
</style>
