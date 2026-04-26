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
const activeTab = ref('info');

const cartStore = useCartStore();
const authStore = useAuthStore();
const isInCart = computed(() => game.value && cartStore.getItemById(game.value.id));
const addToCart = () => {
  if (game.value && authStore.isLoggedIn)
    cartStore.addItem({ id: game.value.id, title: game.value.title, price: game.value.price, image: game.value.image, platform: game.value.platform });
};

// ── Reading progress ──
const readProgress = ref(0);
const onScroll = () => {
  const el = document.documentElement;
  const total = el.scrollHeight - el.clientHeight;
  readProgress.value = total > 0 ? Math.min(100, Math.round((el.scrollTop / total) * 100)) : 0;
};

// ── Scroll-reveal ──
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
    <!-- Ember reading progress bar -->
    <div class="progress-bar" :style="{ width: readProgress + '%' }"></div>

    <!-- Blurred hero backdrop -->
    <div v-if="game" class="hero-backdrop" :style="{ backgroundImage: backgroundImageUrl }"></div>
    <div v-if="game" class="hero-backdrop-overlay"></div>

    <!-- Loading / Error -->
    <div v-if="loading" class="status-box">
      <div class="loading-spinner"></div>
      <p>Поднимаем знамя…</p>
    </div>
    <div v-else-if="error" class="status-box error-box">
      <div class="status-icon">⚠</div>
      <p>{{ error }}</p>
      <RouterLink to="/catalog" class="back-btn">← Вернуться в оружейную</RouterLink>
    </div>

    <!-- GAME CONTENT -->
    <div v-else-if="game" class="gp-inner">

      <!-- BUY BLOCK — кованая плита с обложкой -->
      <header class="buy-block reveal">
        <span class="rivet rivet-tl" aria-hidden="true"></span>
        <span class="rivet rivet-tr" aria-hidden="true"></span>
        <span class="rivet rivet-bl" aria-hidden="true"></span>
        <span class="rivet rivet-br" aria-hidden="true"></span>

        <div class="cover-wrap" :style="{ '--thumb': `url(${coverImageSrc})` }">
          <div class="cover-blur-bg"></div>
          <img :src="coverImageSrc" :alt="`Обложка ${game.title}`" class="cover-img" />
          <div class="cover-glow"></div>
        </div>

        <div class="buy-info">
          <!-- Breadcrumb -->
          <div class="breadcrumb">
            <RouterLink to="/catalog">Оружейная</RouterLink>
            <span class="bc-sep">⚔</span>
            <span class="bc-genre">{{ game.genre }}</span>
          </div>

          <h1 class="game-title">{{ game.title }}</h1>

          <!-- Meta pills -->
          <div class="meta-pills">
            <span class="meta-pill"><span class="mp-icon">◈</span>{{ game.platform }}</span>
            <span class="meta-pill"><span class="mp-icon">⚔</span>{{ game.genre }}</span>
            <span v-if="game.release_year" class="meta-pill"><span class="mp-icon">⚑</span>{{ game.release_year }}</span>
          </div>

          <!-- Price -->
          <div class="price-row">
            <span v-if="game.discount_percent" class="disc-badge">−{{ game.discount_percent }}%</span>
            <span v-if="game.old_price" class="old-price">{{ Number(game.old_price).toFixed(0) }}₽</span>
            <span class="cur-price">
              <span class="cur-val">{{ Number(game.price).toFixed(0) }}</span>
              <span class="cur-unit">₽</span>
            </span>
          </div>

          <!-- Actions -->
          <div class="action-row">
            <button
              @click="addToCart" class="cart-btn" :class="{ 'in-cart': isInCart }"
              :disabled="!authStore.isLoggedIn || isInCart"
              :title="!authStore.isLoggedIn ? 'Войди, чтобы забрать' : isInCart ? 'Уже в добыче' : 'Забрать в добычу'"
            >
              <span v-if="isInCart">
                <span class="btn-icon">✓</span>
                В добыче
              </span>
              <span v-else>
                <span class="btn-icon">⚔</span>
                Забрать
              </span>
            </button>
            <a :href="stopGameUrl" target="_blank" rel="noopener" class="sg-btn">
              <span>Рецензии StopGame</span>
              <span class="sg-arrow">↗</span>
            </a>
          </div>

          <!-- Delivery note -->
          <div class="delivery-note">
            <span class="dn-icon">⚡</span>
            <span>Мгновенная доставка ключа на e-mail после оплаты</span>
          </div>

          <!-- Description -->
          <div v-if="game.description" class="game-description">
            <div class="desc-divider">
              <span></span>
              <span class="desc-divider-spike"></span>
              <span></span>
            </div>
            <h3 class="desc-title">
              <span class="sec-rune">◈</span>
              Об игре
            </h3>
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
          <span class="tab-icon">◈</span>
          <span>Сведения</span>
        </button>
        <button
          @click="activeTab = 'mods'"
          class="tab-btn"
          :class="{ active: activeTab === 'mods' }"
        >
          <span class="tab-icon">⚙</span>
          <span>Моды <span class="tab-count">({{ mods.length }})</span></span>
        </button>
      </div>

      <!-- CONTENT GRID -->
      <div v-show="activeTab === 'info'" class="content-grid">

        <!-- LEFT: Trailer + Screenshots + Reviews -->
        <div class="main-col">
          <section v-if="youtubeEmbedUrl" class="content-card reveal">
            <h2 class="sec-title"><span class="sec-rune">▶</span> Трейлер</h2>
            <div class="video-wrap">
              <iframe :src="youtubeEmbedUrl" :title="`Трейлер ${game.title}`" frameborder="0" allowfullscreen loading="lazy"></iframe>
            </div>
          </section>

          <section v-if="game.images && game.images.length" class="content-card reveal">
            <h2 class="sec-title"><span class="sec-rune">◇</span> Скриншоты</h2>
            <div class="screenshots-grid">
              <a v-for="img in game.images" :key="img.id" :href="resolveImageUrl(img.path)" target="_blank" class="ss-link">
                <img :src="resolveImageUrl(img.path)" :alt="`Скриншот ${game.title}`" class="ss-img" loading="lazy" width="640" height="360" />
                <div class="ss-overlay"><span>+</span></div>
              </a>
            </div>
          </section>

          <section class="content-card reviews-card reveal">
            <h2 class="sec-title">
              <span class="sec-rune">★</span> Рецензии
              <span class="review-count">({{ reviews.length }})</span>
            </h2>
            <ReviewForm v-if="authStore.isLoggedIn" :game-id="gameId" @review-submitted="() => loadReviews(gameId)" />
            <div v-else class="login-notice">
              <span class="ln-icon">✉</span>
              <p>Чтобы оставить рецензию, <RouterLink to="/login" class="notice-link">войди в клан</RouterLink></p>
            </div>
            <div v-if="loadingReviews" class="reviews-loading">Собираем свитки рецензий…</div>
            <ReviewList v-else :reviews="reviews" />
          </section>
        </div>

        <!-- RIGHT SIDEBAR -->
        <aside class="sidebar-col">
          <!-- Details card -->
          <div class="content-card reveal">
            <h3 class="sidebar-title"><span class="sec-rune">⚑</span> Знаки</h3>
            <ul class="details-list">
              <li v-if="game.platform"><span class="dl-key">Платформа</span><strong class="dl-val">{{ game.platform }}</strong></li>
              <li v-if="game.genre"><span class="dl-key">Школа боя</span><strong class="dl-val">{{ game.genre }}</strong></li>
              <li v-if="game.release_year"><span class="dl-key">Год похода</span><strong class="dl-val">{{ game.release_year }}</strong></li>
              <li v-if="game.average_review_rating"><span class="dl-key">Слава</span><strong class="dl-val rating-val">★ {{ Number(game.average_review_rating).toFixed(1) }}</strong></li>
            </ul>
          </div>

          <!-- System Requirements card -->
          <div v-if="hasSystemRequirements" class="content-card reveal">
            <h3 class="sidebar-title"><span class="sec-rune">⚙</span> Системные требования</h3>
            <ul class="details-list">
              <li v-if="game.os_requirements"><span class="dl-key">ОС</span><strong class="dl-val">{{ game.os_requirements }}</strong></li>
              <li v-if="game.processor_requirements"><span class="dl-key">Процессор</span><strong class="dl-val">{{ game.processor_requirements }}</strong></li>
              <li v-if="game.ram_requirements"><span class="dl-key">Память</span><strong class="dl-val">{{ game.ram_requirements }}</strong></li>
              <li v-if="game.graphics_requirements"><span class="dl-key">Видеокарта</span><strong class="dl-val">{{ game.graphics_requirements }}</strong></li>
              <li v-if="game.storage_requirements"><span class="dl-key">Место</span><strong class="dl-val">{{ game.storage_requirements }}</strong></li>
            </ul>
          </div>
        </aside>
      </div>

      <!-- MODS SECTION -->
      <div v-show="activeTab === 'mods'" class="mods-section">
        <section class="content-card reveal">
          <h2 class="sec-title"><span class="sec-rune">⚙</span> Моды для {{ game.title }}</h2>

          <div v-if="loadingMods" class="mods-loading">
            <div class="loading-spinner"></div>
            <p>Ищем в кузнице модмейкеров…</p>
          </div>

          <div v-else-if="mods.length === 0" class="mods-empty">
            <div class="empty-icon">⚒</div>
            <h3>Пока без модификаций</h3>
            <p>Для этой игры ещё не добавлены моды. Следи за обновлениями оружейной.</p>
          </div>

          <div v-else class="mods-list">
            <div
              v-for="mod in mods"
              :key="mod.id"
              class="mod-card"
              :class="{ featured: mod.is_featured }"
            >
              <span v-if="mod.is_featured" class="mod-featured-ribbon">⭐ Избранный</span>

              <div class="mod-header">
                <h3 class="mod-title">{{ mod.title }}</h3>
                <div class="mod-badges">
                  <span v-if="mod.version" class="mod-badge version-badge">v{{ mod.version }}</span>
                </div>
              </div>

              <p v-if="mod.description" class="mod-description">{{ mod.description }}</p>

              <div class="mod-meta">
                <span v-if="mod.author" class="meta-item">
                  <span class="meta-icon">⚑</span> {{ mod.author }}
                </span>
                <span v-if="mod.source_site" class="meta-item">
                  <span class="meta-icon">◈</span> {{ mod.source_site }}
                </span>
                <span v-if="mod.download_count" class="meta-item">
                  <span class="meta-icon">↓</span> {{ formatNumber(mod.download_count) }}
                </span>
                <span v-if="mod.popularity_score" class="meta-item popularity">
                  <span class="meta-icon">★</span> {{ Number(mod.popularity_score).toFixed(1) }}
                </span>
              </div>

              <a
                v-if="mod.external_url"
                :href="mod.external_url"
                target="_blank"
                rel="noopener"
                class="mod-download-btn"
              >
                <span class="btn-icon">⇗</span>
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
          <h2 class="sec-title-lg">
            <span class="sec-rune">◈</span>
            Похожие трофеи
          </h2>
          <RouterLink to="/catalog" class="see-all-link">
            <span>Вся оружейная</span>
            <span class="sal-arrow">→</span>
          </RouterLink>
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
              <span v-if="sg.discount_percent" class="sim-badge">−{{ sg.discount_percent }}%</span>
            </div>
            <div class="sim-info">
              <div class="sim-title">{{ sg.title }}</div>
              <div class="sim-genre">{{ sg.genre }}</div>
              <div class="sim-bottom">
                <span class="sim-price">{{ Number(sg.price).toFixed(0) }}₽</span>
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
/* ============================================================
   ASHENFORGE · GamePage
   ============================================================ */

