<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import { useHead } from '@vueuse/head';
import api from '../api/axios';
import { useCartStore } from '../stores/cart';
import { useAuthStore } from '../stores/auth';
import ReviewList from '../components/ReviewList.vue';
import ReviewForm from '../components/ReviewForm.vue';
import { resolveMediaUrl } from '../utils/media';

const route = useRoute();
const gameId = computed(() => route.params.id);
const game = ref(null);
const similarGames = ref([]);
const reviews = ref([]);
const mods = ref([]);
const loading = ref(true);
const loadingReviews = ref(false);
const loadingMods = ref(false);
const error = ref('');
const activeTab = ref('info'); // 'info' or 'mods'

const cartStore = useCartStore();
const authStore = useAuthStore();
const isInCart = computed(() => game.value && cartStore.getItemById(game.value.id));
const addToCart = () => {
  if (game.value && authStore.isLoggedIn)
    cartStore.addItem({ id: game.value.id, title: game.value.title, price: game.value.price, image: game.value.image, platform: game.value.platform });
};

// ─── Reading progress ───
const readProgress = ref(0);
const onScroll = () => {
  const el = document.documentElement;
  const total = el.scrollHeight - el.clientHeight;
  readProgress.value = total > 0 ? Math.min(100, Math.round((el.scrollTop / total) * 100)) : 0;
};

// ─── Scroll-reveal ───
let revealObs = null;
const setupReveal = () => {
  if (revealObs) revealObs.disconnect();
  revealObs = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('is-visible'); revealObs.unobserve(e.target); } });
  }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
  setTimeout(() => { document.querySelectorAll('.gp-root .reveal').forEach(el => revealObs.observe(el)); }, 200);
};

const resolveImageUrl = (imagePath) => resolveMediaUrl(imagePath);

const loadGame = async (id) => {
  loading.value = true; error.value = ''; game.value = null;
  try {
    const { data } = await api.get(`/games/${id}`);
    game.value = data;
    loadSimilarGames(data.genre, data.id);
    loadReviews(id);
    loadMods(id);
    setupReveal();
  } catch (e) { error.value = 'Игра не найдена или произошла ошибка.'; console.error(e); }
  finally { loading.value = false; }
};

const loadMods = async (id) => {
  loadingMods.value = true;
  try { const { data } = await api.get(`/games/${id}/mods`); mods.value = data; }
  catch (e) { console.error(e); } finally { loadingMods.value = false; }
};

const loadSimilarGames = async (genre, currentId) => {
  if (!genre) return;
  try {
    const { data } = await api.get(`/games?genre=${genre}&limit=4`);
    similarGames.value = data.filter(g => g.id !== currentId).slice(0, 4);
    // секция появляется в DOM только после загрузки — перезапускаем observer
    setTimeout(setupReveal, 100);
  } catch (e) { console.error(e); }
};

const loadReviews = async (id) => {
  loadingReviews.value = true;
  try { const { data } = await api.get(`/games/${id}/reviews`); reviews.value = data; }
  catch (e) { console.error(e); } finally { loadingReviews.value = false; }
};

const stopGameUrl = computed(() => {
  if (game.value?.stopgame_url_code) return game.value.stopgame_url_code;
  if (game.value?.title) return `https://stopgame.ru/search?q=${encodeURIComponent(game.value.title)}`;
  return '#';
});

const coverImageSrc = computed(() => resolveMediaUrl(game.value?.image));
const backgroundImageUrl = computed(() => game.value ? `url(${coverImageSrc.value})` : 'none');

const youtubeEmbedUrl = computed(() => {
  if (!game.value?.trailer_url) return null;
  try {
    const url = new URL(game.value.trailer_url);
    let videoId = url.hostname === 'youtu.be' ? url.pathname.slice(1) : url.searchParams.get('v');
    return videoId ? `https://www.youtube.com/embed/${videoId}` : null;
  } catch { return null; }
});

const formatNumber = (num) => {
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
  return num.toString();
};

