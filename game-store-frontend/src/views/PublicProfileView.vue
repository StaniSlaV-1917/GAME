<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useHead } from '@vueuse/head';
import { useAuthStore } from '../stores/auth';
import { useChatsStore } from '../stores/chats';
import { useToast } from '../composables/useToast';
import api from '../api/axios';
import { resolveMediaUrl } from '../utils/media';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const toast = useToast();

const profile = ref(null);
const posts = ref([]);
const loadingProfile = ref(true);
const loadingPosts = ref(true);
const error = ref('');
const followBusy = ref(false);
const writing = ref(false);
const chatsStore = useChatsStore();

/** Phase 4/D — открыть DM с этим пользователем. */
const handleWriteMessage = async () => {
  if (!authStore.isLoggedIn) {
    router.push({ name: 'login', query: { redirect: route.fullPath } });
    return;
  }
  if (writing.value || !username.value) return;
  writing.value = true;
  try {
    const data = await chatsStore.createDmByUsername(username.value);
    router.push({ name: 'messages-room', params: { roomId: data.id } });
  } catch (e) {
    toast.error(e.response?.data?.message || 'Не удалось открыть чат');
  } finally {
    writing.value = false;
  }
};

// Текущий username из URL — реактивно для смены роута без перезагрузки
const username = computed(() => route.params.username);

// Это мой профиль? (показываем «редактировать» вместо «follow»)
const isOwnProfile = computed(() =>
  authStore.user?.username && profile.value?.username &&
  authStore.user.username.toLowerCase() === profile.value.username.toLowerCase()
);

// Локализация ролей (как в админке)
const roleLabels = { user: 'Воин', manager: 'Кузнец', admin: 'Старейшина' };
const roleIcons  = { user: '⚔', manager: '🔨', admin: '👑' };

useHead(() => ({
  title: profile.value
    ? `${profile.value.fullname || '@' + profile.value.username} — GameStore`
    : 'Профиль воина — GameStore',
  meta: [
    { name: 'description', content: profile.value
      ? `Профиль ${profile.value.fullname || '@' + profile.value.username} на GameStore — посты, реакции, активность.`
      : 'Публичный профиль пользователя GameStore.' },
  ],
}));

const formatDate = (s) => {
  if (!s) return '—';
  return new Date(s).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' });
};

const loadProfile = async () => {
  loadingProfile.value = true;
  error.value = '';
  try {
    const { data } = await api.get(`/users/${username.value}/profile`);
    profile.value = data;
  } catch (e) {
    if (e.response?.status === 404) {
      error.value = 'Воин с таким именем не найден в оплоте.';
    } else {
      error.value = 'Не удалось загрузить профиль. Попробуйте позже.';
    }
    profile.value = null;
  } finally {
    loadingProfile.value = false;
  }
};

const loadPosts = async () => {
  loadingPosts.value = true;
  try {
    const { data } = await api.get(`/users/${username.value}/posts`, {
      params: { per_page: 10 },
    });
    posts.value = data.data || [];
  } catch (e) {
    posts.value = [];
  } finally {
    loadingPosts.value = false;
  }
};

const reload = async () => {
  await loadProfile();
  if (profile.value) await loadPosts();
};

watch(username, () => reload());
onMounted(reload);

// ── Follow / Unfollow ─────────────────────────────

const requireAuth = () => {
  if (!authStore.isLoggedIn) {
    router.push({ name: 'login', query: { redirect: route.fullPath } });
    return false;
  }
  return true;
};

const handleFollow = async () => {
  if (followBusy.value) return;
  if (!requireAuth()) return;
  followBusy.value = true;
  try {
    const { data } = await api.post(`/users/${username.value}/follow`);
    profile.value.is_followed_by_me = data.following;
    profile.value.stats.followers = data.followers_count;
    toast.success('Подписка оформлена');
  } catch (e) {
    if (e.response?.status === 403) {
      toast.error(e.response.data?.message || 'Нельзя подписаться.');
    } else {
      toast.error('Не удалось подписаться.');
    }
  } finally {
    followBusy.value = false;
  }
};