.reveal { opacity: 0; transform: translateY(32px); transition: opacity 0.6s var(--ease-smoke), transform 0.6s var(--ease-smoke); }
.reveal.is-visible { opacity: 1; transform: none; }

.gp-root { position: relative; color: var(--text-bone); min-height: 100vh; }

/* ==========================================================
   PROGRESS BAR
   ========================================================== */
.progress-bar {
  position: fixed;
  top: 0; left: 0;
  height: 2px;
  z-index: 1000;
  background: var(--grad-ember-hot);
  box-shadow: 0 0 12px rgba(255, 122, 43, 0.7);
  transition: width 0.1s linear;
}

/* ==========================================================
   HERO BACKDROP · размытый cover как фон
   ========================================================== */
.hero-backdrop {
  position: fixed;
  inset: 0;
  z-index: -2;
  background-size: cover;
  background-position: center;
  filter: blur(48px) brightness(0.25) saturate(1.3);
  opacity: 0;
  transform: scale(1.08);
  transition: opacity 1s var(--ease-smoke), transform 1s var(--ease-smoke);
}
.gp-root.loaded .hero-backdrop { opacity: 1; transform: scale(1.04); }

.hero-backdrop-overlay {
  position: fixed;
  inset: 0;
  z-index: -1;
  background:
    radial-gradient(ellipse 80% 50% at 50% 0%, rgba(138, 31, 24, 0.25) 0%, transparent 60%),
    linear-gradient(180deg, rgba(8, 6, 10, 0.4) 0%, rgba(18, 16, 13, 0.75) 60%, var(--ash-obsidian) 100%);
  pointer-events: none;
}