const hasSystemRequirements = computed(() => {
  return game.value && (
    game.value.os_requirements ||
    game.value.processor_requirements ||
    game.value.ram_requirements ||
    game.value.graphics_requirements ||
    game.value.storage_requirements
  );
});

useHead(computed(() => {
  if (!game.value) return { title: 'Загрузка...' };
  const title = `Купить ${game.value.title} — GameStore`;
  const desc = `Купите ${game.value.title} для ${game.value.platform}. Мгновенная доставка ключа, низкие цены.`;
  const img = resolveMediaUrl(game.value.image);
  return {
    title,
    meta: [
      { name: 'description', content: desc },
      { property: 'og:type', content: 'product' },
      { property: 'og:title', content: title },
      { property: 'og:description', content: desc },
      { property: 'og:image', content: img },
      { name: 'robots', content: 'index, follow' },
    ],
    script: [{ type: 'application/ld+json', children: JSON.stringify({ '@context': 'https://schema.org', '@type': 'Product', name: game.value.title, image: img, offers: { '@type': 'Offer', priceCurrency: 'RUB', price: game.value.price, availability: 'https://schema.org/InStock' } }) }]
  };
}));

onMounted(() => {
  loadGame(gameId.value);
  window.addEventListener('scroll', onScroll, { passive: true });
});
onUnmounted(() => {
  window.removeEventListener('scroll', onScroll);
  revealObs?.disconnect();
});
watch(gameId, (id) => { if (id) loadGame(id); });
</script>