const handleUnfollow = async () => {
  if (followBusy.value) return;
  if (!confirm(`Отписаться от @${profile.value.username}?`)) return;
  followBusy.value = true;
  try {
    const { data } = await api.delete(`/users/${username.value}/follow`);
    profile.value.is_followed_by_me = data.following;
    profile.value.stats.followers = data.followers_count;
    toast.info('Подписка отменена');
  } catch (e) {
    toast.error('Не удалось отписаться.');
  } finally {
    followBusy.value = false;
  }
};
</script>

<template>
  <div class="profile-page">

    <!-- ═══ Loading skeleton ═══ -->
    <div v-if="loadingProfile" class="loading-state">
      <div class="spinner"></div>
      <p>Ищу воина в оплоте...</p>
    </div>

    <!-- ═══ Ошибка / 404 ═══ -->
    <div v-else-if="error" class="not-found">
      <div class="nf-sigil" aria-hidden="true">⚔</div>
      <span class="tribal-eyebrow">
        <span class="eb-spike"></span>
        Свиток пуст
        <span class="eb-spike"></span>
      </span>
      <h1>Воин не найден</h1>
      <p>{{ error }}</p>
      <RouterLink to="/" class="btn-primary">← На главную</RouterLink>
    </div>

    <!-- ═══ Профиль ═══ -->
    <template v-else-if="profile">

      <!-- ── HERO ── -->
      <div class="profile-hero">
        <div class="hero-bg-blur b1"></div>
        <div class="hero-bg-blur b2"></div>
        <div class="hero-grid"></div>

        <div class="hero-inner">
          <!-- Аватар -->
          <div class="avatar-wrap">
            <div class="avatar-ring"></div>
            <div class="avatar">
              <img v-if="profile.avatar"
                   :src="`/avatars/${encodeURIComponent(profile.avatar)}`"
                   :alt="profile.fullname"
                   class="avatar-img" />
              <span v-else>{{ (profile.fullname || profile.username || '?')[0].toUpperCase() }}</span>
            </div>
          </div>

          <!-- Имя и мета -->
          <div class="hero-meta">
            <div class="hero-name-row">
              <h1 class="hero-name">{{ profile.fullname || ('@' + profile.username) }}</h1>
              <span class="role-badge" :class="`role-${profile.role}`">
                {{ roleIcons[profile.role] }} {{ roleLabels[profile.role] || profile.role }}
              </span>
              <span v-if="profile.is_frozen" class="status-badge frozen" title="Аккаунт временно заморожен">
                ❄ Заморожен
              </span>
            </div>
            <p class="hero-username">@{{ profile.username }}</p>
            <p class="hero-since">В оплоте с {{ formatDate(profile.reg_date) }}</p>
          </div>

          <!-- CTA: «редактировать» (свой), «подписаться/отписаться» (чужой) -->
          <div class="hero-actions">
            <RouterLink v-if="isOwnProfile"
                        to="/profile"
                        class="btn-primary">
              Редактировать
            </RouterLink>
            <template v-else>
              <button v-if="!profile.is_followed_by_me"
                      class="btn-primary"
                      :disabled="followBusy"
                      @click="handleFollow">
                <span v-if="followBusy">…</span>
                <span v-else>+ Подписаться</span>
              </button>
              <button v-else
                      class="btn-secondary following-btn"
                      :disabled="followBusy"
                      @click="handleUnfollow"
                      title="Кликните чтобы отписаться">
                <span v-if="followBusy">…</span>
                <span v-else>✓ Подписан</span>
              </button>

              <!-- Phase 4/D — Написать в DM. Создаёт чат на лету и
                   редиректит на /messages/:roomId -->
              <button
                v-if="authStore.isLoggedIn"
                class="btn-secondary write-btn"
                :disabled="writing"
                @click="handleWriteMessage"
                title="Открыть личную переписку"
              >
                <span v-if="writing">…</span>
                <span v-else>✉ Написать</span>
              </button>
            </template>
          </div>

          <!-- Quick stats -->
          <div class="hero-stats">
            <div class="hs-item">
              <span class="hs-num">{{ profile.stats.followers || 0 }}</span>
              <span class="hs-label">подписчиков</span>
            </div>
            <div class="hs-sep"></div>
            <div class="hs-item">
              <span class="hs-num">{{ profile.stats.following || 0 }}</span>
              <span class="hs-label">подписок</span>
            </div>
            <div class="hs-sep"></div>
            <div class="hs-item">
              <span class="hs-num">{{ profile.stats.posts }}</span>
              <span class="hs-label">постов</span>
            </div>
            <div class="hs-sep"></div>
            <div class="hs-item">
              <span class="hs-num">{{ profile.stats.reactions_received }}</span>
              <span class="hs-label">реакций</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Игры в библиотеке (Phase 3 / Batch C) ── -->
      <div v-if="profile.library && profile.library.length" class="profile-body library-block">
        <div class="panel-header">
          <h2>🎮 Игры в библиотеке</h2>
          <p>Что воин уже добыл из оружейной</p>
        </div>
        <div class="library-grid">
          <RouterLink
            v-for="g in profile.library"
            :key="g.id"
            :to="{ name: 'game', params: { id: g.id } }"
            class="library-card"
            :title="g.title"
          >
            <div class="lib-cover">
              <img :src="resolveMediaUrl(g.image)" :alt="g.title" loading="lazy" />
            </div>
            <span class="lib-title">{{ g.title }}</span>
            <span v-if="g.platform" class="lib-platform">{{ g.platform }}</span>
          </RouterLink>
        </div>
      </div>

      <!-- ── Лента постов автора ── -->
      <div class="profile-body">
        <div class="panel-header">
          <h2>Хроники {{ profile.fullname || '@' + profile.username }}</h2>
          <p>Всё, что было опубликовано этим воином</p>
        </div>

        <div v-if="loadingPosts" class="loading-state inline">
          <div class="spinner"></div>
        </div>

        <div v-else-if="!posts.length" class="empty-state">
          <div class="empty-icon">📜</div>
          <p>Пока ни одного поста. Быть может, ещё впереди?</p>
        </div>

        <div v-else class="posts-grid">
          <RouterLink v-for="post in posts" :key="post.id"
                      :to="{ name: 'post', params: { id: post.id } }"
                      class="post-card">
            <div v-if="post.cover_url" class="post-card-cover">
              <img :src="resolveMediaUrl(post.cover_url)" :alt="post.title || 'Пост'" loading="lazy" />
            </div>
            <div class="post-card-body">
              <div v-if="post.tags?.length" class="post-card-tags">
                <span v-for="tag in post.tags.slice(0,3)" :key="tag" class="post-tag">{{ tag }}</span>
              </div>
              <h3 v-if="post.title" class="post-card-title">{{ post.title }}</h3>
              <p class="post-card-excerpt">{{ post.body.length > 180 ? post.body.slice(0, 180) + '…' : post.body }}</p>
              <div class="post-card-footer">
                <span class="post-meta">{{ formatDate(post.published_at) }}</span>
                <div class="post-stats">
                  <span class="ps-item">🔥 {{ post.reaction_count }}</span>
                  <span class="ps-item">💬 {{ post.comment_count }}</span>
                  <span class="ps-item">👁 {{ post.view_count }}</span>
                </div>
              </div>
            </div>
          </RouterLink>
        </div>
      </div>

    </template>
  </div>