/* ==========================================================
   STATUS
   ========================================================== */
.status-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  gap: 20px;
  text-align: center;
  padding: 40px;
  position: relative;
  z-index: 1;
}
.status-icon { font-size: 3rem; color: var(--ember-flame); }
.error-box p {
  color: var(--text-parchment);
  font-family: var(--font-body);
  font-style: italic;
  font-size: 1.1rem;
}
.back-btn {
  font-family: var(--font-display);
  font-weight: var(--fw-semibold);
  letter-spacing: var(--ls-wide);
  color: var(--ember-gold);
  text-decoration: none;
  transition: color var(--dur-fast);
}
.back-btn:hover { color: var(--ember-spark); }

.loading-spinner {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  border: 3px solid var(--iron-dark);
  border-top-color: var(--ember-flame);
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ==========================================================
   INNER
   ========================================================== */
.gp-inner {
  max-width: var(--content-max);
  margin: 0 auto;
  padding: 32px var(--sp-6) var(--sp-20);
  position: relative;
  z-index: 1;
}

/* ==========================================================
   BUY BLOCK
   ========================================================== */
.buy-block {
  position: relative;
  display: grid;
  grid-template-columns: minmax(220px, 300px) 1fr;
  gap: var(--sp-10);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  clip-path: var(--clip-forged-md);
  padding: 32px;
  margin-bottom: var(--sp-10);
  align-items: start;
  box-shadow:
    inset 0 0 0 1px var(--iron-mid),
    inset 0 0 0 3px var(--iron-void),
    var(--shadow-lift),
    var(--inset-forge);
}

/* Rivets */
.rivet {
  position: absolute;
  width: 10px; height: 10px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%, var(--brass) 0%, var(--bronze) 45%, var(--iron-void) 100%);
  box-shadow:
    inset -1px -1px 2px rgba(0, 0, 0, 0.7),
    inset 1px 1px 1px rgba(255, 201, 121, 0.35),
    0 0 6px rgba(199, 154, 94, 0.5);
  pointer-events: none;
  z-index: 5;
}
.rivet-tl { top: 14px; left: 14px; }
.rivet-tr { top: 14px; right: 14px; }
.rivet-bl { bottom: 14px; left: 14px; }
.rivet-br { bottom: 14px; right: 14px; }

