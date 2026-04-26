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
  width: 90%; max-width: 640px; max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 32px 80px rgba(0,0,0,0.6), 0 0 0 1px rgba(255,255,255,0.05);
  scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.1) transparent;
  animation: slideUp 0.22s ease;
}
@keyframes slideUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }

.modal-title {
  margin: 0 0 28px; font-size: 1.5rem; font-weight: 800;
  padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.08);
  background: linear-gradient(135deg, #fff, #94a3b8);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}

.form-group { margin-bottom: 20px; }
.form-group label {
  display: block; margin-bottom: 7px;
  color: #94a3b8; font-size: 0.8rem; font-weight: 700;
  letter-spacing: 0.5px; text-transform: uppercase;
}
.form-group input,
.form-group textarea {
  width: 100%; padding: 11px 14px;
  border: 1px solid rgba(255,255,255,0.1); border-radius: 10px;
  background: rgba(255,255,255,0.04); color: #e5e7eb;
  font-size: 0.95rem; outline: none; box-sizing: border-box;
  transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}
.form-group textarea { resize: vertical; min-height: 200px; font-family: inherit; line-height: 1.6; }
.form-group input::placeholder, .form-group textarea::placeholder { color: #4b5563; }
.form-group input:focus, .form-group textarea:focus {
  border-color: #8b5cf6;
  background: rgba(139,92,246,0.06);
  box-shadow: 0 0 0 3px rgba(139,92,246,0.18);
}
.form-group input[type="file"] { color: #9ca3af; cursor: pointer; }

.image-preview { margin-top: 14px; }
.image-preview p { margin: 0 0 10px; color: #9ca3af; font-size: 0.82rem; }
.image-preview img { max-width: 100%; max-height: 200px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); }

.form-actions {
  display: flex; justify-content: flex-end; gap: 12px;
  margin-top: 32px; padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.07);
}
.btn-save {
  padding: 12px 28px; border-radius: 10px; border: none;
  font-size: 0.95rem; font-weight: 700; cursor: pointer;
  background: linear-gradient(135deg, #8b5cf6, #6366f1);
  color: #fff; box-shadow: 0 4px 16px rgba(139,92,246,0.35);
  transition: all 0.2s;
}
.btn-save:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(139,92,246,0.5); }
.btn-cancel {
  padding: 12px 24px; border-radius: 10px;
  border: 1px solid rgba(255,255,255,0.12);
  background: rgba(255,255,255,0.05); color: #9ca3af;
  font-size: 0.95rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.btn-cancel:hover { border-color: rgba(255,255,255,0.25); color: #e5e7eb; background: rgba(255,255,255,0.08); }
</style>
