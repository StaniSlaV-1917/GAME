<template>
  <div class="modal-overlay" @click.self="close">
    <div class="modal-content">
      <h2 class="modal-title">{{ isEditing ? 'Редактировать новость' : 'Создать новость' }}</h2>
      <form @submit.prevent="handleSubmit">
        
        <!-- Основные поля -->
        <div class="form-group">
          <label for="title">Заголовок</label>
          <input id="title" v-model="editableArticle.title" type="text" required>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label for="image_url">URL обложки</label>
            <input id="image_url" v-model="editableArticle.image_url" type="text">
          </div>
          <div class="form-group">
            <label for="author">Автор</label>
            <input id="author" v-model="editableArticle.author" type="text">
          </div>
        </div>

        <hr class="form-divider" />

        <!-- Конструктор контента -->
        <h3 class="subsection-title">Содержимое статьи</h3>
        <div class="content-blocks-container">
          <div v-for="(block, index) in editableArticle.content_blocks" :key="`content-${index}`" class="content-block">
            <div class="block-header">
              <span class="block-type-label">{{ block.type }}</span>
              <button type="button" @click="removeContentBlock(index)" class="btn-remove-block">Удалить блок</button>
            </div>
            <div v-if="block.type === 'heading'" class="form-group">
              <label>Текст заголовка</label>
              <input v-model="block.content" type="text" placeholder="Введите заголовок...">
            </div>
            <div v-if="block.type === 'paragraph'" class="form-group">
              <label>Текст параграфа</label>
              <textarea v-model="block.content" rows="5" placeholder="Введите текст..."></textarea>
            </div>
            <div v-if="block.type === 'list'" class="form-group">
              <label>Элементы списка (каждый с новой строки)</label>
              <textarea v-model="block.items" rows="5" placeholder="- Элемент 1\n- Элемент 2"></textarea>
            </div>
          </div>
        </div>
        <div class="add-blocks-actions">
          <span>Добавить новый блок:</span>
          <button type="button" @click="addContentBlock('heading')" class="btn-add-block">Заголовок</button>
          <button type="button" @click="addContentBlock('paragraph')" class="btn-add-block">Параграф</button>
          <button type="button" @click="addContentBlock('list')" class="btn-add-block">Список</button>
        </div>

        <hr class="form-divider" />

        <!-- Галерея -->
        <h3 class="subsection-title">Галерея изображений</h3>
        <div class="gallery-container">
            <div v-for="(item, index) in editableArticle.gallery" :key="`gallery-${index}`" class="gallery-item">
                <input v-model="editableArticle.gallery[index]" type="text" placeholder="https://example.com/image.png">
                <button type="button" @click="removeGalleryItem(index)" class="btn-remove-item">✕</button>
            </div>
        </div>
        <button type="button" @click="addGalleryItem" class="btn-add-item">+ Добавить изображение в галерею</button>


        <div class="form-actions">
          <button type="submit" class="btn-save">{{ isEditing ? 'Сохранить изменения' : 'Создать' }}</button>
          <button type="button" @click="close" class="btn-cancel">Отмена</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  article: Object,
  isEditing: Boolean
});

const emit = defineEmits(['close', 'save']);

const getInitialArticleData = () => ({
  title: '',
  image_url: '',
  author: 'GameStore Staff',
  content_blocks: [],
  gallery: []
});

const editableArticle = ref(getInitialArticleData());

// --- Логика для работы со списками в textarea ---
const listArrayToString = (items) => (Array.isArray(items) ? items.join('\n') : (items || ''));
const stringToListArray = (str) => (typeof str === 'string' ? str.split('\n').map(item => item.trim()).filter(Boolean) : (str || []));

watch(() => props.article, (newArticle) => {
  if (props.isEditing && newArticle) {
    // Создаем полную структуру данных, чтобы избежать отсутствия полей
    const baseArticle = getInitialArticleData();
    const freshArticle = { ...baseArticle, ...JSON.parse(JSON.stringify(newArticle)) };

    // Преобразуем массив 'items' в строку для каждого блока-списка
    if (freshArticle.content_blocks) {
      freshArticle.content_blocks.forEach(block => {
        if (block.type === 'list') {
          block.items = listArrayToString(block.items);
        }
      });
    }
    editableArticle.value = freshArticle;

  } else {
    // Для новой статьи просто используем чистый объект
    editableArticle.value = getInitialArticleData();
  }
}, { immediate: true, deep: true });