/* ==========================================================
   COVER
   ========================================================== */
.cover-wrap {
  position: relative;
  width: 100%;
  aspect-ratio: 3 / 4;
  max-height: 420px;
  overflow: hidden;
  background: var(--ash-void);
  box-shadow:
    inset 0 0 0 1px var(--iron-mid),
    inset 0 0 0 3px var(--iron-void),
    0 16px 40px rgba(0, 0, 0, 0.65);
  flex-shrink: 0;
  clip-path: var(--clip-forged-sm);
}
.cover-blur-bg {
  position: absolute;
  inset: -10px;
  z-index: 0;
  background-image: var(--thumb);
  background-size: cover;
  background-position: center;
  filter: blur(18px) brightness(0.4) saturate(1.2);
  transform: scale(1.08);
}
.cover-img {
  position: relative;
  z-index: 1;
  width: 100%;
  height: 100%;
  object-fit: contain;
  object-position: center;
  display: block;
}
.cover-glow {
  position: absolute;
  inset: -12px;
  z-index: 0;
  filter: blur(26px);
  background: radial-gradient(circle, rgba(226, 67, 16, 0.35), transparent 70%);
  pointer-events: none;
}

/* ==========================================================
   BUY INFO
   ========================================================== */
.buy-info { display: flex; flex-direction: column; gap: 18px; }

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 10px;
  font-family: var(--font-display);
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: var(--ls-wider);
  color: var(--text-ash);
}
.breadcrumb a { color: var(--brass); text-decoration: none; transition: color var(--dur-fast); }
.breadcrumb a:hover { color: var(--ember-spark); }
.bc-sep { color: var(--ember-heart); font-size: 0.7rem; }
.bc-genre { color: var(--text-parchment); }

.game-title {
  font-family: var(--font-display);
  font-size: clamp(1.8rem, 3.4vw, 2.8rem);
  font-weight: var(--fw-black);
  color: var(--text-bright);
  margin: 0;
  line-height: 1.15;
  letter-spacing: var(--ls-tight);
  text-shadow:
    0 2px 0 rgba(0, 0, 0, 0.7),
    0 4px 12px rgba(0, 0, 0, 0.6),
    0 0 40px rgba(226, 67, 16, 0.15);
}

/* Meta pills */
.meta-pills { display: flex; flex-wrap: wrap; gap: 8px; }
.meta-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: linear-gradient(180deg, var(--ash-ironrust) 0%, var(--ash-stone) 100%);
  border: 1px solid var(--iron-mid);
  color: var(--text-bone);
  padding: 6px 14px;
  border-radius: var(--r-xs);
  font-family: var(--font-ui);
  font-size: 0.82rem;
  font-weight: var(--fw-medium);
  box-shadow: var(--inset-iron-top);
}
.mp-icon { color: var(--brass); font-size: 0.9rem; }

/* Price */
.price-row {
  display: flex;
  align-items: baseline;
  gap: 14px;
  padding: 12px 0;
}
.disc-badge {
  background: linear-gradient(135deg, var(--ember-gold) 0%, var(--warn-ember) 100%);
  color: var(--ember-abyss);
  padding: 6px 14px;
  border-radius: var(--r-xs);
  font-family: var(--font-display);
  font-weight: var(--fw-black);
  font-size: 0.95rem;
  letter-spacing: var(--ls-wide);
  text-shadow: 0 1px 1px rgba(255, 255, 255, 0.2);
  box-shadow:
    var(--inset-iron-top),
    inset 0 -2px 3px rgba(0, 0, 0, 0.2);
}
.old-price {
  font-family: var(--font-body);
  color: var(--text-smoke);
  font-size: 1.15rem;
  text-decoration: line-through;
  text-decoration-color: var(--ember-deep);
  text-decoration-thickness: 2px;
}
.cur-price {
  display: inline-flex;
  align-items: baseline;
  gap: 2px;
  font-family: var(--font-display);
  font-weight: var(--fw-black);
  color: var(--ember-gold);
  text-shadow: 0 0 20px rgba(255, 201, 121, 0.4);
}
.cur-val { font-size: 2.6rem; letter-spacing: var(--ls-tight); line-height: 1; }
.cur-unit { font-size: 1.4rem; color: var(--brass); margin-left: 2px; }

