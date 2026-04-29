<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useHead } from '@vueuse/head';
import { useAuthStore } from '../stores/auth';
import { useToast } from '../composables/useToast';
import api from '../api/axios';
import { renderMarkdown } from '../utils/markdown';

const router = useRouter();
const authStore = useAuthStore();
const toast = useToast();

useHead({
  title: 'Новая хроника — GameStore',
  meta: [
    { name: 'description', content: 'Напишите свой пост: обзор, гайд, обсуждение или новость для сообщества GameStore.' },
  ],
});

// ── State ────────────────────────────────────────────────
const form = ref({
  title: '',
  body: '',
  tags: [],         // chips
  game_id: null,
  cover_url: null,
  visibility: 'public',
  publish_now: true,
});

const tagInput = ref('');
const coverFile = ref(null);
const coverPreviewUrl = ref(null);
const uploadingCover = ref(false);
const submitting = ref(false);
const error = ref('');
const tab = ref('write'); // 'write' | 'preview'
const textareaRef = ref(null);

const POPULAR_TAGS = [
  '#обзор', '#гайд', '#обсуждение', '#помощь', '#вопрос',
  '#новость', '#стрим', '#патч', '#мод', '#vpn',
];

const RECOMMENDED_GAMES = ref([]); // подгрузим из /api/games для select

// ── localStorage черновик ────────────────────────────────
const DRAFT_KEY = 'gs_post_draft_v1';

const saveDraft = () => {
  try {
    localStorage.setItem(DRAFT_KEY, JSON.stringify(form.value));
  } catch (_) { /* quota exceeded — ignore */ }
};
const loadDraft = () => {
  try {
    const raw = localStorage.getItem(DRAFT_KEY);
    if (raw) {
      const draft = JSON.parse(raw);
      Object.assign(form.value, draft);
    }
  } catch (_) { /* corrupted — ignore */ }
};
const clearDraft = () => {
  try { localStorage.removeItem(DRAFT_KEY); } catch (_) {}
};

// Авто-сохранение при изменении формы (debounced)
let saveTimer = null;
watch(form, () => {
  clearTimeout(saveTimer);
  saveTimer = setTimeout(saveDraft, 800);
}, { deep: true });

// ── Markdown preview ────────────────────────────────────
const renderedPreview = computed(() => renderMarkdown(form.value.body));

const charCount = computed(() => form.value.body.length);

// ── Toolbar (markdown insert helpers) ─────────────────────
const wrapSelection = (before, after = before) => {
  const ta = textareaRef.value;
  if (!ta) return;
  const start = ta.selectionStart;
  const end = ta.selectionEnd;
  const sel = form.value.body.substring(start, end);
  const newText = form.value.body.substring(0, start) + before + sel + after + form.value.body.substring(end);
  form.value.body = newText;
  // Курсор после before+sel
  setTimeout(() => {
    ta.focus();
    ta.selectionStart = start + before.length;
    ta.selectionEnd   = start + before.length + sel.length;
  }, 0);
};

const insertLine = (prefix) => {
  const ta = textareaRef.value;
  if (!ta) return;
  const start = ta.selectionStart;
  // Найти начало текущей строки
  const before = form.value.body.substring(0, start);
  const lastNl = before.lastIndexOf('\n');
  const lineStart = lastNl + 1;
  form.value.body =
    form.value.body.substring(0, lineStart) +
    prefix + form.value.body.substring(lineStart);
  setTimeout(() => {
    ta.focus();
    ta.selectionStart = ta.selectionEnd = start + prefix.length;
  }, 0);
};

const tools = {
  bold:    () => wrapSelection('**'),
  italic:  () => wrapSelection('_'),
  h1:      () => insertLine('# '),
  h2:      () => insertLine('## '),
  list:    () => insertLine('- '),
  numList: () => insertLine('1. '),
  quote:   () => insertLine('> '),
  link:    () => {
    const url = prompt('URL ссылки:');
    if (url) wrapSelection('[', `](${url})`);
  },
  image:   () => {
    const url = prompt('URL картинки:');
    if (url) {
      const ta = textareaRef.value;
      const pos = ta?.selectionStart ?? form.value.body.length;
      const insert = `![картинка](${url})`;
      form.value.body =
        form.value.body.substring(0, pos) + insert + form.value.body.substring(pos);
    }
  },
  code:    () => wrapSelection('`'),
  codeblock: () => wrapSelection('\n```\n', '\n```\n'),
};

