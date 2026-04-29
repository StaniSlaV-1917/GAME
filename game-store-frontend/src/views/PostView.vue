<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useHead } from '@vueuse/head';
import { useAuthStore } from '../stores/auth';
import { useToast } from '../composables/useToast';
import api from '../api/axios';
import { renderMarkdown } from '../utils/markdown';
import { resolveMediaUrl } from '../utils/media';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const toast = useToast();

const post = ref(null);
const comments = ref([]);
const loadingPost = ref(true);
const loadingComments = ref(true);
const error = ref('');

// Reply state
const replyingTo = ref(null);   // id parent comment
const replyText = ref('');
const submitting = ref(false);

// Top-level comment input
const newCommentText = ref('');

// Edit state
const editingId = ref(null);
const editingText = ref('');

const postId = computed(() => Number(route.params.id));

// Locale
const roleLabels = { user: 'Воин', manager: 'Кузнец', admin: 'Старейшина' };
const roleIcons  = { user: '⚔', manager: '🔨', admin: '👑' };

// SEO
useHead(() => ({
  title: post.value?.title
    ? `${post.value.title} — GameStore`
    : 'Хроника — GameStore',
  meta: [
    { name: 'description', content: post.value
      ? (post.value.body || '').slice(0, 160)
      : 'Пост сообщества GameStore.' },
    { property: 'og:title', content: post.value?.title || 'Хроника GameStore' },
    { property: 'og:image', content: post.value?.cover_url || '/images.png' },
  ],
}));

// ── Computed ──────────────────────────────────────────

const isAuthor = computed(() =>
  post.value && authStore.user && post.value.author_id === authStore.user.id
);
const canModerate = computed(() => authStore.user?.role === 'admin');

const renderedBody = computed(() => renderMarkdown(post.value?.body || ''));

/**
 * Дерево комментариев. Берём плоский список и группируем
 * по parent_id рекурсивно.
 */
const commentTree = computed(() => {
  const byParent = {};
  for (const c of comments.value) {
    const k = c.parent_id ?? 0;
    if (!byParent[k]) byParent[k] = [];
    byParent[k].push(c);
  }
  // Сортируем дочерние по дате (старые сверху)
  for (const k in byParent) {
    byParent[k].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
  }
  return byParent;
});

const rootComments = computed(() => commentTree.value[0] || []);
const childrenOf = (parentId) => commentTree.value[parentId] || [];

