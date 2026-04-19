<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import { useHead } from '@vueuse/head';
import api from '../api/axios';
import { useCartStore } from '../stores/cart';
import { useAuthStore } from '../stores/auth';
import ReviewList from '../components/ReviewList.vue';
import ReviewForm from '../components/ReviewForm.vue';

const route = useRoute();
const gameId = computed(() => route.params.id);
const game = ref(null);
const similarGames = ref([]);
const reviews = ref([]);
const loading = ref(true);
const loadingReviews = ref(false);
const error = ref('');

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

const resolveImageUrl = (imagePath, absolute = false) => {
  if (!imagePath) { const p = '/img/noimage.png'; return absolute ? `${window.location.origin}${p}` : p; }
  if (imagePath.includes('/')) { const u = `http://localhost:8000${imagePath}`; return absolute ? u : u; }
  const lp = `/img/${imagePath}`;
  return absolute ? `${window.location.origin}${lp}` : lp;
};

const loadGame = async (id) => {
  loading.value = true; error.value = ''; game.value = null;
  try {
    const { data } = await api.get(`/games/${id}`);
    game.value = data;
    loadSimilarGames(data.genre, data.id);
    loadReviews(id);
    setupReveal();
  } catch (e) { error.value = 'Игра не найдена или произошла ошибка.'; console.error(e); }
  finally { loading.value = false; }
};