</template>

<style scoped>
.profile-page {
  min-height: 100vh;
  color: var(--text-bone);
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
.loading-state.inline { padding: 40px 24px; }
.spinner {
  width: 36px;
  height: 36px;
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
.nf-sigil {
  font-size: 4rem;
  color: var(--bronze);
  margin-bottom: 8px;
  filter: drop-shadow(0 0 18px rgba(255, 132, 51, 0.3));
}
.not-found h1 {
  font-family: var(--font-display);
  font-size: clamp(1.8rem, 4vw, 2.6rem);
  color: var(--text-bright);
  margin: 6px 0;
}
.not-found p { color: var(--text-parchment); max-width: 480px; }
.btn-primary {
  display: inline-block;
  padding: 12px 28px;
  background: var(--grad-ember);
  color: var(--text-bright);
  font-family: var(--font-display);
  font-weight: var(--fw-bold);
  letter-spacing: 1px;
  text-transform: uppercase;
  text-decoration: none;
  border: 1px solid var(--ember-deep);
  border-radius: 6px;
  box-shadow: var(--inset-iron-top), 0 4px 14px rgba(239, 74, 24, 0.35);
  transition: transform 0.2s var(--ease-smoke), filter 0.2s;
}
.btn-primary:hover { transform: translateY(-2px); filter: brightness(1.1); }

.btn-secondary {
  display: inline-block;
  padding: 11px 24px;
  background: linear-gradient(180deg, var(--ash-stone), var(--ash-coal));
  color: var(--text-parchment);
  font-family: var(--font-display);
  font-weight: var(--fw-semibold);
  letter-spacing: 0.5px;
  border: 1px solid var(--iron-mid);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s var(--ease-smoke);
}
.btn-secondary:disabled { opacity: 0.6; cursor: not-allowed; }

/* «Подписан» — на hover становится «Отписаться» (красный) */
.following-btn {
  position: relative;
  transition: all 0.2s var(--ease-smoke);
}
.following-btn:hover:not(:disabled) {
  background: linear-gradient(180deg, rgba(168, 35, 24, 0.55), rgba(122, 28, 20, 0.7));
  border-color: var(--ember-flame);
  color: var(--text-bright);
}
.following-btn:hover:not(:disabled) span::before {
  content: '−';
  margin-right: 4px;
}
.following-btn:hover:not(:disabled) span {
  visibility: hidden;
  position: relative;
}
.following-btn:hover:not(:disabled) span::after {
  content: '− Отписаться';
  position: absolute;
  inset: 0;
  visibility: visible;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* ═══ Hero ═══ */
.profile-hero {
  position: relative;
  overflow: hidden;
  background: linear-gradient(180deg,
    var(--ash-obsidian) 0%,
    var(--ash-coal) 45%,
    var(--ash-obsidian) 100%);
  border-bottom: 1px solid var(--iron-dark);
  padding: 64px 24px 56px;
  box-shadow: var(--inset-forge);
  isolation: isolate;
}
.hero-bg-blur {
  position: absolute;
  border-radius: 50%;
  filter: blur(110px);
  pointer-events: none;
}
.hero-bg-blur.b1 {
  width: 520px; height: 420px;
  background: radial-gradient(circle, var(--ember-glow) 0%, transparent 70%);
  top: -140px; left: -120px;
  opacity: 0.28;
}
.hero-bg-blur.b2 {
  width: 420px; height: 320px;
  background: radial-gradient(circle, var(--ember-heart) 0%, transparent 70%);
  bottom: -90px; right: -60px;
  opacity: 0.22;
}
.hero-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(122, 93, 72, 0.06) 1px, transparent 1px),
    linear-gradient(90deg, rgba(122, 93, 72, 0.06) 1px, transparent 1px);
  background-size: 54px 54px;
  mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 40%, transparent 100%);
  -webkit-mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 40%, transparent 100%);
  pointer-events: none;
}