/* Actions */
.action-row {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}
.cart-btn {
  flex: 1;
  min-width: 180px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 14px 26px;
  border: 1px solid var(--ember-heart);
  background: var(--grad-ember);
  color: var(--text-bright);
  font-family: var(--font-display);
  font-size: 1rem;
  font-weight: var(--fw-bold);
  text-transform: uppercase;
  letter-spacing: var(--ls-wide);
  cursor: pointer;
  border-radius: var(--r-xs);
  transition: all var(--dur-med) var(--ease-smoke);
  position: relative;
  overflow: hidden;
  box-shadow:
    var(--inset-iron-top),
    inset 0 -2px 4px rgba(0, 0, 0, 0.35),
    var(--glow-ember-soft);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.7);
}
.cart-btn::before {
  content: '';
  position: absolute;
  top: 0; left: -120%;
  width: 50%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 201, 121, 0.4), transparent);
  transform: skewX(-20deg);
  transition: left 0.7s var(--ease-smoke);
}
.cart-btn:hover:not(:disabled) {
  filter: brightness(1.15);
  box-shadow: var(--inset-iron-top), inset 0 -2px 4px rgba(0, 0, 0, 0.35), var(--glow-ember-strong);
  transform: translateY(-2px);
}
.cart-btn:hover:not(:disabled)::before { left: 120%; }
.cart-btn:active:not(:disabled) { animation: forgeClang var(--dur-med) var(--ease-forge); }
.cart-btn:disabled {
  background: linear-gradient(180deg, var(--iron-dark) 0%, var(--iron-void) 100%);
  border-color: var(--iron-mid);
  color: var(--text-smoke);
  cursor: not-allowed;
  box-shadow: inset 0 -2px 3px rgba(0, 0, 0, 0.35);
  text-shadow: none;
}
.cart-btn.in-cart {
  background: linear-gradient(180deg, var(--orc-green) 0%, var(--orc-moss) 100%);
  border-color: var(--orc-emerald);
  cursor: default;
}
.cart-btn .btn-icon { font-size: 1.1rem; }

.sg-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 14px 20px;
  border-radius: var(--r-xs);
  border: 1px solid var(--iron-mid);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  color: var(--text-parchment);
  font-family: var(--font-display);
  font-size: 0.85rem;
  font-weight: var(--fw-semibold);
  text-transform: uppercase;
  letter-spacing: var(--ls-wide);
  text-decoration: none;
  white-space: nowrap;
  transition: all var(--dur-fast) var(--ease-smoke);
  box-shadow: var(--inset-iron-top);
}
.sg-btn:hover {
  color: var(--text-bright);
  border-color: var(--iron-warm);
  background: linear-gradient(180deg, var(--ash-ironrust) 0%, var(--ash-stone) 100%);
  box-shadow: var(--glow-ember-soft), var(--inset-iron-top);
}
.sg-arrow { color: var(--brass); }

/* Delivery note */
.delivery-note {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border-left: 2px solid var(--ember-deep);
  background: rgba(194, 40, 26, 0.06);
  font-family: var(--font-body);
  font-style: italic;
  font-size: 0.92rem;
  color: var(--text-parchment);
}
.dn-icon {
  color: var(--ember-glow);
  font-size: 1.1rem;
  filter: drop-shadow(0 0 4px rgba(255, 122, 43, 0.6));
}

/* ==========================================================
   GAME DESCRIPTION
   ========================================================== */
.game-description {
  margin-top: var(--sp-5);
  padding-top: var(--sp-2);
}
.desc-divider {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: var(--sp-5);
  height: 12px;
}
.desc-divider > span:first-child,
.desc-divider > span:last-child {
  flex: 1;
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--iron-mid) 50%, transparent);
}
.desc-divider-spike {
  width: 0; height: 0;
  border-left: 5px solid transparent;
  border-right: 5px solid transparent;
  border-top: 8px solid var(--ember-deep);
  filter: drop-shadow(0 0 4px rgba(194, 40, 26, 0.5));
  flex-shrink: 0;
}

.desc-title {
  font-family: var(--font-display);
  font-size: 1.15rem;
  font-weight: var(--fw-bold);
  color: var(--text-bright);
  margin: 0 0 var(--sp-3);
  display: flex;
  align-items: center;
  gap: 10px;
  letter-spacing: var(--ls-wide);
  text-transform: uppercase;
}
.sec-rune {
  color: var(--ember-glow);
  font-size: 1rem;
  filter: drop-shadow(0 0 4px rgba(255, 122, 43, 0.5));
}
.desc-body {
  font-family: var(--font-body);
  font-size: 0.98rem;
  line-height: 1.75;
  color: var(--text-parchment);
}
.desc-body :deep(p) { margin: 0 0 1em; }
.desc-body :deep(p:last-child) { margin: 0; }
.desc-body :deep(h3) {
  font-family: var(--font-display);
  color: var(--text-bright);
  margin: 1.4em 0 0.6em;
  font-size: 1.05rem;
  font-weight: var(--fw-semibold);
  letter-spacing: var(--ls-wide);
}
.desc-body :deep(ul) { margin: 0.6em 0; padding-left: 1.5em; }
.desc-body :deep(li) { margin: 0.4em 0; }
.desc-body :deep(strong) { color: var(--text-bright); }
.desc-body :deep(em) { color: var(--brass); font-style: italic; }

/* ==========================================================
   TABS
   ========================================================== */
