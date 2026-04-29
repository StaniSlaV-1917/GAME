<script setup>
import { ref, computed, onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useHead } from '@vueuse/head';
import { useAuthStore } from '../stores/auth';
import api from '../api/axios';
import { resolveMediaUrl } from '../utils/media';

const route = useRoute();
const authStore = useAuthStore();

const posts = ref([]);
const loading = ref(true);
const error = ref('');
const mode = ref('trending'); // 'following' | 'trending'
const isEmpty = ref(false);
const total = ref(0);

useHead({
  title: 'Лента — GameStore',
  meta: [
    { name: 'description', content: 'Свежие хроники сообщества GameStore.' },
  ],
});

const formatDate = (s) => {
  if (!s) return '';
  const d = new Date(s);
  const diff = (Date.now() - d.getTime()) / 1000;
  if (diff < 60) return 'только что';
  if (diff < 3600) return Math.floor(diff / 60) + ' мин назад';
  if (diff < 86400) return Math.floor(diff / 3600) + ' ч назад';
  if (diff < 604800) return Math.floor(diff / 86400) + ' дн назад';
  return d.toLocaleDateString('ru-RU');
};

const loadFeed = async () => {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await api.get('/feed', { params: { per_page: 15 } });
    posts.value = data.data || [];
    mode.value = data.mode || 'trending';
    isEmpty.value = data.is_empty || false;
    total.value = data.total || 0;
  } catch (e) {
    error.value = 'Не удалось загрузить ленту.';
  } finally {
    loading.value = false;
  }
};

onMounted(loadFeed);
</script>

<template>
  <div class="feed-page">
    <div class="feed-wrap">

      <!-- ── Header ── -->
      <div class="feed-header">
        <div>
          <span class="tribal-eyebrow">
            <span class="eb-spike"></span>
            {{ mode === 'following' ? 'Лента подписок' : 'Тренды недели' }}
            <span class="eb-spike"></span>
          </span>
          <h1>
            {{ mode === 'following'
              ? 'Хроники тех, за кем вы следите'
              : 'Самое горящее за неделю' }}
          </h1>
          <p class="feed-sub">
            {{ mode === 'following'
              ? `${total} ${total === 1 ? 'пост' : 'постов'} от ваших воинов`
              : `Топ хроник по реакциям сообщества` }}
          </p>
        </div>

        <RouterLink
          v-if="authStore.isLoggedIn"
          :to="{ name: 'post-new' }"
          class="btn-primary write-btn"
        >
          ⚒ Написать
        </RouterLink>
      </div>

      <!-- ── Trending hint для логинутых без подписок ── -->
      <div
        v-if="authStore.isLoggedIn && mode === 'trending' && posts.length"
        class="hint-banner"
      >
        <p>
          📜 Вы пока ни на кого не подписаны — показываем тренды.
          Подпишитесь на воинов в их профилях, чтобы видеть их посты в ленте.
        </p>
      </div>

      <!-- ── Loading ── -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Собираем свитки…</p>
      </div>

      <!-- ── Error ── -->
      <div v-else-if="error" class="empty-state">
        <p>{{ error }}</p>
      </div>

      <!-- ── Empty state ── -->
      <div v-else-if="!posts.length" class="empty-state">
        <div class="empty-icon">📜</div>
        <h2>Свитки пусты</h2>
        <p v-if="!authStore.isLoggedIn">
          Лента пока ничем не наполнена. Зайдите чуть позже —
          или станьте первым воином с хроникой!
        </p>
        <p v-else>
          Никто ещё не опубликовал постов на этой неделе.
        </p>
        <RouterLink
          v-if="authStore.isLoggedIn"
          :to="{ name: 'post-new' }"
          class="btn-primary"
        >
          ⚒ Написать первый пост
        </RouterLink>
      </div>

      <!-- ── Feed ── -->
      <div v-else class="feed-list">
        <RouterLink
          v-for="p in posts"
          :key="p.id"
          :to="{ name: 'post', params: { id: p.id } }"
          class="feed-card"
        >
          <div v-if="p.cover_url" class="fc-cover">
            <img :src="resolveMediaUrl(p.cover_url)" :alt="p.title || 'Хроника'" loading="lazy" />
          </div>
          <div class="fc-body">
            <div v-if="p.tags?.length" class="fc-tags">
              <span v-for="tag in p.tags.slice(0, 3)" :key="tag" class="fc-tag">{{ tag }}</span>
            </div>
            <h3 v-if="p.title" class="fc-title">{{ p.title }}</h3>
            <p class="fc-excerpt">{{ p.body.length > 220 ? p.body.slice(0, 220) + '…' : p.body }}</p>
            <div class="fc-meta">
              <div class="fc-author" v-if="p.author">
                <span class="fc-avatar">
                  <img v-if="p.author.avatar"
                       :src="`/avatars/${encodeURIComponent(p.author.avatar)}`"
                       :alt="p.author.fullname || p.author.username" />
                  <span v-else>{{ (p.author.fullname || p.author.username || '?')[0].toUpperCase() }}</span>
                </span>
                <span class="fc-author-name">
                  {{ p.author.fullname || ('@' + p.author.username) }}
                </span>
                <span class="fc-date">· {{ formatDate(p.published_at) }}</span>
              </div>
              <div class="fc-stats">
                <span>🔥 {{ p.reaction_count }}</span>
                <span>💬 {{ p.comment_count }}</span>
                <span>👁 {{ p.view_count }}</span>
              </div>
            </div>
          </div>
        </RouterLink>
      </div>

    </div>
  </div>