.hero-inner {
  position: relative;
  z-index: 2;
  max-width: var(--content-max);
  margin: 0 auto;
  display: grid;
  grid-template-columns: auto 1fr auto;
  grid-template-rows: auto auto;
  gap: 18px 28px;
  align-items: center;
}

/* Avatar */
.avatar-wrap {
  position: relative;
  width: 130px;
  height: 130px;
  grid-row: 1 / 3;
}
.avatar-ring {
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  background: conic-gradient(from 180deg, var(--ember-flame), var(--bronze), var(--ember-glow), var(--ember-flame));
  animation: ringSpin 12s linear infinite;
  filter: blur(2px);
  opacity: 0.65;
}
@keyframes ringSpin { to { transform: rotate(360deg); } }
.avatar {
  position: relative;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: linear-gradient(180deg, var(--ash-stone), var(--ash-coal));
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-size: 3.4rem;
  color: var(--ember-gold);
  font-weight: var(--fw-black);
  border: 2px solid var(--iron-dark);
  overflow: hidden;
  box-shadow: var(--inset-iron-top), var(--shadow-cast);
}
.avatar-img { width: 100%; height: 100%; object-fit: cover; }

/* Meta */
.hero-meta { min-width: 0; }
.hero-name-row {
  display: flex;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
  margin-bottom: 6px;
}
.hero-name {
  font-family: var(--font-display);
  font-size: clamp(1.7rem, 3.2vw, 2.2rem);
  color: var(--text-bright);
  margin: 0;
  font-weight: var(--fw-black);
  letter-spacing: 0.3px;
}
.hero-username {
  font-family: var(--font-ui);
  font-size: 0.95rem;
  color: var(--ember-spark);
  margin: 0 0 4px;
  letter-spacing: 0.4px;
}
.hero-since {
  font-size: 0.88rem;
  color: var(--text-ash);
  margin: 0;
}