<template>
  <div class="gp-root" :class="{ loaded: game }">
    <!-- ─── Reading Progress Bar ─── -->
    <div class="progress-bar" :style="{ width: readProgress + '%' }"></div>

    <!-- ─── Blurred hero backdrop ─── -->
    <div v-if="game" class="hero-backdrop" :style="{ backgroundImage: backgroundImageUrl }"></div>

    <!-- ─── Loading / Error ─── -->
    <div v-if="loading" class="status-box">
      <div class="loading-spinner"></div>
      <p>Загружаем игру...</p>
    </div>
    <div v-else-if="error" class="status-box error-box">
      <div class="status-icon">⚠️</div>
      <p>{{ error }}</p>
      <RouterLink to="/catalog" class="back-btn">← Вернуться в каталог</RouterLink>
    </div>

    <!-- ─── GAME CONTENT ─── -->
    <div v-else-if="game" class="gp-inner">

      <!-- local top-area backdrop (semi-transparent cover art behind buy block) -->
      <div
        class="local-backdrop"
        :style="{ backgroundImage: backgroundImageUrl }"
      ></div>

      <!-- BUY BLOCK -->
      <header class="buy-block reveal">
        <div class="cover-wrap" :style="{ '--thumb': `url(${coverImageSrc})` }">
          <!-- Blurred backdrop so landscape images don't leave ugly gaps -->
          <div class="cover-blur-bg"></div>
          <img :src="coverImageSrc" :alt="`Обложка ${game.title}`" class="cover-img" />
          <div class="cover-glow"></div>
        </div>
        <div class="buy-info">
          <!-- Breadcrumb -->
          <div class="breadcrumb">
            <RouterLink to="/catalog">Каталог</RouterLink>
            <span>›</span>
            <span class="bc-genre">{{ game.genre }}</span>
          </div>

          <h1 class="game-title">{{ game.title }}</h1>

          <!-- Meta pills -->
          <div class="meta-pills">
            <span class="meta-pill">{{ game.platform }}</span>
            <span class="meta-pill">🎭 {{ game.genre }}</span>
            <span v-if="game.release_year" class="meta-pill">📅 {{ game.release_year }}</span>
          </div>

          <!-- Price -->
          <div class="price-row">
            <span v-if="game.discount_percent" class="disc-badge">-{{ game.discount_percent }}%</span>
            <span v-if="game.old_price" class="old-price">{{ Number(game.old_price).toFixed(0) }} ₽</span>
            <span class="cur-price">{{ Number(game.price).toFixed(0) }} ₽</span>
          </div>

          <!-- Actions -->
          <div class="action-row">
            <button
              @click="addToCart" class="cart-btn" :class="{ 'in-cart': isInCart }"
              :disabled="!authStore.isLoggedIn || isInCart"
              :title="!authStore.isLoggedIn ? 'Войдите, чтобы купить' : isInCart ? 'Уже в корзине' : 'В корзину'"
            >
              <span v-if="isInCart">✓ В корзине</span>
              <span v-else>В корзину</span>
            </button>
            <a :href="stopGameUrl" target="_blank" rel="noopener" class="sg-btn">Обзоры StopGame ↗</a>
          </div>

          <!-- Delivery note -->
          <div class="delivery-note">
            Мгновенная доставка ключа на e-mail после оплаты
          </div>

          <!-- Description -->
          <div v-if="game.description" class="game-description">
            <h3 class="desc-title">📖 Об игре</h3>
            <div v-html="game.description" class="desc-body"></div>
          </div>
        </div>
      </header>

      <!-- TABS -->
      <div class="tabs-container reveal">
        <button
          @click="activeTab = 'info'"
          class="tab-btn"
          :class="{ active: activeTab === 'info' }"
        >
          📋 Об игре
        </button>
        <button
          @click="activeTab = 'mods'"
          class="tab-btn"
          :class="{ active: activeTab === 'mods' }"
        >
          🎮 Моды ({{ mods.length }})
        </button>
      </div>

      <!-- CONTENT GRID -->
      <div v-show="activeTab === 'info'" class="content-grid">

        <!-- LEFT: Trailer + Screenshots + Reviews -->
        <div class="main-col">

          <section v-if="youtubeEmbedUrl" class="content-card reveal">
            <h2 class="sec-title"><span class="sec-accent">▶</span> Трейлер</h2>
            <div class="video-wrap">
              <iframe :src="youtubeEmbedUrl" :title="`Трейлер ${game.title}`" frameborder="0" allowfullscreen loading="lazy"></iframe>
            </div>
          </section>

          <section v-if="game.images && game.images.length" class="content-card reveal">
            <h2 class="sec-title"><span class="sec-accent">🖼</span> Скриншоты</h2>
            <div class="screenshots-grid">
              <a v-for="img in game.images" :key="img.id" :href="resolveImageUrl(img.path)" target="_blank" class="ss-link">
                <img :src="resolveImageUrl(img.path)" :alt="`Скриншот ${game.title}`" class="ss-img" loading="lazy" width="640" height="360" />
                <div class="ss-overlay"></div>
              </a>
            </div>
          </section>

          <section class="content-card reviews-card reveal">
            <h2 class="sec-title"><span class="sec-accent">★</span> Отзывы <span class="review-count">({{ reviews.length }})</span></h2>
            <ReviewForm v-if="authStore.isLoggedIn" :game-id="gameId" @review-submitted="() => loadReviews(gameId)" />
            <div v-else class="login-notice">
              <p>Чтобы оставить отзыв, <RouterLink to="/login" class="notice-link">войдите в аккаунт</RouterLink></p>
            </div>
            <div v-if="loadingReviews" class="reviews-loading">Загружаем отзывы...</div>
            <ReviewList v-else :reviews="reviews" />
          </section>
        </div>

        <!-- RIGHT SIDEBAR -->
        <aside class="sidebar-col">
          <!-- Details card -->
          <div class="content-card reveal">
            <h3 class="sidebar-title">Детали</h3>
            <ul class="details-list">
              <li v-if="game.platform"><span class="dl-key">Платформа</span><strong class="dl-val">{{ game.platform }}</strong></li>
              <li v-if="game.genre"><span class="dl-key">Жанр</span><strong class="dl-val">{{ game.genre }}</strong></li>
              <li v-if="game.release_year"><span class="dl-key">Год выхода</span><strong class="dl-val">{{ game.release_year }}</strong></li>
              <li v-if="game.average_review_rating"><span class="dl-key">Рейтинг</span><strong class="dl-val rating-val">{{ Number(game.average_review_rating).toFixed(1) }} ★</strong></li>
            </ul>
          </div>

          <!-- System Requirements card -->
          <div v-if="hasSystemRequirements" class="content-card reveal">
            <h3 class="sidebar-title">💻 Системные требования</h3>
            <ul class="details-list">
              <li v-if="game.os_requirements"><span class="dl-key">ОС</span><strong class="dl-val">{{ game.os_requirements }}</strong></li>
              <li v-if="game.processor_requirements"><span class="dl-key">Процессор</span><strong class="dl-val">{{ game.processor_requirements }}</strong></li>
              <li v-if="game.ram_requirements"><span class="dl-key">Оперативная память</span><strong class="dl-val">{{ game.ram_requirements }}</strong></li>
              <li v-if="game.graphics_requirements"><span class="dl-key">Видеокарта</span><strong class="dl-val">{{ game.graphics_requirements }}</strong></li>
              <li v-if="game.storage_requirements"><span class="dl-key">Место на диске</span><strong class="dl-val">{{ game.storage_requirements }}</strong></li>
            </ul>
          </div>
        </aside>
      </div>

      <!-- MODS SECTION -->
      <div v-show="activeTab === 'mods'" class="mods-section">
        <section class="content-card reveal">
          <h2 class="sec-title"><span class="sec-accent">🎮</span> Моды для {{ game.title }}</h2>

          <div v-if="loadingMods" class="mods-loading">
            <div class="loading-spinner"></div>
            <p>Загружаем моды...</p>
          </div>

          <div v-else-if="mods.length === 0" class="mods-empty">
            <div class="empty-icon">📦</div>
            <h3>Модов пока нет</h3>
            <p>Для этой игры ещё не добавлены моды. Следите за обновлениями!</p>
          </div>

          <div v-else class="mods-list">
            <div
              v-for="mod in mods"
              :key="mod.id"
              class="mod-card"
              :class="{ featured: mod.is_featured }"
            >
              <div class="mod-header">
                <h3 class="mod-title">{{ mod.title }}</h3>
                <div class="mod-badges">
                  <span v-if="mod.is_featured" class="badge featured-badge">⭐ Избранный</span>
                  <span v-if="mod.version" class="badge version-badge">v{{ mod.version }}</span>
                </div>
              </div>

              <p v-if="mod.description" class="mod-description">{{ mod.description }}</p>

              <div class="mod-meta">
                <span v-if="mod.author" class="meta-item">
                  <span class="meta-icon">👤</span> {{ mod.author }}
                </span>
                <span v-if="mod.source_site" class="meta-item">
                  <span class="meta-icon">🌐</span> {{ mod.source_site }}
                </span>
                <span v-if="mod.download_count" class="meta-item">
                  <span class="meta-icon">⬇️</span> {{ formatNumber(mod.download_count) }}
                </span>
                <span v-if="mod.popularity_score" class="meta-item">
                  <span class="meta-icon">⭐</span> {{ Number(mod.popularity_score).toFixed(1) }}
                </span>
              </div>

              <a
                v-if="mod.external_url"
                :href="mod.external_url"
                target="_blank"
                rel="noopener"
                class="mod-download-btn"
              >
                <span class="btn-icon">🔗</span>
                <span class="btn-text">Открыть источник</span>
                <span class="btn-arrow">→</span>
              </a>
            </div>
          </div>
        </section>
      </div>

      <!-- SIMILAR GAMES -->
      <section v-if="similarGames.length" class="similar-section reveal">
        <div class="sec-header">
          <h2 class="sec-title-lg">Похожие игры</h2>
          <RouterLink to="/catalog" class="see-all-link">Весь каталог →</RouterLink>
        </div>
        <div class="similar-grid">
          <RouterLink
            v-for="sg in similarGames" :key="sg.id"
            :to="`/games/${sg.id}`"
            class="sim-card"
          >
            <div class="sim-img-wrap" :style="{ '--simthumb': `url(${resolveImageUrl(sg.image)})` }">
              <div class="sim-blur-bg"></div>
              <img :src="resolveImageUrl(sg.image)" :alt="sg.title" class="sim-img" loading="lazy" />
              <div class="sim-gradient"></div>
              <span v-if="sg.discount_percent" class="sim-badge">-{{ sg.discount_percent }}%</span>
            </div>
            <div class="sim-info">
              <div class="sim-title">{{ sg.title }}</div>
              <div class="sim-genre">{{ sg.genre }}</div>
              <div class="sim-bottom">
                <span class="sim-price">{{ Number(sg.price).toFixed(0) }} ₽</span>
                <span v-if="sg.rating" class="sim-rating">★ {{ Number(sg.rating).toFixed(1) }}</span>
              </div>
            </div>
          </RouterLink>
        </div>
      </section>

    </div>
  </div>