.tabs-container {
  display: flex;
  gap: 4px;
  margin-bottom: var(--sp-6);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  padding: 6px;
  border: 1px solid var(--iron-mid);
  box-shadow:
    var(--inset-iron-top),
    inset 0 -1px 0 var(--iron-void);
  clip-path: var(--clip-forged-sm);
}
.tab-btn {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 14px 20px;
  border: none;
  border-radius: var(--r-xs);
  background: transparent;
  color: var(--text-ash);
  font-family: var(--font-display);
  font-size: 0.92rem;
  font-weight: var(--fw-semibold);
  text-transform: uppercase;
  letter-spacing: var(--ls-wide);
  cursor: pointer;
  transition: all var(--dur-fast) var(--ease-smoke);
}
.tab-icon { color: var(--brass); font-size: 1rem; transition: color var(--dur-fast); }
.tab-count {
  font-family: var(--font-ui);
  font-weight: var(--fw-regular);
  color: var(--text-smoke);
  font-size: 0.88rem;
  margin-left: 2px;
}
.tab-btn:hover {
  color: var(--text-bright);
  background: rgba(255, 122, 43, 0.05);
}
.tab-btn:hover .tab-icon { color: var(--ember-spark); }
.tab-btn.active {
  color: var(--text-bright);
  background: var(--grad-ember);
  border: 1px solid var(--ember-heart);
  box-shadow:
    var(--inset-iron-top),
    inset 0 -2px 3px rgba(0, 0, 0, 0.3),
    var(--glow-ember-soft);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
}
.tab-btn.active .tab-icon { color: var(--ember-gold); filter: drop-shadow(0 0 6px rgba(255, 201, 121, 0.7)); }
.tab-btn.active .tab-count { color: rgba(255, 255, 255, 0.8); }

/* ==========================================================
   CONTENT GRID
   ========================================================== */
.content-grid {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 24px;
  align-items: start;
}
.main-col { display: flex; flex-direction: column; gap: 24px; min-width: 0; }
.sidebar-col { display: flex; flex-direction: column; gap: 24px; position: sticky; top: 88px; }

/* Content card */
.content-card {
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  clip-path: var(--clip-forged-sm);
  padding: 26px;
  box-shadow:
    inset 0 0 0 1px var(--iron-mid),
    inset 0 0 0 3px var(--iron-void),
    var(--shadow-cast);
}

.sec-title {
  font-family: var(--font-display);
  font-size: 1.2rem;
  font-weight: var(--fw-bold);
  color: var(--text-bright);
  margin: 0 0 var(--sp-5);
  display: flex;
  align-items: center;
  gap: 10px;
  letter-spacing: var(--ls-wide);
  text-transform: uppercase;
}
.review-count {
  font-family: var(--font-body);
  font-style: italic;
  font-size: 0.95rem;
  color: var(--text-smoke);
  font-weight: var(--fw-regular);
  text-transform: none;
  letter-spacing: normal;
}

.sidebar-title {
  font-family: var(--font-display);
  font-size: 1rem;
  font-weight: var(--fw-bold);
  color: var(--text-bright);
  margin: 0 0 var(--sp-4);
  display: flex;
  align-items: center;
  gap: 10px;
  letter-spacing: var(--ls-wide);
  text-transform: uppercase;
}

/* Video */
.video-wrap {
  position: relative;
  padding-bottom: 56.25%;
  height: 0;
  overflow: hidden;
  border: 1px solid var(--iron-mid);
  box-shadow: var(--shadow-cast);
}
.video-wrap iframe {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}

/* Screenshots */
.screenshots-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
}
.ss-link {
  position: relative;
  display: block;
  overflow: hidden;
  clip-path: var(--clip-forged-sm);
  border: 1px solid var(--iron-mid);
  box-shadow: var(--shadow-subtle);
}
.ss-img {
  width: 100%;
  height: auto;
  display: block;
  transition: transform var(--dur-slow) var(--ease-smoke);
}
.ss-link:hover .ss-img { transform: scale(1.06); }
.ss-overlay {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle, rgba(226, 67, 16, 0.55) 0%, rgba(8, 6, 10, 0.75) 70%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-size: 2rem;
  color: var(--text-bright);
  opacity: 0;
  transition: opacity var(--dur-med) var(--ease-smoke);
  text-shadow: 0 0 12px rgba(255, 201, 121, 0.8);
}
.ss-link:hover .ss-overlay { opacity: 1; }

/* Login notice */
.login-notice {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 18px;
  background: linear-gradient(180deg, rgba(90, 20, 18, 0.2) 0%, rgba(42, 10, 8, 0.15) 100%);
  border: 1px solid var(--ember-deep);
  border-left: 3px solid var(--ember-heart);
  margin-bottom: var(--sp-5);
  color: var(--text-parchment);
  font-family: var(--font-body);
  font-style: italic;
  font-size: 0.95rem;
}
.login-notice p { margin: 0; }
.ln-icon { color: var(--ember-glow); font-size: 1.1rem; }
.notice-link {
  color: var(--ember-gold);
  font-style: normal;
  font-family: var(--font-display);
  font-weight: var(--fw-semibold);
  text-decoration: none;
  transition: color var(--dur-fast);
}
.notice-link:hover { color: var(--ember-spark); }
.reviews-loading {
  color: var(--text-smoke);
  text-align: center;
  padding: var(--sp-5);
  font-family: var(--font-body);
  font-style: italic;
}

