
<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useHead } from '@vueuse/head';
import api from '../api/axios';
import { useCartStore } from '../stores/cart';

const route = useRoute();
const gameId = computed(() => route.params.id);
const game = ref(null);
const similarGames = ref([]);
const loading = ref(true);
const error = ref('');

const cartStore = useCartStore();

const getGameDataForCart = (gameData) => ({
    id: gameData.id,
    title: gameData.title,
    price: gameData.price,
    image: gameData.image,
    platform: gameData.platform,
});

const isInCart = computed(() => game.value && cartStore.getItemById(game.value.id));

const addToCart = () => {
  if (game.value) {
    cartStore.addItem(getGameDataForCart(game.value));
  }
};

const resolveImageUrl = (imagePath, absolute = false) => {
    if (!imagePath) {
        const placeholder = '/img/noimage.png';
        return absolute ? `${window.location.origin}${placeholder}` : placeholder;
    }
    if (imagePath.includes('/')) {
        return `http://localhost:8000${imagePath}`;
    }
    const localPath = `/img/${imagePath}`;
    return absolute ? `${window.location.origin}${localPath}` : localPath;
};

const loadGame = async (id) => {
  loading.value = true;
  error.value = '';
  game.value = null;
  try {
    const { data } = await api.get(`/games/${id}`);
    game.value = data;
    loadSimilarGames(data.genre, data.id);
  } catch (e) {
    error.value = 'Игра не найдена или произошла ошибка.';
    console.error('Ошибка загрузки игры:', e);
  } finally {
    loading.value = false;
  }
};

const loadSimilarGames = async (genre, currentGameId) => {
  if (!genre) return;
  try {
    const { data } = await api.get(`/games?genre=${genre}&limit=4`);
    similarGames.value = data.filter(g => g.id !== currentGameId).slice(0, 3);
  } catch (e) {
    console.error('Ошибка загрузки похожих игр:', e);
  }
};

const stopGameUrl = computed(() => {
    // 1. Предпочитаем прямую ссылку из данных
    if (game.value?.stopgame_url_code) {
        return game.value.stopgame_url_code;
    }
    // 2. В качестве запасного варианта - поиск по названию
    if (game.value?.title) {
        const query = encodeURIComponent(game.value.title);
        return `https://stopgame.ru/search?q=${query}`;
    }
    // 3. Если нет данных, возвращаем безопасное значение
    return '#';
});

const coverImageSrc = computed(() => resolveImageUrl(game.value?.image));

const youtubeEmbedUrl = computed(() => {
    if (!game.value?.trailer_url) return null;
    try {
        const url = new URL(game.value.trailer_url);
        let videoId;
        if (url.hostname === 'youtu.be') {
            videoId = url.pathname.slice(1);
        } else {
            videoId = url.searchParams.get('v');
        }
        return videoId ? `https://www.youtube.com/embed/${videoId}` : null;
    } catch (e) {
        console.error('Invalid trailer URL', e);
        return null;
    }
});

useHead(computed(() => {
  if (!game.value) return { title: 'Загрузка...' };

  const title = `Купить ${game.value.title} PC ключ - цена в GameStore`;
  const description = `✓ Покупайте ${game.value.title} для ${game.value.platform} в нашем магазине. ⭐ Моментальная доставка ключа, низкие цены и отзывы покупателей. Узнайте системные требования и смотрите трейлер.`;

  const structuredData = {
    '@context': 'https://schema.org',
    '@type': 'Product',
    name: game.value.title,
    description: game.value.description,
    image: resolveImageUrl(game.value.image, true), 
    sku: game.value.id,
    offers: {
      '@type': 'Offer',
      url: window.location.href,
      priceCurrency: 'RUB',
      price: game.value.price,
      availability: 'https://schema.org/InStock',
      seller: { '@type': 'Organization', name: 'GameStore' }
    },
    gamePlatform: game.value.platform,
  };

  return {
    title: title,
    meta: [{ name: 'description', content: description }],
    script: [{ type: 'application/ld+json', children: JSON.stringify(structuredData) }]
  };
}));

onMounted(() => loadGame(gameId.value));
watch(gameId, (newId) => { if (newId) loadGame(newId); });

</script>

