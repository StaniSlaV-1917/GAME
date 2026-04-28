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
/* AdminNewsEditModal · Ashenforge — свиток хроник */
.modal-overlay {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(8, 6, 10, 0.82);
  backdrop-filter: blur(6px);
  /* flex-start + padding-top = модалка под хедером, внутренний скролл
     сохранён в .modal-content через max-height + overflow-y. */
  display: flex; justify-content: center; align-items: flex-start;
  padding: calc(73px + 16px) 20px 16px;
  z-index: 9999;
  animation: anFade 0.22s var(--ease-smoke);
}
@keyframes anFade { from { opacity: 0; } to { opacity: 1; } }

.modal-content {
  position: relative;
  background: linear-gradient(180deg,
    var(--ash-ironrust) 0%,
    var(--ash-stone) 40%,
    var(--ash-coal) 100%);
  border: 1px solid var(--bronze-dark);
  clip-path: var(--clip-forged-md);
  padding: 34px 36px;
  width: 92%; max-width: 660px;
  max-height: calc(100vh - 73px - 32px);
  overflow-y: auto;
  box-shadow:
    inset 0 0 0 1px var(--iron-mid),
    var(--shadow-deep),
    var(--inset-forge);
  scrollbar-width: thin;
  scrollbar-color: var(--bronze-dark) transparent;
  animation: anSlide 0.28s var(--ease-forge);
}
.modal-content::-webkit-scrollbar { width: 6px; }
.modal-content::-webkit-scrollbar-thumb { background: var(--bronze-dark); }
@keyframes anSlide { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: none; } }

.modal-title {
  margin: 0 0 28px;
  font-family: var(--font-display);
  font-size: 1.55rem;
  font-weight: 700;
  color: var(--text-bright);
  padding-bottom: 18px;
  border-bottom: 1px dashed var(--iron-dark);
  letter-spacing: 0.3px;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
}

.form-group { margin-bottom: 22px; }
.form-group label {
  display: block;
  margin-bottom: 8px;
  font-family: var(--font-ui);
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  color: var(--bronze);
}
.form-group input,
.form-group textarea {
  width: 100%;
  padding: 12px 15px;
  border: 1px solid var(--iron-mid);
  background: linear-gradient(180deg, rgba(8, 6, 10, 0.75), rgba(18, 16, 13, 0.85));
  color: var(--text-bone);
  font-family: var(--font-body);
  font-size: 0.96rem;
  outline: none;
  box-sizing: border-box;
  box-shadow: var(--inset-iron-top);
  transition: border-color 0.2s var(--ease-smoke), box-shadow 0.2s var(--ease-smoke);
}
.form-group textarea { resize: vertical; min-height: 220px; font-family: var(--font-body); line-height: 1.7; }
.form-group input::placeholder, .form-group textarea::placeholder { color: var(--text-void); }
.form-group input:focus, .form-group textarea:focus {
  border-color: var(--ember-flame);
  box-shadow: var(--inset-iron-top), 0 0 0 3px rgba(226, 67, 16, 0.14);
}
.form-group input[type="file"] { color: var(--text-parchment); cursor: pointer; }

.image-preview { margin-top: 14px; }
.image-preview p {
  margin: 0 0 10px;
  font-family: var(--font-ui);
  color: var(--text-ash);
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 1.3px;
}
.image-preview img {
  max-width: 100%;
  max-height: 220px;
  border: 1px solid var(--iron-mid);
  box-shadow: var(--shadow-cast), var(--inset-iron-top);
  filter: saturate(0.92);
}

.form-actions {
  display: flex; justify-content: flex-end; gap: 12px;
  margin-top: 32px;
  padding-top: 22px;
  border-top: 1px dashed var(--iron-dark);
}

.btn-save {
  position: relative;
  padding: 13px 30px;
  border: 1px solid var(--ember-heart);
  background: var(--grad-ember);
  color: var(--text-bright);
  font-family: var(--font-display);
  font-size: 0.92rem;
  font-weight: 700;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  cursor: pointer;
  clip-path: var(--clip-forged-sm);
  box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.35), var(--glow-ember);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
  overflow: hidden;
  transition: transform 0.2s var(--ease-forge), box-shadow 0.2s var(--ease-smoke);
}
.btn-save::after {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(90deg, transparent, rgba(255, 201, 121, 0.4), transparent);
  transform: translateX(-120%);
  transition: transform 0.6s var(--ease-smoke);
  pointer-events: none;
}
.btn-save:hover {
  transform: translateY(-2px);
  box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.35), var(--glow-ember-strong);
}
.btn-save:hover::after { transform: translateX(120%); }

.btn-cancel {
  padding: 13px 24px;
  border: 1px solid var(--bronze-dark);
  background: transparent;
  color: var(--text-parchment);
  font-family: var(--font-ui);
  font-size: 0.88rem;
  font-weight: 700;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  cursor: pointer;
  box-shadow: var(--inset-iron-top);
  clip-path: var(--clip-forged-sm);
  transition: all 0.22s var(--ease-smoke);
}
.btn-cancel:hover {
  border-color: var(--bronze);
  color: var(--text-bright);
  background: rgba(122, 93, 72, 0.12);
}
</style>