/* Details list */
.details-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0;
}
.details-list li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px dashed var(--iron-dark);
  font-family: var(--font-ui);
  font-size: 0.9rem;
}
.details-list li:last-child { border: none; }
.dl-key {
  color: var(--text-ash);
  font-family: var(--font-tribal);
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: var(--ls-wide);
}
.dl-val {
  color: var(--text-bone);
  font-weight: var(--fw-semibold);
  text-align: right;
  max-width: 60%;
}
.rating-val {
  color: var(--ember-gold);
  text-shadow: 0 0 6px rgba(255, 201, 121, 0.4);
}

/* ==========================================================
   MODS
   ========================================================== */
.mods-section { margin-bottom: var(--sp-10); }
.mods-loading, .mods-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--sp-16) var(--sp-5);
  text-align: center;
  gap: var(--sp-4);
}
.mods-empty .empty-icon {
  font-size: 4rem;
  color: var(--bronze);
  opacity: 0.6;
}
.mods-empty h3 {
  font-family: var(--font-display);
  color: var(--text-bright);
  font-size: 1.3rem;
  letter-spacing: var(--ls-wide);
  margin: 0;
}
.mods-empty p {
  font-family: var(--font-body);
  font-style: italic;
  color: var(--text-ash);
  margin: 0;
  max-width: 400px;
}
.mods-loading p { font-family: var(--font-body); font-style: italic; color: var(--text-ash); }

.mods-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 16px;
}

.mod-card {
  position: relative;
  background: linear-gradient(180deg, var(--ash-ironrust) 0%, var(--ash-stone) 100%);
  border: 1px solid var(--iron-mid);
  padding: 22px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  transition: all var(--dur-med) var(--ease-smoke);
  box-shadow: var(--inset-iron-top), var(--shadow-subtle);
  clip-path: var(--clip-forged-sm);
}
.mod-card:hover {
  border-color: var(--iron-warm);
  box-shadow:
    var(--inset-iron-top),
    var(--shadow-cast),
    var(--glow-ember-soft);
  transform: translateY(-3px);
}
.mod-card.featured {
  border-color: var(--bronze-dark);
  background: linear-gradient(180deg, rgba(160, 115, 72, 0.15) 0%, var(--ash-stone) 100%);
}
.mod-card.featured:hover { border-color: var(--brass); box-shadow: var(--inset-iron-top), var(--shadow-cast), var(--glow-brass); }

.mod-featured-ribbon {
  position: absolute;
  top: 10px;
  right: -4px;
  background: linear-gradient(135deg, var(--brass) 0%, var(--bronze) 100%);
  color: var(--ember-abyss);
  font-family: var(--font-display);
  font-size: 0.72rem;
  font-weight: var(--fw-black);
  text-transform: uppercase;
  letter-spacing: var(--ls-wide);
  padding: 4px 12px;
  clip-path: polygon(0 0, 100% 0, 100% 100%, 8px 100%, 0 50%);
  box-shadow: var(--inset-iron-top);
}

.mod-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  gap: 12px;
  padding-right: 80px;
}
.mod-title {
  font-family: var(--font-display);
  font-size: 1.08rem;
  font-weight: var(--fw-bold);
  color: var(--text-bright);
  margin: 0;
  line-height: 1.3;
  letter-spacing: var(--ls-wide);
}
.mod-badges { display: flex; gap: 6px; flex-wrap: wrap; }
.mod-badge {
  font-family: var(--font-display);
  font-size: 0.72rem;
  font-weight: var(--fw-semibold);
  letter-spacing: var(--ls-wide);
  padding: 3px 8px;
  border-radius: var(--r-xs);
  white-space: nowrap;
  border: 1px solid;
}
.version-badge {
  background: rgba(74, 115, 149, 0.15);
  color: var(--info-ice);
  border-color: var(--info-frost);
}

.mod-description {
  font-family: var(--font-body);
  font-size: 0.92rem;
  line-height: 1.55;
  color: var(--text-parchment);
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin: 0;
}

.mod-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  font-family: var(--font-ui);
  font-size: 0.82rem;
  color: var(--text-ash);
  padding-top: 6px;
  border-top: 1px dashed var(--iron-dark);
}
.meta-item { display: flex; align-items: center; gap: 5px; }
.meta-icon { color: var(--brass); font-size: 0.9rem; }
.meta-item.popularity { color: var(--ember-gold); }
.meta-item.popularity .meta-icon { color: var(--ember-gold); }

.mod-download-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 11px 16px;
  border-radius: var(--r-xs);
  border: 1px solid var(--ember-heart);
  background: var(--grad-ember);
  color: var(--text-bright);
  font-family: var(--font-display);
  font-size: 0.85rem;
  font-weight: var(--fw-bold);
  text-transform: uppercase;
  letter-spacing: var(--ls-wide);
  text-decoration: none;
  margin-top: auto;
  transition: all var(--dur-fast) var(--ease-smoke);
  box-shadow:
    var(--inset-iron-top),
    inset 0 -2px 3px rgba(0, 0, 0, 0.35),
    var(--glow-ember-soft);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
}
.mod-download-btn:hover {
  filter: brightness(1.15);
  box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.35), var(--glow-ember-strong);
  transform: translateY(-1px);
}
.btn-icon { font-size: 1rem; }
.btn-text { flex: 1; text-align: center; }
.btn-arrow { transition: transform var(--dur-fast); }
.mod-download-btn:hover .btn-arrow { transform: translateX(3px); }