// Date format
const formatDate = (s) => {
  if (!s) return '';
  return new Date(s).toLocaleString('ru-RU', {
    day: 'numeric', month: 'long', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
};

// ── Loaders ───────────────────────────────────────────

const loadPost = async () => {
  loadingPost.value = true;
  error.value = '';
  try {
    const { data } = await api.get(`/posts/${postId.value}`);
    post.value = data;
  } catch (e) {
    if (e.response?.status === 404) {
      error.value = 'Хроника не найдена или удалена.';
    } else {
      error.value = 'Не удалось загрузить пост.';
    }
    post.value = null;
  } finally {
    loadingPost.value = false;
  }
};

const loadComments = async () => {
  loadingComments.value = true;
  try {
    const { data } = await api.get(`/posts/${postId.value}/comments`);
    comments.value = data.data || [];
  } catch (_) {
    comments.value = [];
  } finally {
    loadingComments.value = false;
  }
};

// ── Comments actions ────────────────────────────────

const submitComment = async (parentId = null, bodyOverride = null) => {
  if (submitting.value) return;
  if (!authStore.isLoggedIn) {
    router.push({ name: 'login', query: { redirect: route.fullPath } });
    return;
  }
  const body = (bodyOverride ?? (parentId ? replyText.value : newCommentText.value)).trim();
  if (!body) return;

  submitting.value = true;
  try {
    const { data } = await api.post(`/posts/${postId.value}/comments`, {
      body,
      parent_id: parentId,
    });
    comments.value.push(data);
    if (post.value) post.value.comment_count++;
    if (parentId) {
      replyText.value = '';
      replyingTo.value = null;
    } else {
      newCommentText.value = '';
    }
    toast.success('Коммент опубликован');
  } catch (e) {
    toast.error(e.response?.data?.message || 'Не удалось отправить.');
  } finally {
    submitting.value = false;
  }
};

const startEdit = (c) => {
  editingId.value = c.id;
  editingText.value = c.body;
};

const cancelEdit = () => {
  editingId.value = null;
  editingText.value = '';
};

const saveEdit = async (c) => {
  if (submitting.value) return;
  const body = editingText.value.trim();
  if (!body) return;
  submitting.value = true;
  try {
    const { data } = await api.put(`/comments/${c.id}`, { body });
    const idx = comments.value.findIndex(x => x.id === c.id);
    if (idx >= 0) comments.value[idx] = data;
    cancelEdit();
    toast.success('Коммент обновлён');
  } catch (e) {
    toast.error(e.response?.data?.message || 'Не удалось сохранить.');
  } finally {
    submitting.value = false;
  }
};

const deleteComment = async (c) => {
  if (!confirm('Удалить комментарий?')) return;
  try {
    await api.delete(`/comments/${c.id}`);
    comments.value = comments.value.filter(x => x.id !== c.id);
    if (post.value) post.value.comment_count = Math.max(0, post.value.comment_count - 1);
    toast.success('Удалено');
  } catch (e) {
    toast.error(e.response?.data?.message || 'Не удалось удалить.');
  }
};

// ── Post actions (edit/delete) ──────────────────────

const editPost = () => {
  // Phase 2 / Batch C добавил /post/new, но edit пока заглушка-redirect
  toast.info('Редактирование будет в следующей итерации.');
};

const deletePost = async () => {
  if (!confirm('Удалить пост безвозвратно?')) return;
  try {
    await api.delete(`/posts/${postId.value}`);
    toast.success('Пост удалён.');
    router.push({ name: 'home' });
  } catch (e) {
    toast.error(e.response?.data?.message || 'Не удалось удалить.');
  }
};

// ── Lifecycle ────────────────────────────────────

watch(postId, async () => {
  await loadPost();
  if (post.value) await loadComments();
});

onMounted(async () => {
  await loadPost();
  if (post.value) await loadComments();
});

// Авторизация для CTA
const requireAuth = () => {
  if (!authStore.isLoggedIn) {
    router.push({ name: 'login', query: { redirect: route.fullPath } });
    return false;
  }
  return true;
};

const startReply = (commentId) => {
  if (!requireAuth()) return;
  replyingTo.value = commentId;
  replyText.value = '';
  nextTick(() => {
    document.querySelector(`#reply-form-${commentId} textarea`)?.focus();
  });
};
</script>

<template>
  <div class="post-page">

    <!-- Loading -->
    <div v-if="loadingPost" class="loading-state">
      <div class="spinner"></div>
      <p>Раскручиваю свиток…</p>
    </div>

    <!-- 404 -->
    <div v-else-if="error" class="not-found">
      <div class="nf-sigil">📜</div>
      <span class="tribal-eyebrow">
        <span class="eb-spike"></span>
        Свиток исчез
        <span class="eb-spike"></span>
      </span>
      <h1>Хроника не найдена</h1>
      <p>{{ error }}</p>
      <RouterLink to="/news" class="btn-primary">← К ленте хроник</RouterLink>
    </div>

    <!-- Post -->
    <article v-else-if="post" class="post-article">

      <!-- ── Hero с обложкой ── -->
      <header class="post-hero" :class="{ 'no-cover': !post.cover_url }">
        <div v-if="post.cover_url" class="post-cover">
          <img :src="resolveMediaUrl(post.cover_url)" :alt="post.title || 'Обложка'" />
          <div class="cover-overlay"></div>
        </div>

        <div class="hero-inner">
          <div v-if="post.tags?.length" class="post-tags">
            <span v-for="tag in post.tags" :key="tag" class="post-tag">{{ tag }}</span>
          </div>

          <h1 v-if="post.title" class="post-title">{{ post.title }}</h1>

          <div class="post-meta">
            <RouterLink
              v-if="post.author?.username"
              :to="{ name: 'user-profile', params: { username: post.author.username } }"
              class="meta-author"
            >
              <span class="author-avatar">
                <img v-if="post.author.avatar"
                     :src="`/avatars/${encodeURIComponent(post.author.avatar)}`"
                     :alt="post.author.fullname || post.author.username" />
                <span v-else>{{ (post.author.fullname || post.author.username || '?')[0].toUpperCase() }}</span>
              </span>
              <span class="author-name">
                <strong>{{ post.author.fullname || '@' + post.author.username }}</strong>
                <span class="author-role">
                  {{ roleIcons[post.author.role] }} {{ roleLabels[post.author.role] }}
                </span>
              </span>
            </RouterLink>
            <div v-else-if="post.author" class="meta-author no-link">
              <span class="author-avatar">
                <span>{{ (post.author.fullname || '?')[0].toUpperCase() }}</span>
              </span>
              <span class="author-name">
                <strong>{{ post.author.fullname }}</strong>
              </span>
            </div>

            <span class="meta-sep">·</span>
            <time class="meta-date">{{ formatDate(post.published_at || post.created_at) }}</time>

            <span v-if="post.game" class="game-link">
              <span class="meta-sep">·</span>
              <RouterLink :to="{ name: 'game', params: { id: post.game.id } }" class="game-chip">
                🎮 {{ post.game.title }}
              </RouterLink>
            </span>
          </div>
        </div>
      </header>

      <!-- ── Body ── -->
      <div class="post-body-wrap">
        <div class="post-body md-rendered" v-html="renderedBody"></div>

        <!-- Stats + actions -->
        <div class="post-footer-bar">
          <div class="post-stats">
            <span class="ps-item">👁 {{ post.view_count }}</span>
            <span class="ps-item">🔥 {{ post.reaction_count }}</span>
            <span class="ps-item">💬 {{ post.comment_count }}</span>
          </div>

          <div v-if="isAuthor || canModerate" class="post-actions">
            <button v-if="isAuthor" class="btn-ghost" @click="editPost">✎ Редактировать</button>
            <button class="btn-ghost danger" @click="deletePost">🗑 Удалить</button>
          </div>
        </div>
      </div>

      <!-- ═══ КОММЕНТАРИИ ═══ -->
      <section class="comments-section">
        <div class="comments-header">
          <h2>Обсуждение <span class="count">({{ comments.length }})</span></h2>
        </div>

        <!-- Top-level comment input -->
        <div v-if="authStore.isLoggedIn" class="new-comment">
          <textarea
            v-model="newCommentText"
            placeholder="Напишите свой ответ..."
            rows="3"
            class="comment-textarea"
            maxlength="5000"
          ></textarea>
          <div class="comment-input-actions">
            <small class="hint">Markdown поддерживается. Ctrl+Enter — отправить.</small>
            <button
              class="btn-primary"
              :disabled="submitting || !newCommentText.trim()"
              @click="submitComment(null)"
            >
              <span v-if="submitting">Отправка…</span>
              <span v-else>Отправить</span>
            </button>
          </div>
        </div>
        <div v-else class="login-cta">
          <RouterLink :to="{ name: 'login', query: { redirect: route.fullPath } }" class="btn-secondary">
            Войти, чтобы комментировать
          </RouterLink>
        </div>

        <!-- Comments tree -->
        <div v-if="loadingComments" class="loading-state inline">
          <div class="spinner"></div>
        </div>

        <div v-else-if="!rootComments.length" class="empty-state">
          <p>Будьте первым, кто бросит свой клин в этот разговор.</p>
        </div>

        <div v-else class="comments-list">
          <CommentItem
            v-for="c in rootComments"
            :key="c.id"
            :comment="c"
            :children-fn="childrenOf"
            :replying-to="replyingTo"
            :reply-text="replyText"
            :editing-id="editingId"
            :editing-text="editingText"
            :submitting="submitting"
            :current-user-id="authStore.user?.id"
            :is-admin="canModerate"
            @reply="startReply"
            @reply-cancel="replyingTo = null"
            @reply-submit="(parentId) => submitComment(parentId)"
            @reply-input="(v) => replyText = v"
            @edit-start="startEdit"
            @edit-cancel="cancelEdit"
            @edit-save="saveEdit"
            @edit-input="(v) => editingText = v"
            @delete="deleteComment"
            @author-click="(username) => router.push({ name: 'user-profile', params: { username } })"
          />
        </div>
      </section>
    </article>
  </div>
</template>

<script>
// ─── Inline-рекурсивный компонент CommentItem ───
import { defineComponent, h, computed } from 'vue';
import { renderMarkdown } from '../utils/markdown.js';

const CommentItem = defineComponent({
  name: 'CommentItem',
  props: {
    comment: Object,
    childrenFn: Function,
    replyingTo: [Number, String, null],
    replyText: String,
    editingId: [Number, String, null],
    editingText: String,
    submitting: Boolean,
    currentUserId: [Number, String, null],
    isAdmin: Boolean,
  },
  emits: [
    'reply', 'reply-cancel', 'reply-submit', 'reply-input',
    'edit-start', 'edit-cancel', 'edit-save', 'edit-input',
    'delete', 'author-click',
  ],
  setup(props, { emit }) {
    const c = props.comment;
    const isOwn = computed(() => props.currentUserId && c.author_id === props.currentUserId);
    const canDelete = computed(() => isOwn.value || props.isAdmin);
    const isEditing = computed(() => props.editingId === c.id);
    const isReplying = computed(() => props.replyingTo === c.id);

    const formatDate = (s) => {
      if (!s) return '';
      const d = new Date(s);
      const diff = (Date.now() - d.getTime()) / 1000;
      if (diff < 60) return 'только что';
      if (diff < 3600) return Math.floor(diff / 60) + ' мин назад';
      if (diff < 86400) return Math.floor(diff / 3600) + ' ч назад';
      return d.toLocaleDateString('ru-RU');
    };

    const onAuthorClick = () => {
      if (c.author?.username) emit('author-click', c.author.username);
    };

    const renderBody = () => renderMarkdown(c.body);

    const subtree = computed(() => props.childrenFn(c.id));

    return () => {
      const roleLabels = { user: 'Воин', manager: 'Кузнец', admin: 'Старейшина' };
      const roleIcons  = { user: '⚔', manager: '🔨', admin: '👑' };
      const role = c.author?.role || 'user';
      const depth = Math.min(c.depth || 0, 6); // cap visual indent

      return h('div', { class: ['comment', `depth-${depth}`] }, [
        // Author + meta
        h('div', { class: 'comment-head' }, [
          h('span', {
            class: ['comment-avatar', { clickable: !!c.author?.username }],
            onClick: onAuthorClick,
          }, [
            c.author?.avatar
              ? h('img', { src: `/avatars/${encodeURIComponent(c.author.avatar)}`, alt: c.author.fullname || c.author.username })
              : h('span', (c.author?.fullname || c.author?.username || '?')[0].toUpperCase()),
          ]),
          h('div', { class: 'comment-meta' }, [
            h('div', { class: 'comment-author-line' }, [
              h('strong', {
                class: { clickable: !!c.author?.username },
                onClick: onAuthorClick,
              }, c.author?.fullname || ('@' + (c.author?.username || 'anon'))),
              c.author?.username ? h('span', { class: 'comment-author-uname' }, '@' + c.author.username) : null,
              h('span', { class: `role-pip role-${role}` }, roleIcons[role]),
            ]),
            h('span', { class: 'comment-date' }, formatDate(c.created_at)),
          ]),
        ]),

        // Body — edit form OR rendered
        isEditing.value
          ? h('div', { class: 'comment-edit' }, [
              h('textarea', {
                value: props.editingText,
                rows: 3,
                maxlength: 5000,
                onInput: (e) => emit('edit-input', e.target.value),
                class: 'comment-textarea',
              }),
              h('div', { class: 'comment-edit-actions' }, [
                h('button', {
                  class: 'btn-ghost small',
                  onClick: () => emit('edit-cancel'),
                  disabled: props.submitting,
                }, 'Отмена'),
                h('button', {
                  class: 'btn-primary small',
                  onClick: () => emit('edit-save', c),
                  disabled: props.submitting || !props.editingText.trim(),
                }, 'Сохранить'),
              ]),
            ])
          : h('div', { class: 'comment-body md-rendered', innerHTML: renderBody() }),

        // Actions row
        !isEditing.value
          ? h('div', { class: 'comment-actions' }, [
              h('button', { class: 'comment-action', onClick: () => emit('reply', c.id) }, '↪ Ответить'),
              isOwn.value
                ? h('button', { class: 'comment-action', onClick: () => emit('edit-start', c) }, '✎ Изменить')
                : null,
              canDelete.value
                ? h('button', { class: 'comment-action danger', onClick: () => emit('delete', c) }, '🗑 Удалить')
                : null,
            ])
          : null,

        // Reply form
        isReplying.value && !isEditing.value
          ? h('div', { id: `reply-form-${c.id}`, class: 'reply-form' }, [
              h('textarea', {
                value: props.replyText,
                rows: 2,
                maxlength: 5000,
                placeholder: 'Ваш ответ...',
                onInput: (e) => emit('reply-input', e.target.value),
                class: 'comment-textarea',
                autofocus: true,
              }),
              h('div', { class: 'comment-edit-actions' }, [
                h('button', {
                  class: 'btn-ghost small',
                  onClick: () => emit('reply-cancel'),
                  disabled: props.submitting,
                }, 'Отмена'),
                h('button', {
                  class: 'btn-primary small',
                  onClick: () => emit('reply-submit', c.id),
                  disabled: props.submitting || !props.replyText.trim(),
                }, props.submitting ? 'Отправка…' : 'Ответить'),
              ]),
            ])
          : null,

        // Children — recursive render
        subtree.value.length
          ? h('div', { class: 'comment-children' },
              subtree.value.map((child) => h(CommentItem, {
                key: child.id,
                comment: child,
                childrenFn: props.childrenFn,
                replyingTo: props.replyingTo,
                replyText: props.replyText,
                editingId: props.editingId,
                editingText: props.editingText,
                submitting: props.submitting,
                currentUserId: props.currentUserId,
                isAdmin: props.isAdmin,
                onReply: (id) => emit('reply', id),
                'onReply-cancel': () => emit('reply-cancel'),
                'onReply-submit': (id) => emit('reply-submit', id),
                'onReply-input': (v) => emit('reply-input', v),
                'onEdit-start': (cm) => emit('edit-start', cm),
                'onEdit-cancel': () => emit('edit-cancel'),
                'onEdit-save': (cm) => emit('edit-save', cm),
                'onEdit-input': (v) => emit('edit-input', v),
                onDelete: (cm) => emit('delete', cm),
                'onAuthor-click': (un) => emit('author-click', un),
              })))
          : null,
      ]);
    };
  },
});

export default {
  components: { CommentItem },
};
</script>

<style scoped>
.post-page {
  min-height: 100vh;
  background: var(--ash-obsidian);
}

/* ═══ Loading / 404 ═══ */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 18px;
  padding: 100px 24px;
  color: var(--text-parchment);
}
.loading-state.inline { padding: 30px 0; }
.spinner {
  width: 36px; height: 36px;
  border: 3px solid var(--iron-mid);
  border-top-color: var(--ember-flame);
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.not-found {
  text-align: center;
  padding: 100px 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
}
.nf-sigil { font-size: 4rem; color: var(--bronze); margin-bottom: 8px; }
.not-found h1 {
  font-family: var(--font-display);
  font-size: clamp(1.7rem, 4vw, 2.4rem);
  color: var(--text-bright);
  margin: 6px 0;
}
.not-found p { color: var(--text-parchment); max-width: 480px; }

.btn-primary {
  display: inline-block;
  padding: 12px 24px;
  background: var(--grad-ember);
  color: var(--text-bright);
  font-family: var(--font-display);
  font-weight: var(--fw-bold);
  letter-spacing: 0.5px;
  text-transform: uppercase;
  border: 1px solid var(--ember-deep);
  border-radius: 6px;
  cursor: pointer;
  text-decoration: none;
  box-shadow: var(--inset-iron-top), 0 4px 12px rgba(239, 74, 24, 0.3);
  transition: all 0.2s;
}
.btn-primary:hover:not(:disabled) { transform: translateY(-1px); filter: brightness(1.1); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-primary.small { padding: 8px 16px; font-size: 0.85rem; }

.btn-secondary {
  display: inline-block;
  padding: 11px 22px;
  background: var(--ash-coal);
  color: var(--text-bone);
  border: 1px solid var(--iron-mid);
  border-radius: 6px;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.2s;
}
.btn-secondary:hover { border-color: var(--bronze); }

.btn-ghost {
  padding: 8px 14px;
  background: transparent;
  color: var(--text-parchment);
  border: 1px solid var(--iron-mid);
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.86rem;
  transition: all 0.2s;
}
.btn-ghost:hover { background: var(--ash-coal); color: var(--text-bone); }
.btn-ghost.danger:hover { color: var(--ember-flame); border-color: var(--ember-flame); }
.btn-ghost.small { padding: 5px 11px; font-size: 0.78rem; }

/* ═══ Article ═══ */
.post-article {
  max-width: 880px;
  margin: 0 auto;
  padding: 0 0 80px;
}

/* Hero */
.post-hero {
  position: relative;
  margin-bottom: 0;
}
.post-hero.no-cover {
  padding: 80px 24px 40px;
  background: linear-gradient(180deg, var(--ash-coal) 0%, var(--ash-obsidian) 100%);
  border-bottom: 1px solid var(--iron-mid);
}
.post-cover {
  position: relative;
  width: 100%;
  height: clamp(300px, 50vw, 480px);
  overflow: hidden;
}
.post-cover img {
  width: 100%; height: 100%;
  object-fit: cover;
}
.cover-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(180deg,
    rgba(8, 6, 10, 0.3) 0%,
    rgba(8, 6, 10, 0.6) 60%,
    rgba(8, 6, 10, 0.95) 100%);
}
.hero-inner {
  position: relative;
  max-width: 800px;
  margin: 0 auto;
  padding: 32px 24px 24px;
}
.post-hero:not(.no-cover) .hero-inner {
  margin-top: -200px;
  z-index: 2;
}

.post-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 7px;
  margin-bottom: 14px;
}
.post-tag {
  font-family: var(--font-ui);
  font-size: 0.76rem;
  color: var(--ember-spark);
  background: rgba(226, 67, 16, 0.12);
  padding: 3px 10px;
  border-radius: 12px;
  border: 1px solid rgba(226, 67, 16, 0.3);
}
.post-title {
  font-family: var(--font-display);
  font-size: clamp(1.7rem, 4vw, 2.6rem);
  color: var(--text-bright);
  margin: 0 0 16px;
  line-height: 1.15;
  font-weight: var(--fw-black);
}

.post-meta {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  font-size: 0.92rem;
  color: var(--text-parchment);
}
.meta-author {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  color: inherit;
  transition: opacity 0.2s;
}
.meta-author:hover { opacity: 0.85; }
.meta-author.no-link { cursor: default; }

.author-avatar {
  width: 36px; height: 36px;
  border-radius: 50%;
  background: var(--ash-stone);
  display: flex; align-items: center; justify-content: center;
  overflow: hidden;
  border: 1px solid var(--iron-mid);
  font-weight: var(--fw-bold);
  color: var(--ember-gold);
}
.author-avatar img { width: 100%; height: 100%; object-fit: cover; }

.author-name {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.author-name strong { color: var(--text-bone); }
.author-role {
  font-size: 0.74rem;
  color: var(--text-ash);
  letter-spacing: 0.4px;
}
.meta-sep { color: var(--text-smoke); }
.meta-date { color: var(--text-ash); }
.game-link { display: inline-flex; align-items: center; gap: 8px; }
.game-chip {
  font-size: 0.86rem;
  padding: 3px 10px;
  background: rgba(199, 154, 94, 0.12);
  color: var(--brass);
  border-radius: 12px;
  text-decoration: none;
  border: 1px solid rgba(199, 154, 94, 0.3);
}

/* Body */
.post-body-wrap {
  max-width: 760px;
  margin: 30px auto 0;
  padding: 0 24px;
}
.post-body {
  font-size: 1.06rem;
  line-height: 1.78;
  color: var(--text-bone);
}

.post-footer-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 14px;
  margin-top: 36px;
  padding: 16px 0;
  border-top: 1px solid var(--iron-mid);
  border-bottom: 1px solid var(--iron-mid);
}
.post-stats { display: flex; gap: 22px; }
.ps-item { color: var(--text-parchment); font-size: 0.95rem; }
.post-actions { display: flex; gap: 10px; flex-wrap: wrap; }

/* ═══ Markdown rendered (общие стили — :deep чтобы дочерние применились) ═══ */
.md-rendered :deep(h1) { font-family: var(--font-display); font-size: 1.7rem; color: var(--text-bright); margin: 1em 0 0.5em; }
.md-rendered :deep(h2) { font-family: var(--font-display); font-size: 1.35rem; color: var(--text-bright); margin: 1em 0 0.5em; }
.md-rendered :deep(h3) { font-family: var(--font-display); font-size: 1.1rem; color: var(--text-bone); margin: 0.8em 0 0.4em; }
.md-rendered :deep(p) { margin: 0.7em 0; }
.md-rendered :deep(a) { color: var(--ember-spark); text-decoration: underline; text-decoration-color: rgba(255, 167, 88, 0.4); }
.md-rendered :deep(a:hover) { color: var(--ember-gold); text-decoration-color: var(--ember-gold); }
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

/* ═══ Comments ═══ */
.comments-section {
  max-width: 760px;
  margin: 50px auto 0;
  padding: 0 24px;
}
.comments-header {
  margin-bottom: 22px;
}
.comments-header h2 {
  font-family: var(--font-display);
  font-size: 1.4rem;
  color: var(--text-bright);
  margin: 0;
}
.comments-header .count {
  color: var(--text-ash);
  font-weight: 400;
  font-size: 1rem;
}

.new-comment {
  margin-bottom: 28px;
}
.comment-textarea {
  width: 100%;
  padding: 12px 16px;
  background: linear-gradient(180deg, rgba(8, 6, 10, 0.6), rgba(18, 16, 13, 0.7));
  border: 1px solid var(--iron-mid);
  border-radius: 6px;
  color: var(--text-bone);
  font-family: var(--font-body);
  font-size: 0.95rem;
  resize: vertical;
  outline: none;
  transition: border-color 0.2s;
}
.comment-textarea:focus { border-color: var(--ember-flame); }

.comment-input-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-top: 8px;
  flex-wrap: wrap;
}
.hint { color: var(--text-ash); font-size: 0.78rem; }