.role-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 12px;
  border-radius: 12px;
  font-family: var(--font-display);
  font-size: 0.78rem;
  font-weight: var(--fw-semibold);
  text-transform: uppercase;
  letter-spacing: 0.4px;
}
.role-badge.role-user {
  background: rgba(199, 154, 94, 0.12);
  color: var(--brass);
  border: 1px solid rgba(199, 154, 94, 0.4);
}
.role-badge.role-manager {
  background: rgba(122, 28, 20, 0.18);
  color: #ff8433;
  border: 1px solid rgba(226, 67, 16, 0.5);
}
.role-badge.role-admin {
  background: linear-gradient(135deg, rgba(255, 132, 51, 0.2), rgba(255, 201, 121, 0.18));
  color: var(--ember-gold);
  border: 1px solid rgba(255, 167, 88, 0.6);
  box-shadow: 0 0 8px rgba(255, 132, 51, 0.25);
}

.status-badge.frozen {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  background: rgba(74, 115, 149, 0.18);
  color: #7db3d4;
  border: 1px solid rgba(74, 115, 149, 0.5);
  border-radius: 12px;
  font-size: 0.78rem;
  font-weight: var(--fw-semibold);
}

.hero-actions {
  grid-row: 1;
  grid-column: 3;
}

/* Quick stats */
.hero-stats {
  grid-column: 2 / 4;
  display: flex;
  align-items: center;
  gap: 22px;
  flex-wrap: wrap;
}
.hs-item {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 2px;
}
.hs-num {
  font-family: var(--font-display);
  font-size: 1.6rem;
  font-weight: var(--fw-black);
  color: var(--ember-gold);
  letter-spacing: 0.3px;
  line-height: 1;
}
.hs-label {
  font-size: 0.78rem;
  color: var(--text-ash);
  letter-spacing: 0.3px;
}
.hs-sep {
  width: 1px;
  height: 28px;
  background: var(--iron-mid);
}

/* ═══ Body — посты ═══ */
.profile-body {
  max-width: var(--content-max);
  margin: 40px auto 80px;
  padding: 0 24px;
}
.panel-header {
  margin-bottom: 24px;
}
.panel-header h2 {
  font-family: var(--font-display);
  font-size: clamp(1.4rem, 3vw, 1.8rem);
  color: var(--text-bright);
  margin: 0 0 4px;
}
.panel-header p {
  color: var(--text-parchment);
  margin: 0;
}