/* ==========================================================
   SIMILAR GAMES
   ========================================================== */
.similar-section { margin-top: var(--sp-10); }
.sec-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--sp-6);
}
.sec-title-lg {
  font-family: var(--font-display);
  font-size: 1.4rem;
  font-weight: var(--fw-bold);
  color: var(--text-bright);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 12px;
  letter-spacing: var(--ls-wide);
  text-transform: uppercase;
}
.see-all-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-family: var(--font-display);
  font-size: 0.85rem;
  font-weight: var(--fw-semibold);
  text-transform: uppercase;
  letter-spacing: var(--ls-wide);
  color: var(--ember-gold);
  text-decoration: none;
  transition: color var(--dur-fast);
}
.see-all-link:hover { color: var(--ember-spark); }
.see-all-link:hover .sal-arrow { transform: translateX(4px); }
.sal-arrow { transition: transform var(--dur-fast); }

.similar-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
  gap: 16px;
}
.sim-card {
  text-decoration: none;
  overflow: hidden;
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  clip-path: var(--clip-forged-sm);
  box-shadow:
    inset 0 0 0 1px var(--iron-mid),
    inset 0 0 0 3px var(--iron-void),
    var(--shadow-subtle);
  transition: all var(--dur-med) var(--ease-smoke);
  display: flex;
  flex-direction: column;
}
.sim-card:hover {
  transform: translateY(-5px);
  box-shadow:
    inset 0 0 0 1px var(--bronze-dark),
    inset 0 0 0 3px var(--iron-void),
    var(--shadow-lift),
    var(--glow-ember-soft);
}
.sim-img-wrap {
  position: relative;
  aspect-ratio: 3 / 4;
  overflow: hidden;
  background: var(--ash-void);
  border-bottom: 1px solid var(--iron-dark);
}
.sim-blur-bg {
  position: absolute;
  inset: -8px;
  z-index: 0;
  background-image: var(--simthumb);
  background-size: cover;
  background-position: center;
  filter: blur(14px) brightness(0.4) saturate(1.1);
  transform: scale(1.06);
}
.sim-img {
  position: relative;
  z-index: 1;
  width: 100%;
  height: 100%;
  object-fit: contain;
  object-position: center;
  transition: transform var(--dur-slow) var(--ease-smoke);
}
.sim-card:hover .sim-img { transform: scale(1.05); }
.sim-gradient {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 50%;
  background: linear-gradient(to top, rgba(8, 6, 10, 0.9), transparent);
  z-index: 2;
}
.sim-badge {
  position: absolute;
  top: 10px;
  left: 10px;
  z-index: 3;
  background: linear-gradient(135deg, var(--ember-gold) 0%, var(--warn-ember) 100%);
  color: var(--ember-abyss);
  font-family: var(--font-display);
  font-weight: var(--fw-black);
  font-size: 0.7rem;
  padding: 3px 8px;
  border-radius: var(--r-xs);
  letter-spacing: var(--ls-wide);
  box-shadow: var(--inset-iron-top), 0 2px 4px rgba(0, 0, 0, 0.4);
}
.sim-info {
  padding: 13px 14px 15px;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.sim-title {
  font-family: var(--font-display);
  font-size: 0.92rem;
  font-weight: var(--fw-semibold);
  color: var(--text-bright);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  letter-spacing: var(--ls-wide);
}
.sim-genre {
  font-family: var(--font-body);
  font-style: italic;
  font-size: 0.76rem;
  color: var(--text-ash);
}
.sim-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 6px;
}
.sim-price {
  font-family: var(--font-display);
  color: var(--ember-gold);
  font-weight: var(--fw-bold);
  font-size: 0.95rem;
  text-shadow: 0 0 6px rgba(255, 201, 121, 0.3);
}
.sim-rating {
  font-family: var(--font-display);
  font-size: 0.78rem;
  color: var(--ember-gold);
  font-weight: var(--fw-semibold);
}

/* ==========================================================
   RESPONSIVE
   ========================================================== */
@media (max-width: 1024px) {
  .content-grid { grid-template-columns: 1fr; }
  .sidebar-col { position: static; }
}
@media (max-width: 768px) {
  .buy-block { grid-template-columns: 1fr; gap: 24px; padding: 24px; }
  .cover-wrap { max-width: 260px; margin: 0 auto; }
  .game-title { font-size: 1.8rem; text-align: center; }
  .meta-pills { justify-content: center; }
  .price-row { justify-content: center; }
  .action-row { flex-direction: column; }
  .mod-header { padding-right: 0; }
  .mod-featured-ribbon { position: static; margin-bottom: 8px; clip-path: none; align-self: flex-start; border-radius: var(--r-xs); }
}
@media (max-width: 480px) {
  .screenshots-grid { grid-template-columns: 1fr; }
  .mod-meta { gap: 10px; }
  .tab-btn { padding: 12px 14px; font-size: 0.82rem; }
}
</style>