.login-cta {
  text-align: center;
  padding: 24px;
  background: rgba(8, 6, 10, 0.4);
  border: 1px dashed var(--iron-mid);
  border-radius: 6px;
  margin-bottom: 28px;
}

.empty-state {
  text-align: center;
  padding: 40px 24px;
  color: var(--text-parchment);
  border: 1px dashed var(--iron-mid);
  border-radius: 6px;
}

.comments-list { display: flex; flex-direction: column; gap: 16px; }

/* ═══ Comment item ═══ */
:deep(.comment) {
  padding: 14px 16px;
  background: linear-gradient(180deg, rgba(8, 6, 10, 0.4), rgba(18, 16, 13, 0.5));
  border: 1px solid var(--iron-mid);
  border-radius: 6px;
}
:deep(.comment.depth-1) { margin-left: 24px; border-left: 2px solid rgba(199, 154, 94, 0.3); }
:deep(.comment.depth-2) { margin-left: 24px; border-left: 2px solid rgba(199, 154, 94, 0.25); }
:deep(.comment.depth-3) { margin-left: 24px; border-left: 2px solid rgba(199, 154, 94, 0.2); }
:deep(.comment.depth-4) { margin-left: 24px; border-left: 2px solid rgba(199, 154, 94, 0.15); }
:deep(.comment.depth-5) { margin-left: 24px; border-left: 2px solid rgba(199, 154, 94, 0.1); }
:deep(.comment.depth-6) { margin-left: 24px; border-left: 2px solid rgba(199, 154, 94, 0.08); }