<template>
  <main class="page-wrapper">
    <div v-if="loading" class="status-message">Загрузка...</div>
    <div v-else-if="error" class="status-message error">{{ error }}</div>

    <div v-else-if="game" class="game-layout-grid">
      <!-- ******** HEADER (BUY BLOCK) ******** -->
      <header class="game-header">
        <div class="header-cover-container">
          <img :src="coverImageSrc" :alt="`Обложка ${game.title}`" class="header-cover-image">
        </div>
        <div class="header-info-container">
          <h1 class="game-title">{{ game.title }}</h1>
          <div class="price-block">
              <span v-if="game.discount_percent" class="discount-badge">-{{ game.discount_percent }}%</span>
              <span v-if="game.old_price" class="old-price">{{ Number(game.old_price).toFixed(0) }} ₽</span>
              <span class="current-price">{{ Number(game.price).toFixed(0) }} ₽</span>
          </div>
          <div class="action-buttons">
            <button 
              @click="addToCart" 
              class="add-to-cart-btn">
              Добавить в корзину
            </button>
            <a :href="stopGameUrl" target="_blank" rel="noopener noreferrer" class="external-link-btn">
              Обзоры на StopGame
            </a>
          </div>
          <p class="delivery-info">Мгновенная доставка ключа на e-mail</p>
        </div>
      </header>

      <!-- ******** MAIN CONTENT GRID ******** -->
      <div class="content-grid">
        <!-- Left Column -->
        <div class="main-content-col">
          <section v-if="youtubeEmbedUrl" class="content-section">
            <h2 class="section-title">Трейлер</h2>
            <div class="video-container">
              <iframe :src="youtubeEmbedUrl" :title="`Официальный трейлер ${game.title}`" frameborder="0" allowfullscreen></iframe>
            </div>
          </section>

          <section v-if="game.images && game.images.length" class="content-section">
            <h2 class="section-title">Скриншоты</h2>
            <div class="screenshots-grid">
              <a v-for="image in game.images" :key="image.id" :href="`http://localhost:8000${image.path}`" target="_blank">
                <img :src="`http://localhost:8000${image.path}`" :alt="`Скриншот ${game.title}`" class="screenshot-img" />
              </a>
            </div>
          </section>
        </div>

        <!-- Right Sidebar -->
        <aside class="sidebar-col">
          <div class="details-card">
            <h3 class="details-card-title">Детали игры</h3>
            <ul class="details-list">
              <li><span>Платформа:</span> <strong>{{ game.platform }}</strong></li>
              <li><span>Жанр:</span> <strong>{{ game.genre }}</strong></li>
              <li><span>Дата выхода:</span> <strong>{{ game.release_year }}</strong></li>
            </ul>
          </div>

          <section class="content-section">
            <h2 class="section-title">Об игре {{ game.title }}</h2>
            <div v-if="game.description" v-html="game.description" class="description-content"></div>
            <p v-else>В онлайн-магазине <strong>GameStore</strong> вы можете <strong>купить ключ {{ game.title }}</strong> для платформы {{ game.platform }} по самой выгодной цене. Это знаменитая игра в жанре <em>{{ game.genre }}</em>, выпущенная в {{ game.release_year }} году, которая уже успела завоевать сердца тысяч геймеров.</p>
          </section>
        </aside>
      </div>

       <!-- ******** SIMILAR GAMES ******** -->
      <section v-if="similarGames.length" class="similar-games-section">
        <h2 class="section-title">Похожие игры</h2>
        <div class="similar-games-grid">
           <router-link v-for="simGame in similarGames" :key="simGame.id" :to="`/games/${simGame.id}`" class="similar-game-card">
                <img :src="resolveImageUrl(simGame.image)" :alt="simGame.title" class="similar-game-img" />
                <div class="similar-game-info">
                  <div class="similar-game-title">{{ simGame.title }}</div>
                  <div class="similar-game-price">{{ Number(simGame.price).toFixed(0) }} ₽</div>
                </div>
            </router-link>
        </div>
      </section>

    </div>
  </main>
</template>