const loadSimilarGames = async (genre, currentId) => {
  if (!genre) return;
  try { const { data } = await api.get(`/games?genre=${genre}&limit=4`); similarGames.value = data.filter(g => g.id !== currentId).slice(0, 4); }
  catch (e) { console.error(e); }
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

const coverImageSrc = computed(() => resolveImageUrl(game.value?.image));
const backgroundImageUrl = computed(() => game.value ? `url(${coverImageSrc.value})` : 'none');

const youtubeEmbedUrl = computed(() => {
  if (!game.value?.trailer_url) return null;
  try {
    const url = new URL(game.value.trailer_url);
    let videoId = url.hostname === 'youtu.be' ? url.pathname.slice(1) : url.searchParams.get('v');
    return videoId ? `https://www.youtube.com/embed/${videoId}` : null;
  } catch { return null; }
});

useHead(computed(() => {
  if (!game.value) return { title: 'Загрузка...' };
  const title = `Купить ${game.value.title} — GameStore`;
  const desc = `Купите ${game.value.title} для ${game.value.platform}. Мгновенная доставка ключа, низкие цены.`;
  return {
    title,
    meta: [{ name: 'description', content: desc }],
    script: [{ type: 'application/ld+json', children: JSON.stringify({ '@context': 'https://schema.org', '@type': 'Product', name: game.value.title, image: resolveImageUrl(game.value.image, true), offers: { '@type': 'Offer', priceCurrency: 'RUB', price: game.value.price, availability: 'https://schema.org/InStock' } }) }]
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

      <!-- BUY BLOCK -->
      <header class="buy-block reveal">
        <div class="cover-wrap">
          <img :src="coverImageSrc" :alt="`Обложка ${game.title}`" class="cover-img" width="300" height="420" />
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
            <span class="meta-pill">🎮 {{ game.platform }}</span>
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
              <span v-else>🛒 В корзину</span>
            </button>
            <a :href="stopGameUrl" target="_blank" rel="noopener" class="sg-btn">Обзоры StopGame ↗</a>
          </div>

          <!-- Delivery note -->
          <div class="delivery-note">
            <span>⚡</span> Мгновенная доставка ключа на e-mail после оплаты
          </div>

          <!-- Guarantees -->
          <div class="guarantees">
            <div class="guarantee-item"><span class="gi-icon">🔒</span><span>Безопасная оплата</span></div>
            <div class="guarantee-item"><span class="gi-icon">🎯</span><span>Лицензионный ключ</span></div>
            <div class="guarantee-item"><span class="gi-icon">💬</span><span>Поддержка 24/7</span></div>
          </div>
        </div>
      </header>

      <!-- CONTENT GRID -->
      <div class="content-grid">

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
              <a v-for="img in game.images" :key="img.id" :href="`http://localhost:8000${img.path}`" target="_blank" class="ss-link">
                <img :src="`http://localhost:8000${img.path}`" :alt="`Скриншот ${game.title}`" class="ss-img" loading="lazy" width="640" height="360" />
                <div class="ss-overlay">🔍</div>
              </a>
            </div>
          </section>

          <section class="content-card reviews-card reveal">
            <h2 class="sec-title"><span class="sec-accent">★</span> Отзывы <span class="review-count">({{ reviews.length }})</span></h2>
            <ReviewForm v-if="authStore.isLoggedIn" :game-id="gameId" @review-submitted="() => loadReviews(gameId)" />
            <div v-else class="login-notice">
              <span>💬</span>
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
            <h3 class="sidebar-title">📋 Детали</h3>
            <ul class="details-list">
              <li v-if="game.platform"><span class="dl-key">Платформа</span><strong class="dl-val">{{ game.platform }}</strong></li>
              <li v-if="game.genre"><span class="dl-key">Жанр</span><strong class="dl-val">{{ game.genre }}</strong></li>
              <li v-if="game.release_year"><span class="dl-key">Год выхода</span><strong class="dl-val">{{ game.release_year }}</strong></li>
              <li v-if="game.average_review_rating"><span class="dl-key">Рейтинг</span><strong class="dl-val rating-val">{{ Number(game.average_review_rating).toFixed(1) }} ★</strong></li>
            </ul>
          </div>

          <!-- Description -->
          <div class="content-card reveal">
            <h3 class="sidebar-title">📖 Об игре</h3>
            <div v-if="game.description" v-html="game.description" class="desc-body"></div>
            <p v-else class="desc-body">
              В онлайн-магазине <strong>GameStore</strong> вы можете купить лицензионный ключ <strong>{{ game.title }}</strong> для {{ game.platform }} по самой выгодной цене.
            </p>
          </div>
        </aside>
      </div>

      <!-- SIMILAR GAMES -->
      <section v-if="similarGames.length" class="similar-section reveal">
        <h2 class="sec-title-lg"><span class="sec-accent">🎲</span> Похожие игры</h2>
        <div class="similar-grid">
          <RouterLink
            v-for="sg in similarGames" :key="sg.id"
            :to="`/games/${sg.id}`"
            class="sim-card"
          >
            <div class="sim-img-wrap">
              <img :src="resolveImageUrl(sg.image)" :alt="sg.title" class="sim-img" loading="lazy" width="200" height="280" />
              <div class="sim-gradient"></div>
            </div>
            <div class="sim-info">
              <div class="sim-title">{{ sg.title }}</div>
              <div class="sim-price">{{ Number(sg.price).toFixed(0) }} ₽</div>
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
  filter: blur(28px) brightness(0.3) saturate(1.2);
  opacity: 0; transform: scale(1.05);
  transition: opacity 0.9s ease;
}
.gp-root.loaded .hero-backdrop { opacity: 1; }

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
.gp-inner { max-width: 1220px; margin: 0 auto; padding: 30px 24px 80px; }

/* ─── Buy Block ─── */
.buy-block {
  display: grid; grid-template-columns: 280px 1fr; gap: 36px;
  background: rgba(15,23,42,0.7); backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.1); border-radius: 20px;
  padding: 28px; margin-bottom: 36px; align-items: start;
}

.cover-wrap { position: relative; }
.cover-img { width: 100%; border-radius: 12px; display: block; box-shadow: 0 16px 40px rgba(0,0,0,0.5); }
.cover-glow { position: absolute; inset: -10px; border-radius: 18px; filter: blur(20px); background: radial-gradient(circle, rgba(59,130,246,0.3), transparent 70%); z-index: -1; }

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

.guarantees { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
.guarantee-item {
  background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.07);
  border-radius: 10px; padding: 10px; display: flex; flex-direction: column;
  align-items: center; gap: 5px; text-align: center; font-size: 0.78rem; color: #9ca3af;
}
.gi-icon { font-size: 1.2rem; }

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

/* Description */
.desc-body { font-size: 0.94rem; line-height: 1.8; color: #9ca3af; margin: 0; }
.desc-body :deep(p) { margin: 0 0 1em; }
.desc-body :deep(p:last-child) { margin: 0; }

/* ─── Similar Games ─── */
.similar-section { margin-top: 32px; }
.sec-title-lg { font-size: 1.5rem; font-weight: 700; color: #fff; margin: 0 0 24px; display: flex; align-items: center; gap: 12px; }
.similar-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 18px; }

.sim-card {
  text-decoration: none; border-radius: 12px; overflow: hidden;
  background: rgba(15,23,42,0.7); border: 1px solid rgba(255,255,255,0.08);
  transition: all 0.25s ease;
}
.sim-card:hover { transform: translateY(-6px); border-color: rgba(59,130,246,0.4); box-shadow: 0 14px 32px rgba(0,0,0,0.5); }
.sim-img-wrap { position: relative; aspect-ratio: 3/4; overflow: hidden; }
.sim-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease; }
.sim-card:hover .sim-img { transform: scale(1.06); }
.sim-gradient { position: absolute; bottom: 0; left: 0; right: 0; height: 60%; background: linear-gradient(to top, rgba(15,23,42,0.9), transparent); }
.sim-info { padding: 12px; }
.sim-title { font-size: 0.9rem; font-weight: 600; color: #f1f5f9; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sim-price { color: #4ade80; font-weight: 700; font-size: 0.95rem; }

/* ─── Responsive ─── */
@media (max-width: 1024px) { .content-grid { grid-template-columns: 1fr; } .sidebar-col { position: static; } }
@media (max-width: 768px) {
  .buy-block { grid-template-columns: 1fr; }
  .cover-wrap { max-width: 240px; margin: 0 auto; }
  .game-title { font-size: 1.8rem; text-align: center; }
  .meta-pills { justify-content: center; }
  .price-row { justify-content: center; }
  .action-row { flex-direction: column; }
  .guarantees { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 480px) { .guarantees { grid-template-columns: 1fr; } }
</style>