</template>

<style scoped>
.feed-page {
  min-height: 100vh;
  padding: 40px 24px 80px;
  background: var(--ash-obsidian);
}
.feed-wrap {
  max-width: 880px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.feed-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 18px;
  flex-wrap: wrap;
}
.feed-header h1 {
  font-family: var(--font-display);
  font-size: clamp(1.7rem, 3.5vw, 2.4rem);
  color: var(--text-bright);
  margin: 6px 0 4px;
  letter-spacing: 0.3px;
}
.feed-sub {
  color: var(--text-parchment);
  margin: 0;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 12px 22px;
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
  transition: all 0.2s var(--ease-smoke);
}
.btn-primary:hover { transform: translateY(-1px); filter: brightness(1.1); }
.btn-primary.write-btn { font-size: 0.9rem; }

.hint-banner {
  padding: 14px 20px;
  background: linear-gradient(180deg, rgba(199, 154, 94, 0.1), rgba(8, 6, 10, 0.4));
  border: 1px solid var(--bronze-dark);
  border-radius: 6px;
  color: var(--text-parchment);
}
.hint-banner p { margin: 0; font-size: 0.92rem; }

/* Loading */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
  padding: 80px 24px;
  color: var(--text-parchment);
}
.spinner {
  width: 36px; height: 36px;
  border: 3px solid var(--iron-mid);
  border-top-color: var(--ember-flame);
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Empty state */
.empty-state {
  text-align: center;
  padding: 60px 24px;
  background: linear-gradient(180deg, rgba(8, 6, 10, 0.3), rgba(20, 16, 13, 0.4));
  border: 1px dashed var(--iron-mid);
  border-radius: 8px;
  color: var(--text-parchment);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}
.empty-icon { font-size: 3.4rem; opacity: 0.5; }
.empty-state h2 {
  font-family: var(--font-display);
  font-size: 1.5rem;
  color: var(--text-bright);
  margin: 0;
}
.empty-state p { margin: 0; max-width: 480px; }

/* Feed cards */
.feed-list {
  display: flex;
  flex-direction: column;
  gap: 18px;
}
.feed-card {
  display: flex;
  gap: 18px;
  padding: 16px;
  background: linear-gradient(180deg, rgba(8, 6, 10, 0.5), rgba(18, 16, 13, 0.6));
  border: 1px solid var(--iron-mid);
  border-radius: 6px;
  text-decoration: none;
  color: inherit;
  transition: all 0.2s var(--ease-smoke);
}
.feed-card:hover {
  border-color: var(--bronze);
  box-shadow: var(--inset-iron-top), 0 0 22px rgba(239, 74, 24, 0.22);
  transform: translateY(-2px);
}

.fc-cover {
  flex-shrink: 0;
  width: 200px;
  height: 130px;
  border-radius: 4px;
  overflow: hidden;
  background: var(--ash-coal);
}
.fc-cover img {
  width: 100%; height: 100%;
  object-fit: cover;
}

.fc-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 10px;
  min-width: 0;
}
.fc-tags { display: flex; flex-wrap: wrap; gap: 6px; }
.fc-tag {
  font-family: var(--font-ui);
  font-size: 0.75rem;
  color: var(--ember-spark);
  background: rgba(226, 67, 16, 0.1);
  padding: 2px 9px;
  border-radius: 10px;
  border: 1px solid rgba(226, 67, 16, 0.25);
}
.fc-title {
  font-family: var(--font-display);
  font-size: 1.2rem;
  color: var(--text-bright);
  margin: 0;
  line-height: 1.3;
}
.fc-excerpt {
  font-size: 0.92rem;
  color: var(--text-parchment);
  line-height: 1.55;
  margin: 0;
  flex: 1;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.fc-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
  font-size: 0.82rem;
  color: var(--text-ash);
  padding-top: 8px;
  border-top: 1px solid var(--iron-mid);
}
.fc-author {
  display: flex;
  align-items: center;
  gap: 8px;
}
.fc-avatar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: var(--ash-stone);
  color: var(--ember-gold);
  font-weight: var(--fw-bold);
  font-size: 0.78rem;
  overflow: hidden;
}
.fc-avatar img { width: 100%; height: 100%; object-fit: cover; }
.fc-author-name { color: var(--text-bone); font-size: 0.88rem; }
.fc-date { color: var(--text-ash); }
.fc-stats { display: flex; gap: 12px; }

@media (max-width: 600px) {
  .feed-card { flex-direction: column; }
  .fc-cover { width: 100%; height: 180px; }
}
</style>