// --- Управление блоками контента ---
const addContentBlock = (type) => {
  const newBlock = { type };
  if (type === 'heading' || type === 'paragraph') newBlock.content = '';
  if (type === 'list') newBlock.items = '';
  editableArticle.value.content_blocks.push(newBlock);
};

const removeContentBlock = (index) => {
  editableArticle.value.content_blocks.splice(index, 1);
};

// --- Управление галереей ---
const addGalleryItem = () => {
  if (!Array.isArray(editableArticle.value.gallery)) {
      editableArticle.value.gallery = [];
  }
  editableArticle.value.gallery.push('');
};

const removeGalleryItem = (index) => {
  editableArticle.value.gallery.splice(index, 1);
};

const handleSubmit = () => {
  const finalData = JSON.parse(JSON.stringify(editableArticle.value));

  // Преобразуем строки обратно в массивы для блоков-списков
  if (finalData.content_blocks) {
      finalData.content_blocks.forEach(block => {
        if (block.type === 'list') {
          block.items = stringToListArray(block.items);
        }
      });
  }
  // Очистка пустых значений галереи
  if (finalData.gallery) {
      finalData.gallery = finalData.gallery.filter(url => url && url.trim() !== '');
  }

  emit('save', finalData);
  close();
};

const close = () => {
  emit('close');
};

</script>

<style scoped>
/* ... (остальные стили без изменений) ... */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7); display: flex; justify-content: center; align-items: center; z-index: 1000; }
.modal-content { background-color: #2a3a50; padding: 25px; border-radius: 10px; width: 90%; max-width: 750px; max-height: 90vh; overflow-y: auto; box-shadow: 0 5px 20px rgba(0,0,0,0.4); }
.modal-title { margin: 0 0 20px; color: #fff; font-size: 1.6rem; font-weight: 600; border-bottom: 1px solid #4a5a70; padding-bottom: 15px; }
.subsection-title { color: #a0c3ff; font-size: 1.2rem; margin: 20px 0 15px; font-weight: 500; }
.form-divider { border: none; border-top: 1px solid #4a5a70; margin: 25px 0; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-group { display: flex; flex-direction: column; margin-bottom: 15px; }
.form-group label { margin-bottom: 8px; color: #c0d0e0; font-size: 0.9rem; }
.form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #4a5a70; border-radius: 5px; background-color: #1e2a3a; color: #e0e0e0; font-size: 1rem; }
.form-group input:focus, .form-group textarea:focus { outline: none; border-color: #3b82f6; }
textarea { resize: vertical; }

.content-blocks-container { display: flex; flex-direction: column; gap: 20px; }
.content-block { background-color: #1e2a3a; border: 1px solid #4a5a70; padding: 15px; border-radius: 8px; }
.block-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
.block-type-label { font-weight: bold; color: #a0c3ff; text-transform: capitalize; }
.btn-remove-block { background: #ef4444; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; }
.add-blocks-actions { margin-top: 20px; display: flex; align-items: center; gap: 10px; color: #c0d0e0; }
.btn-add-block { background: #374151; color: white; border: none; padding: 8px 12px; border-radius: 5px; cursor: pointer; }

/* Стили для галереи */
.gallery-container { display: flex; flex-direction: column; gap: 10px; margin-bottom: 15px; }
.gallery-item { display: flex; align-items: center; gap: 10px; }
.gallery-item input { flex-grow: 1; }
.btn-remove-item { background: #ef4444; color: white; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; justify-content: center; align-items: center; }
.btn-add-item { background-color: #4b5563; color: white; padding: 8px 15px; border: none; border-radius: 6px; cursor: pointer; align-self: flex-start; }

.form-actions { display: flex; justify-content: flex-end; gap: 15px; margin-top: 25px; padding-top: 20px; border-top: 1px solid #4a5a70; }
.btn-save { background-color: #3b82f6; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; }
.btn-cancel { background-color: #4b5563; color: #e5e7eb; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; }
</style>