// ── Tags ─────────────────────────────────────────────────
const normalizeTag = (raw) => {
  let t = String(raw || '').trim().toLowerCase().replace(/\s+/g, '-');
  if (t && !t.startsWith('#')) t = '#' + t;
  return t;
};

const addTag = (raw) => {
  const t = normalizeTag(raw);
  if (!t || t.length < 2 || t.length > 30) return;
  if (form.value.tags.includes(t)) return;
  if (form.value.tags.length >= 5) {
    toast.warning('Максимум 5 тегов');
    return;
  }
  form.value.tags.push(t);
};

const onTagKeydown = (e) => {
  if (e.key === 'Enter' || e.key === ',') {
    e.preventDefault();
    addTag(tagInput.value);
    tagInput.value = '';
  } else if (e.key === 'Backspace' && tagInput.value === '' && form.value.tags.length) {
    form.value.tags.pop();
  }
};

const removeTag = (t) => {
  form.value.tags = form.value.tags.filter(x => x !== t);
};

// ── Cover upload ─────────────────────────────────────────
const onCoverChange = async (e) => {
  const file = e.target.files?.[0];
  if (!file) return;

  if (file.size > 5 * 1024 * 1024) {
    toast.error('Картинка слишком большая (макс. 5 МБ)');
    return;
  }

  // Локальный preview
  coverPreviewUrl.value = URL.createObjectURL(file);
  coverFile.value = file;

  // Загружаем на сервер
  uploadingCover.value = true;
  try {
    const formData = new FormData();
    formData.append('image', file);
    const { data } = await api.post('/posts/upload-cover', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    form.value.cover_url = data.url;
    toast.success('Обложка загружена');
  } catch (e) {
    toast.error(e.response?.data?.message || 'Не удалось загрузить обложку');
    coverPreviewUrl.value = null;
    coverFile.value = null;
  } finally {
    uploadingCover.value = false;
  }
};

const removeCover = () => {
  coverPreviewUrl.value = null;
  coverFile.value = null;
  form.value.cover_url = null;
};

// ── Игры (для опционального select) ──────────────────────
const loadGames = async () => {
  try {
    const { data } = await api.get('/games', { params: { per_page: 100 } });
    RECOMMENDED_GAMES.value = (data.data || data).map(g => ({
      id: g.id,
      title: g.title,
    }));
  } catch (_) {
    RECOMMENDED_GAMES.value = [];
  }
};

// ── Submit ───────────────────────────────────────────────
const validate = () => {
  if (form.value.body.trim().length < 10) {
    error.value = 'Тело поста минимум 10 символов.';
    return false;
  }
  if (form.value.tags.length === 0) {
    error.value = 'Укажите минимум 1 тег (например, #обзор или #гайд).';
    return false;
  }
  return true;
};

const submit = async () => {
  if (submitting.value) return;
  error.value = '';
  if (!validate()) return;

  submitting.value = true;
  try {
    const { data } = await api.post('/posts', form.value);
    clearDraft();
    toast.success(
      data.moderation_status === 'approved'
        ? 'Пост опубликован!'
        : 'Пост отправлен на модерацию.'
    );
    router.push({ name: 'post', params: { id: data.id } });
  } catch (e) {
    if (e.response?.status === 422) {
      const errors = e.response.data?.errors;
      if (errors) {
        error.value = Object.values(errors)[0]?.[0] || 'Проверьте форму.';
      } else {
        error.value = e.response.data?.message || 'Проверьте форму.';
      }
    } else if (e.response?.status === 403) {
      error.value = e.response.data?.message || 'Нет прав на создание поста.';
    } else if (e.response?.status === 429) {
      error.value = 'Слишком много постов за минуту. Подождите.';
    } else {
      error.value = 'Не удалось опубликовать пост.';
    }
    toast.error(error.value);
  } finally {
    submitting.value = false;
  }
};

const cancel = () => {
  if (form.value.body || form.value.title) {
    if (!confirm('Черновик сохранится в браузере. Уйти?')) return;
  }
  router.back();
};

const discardDraft = () => {
  if (!confirm('Удалить черновик безвозвратно?')) return;
  form.value = {
    title: '',
    body: '',
    tags: [],
    game_id: null,
    cover_url: null,
    visibility: 'public',
    publish_now: true,
  };
  clearDraft();
  toast.info('Черновик удалён');
};

// ── Lifecycle ────────────────────────────────────────────
onMounted(() => {
  loadDraft();
  loadGames();
});
onBeforeUnmount(() => {
  clearTimeout(saveTimer);
  saveDraft();
});
</script>

<template>
  <div class="post-editor-page">
    <div class="editor-wrap">

      <!-- ── Header ── -->
      <div class="editor-header">
        <div>
          <span class="tribal-eyebrow">
            <span class="eb-spike"></span>
            Новая хроника
            <span class="eb-spike"></span>
          </span>
          <h1>Создать пост</h1>
        </div>
        <div class="header-actions">
          <button class="btn-ghost" @click="cancel">Отмена</button>
          <button
            v-if="form.body || form.title || form.tags.length"
            class="btn-ghost danger"
            @click="discardDraft"
            title="Удалить черновик"
          >🗑 Черновик</button>
          <button
            class="btn-primary"
            :disabled="submitting || uploadingCover"
            @click="submit"
          >
            <span v-if="submitting">Куём…</span>
            <span v-else>Опубликовать</span>
          </button>
        </div>
      </div>

      <Transition name="msg">
        <div v-if="error" class="err-banner">⚠ {{ error }}</div>
      </Transition>

      <!-- ── Cover ── -->
      <div class="cover-block">
        <div v-if="!coverPreviewUrl && !form.cover_url" class="cover-placeholder">
          <input
            type="file"
            accept="image/jpeg,image/jpg,image/png,image/webp"
            @change="onCoverChange"
            id="cover-upload"
            class="cover-input"
            :disabled="uploadingCover"
          />
          <label for="cover-upload" class="cover-label">
            <span class="cover-icon">🖼</span>
            <span class="cover-text">
              <strong>Добавить обложку</strong>
              <small>JPG, PNG, WebP — до 5 МБ</small>
            </span>
          </label>
        </div>
        <div v-else class="cover-preview">
          <img :src="coverPreviewUrl || form.cover_url" alt="Обложка" />
          <button class="cover-remove" @click="removeCover" :disabled="uploadingCover">
            ✕ Убрать
          </button>
          <div v-if="uploadingCover" class="cover-loading">
            <div class="spinner"></div> Загружаем…
          </div>
        </div>
      </div>

      <!-- ── Title ── -->
      <input
        v-model="form.title"
        type="text"
        class="title-input"
        placeholder="Заголовок (необязательно)"
        maxlength="200"
      />

      <!-- ── Tags ── -->
      <div class="tags-block">
        <label class="block-label">
          Теги <span class="hint">от 1 до 5, через Enter</span>
        </label>
        <div class="tags-input-wrap">
          <span
            v-for="t in form.tags"
            :key="t"
            class="tag-chip"
          >
            {{ t }}
            <button type="button" @click="removeTag(t)" aria-label="Удалить тег">✕</button>
          </span>
          <input
            v-model="tagInput"
            @keydown="onTagKeydown"
            @blur="addTag(tagInput); tagInput = ''"
            type="text"
            class="tag-input"
            placeholder="#обзор"
          />
        </div>
        <div class="popular-tags">
          <button
            v-for="t in POPULAR_TAGS.filter(p => !form.tags.includes(p))"
            :key="t"
            class="popular-tag"
            type="button"
            @click="addTag(t)"
          >+ {{ t }}</button>
        </div>
      </div>

      <!-- ── Game (optional) ── -->
      <div class="field">
        <label class="block-label">
          Привязать к игре <span class="hint">не обязательно</span>
        </label>
        <select v-model="form.game_id" class="game-select">
          <option :value="null">— без привязки —</option>
          <option v-for="g in RECOMMENDED_GAMES" :key="g.id" :value="g.id">
            {{ g.title }}
          </option>
        </select>
      </div>

      <!-- ── Editor (markdown) ── -->
      <div class="editor-block">
        <div class="editor-tabs">
          <button :class="{ active: tab === 'write' }" @click="tab = 'write'">
            ⚒ Написать
          </button>
          <button :class="{ active: tab === 'preview' }" @click="tab = 'preview'">
            👁 Превью
          </button>
          <span class="char-counter" :class="{ warn: charCount > 45000 }">
            {{ charCount }} / 50 000
          </span>
        </div>

        <div v-if="tab === 'write'" class="write-pane">
          <!-- Toolbar -->
          <div class="md-toolbar">
            <button type="button" @click="tools.bold" title="Жирный (**)"><strong>B</strong></button>
            <button type="button" @click="tools.italic" title="Курсив (_)"><em>I</em></button>
            <span class="md-sep"></span>
            <button type="button" @click="tools.h1" title="Заголовок H1">H1</button>
            <button type="button" @click="tools.h2" title="Заголовок H2">H2</button>
            <span class="md-sep"></span>
            <button type="button" @click="tools.link" title="Ссылка">🔗</button>
            <button type="button" @click="tools.image" title="Картинка">🖼</button>
            <span class="md-sep"></span>
            <button type="button" @click="tools.list" title="Список">≡</button>
            <button type="button" @click="tools.numList" title="Нумерованный список">1.</button>
            <button type="button" @click="tools.quote" title="Цитата">❝</button>
            <span class="md-sep"></span>
            <button type="button" @click="tools.code" title="Inline-код">&lt;/&gt;</button>
            <button type="button" @click="tools.codeblock" title="Блок кода">{}</button>
          </div>
          <textarea
            ref="textareaRef"
            v-model="form.body"
            class="md-textarea"
            :placeholder="`Расскажите свою историю...\n\nПоддерживается **markdown**:\n- # Заголовок\n- **жирный** и _курсив_\n- [ссылка](https://...)\n- > цитата\n- \`код\``"
            maxlength="50000"
          ></textarea>
        </div>

        <div v-else class="preview-pane">
          <div v-if="!form.body.trim()" class="preview-empty">
            Здесь появится превью — начните писать на вкладке «Написать».
          </div>
          <div v-else class="md-rendered" v-html="renderedPreview"></div>
        </div>
      </div>

      <p class="footer-note">
        💡 Черновик сохраняется в браузере автоматически. Можно безопасно
        закрыть вкладку.
      </p>
    </div>
  </div>
</template>

<style scoped>
.post-editor-page {
  min-height: 100vh;
  padding: 40px 24px 80px;
  background: var(--ash-obsidian);
}
.editor-wrap {
  max-width: 920px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 22px;
}

/* ── Header ── */
.editor-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 16px;
  flex-wrap: wrap;
}
.editor-header h1 {
  font-family: var(--font-display);
  font-size: clamp(1.7rem, 3.5vw, 2.4rem);
  color: var(--text-bright);
  margin: 6px 0 0;
}
.header-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.btn-primary {
  padding: 12px 28px;
  background: var(--grad-ember);
  color: var(--text-bright);
  font-family: var(--font-display);
  font-weight: var(--fw-bold);
  letter-spacing: 1px;
  text-transform: uppercase;
  border: 1px solid var(--ember-deep);
  border-radius: 6px;
  cursor: pointer;
  box-shadow: var(--inset-iron-top), 0 4px 14px rgba(239, 74, 24, 0.35);
  transition: transform 0.2s, filter 0.2s;
}
.btn-primary:hover:not(:disabled) { transform: translateY(-2px); filter: brightness(1.1); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-ghost {
  padding: 11px 18px;
  background: transparent;
  color: var(--text-parchment);
  border: 1px solid var(--iron-mid);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-ghost:hover { background: var(--ash-coal); color: var(--text-bone); }
.btn-ghost.danger:hover { color: var(--ember-flame); border-color: var(--ember-flame); }

.err-banner {
  padding: 12px 18px;
  background: linear-gradient(180deg, rgba(226, 67, 16, 0.18), rgba(138, 31, 24, 0.12));
  border: 1px solid rgba(226, 67, 16, 0.5);
  border-radius: 6px;
  color: #ff8433;
}

/* ── Cover ── */
.cover-block { position: relative; }
.cover-placeholder {
  position: relative;
  background: linear-gradient(180deg, var(--ash-coal), var(--ash-stone));
  border: 2px dashed var(--iron-mid);
  border-radius: 8px;
  min-height: 180px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}
.cover-placeholder:hover { border-color: var(--bronze); }
.cover-input { position: absolute; opacity: 0; inset: 0; cursor: pointer; }
.cover-label {
  display: flex; flex-direction: column; align-items: center; gap: 8px;
  color: var(--text-parchment);
  pointer-events: none;
}
.cover-icon { font-size: 2.4rem; }
.cover-text strong { font-family: var(--font-display); color: var(--text-bone); }
.cover-text small { display: block; font-size: 0.78rem; color: var(--text-ash); margin-top: 2px; }

.cover-preview {
  position: relative;
  border-radius: 8px;
  overflow: hidden;
  background: var(--ash-coal);
}
.cover-preview img {
  display: block;
  width: 100%;
  max-height: 360px;
  object-fit: cover;
}
.cover-remove {
  position: absolute;
  top: 12px;
  right: 12px;
  padding: 8px 14px;
  background: rgba(8, 6, 10, 0.85);
  color: #ff8433;
  border: 1px solid rgba(255, 132, 51, 0.5);
  border-radius: 6px;
  cursor: pointer;
  backdrop-filter: blur(4px);
}
.cover-loading {
  position: absolute; inset: 0;
  background: rgba(8, 6, 10, 0.8);
  display: flex; align-items: center; justify-content: center;
  gap: 10px; color: var(--text-bone);
}
.spinner {
  width: 22px; height: 22px;
  border: 2px solid var(--iron-mid);
  border-top-color: var(--ember-flame);
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Title ── */
.title-input {
  width: 100%;
  padding: 16px 20px;
  background: linear-gradient(180deg, rgba(8, 6, 10, 0.6), rgba(18, 16, 13, 0.7));
  border: 1px solid var(--iron-mid);
  border-radius: 6px;
  color: var(--text-bright);
  font-family: var(--font-display);
  font-size: 1.35rem;
  font-weight: var(--fw-bold);
  outline: none;
  transition: border-color 0.2s;
}
.title-input:focus { border-color: var(--ember-flame); }
.title-input::placeholder { color: var(--text-smoke); }

/* ── Tags ── */
.tags-block, .field { display: flex; flex-direction: column; gap: 10px; }
.block-label {
  font-family: var(--font-display);
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 1.2px;
  color: var(--text-bone);
}
.hint {
  font-weight: 400;
  font-size: 0.74rem;
  color: var(--text-ash);
  letter-spacing: 0;
  text-transform: none;
}
.tags-input-wrap {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  min-height: 50px;
  padding: 10px 14px;
  background: linear-gradient(180deg, rgba(8, 6, 10, 0.6), rgba(18, 16, 13, 0.7));
  border: 1px solid var(--iron-mid);
  border-radius: 6px;
}
.tag-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 4px 4px 10px;
  background: rgba(226, 67, 16, 0.15);
  color: var(--ember-spark);
  border: 1px solid rgba(226, 67, 16, 0.4);
  border-radius: 14px;
  font-family: var(--font-ui);
  font-size: 0.85rem;
}
.tag-chip button {
  width: 20px; height: 20px;
  background: transparent;
  border: none;
  color: var(--ember-spark);
  cursor: pointer;
  border-radius: 50%;
  transition: background 0.15s;
}
.tag-chip button:hover { background: rgba(226, 67, 16, 0.25); }
.tag-input {
  flex: 1;
  min-width: 120px;
  background: transparent;
  border: none;
  outline: none;
  color: var(--text-bone);
  font-size: 0.95rem;
}
.tag-input::placeholder { color: var(--text-smoke); }
.popular-tags { display: flex; flex-wrap: wrap; gap: 6px; }
.popular-tag {
  padding: 4px 10px;
  background: transparent;
  color: var(--text-ash);
  border: 1px dashed var(--iron-mid);
  border-radius: 12px;
  font-size: 0.78rem;
  cursor: pointer;
  transition: all 0.2s;
}
.popular-tag:hover {
  color: var(--ember-spark);
  border-color: var(--ember-spark);
  background: rgba(226, 67, 16, 0.08);
}

/* ── Game select ── */
.game-select {
  padding: 10px 14px;
  background: linear-gradient(180deg, rgba(8, 6, 10, 0.6), rgba(18, 16, 13, 0.7));
  border: 1px solid var(--iron-mid);
  border-radius: 6px;
  color: var(--text-bone);
  font-family: var(--font-body);
  font-size: 0.95rem;
  outline: none;
  cursor: pointer;
}
.game-select:focus { border-color: var(--ember-flame); }

/* ── Editor ── */
.editor-block {
  background: linear-gradient(180deg, rgba(8, 6, 10, 0.6), rgba(18, 16, 13, 0.7));
  border: 1px solid var(--iron-mid);
  border-radius: 6px;
  overflow: hidden;
}
.editor-tabs {
  display: flex;
  align-items: center;
  border-bottom: 1px solid var(--iron-mid);
  background: var(--ash-coal);
}
.editor-tabs button {
  padding: 10px 18px;
  background: transparent;
  color: var(--text-parchment);
  border: none;
  border-right: 1px solid var(--iron-mid);
  cursor: pointer;
  font-family: var(--font-display);
  font-size: 0.88rem;
  letter-spacing: 0.5px;
  transition: all 0.2s;
}
.editor-tabs button:hover { color: var(--text-bone); background: rgba(226, 67, 16, 0.08); }
.editor-tabs button.active {
  color: var(--ember-gold);
  background: linear-gradient(180deg, rgba(226, 67, 16, 0.12), transparent);
  border-bottom: 2px solid var(--ember-flame);
}
.char-counter {
  margin-left: auto;
  padding: 0 18px;
  font-family: var(--font-ui);
  font-size: 0.8rem;
  color: var(--text-ash);
}
.char-counter.warn { color: #ff8433; }

.md-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  padding: 8px 12px;
  background: rgba(8, 6, 10, 0.4);
  border-bottom: 1px solid var(--iron-mid);
}
.md-toolbar button {
  min-width: 32px;
  height: 32px;
  padding: 0 8px;
  background: transparent;
  color: var(--text-parchment);
  border: 1px solid transparent;
  border-radius: 4px;
  cursor: pointer;
  font-family: var(--font-ui);
  font-size: 0.85rem;
  transition: all 0.15s;
}
.md-toolbar button:hover {
  background: rgba(226, 67, 16, 0.12);
  color: var(--ember-gold);
  border-color: rgba(226, 67, 16, 0.3);
}
.md-sep {
  width: 1px;
  height: 22px;
  background: var(--iron-mid);
  margin: 0 4px;
  align-self: center;
}
.md-textarea {
  width: 100%;
  min-height: 360px;
  padding: 18px 22px;
  background: transparent;
  border: none;
  resize: vertical;
  color: var(--text-bone);
  font-family: var(--font-body);
  font-size: 1rem;
  line-height: 1.65;
  outline: none;
}
.md-textarea::placeholder {
  color: var(--text-smoke);
  white-space: pre-wrap;
}

.preview-pane { padding: 24px 28px; min-height: 360px; }
.preview-empty {
  color: var(--text-ash);
  font-style: italic;
  text-align: center;
  padding: 60px 0;
}
.md-rendered {
  color: var(--text-bone);
  font-family: var(--font-body);
  line-height: 1.7;
}
.md-rendered :deep(h1) { font-family: var(--font-display); font-size: 1.8rem; color: var(--text-bright); margin: 1em 0 0.5em; }
.md-rendered :deep(h2) { font-family: var(--font-display); font-size: 1.4rem; color: var(--text-bright); margin: 1em 0 0.5em; }
.md-rendered :deep(h3) { font-family: var(--font-display); font-size: 1.15rem; color: var(--text-bone); margin: 0.8em 0 0.4em; }
.md-rendered :deep(p) { margin: 0.7em 0; }
.md-rendered :deep(a) { color: var(--ember-spark); text-decoration: underline; }
.md-rendered :deep(blockquote) {
  border-left: 3px solid var(--ember-flame);
  padding: 4px 14px;
  margin: 1em 0;
  background: rgba(226, 67, 16, 0.08);
  color: var(--text-parchment);
  font-style: italic;
}
.md-rendered :deep(code) {
  background: rgba(8, 6, 10, 0.6);
  padding: 2px 6px;
  border-radius: 3px;
  border: 1px solid var(--iron-mid);
  font-family: 'Courier New', monospace;
  font-size: 0.92em;
  color: var(--ember-gold);
}
.md-rendered :deep(pre) {
  background: rgba(8, 6, 10, 0.7);
  padding: 14px 18px;
  border-radius: 6px;
  border: 1px solid var(--iron-mid);
  overflow-x: auto;
  margin: 1em 0;
}
.md-rendered :deep(pre code) {
  background: transparent;
  border: none;
  padding: 0;
  color: var(--text-bone);
}
.md-rendered :deep(ul), .md-rendered :deep(ol) { padding-left: 22px; margin: 0.7em 0; }
.md-rendered :deep(li) { margin: 0.3em 0; }
.md-rendered :deep(img) { display: block; max-width: 100%; height: auto; border-radius: 4px; margin: 1em 0; }
.md-rendered :deep(table) { border-collapse: collapse; margin: 1em 0; }
.md-rendered :deep(th), .md-rendered :deep(td) { padding: 6px 12px; border: 1px solid var(--iron-mid); }
.md-rendered :deep(th) { background: var(--ash-coal); font-weight: 700; }
.md-rendered :deep(hr) { border: none; border-top: 1px solid var(--iron-mid); margin: 1.5em 0; }

.footer-note {
  font-size: 0.85rem;
  color: var(--text-ash);
  text-align: center;
}
</style>