</template>

<style scoped>
/* ─── Reveal ─── */
.reveal { opacity: 0; transform: translateY(32px); transition: opacity 0.6s ease, transform 0.6s ease; }
.reveal.is-visible { opacity: 1; transform: none; }

/* ─── Root ─── */
.gp-root { position: relative; color: #e5e7eb; min-height: 100vh; }

/* ─── Progress Bar ─── */
.progress-bar {
  position: fixed; top: 0; left: 0; height: 3px; z-index: 1000;
  background: linear-gradient(90deg, #3b82f6, #6366f1, #8b5cf6);
  transition: width 0.1s linear;
  box-shadow: 0 0 10px rgba(99,102,241,0.6);
}

/* ─── Backdrop ─── */
.hero-backdrop {
  position: fixed; inset: 0; z-index: -1;
  background-size: cover; background-position: center;
  filter: blur(40px) brightness(0.22) saturate(1.4);
  opacity: 0; transform: scale(1.08);
  transition: opacity 1s ease, transform 1s ease;
}
.gp-root.loaded .hero-backdrop { opacity: 1; transform: scale(1.04); }

/* ─── Status ─── */
.status-box {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  min-height: 60vh; gap: 20px; text-align: center; padding: 40px;
}
.status-icon { font-size: 3rem; }
.error-box p { color: #fca5a5; font-size: 1.1rem; }
.back-btn { color: #60a5fa; text-decoration: none; font-weight: 600; transition: color 0.2s; }
.back-btn:hover { color: #93c5fd; }

.loading-spinner {
  width: 48px; height: 48px; border-radius: 50%;
  border: 3px solid rgba(59,130,246,0.2); border-top-color: #3b82f6;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ─── Inner ─── */
.gp-inner { max-width: 1220px; margin: 0 auto; padding: 30px 24px 80px; position: relative; }

/* Local backdrop — cover art fills top zone behind buy-block */
.local-backdrop {
  position: absolute;
  top: -30px; left: -60px; right: -60px;
  height: 520px;
  background-size: cover; background-position: center top;
  filter: blur(60px) brightness(0.28) saturate(1.5);
  border-radius: 0 0 60px 60px;
  pointer-events: none; z-index: 0;
}
.buy-block, .content-grid, .similar-section { position: relative; z-index: 1; }

/* ─── Buy Block ─── */
.buy-block {
  display: grid; grid-template-columns: minmax(200px, 280px) 1fr; gap: 36px;
  background: rgba(15,23,42,0.7); backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.1); border-radius: 20px;
  padding: 28px; margin-bottom: 36px; align-items: start;
}

/* Cover: fixed-ratio box, blurred bg fills gaps for landscape art */
.cover-wrap {
  position: relative;
  width: 100%;
  /* Tall enough for portrait, comfortable for landscape */
  aspect-ratio: 3 / 4;
  max-height: 420px;
  border-radius: 14px;
  overflow: hidden;
  background: #0a0f1e;
  box-shadow: 0 16px 40px rgba(0,0,0,0.55);
  flex-shrink: 0;
}
/* Blurred duplicate — fills letterbox gaps for wide images */
.cover-blur-bg {
  position: absolute; inset: -8px; z-index: 0;
  background-image: var(--thumb);
  background-size: cover; background-position: center;
  filter: blur(18px) brightness(0.5) saturate(1.3);
  transform: scale(1.05);
}
/* Actual cover — shown at full size, never cropped */
.cover-img {
  position: relative; z-index: 1;
  width: 100%; height: 100%;
  object-fit: contain; object-position: center;
  display: block;
}
.cover-glow {
  position: absolute; inset: -10px; z-index: 0;
  border-radius: 18px;
  filter: blur(24px);
  background: radial-gradient(circle, rgba(59,130,246,0.25), transparent 70%);
  pointer-events: none;
}

.buy-info { display: flex; flex-direction: column; gap: 18px; }

.breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 0.82rem; color: #6b7280; }
.breadcrumb a { color: #6b7280; text-decoration: none; transition: color 0.2s; }
.breadcrumb a:hover { color: #93c5fd; }
.bc-genre { color: #9ca3af; }

.game-title { font-size: clamp(1.8rem, 3vw, 2.8rem); font-weight: 800; color: #fff; margin: 0; line-height: 1.2; }

.meta-pills { display: flex; flex-wrap: wrap; gap: 8px; }
.meta-pill { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); color: #d1d5db; padding: 5px 14px; border-radius: 999px; font-size: 0.8rem; }

.price-row { display: flex; align-items: baseline; gap: 14px; }
.disc-badge { background: rgba(239,68,68,0.85); color: #fff; padding: 4px 12px; border-radius: 8px; font-weight: 700; font-size: 0.9rem; }
.old-price { color: #6b7280; font-size: 1.2rem; text-decoration: line-through; }
.cur-price { color: #4ade80; font-size: 2.4rem; font-weight: 800; }

.action-row { display: flex; gap: 14px; flex-wrap: wrap; }
.cart-btn {
  flex: 1; min-width: 160px; padding: 14px 24px; border-radius: 12px; border: none;
  background: linear-gradient(135deg, #3b82f6, #6366f1); color: #fff;
  font-size: 1rem; font-weight: 700; cursor: pointer; transition: all 0.25s ease;
  box-shadow: 0 6px 20px rgba(59,130,246,0.35);
}
.cart-btn:hover:not(:disabled) { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(59,130,246,0.5); }
.cart-btn:disabled { cursor: not-allowed; background: #374151; box-shadow: none; }
.cart-btn.in-cart { background: linear-gradient(135deg, #16a34a, #22c55e); box-shadow: 0 6px 20px rgba(34,197,94,0.35); }

.sg-btn {
  padding: 14px 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.15);
  background: rgba(255,255,255,0.06); color: #d1d5db; font-weight: 600;
  text-decoration: none; font-size: 0.9rem; transition: all 0.22s ease; white-space: nowrap;
  display: flex; align-items: center;
}
.sg-btn:hover { border-color: rgba(255,255,255,0.3); background: rgba(255,255,255,0.1); color: #fff; }

.delivery-note { font-size: 0.88rem; color: #9ca3af; display: flex; align-items: center; gap: 8px; }

/* ─── Game Description ─── */
.game-description {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid rgba(255,255,255,0.1);
}
.desc-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #fff;
  margin: 0 0 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.game-description .desc-body {
  font-size: 0.95rem;
  line-height: 1.7;
  color: #9ca3af;
  margin: 0;
}
.game-description .desc-body :deep(p) {
  margin: 0 0 1em;
}
.game-description .desc-body :deep(p:last-child) {
  margin: 0;
}
.game-description .desc-body :deep(h3) {
  color: #e5e7eb;
  margin: 1.2em 0 0.6em;
  font-size: 1rem;
  font-weight: 600;
}
.game-description .desc-body :deep(ul) {
  margin: 0.6em 0;
  padding-left: 1.5em;
}
.game-description .desc-body :deep(li) {
  margin: 0.4em 0;
}

/* ─── Content Grid ─── */
.content-grid { display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start; }
.main-col { display: flex; flex-direction: column; gap: 24px; min-width: 0; }
.sidebar-col { display: flex; flex-direction: column; gap: 24px; position: sticky; top: 84px; }

/* ─── Content Card ─── */
.content-card {
  background: rgba(15,23,42,0.7); backdrop-filter: blur(16px);
  border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 24px;
}

.sec-title { font-size: 1.3rem; font-weight: 700; color: #fff; margin: 0 0 20px; display: flex; align-items: center; gap: 10px; }
.sec-accent { font-size: 1rem; }
.review-count { font-size: 0.9rem; color: #6b7280; font-weight: 400; }

.sidebar-title { font-size: 1.05rem; font-weight: 700; color: #fff; margin: 0 0 16px; }

/* Video */
.video-wrap { position: relative; padding-bottom: 56.25%; height: 0; border-radius: 10px; overflow: hidden; }
.video-wrap iframe { position: absolute; inset: 0; width: 100%; height: 100%; }

/* Screenshots */
.screenshots-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
.ss-link { position: relative; border-radius: 8px; overflow: hidden; display: block; }
.ss-img { width: 100%; height: auto; display: block; transition: transform 0.3s ease; }
.ss-link:hover .ss-img { transform: scale(1.04); }
.ss-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; opacity: 0; transition: opacity 0.2s; }
.ss-link:hover .ss-overlay { opacity: 1; }

/* Login notice */
.login-notice {
  display: flex; align-items: center; gap: 12px; padding: 16px;
  background: rgba(59,130,246,0.08); border: 1px solid rgba(59,130,246,0.2);
  border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; color: #9ca3af;
}
.notice-link { color: #60a5fa; text-decoration: none; font-weight: 600; }
.reviews-loading { color: #6b7280; text-align: center; padding: 20px; }

/* Details list */
.details-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 2px; }
.details-list li { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.06); font-size: 0.92rem; }
.details-list li:last-child { border: none; }
.dl-key { color: #6b7280; }
.dl-val { color: #e5e7eb; }
.rating-val { color: #fbbf24; }

/* ─── Similar Games ─── */
.similar-section { margin-top: 40px; }
.sec-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.sec-title-lg { font-size: 1.4rem; font-weight: 700; color: #f1f5f9; margin: 0; }
.see-all-link { font-size: 0.88rem; color: #60a5fa; text-decoration: none; opacity: 0.8; transition: opacity 0.2s; }
.see-all-link:hover { opacity: 1; }
.similar-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
  gap: 16px;
}

.sim-card {
  text-decoration: none; border-radius: 14px; overflow: hidden;
  background: rgba(15,23,42,0.75); border: 1px solid rgba(255,255,255,0.08);
  box-shadow: 0 4px 16px rgba(0,0,0,0.3);
  transition: transform 0.25s ease, border-color 0.25s, box-shadow 0.25s;
  display: flex; flex-direction: column;
}
.sim-card:hover {
  transform: translateY(-7px) scale(1.01);
  border-color: rgba(59,130,246,0.45);
  box-shadow: 0 16px 36px rgba(0,0,0,0.5), 0 0 24px rgba(59,130,246,0.18);
}
.sim-img-wrap {
  position: relative; aspect-ratio: 3/4; overflow: hidden; background: #0a0f1e;
}
.sim-blur-bg {
  position: absolute; inset: -8px; z-index: 0;
  background-image: var(--simthumb); background-size: cover; background-position: center;
  filter: blur(12px) brightness(0.45) saturate(1.2);
  transform: scale(1.05);
}
.sim-img {
  position: relative; z-index: 1;
  width: 100%; height: 100%;
  object-fit: contain; object-position: center;
  transition: transform 0.35s ease;
}
.sim-card:hover .sim-img { transform: scale(1.05); }
.sim-gradient {
  position: absolute; bottom: 0; left: 0; right: 0; height: 55%;
  background: linear-gradient(to top, rgba(10,15,30,0.92), transparent);
  z-index: 2;
}
.sim-badge {
  position: absolute; top: 8px; left: 8px; z-index: 3;
  background: rgba(239,68,68,0.88); color: #fff;
  font-size: 0.68rem; font-weight: 700; padding: 3px 8px; border-radius: 6px;
  backdrop-filter: blur(4px);
}
.sim-info { padding: 11px 12px 13px; flex: 1; display: flex; flex-direction: column; gap: 4px; }
.sim-title { font-size: 0.88rem; font-weight: 700; color: #f1f5f9; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sim-genre { font-size: 0.74rem; color: #64748b; }
.sim-bottom { display: flex; align-items: center; justify-content: space-between; margin-top: 6px; }
.sim-price { color: #4ade80; font-weight: 700; font-size: 0.92rem; }
.sim-rating { font-size: 0.78rem; color: #fbbf24; font-weight: 600; }

/* ─── Responsive ─── */
@media (max-width: 1024px) { .content-grid { grid-template-columns: 1fr; } .sidebar-col { position: static; } }
@media (max-width: 768px) {
  .buy-block { grid-template-columns: 1fr; }
  .cover-wrap { max-width: 240px; margin: 0 auto; }
  .game-title { font-size: 1.8rem; text-align: center; }
  .meta-pills { justify-content: center; }
  .price-row { justify-content: center; }
  .action-row { flex-direction: column; }
}

/* ─── Tabs ─── */
.tabs-container {
  display: flex; gap: 8px; margin-bottom: 24px;
  background: rgba(15,23,42,0.5); padding: 6px; border-radius: 12px;
  border: 1px solid rgba(255,255,255,0.08);
}
.tab-btn {
  flex: 1; padding: 12px 20px; border: none; border-radius: 8px;
  background: transparent; color: #9ca3af; font-size: 0.95rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px;
}
.tab-btn:hover { color: #e5e7eb; background: rgba(255,255,255,0.05); }
.tab-btn.active {
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: #fff; box-shadow: 0 4px 12px rgba(59,130,246,0.3);
}

/* ─── Mods Section ─── */
.mods-section { margin-bottom: 36px; }
.mods-loading, .mods-empty {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: 60px 20px; text-align: center; gap: 16px;
}
.mods-empty .empty-icon { font-size: 4rem; opacity: 0.5; }
.mods-empty h3 { margin: 0; color: #e5e7eb; font-size: 1.2rem; }
.mods-empty p { margin: 0; color: #6b7280; font-size: 0.95rem; }

.mods-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; }
.mod-card {
  background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
  border-radius: 12px; padding: 20px; transition: all 0.2s;
  display: flex; flex-direction: column; gap: 12px;
}
.mod-card:hover {
  border-color: rgba(59,130,246,0.3); background: rgba(59,130,246,0.05);
  transform: translateY(-2px);
}
.mod-card.featured {
  border-color: rgba(251,191,36,0.3); background: rgba(251,191,36,0.05);
}
.mod-card.featured:hover { border-color: rgba(251,191,36,0.5); }

.mod-header { display: flex; justify-content: space-between; align-items: start; gap: 12px; }
.mod-title { margin: 0; color: #e5e7eb; font-size: 1.1rem; font-weight: 600; line-height: 1.3; }
.mod-badges { display: flex; gap: 6px; flex-wrap: wrap; }
.badge {
  padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600;
  white-space: nowrap;
}
.featured-badge { background: rgba(251,191,36,0.2); color: #fbbf24; }
.version-badge { background: rgba(59,130,246,0.2); color: #60a5fa; }

.mod-description {
  margin: 0; color: #9ca3af; font-size: 0.9rem; line-height: 1.5;
  display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
}

.mod-meta { display: flex; flex-wrap: wrap; gap: 12px; font-size: 0.85rem; color: #6b7280; }
.meta-item { display: flex; align-items: center; gap: 4px; }
.meta-icon { font-size: 0.9rem; }

.mod-download-btn {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  padding: 10px 16px; border-radius: 8px; border: none;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: #fff; font-size: 0.9rem; font-weight: 600; text-decoration: none;
  cursor: pointer; transition: all 0.2s; margin-top: auto;
}
.mod-download-btn:hover {
  transform: translateY(-2px); box-shadow: 0 6px 16px rgba(59,130,246,0.4);
}
.btn-icon { font-size: 1rem; }
.btn-text { flex: 1; }
.btn-arrow { font-size: 1.1rem; }
</style>