/* ═══ Библиотека игр (Phase 3 / Batch C) ═══ */
.library-block { margin-top: 30px; margin-bottom: 30px; }
.library-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 14px;
}
.library-card {
  display: flex;
  flex-direction: column;
  text-decoration: none;
  color: inherit;
  background: linear-gradient(180deg, var(--ash-stone), var(--ash-coal));
  border: 1px solid var(--iron-mid);
  border-radius: 6px;
  overflow: hidden;
  transition: all 0.2s var(--ease-smoke);
}
.library-card:hover {
  transform: translateY(-3px);
  border-color: var(--bronze);
  box-shadow: var(--inset-iron-top), 0 0 18px rgba(239, 74, 24, 0.22);
}
.lib-cover {
  width: 100%;
  height: 180px;
  background: var(--ash-obsidian);
  overflow: hidden;
}
.lib-cover img {
  width: 100%; height: 100%;
  object-fit: cover;
  transition: transform 0.4s var(--ease-smoke);
}
.library-card:hover .lib-cover img { transform: scale(1.06); }
.lib-title {
  padding: 8px 12px 2px;
  font-family: var(--font-display);
  font-size: 0.92rem;
  color: var(--text-bone);
  font-weight: var(--fw-semibold);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.lib-platform {
  padding: 0 12px 8px;
  font-size: 0.75rem;
  color: var(--text-ash);
  font-family: var(--font-ui);
}

.empty-state {
  text-align: center;
  padding: 60px 24px;
  background: linear-gradient(180deg, rgba(8, 6, 10, 0.4), rgba(20, 16, 13, 0.4));
  border: 1px dashed var(--iron-mid);
  border-radius: 8px;
  color: var(--text-parchment);
}
.empty-icon {
  font-size: 3rem;
  margin-bottom: 14px;
  opacity: 0.5;
}

.posts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}
.post-card {
  display: flex;
  flex-direction: column;
  background: linear-gradient(180deg, var(--ash-stone), var(--ash-coal));
  border: 1px solid var(--iron-mid);
  border-radius: 6px;
  overflow: hidden;
  text-decoration: none;
  color: inherit;
  transition: all 0.25s var(--ease-smoke);
  box-shadow: var(--inset-iron-top), var(--shadow-cast);
}
.post-card:hover {
  transform: translateY(-3px);
  border-color: var(--bronze);
  box-shadow: var(--inset-iron-top), var(--shadow-deep), 0 0 22px rgba(239, 74, 24, 0.25);
}
.post-card-cover {
  width: 100%;
  height: 160px;
  background: var(--ash-obsidian);
  overflow: hidden;
}
.post-card-cover img {
  width: 100%; height: 100%;
  object-fit: cover;
  transition: transform 0.4s var(--ease-smoke);
}
.post-card:hover .post-card-cover img { transform: scale(1.05); }
.post-card-body {
  padding: 18px 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  flex: 1;
}
.post-card-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}
.post-tag {
  font-family: var(--font-ui);
  font-size: 0.72rem;
  color: var(--ember-spark);
  background: rgba(226, 67, 16, 0.1);
  padding: 2px 8px;
  border-radius: 10px;
  border: 1px solid rgba(226, 67, 16, 0.25);
}
.post-card-title {
  font-family: var(--font-display);
  font-size: 1.1rem;
  color: var(--text-bright);
  margin: 0;
  line-height: 1.3;
}
.post-card-excerpt {
  font-size: 0.92rem;
  color: var(--text-parchment);
  line-height: 1.55;
  margin: 0;
  flex: 1;
}
.post-card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 10px;
  border-top: 1px solid var(--iron-mid);
}
.post-meta {
  font-size: 0.8rem;
  color: var(--text-ash);
}
.post-stats {
  display: flex;
  gap: 12px;
  font-size: 0.82rem;
  color: var(--text-parchment);
}
.ps-item {
  display: inline-flex;
  align-items: center;
  gap: 3px;
}

/* Mobile */
@media (max-width: 720px) {
  .profile-hero { padding: 40px 18px 36px; }
  .hero-inner {
    grid-template-columns: 100px 1fr;
    grid-template-rows: auto auto auto;
  }
  .avatar-wrap { width: 100px; height: 100px; grid-row: 1; }
  .hero-meta { grid-column: 2; grid-row: 1; }
  .hero-actions { grid-column: 1 / 3; grid-row: 2; margin-top: 8px; }
  .hero-stats { grid-column: 1 / 3; grid-row: 3; }
  .hero-name { font-size: 1.4rem; }
  .hero-name-row { gap: 8px; }
}
</style>