:deep(.comment-head) {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 10px;
}
:deep(.comment-avatar) {
  width: 34px; height: 34px;
  border-radius: 50%;
  background: var(--ash-stone);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border: 1px solid var(--iron-mid);
  font-weight: var(--fw-bold);
  color: var(--ember-gold);
  font-size: 0.86rem;
  flex-shrink: 0;
}
:deep(.comment-avatar img) { width: 100%; height: 100%; object-fit: cover; }
:deep(.comment-avatar.clickable) { cursor: pointer; transition: opacity 0.2s; }
:deep(.comment-avatar.clickable:hover) { opacity: 0.85; }

:deep(.comment-meta) {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}
:deep(.comment-author-line) {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
:deep(.comment-author-line strong) {
  color: var(--text-bone);
  font-size: 0.92rem;
}
:deep(.comment-author-line strong.clickable) {
  cursor: pointer;
  transition: color 0.15s;
}
:deep(.comment-author-line strong.clickable:hover) { color: var(--ember-spark); }

:deep(.comment-author-uname) {
  font-size: 0.78rem;
  color: var(--text-ash);
  font-family: var(--font-ui);
}
:deep(.role-pip) {
  font-size: 0.72rem;
  padding: 1px 6px;
  border-radius: 8px;
  background: rgba(199, 154, 94, 0.12);
  border: 1px solid rgba(199, 154, 94, 0.3);
}
:deep(.role-pip.role-admin) {
  background: rgba(255, 132, 51, 0.15);
  color: var(--ember-gold);
  border-color: rgba(255, 167, 88, 0.5);
}
:deep(.role-pip.role-manager) {
  background: rgba(122, 28, 20, 0.18);
  color: #ff8433;
  border-color: rgba(226, 67, 16, 0.4);
}

:deep(.comment-date) {
  font-size: 0.76rem;
  color: var(--text-ash);
}

:deep(.comment-body) {
  font-size: 0.94rem;
  color: var(--text-bone);
  line-height: 1.6;
  margin-bottom: 8px;
}

:deep(.comment-actions) {
  display: flex;
  gap: 14px;
  margin-top: 6px;
}
:deep(.comment-action) {
  background: transparent;
  border: none;
  color: var(--text-ash);
  font-size: 0.82rem;
  cursor: pointer;
  padding: 2px 0;
  transition: color 0.15s;
}
:deep(.comment-action:hover) { color: var(--ember-spark); }
:deep(.comment-action.danger:hover) { color: var(--ember-flame); }

:deep(.reply-form) {
  margin-top: 12px;
}
:deep(.comment-edit) {
  margin: 6px 0;
}
:deep(.comment-edit-actions) {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
  margin-top: 6px;
}

:deep(.comment-children) {
  margin-top: 14px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

@media (max-width: 600px) {
  .post-cover { height: 240px; }
  .post-hero:not(.no-cover) .hero-inner { margin-top: -120px; }
  .post-title { font-size: 1.5rem; }
  :deep(.comment.depth-1),
  :deep(.comment.depth-2),
  :deep(.comment.depth-3),
  :deep(.comment.depth-4),
  :deep(.comment.depth-5),
  :deep(.comment.depth-6) { margin-left: 12px; }
}
</style>