<style scoped>
.page-wrapper { max-width: 1200px; margin: 0 auto; padding: 24px; color: #e5e7eb; }
.status-message { text-align: center; padding: 60px; font-size: 1.2rem; }
.status-message.error { color: #fca5a5; }
.section-title { font-size: 1.8rem; font-weight: 700; color: #fff; margin-top: 0; margin-bottom: 20px; border-bottom: 2px solid #3b82f6; padding-bottom: 10px; }
.game-header { display: grid; grid-template-columns: 300px 1fr; gap: 32px; background: #111827; padding: 24px; border-radius: 12px; margin-bottom: 32px; align-items: center; }
.header-cover-image { width: 100%; border-radius: 8px; box-shadow: 0 10px 20px rgba(0,0,0,0.3); }
.header-info-container { display: flex; flex-direction: column; }
.game-title { font-size: 3rem; font-weight: 800; line-height: 1.1; margin: 0 0 16px; }
.price-block { display: flex; align-items: baseline; gap: 12px; margin-bottom: 20px; }
.current-price { font-size: 2.5rem; font-weight: 800; color: #4ade80; }
.old-price { font-size: 1.25rem; color: #6b7280; text-decoration: line-through; }
.discount-badge { background-color: #ef4444; color: white; padding: 5px 10px; border-radius: 6px; font-weight: 700; }

.action-buttons {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 12px;
}

.add-to-cart-btn, .external-link-btn {
  width: 100%;
  border: none;
  font-size: 1.1rem;
  font-weight: 700;
  padding: 16px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: center;
  text-decoration: none;
  display: flex;
  align-items: center;
  justify-content: center;
}

.add-to-cart-btn {
  background-color: #3b82f6;
  color: white;
}
.add-to-cart-btn:hover:not(:disabled) { background-color: #2563eb; transform: translateY(-2px); }
.add-to-cart-btn:disabled,
.add-to-cart-btn.in-cart {
  background-color: #22c55e;
  cursor: not-allowed;
  transform: none;
}
.add-to-cart-btn:disabled:hover { filter: brightness(1.0); }

.external-link-btn {
  background-color: #4b5563;
  color: white;
}
.external-link-btn:hover {
  background-color: #6b7280;
  transform: translateY(-2px);
}

.delivery-info { color: #9ca3af; margin-top: 0; font-size: 0.9rem; text-align: center; }
.content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 32px; }
.main-content-col { display: flex; flex-direction: column; gap: 32px; min-width: 0; }
.content-section { background: #111827; border: 1px solid #1f2937; padding: 24px; border-radius: 12px; }
.sidebar-col { position: sticky; top: 80px; align-self: start; display: flex; flex-direction: column; gap: 32px; }
.details-card { background: #111827; border: 1px solid #1f2937; padding: 24px; border-radius: 12px; }
.details-card-title { font-size: 1.3rem; margin: 0 0 16px; color: #fff; }
.details-list { list-style: none; padding: 0; margin: 0; }
.details-list li { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #1f2937; font-size: 0.95rem; }
.details-list li:last-child { border: none; }
.details-list li span { color: #9ca3af; }
.details-list li strong { color: #e5e7eb; }
.video-container { position: relative; padding-bottom: 56.25%; height: 0; border-radius: 8px; overflow: hidden; }
.video-container iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
.screenshots-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
.screenshot-img { width: 100%; border-radius: 8px; transition: transform 0.2s ease; }
.screenshot-img:hover { transform: scale(1.05); }

.description-content, .content-section > p {
  font-size: 1rem;
  line-height: 1.8;
  color: #d1d5db;
}
.description-content :first-child, .content-section > p:first-child { margin-top: 0; }
.description-content :last-child, .content-section > p:last-child { margin-bottom: 0; }

.similar-games-section { margin-top: 32px; }
.similar-games-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
.similar-game-card { text-decoration: none; background: #1f2937; border-radius: 8px; overflow: hidden; transition: transform 0.2s ease, box-shadow 0.2s ease; }
.similar-game-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.25); }
.similar-game-img { width: 100%; aspect-ratio: 4/5; object-fit: cover; }
.similar-game-info { padding: 12px; }
.similar-game-title { font-weight: 600; color: #fff; margin-bottom: 4px; white-space: nowrap; text-overflow: ellipsis; overflow: hidden; }
.similar-game-price { color: #4ade80; font-weight: 700; }

@media (max-width: 992px) {
  .content-grid { grid-template-columns: 1fr; }
  .sidebar-col { position: static; top: auto; }
}
@media (max-width: 768px) {
  .game-header { grid-template-columns: 1fr; text-align: center; }
  .header-cover-container { max-width: 300px; margin: 0 auto; }
  .header-info-container { align-items: center; }
  .price-block { justify-content: center; }
  .game-title { font-size: 2.5rem; }
  .action-buttons { grid-template-columns: 1fr; }
}
</style>
