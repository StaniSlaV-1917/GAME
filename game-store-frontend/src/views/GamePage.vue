
<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
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
const isInCart = computed(() => game.value && cartStore.getItemById(game.value.id));
const addToCart = () => {
  if (game.value) {
    cartStore.addItem(game.value);
  }
};


const loadGame = async (id) => {
  loading.value = true;
  error.value = '';
  game.value = null;
  try {
    const { data } = await api.get(`/games/${id}`);
    // Эмулируем получение доп. данных, пока бэкенд не обновлен
    // В будущем эти поля будут приходить с сервера
    const demoData = {
      '1': { developer: 'FromSoftware', publisher: 'Bandai Namco', trailerUrl: 'https://www.youtube.com/embed/K_03kFqWfqs', screenshots: ['img/er_ss1.jpg', 'img/er_ss2.jpg', 'img/er_ss3.jpg', 'img/er_ss4.jpg'], systemRequirements: null, },
      '2': { developer: 'CD PROJEKT RED', publisher: 'CD PROJEKT RED', trailerUrl: 'https://www.youtube.com/embed/BO8lX3hDU3g', screenshots: ['img/cp2077_ss1.jpg', 'img/cp2077_ss2.jpg', 'img/cp2077_ss3.jpg', 'img/cp2077_ss4.jpg'], systemRequirements: { min: { os: 'Windows 10 (64-bit)', processor: 'Core i7-6700 or Ryzen 5 1600', memory: '12 GB RAM', graphics: 'GTX 1060 6GB or RX 580 8GB', storage: '70 GB SSD', }, rec: { os: 'Windows 10 (64-bit)', processor: 'Core i7-12700 or Ryzen 7 7800X3D', memory: '16 GB RAM', graphics: 'RTX 2060 Super or RX 5700 XT', storage: '70 GB SSD', }, }, },
    };
    game.value = { ...data, ...demoData[data.id] };

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

const coverImageSrc = computed(() => {
  if (!game.value?.image) return '/img/noimage.png';
  return game.value.image.startsWith('img/') ? `/${game.value.image}` : `/img/${game.value.image}`;
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
    image: `${window.location.origin}${coverImageSrc.value}`,
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
    ...(game.value.average_review_rating && {
      aggregateRating: {
        '@type': 'AggregateRating',
        ratingValue: Number(game.value.average_review_rating).toFixed(1),
        reviewCount: game.value.reviews_count || 0
      }
    })
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
              <span v-if="game.discount > 0" class="discount-badge">-{{ game.discount }}%</span>
              <span v-if="game.old_price" class="old-price">{{ Number(game.old_price).toFixed(0) }} ₽</span>
              <span class="current-price">{{ Number(game.price).toFixed(0) }} ₽</span>
          </div>
          <button 
            @click="addToCart" 
            :disabled="isInCart" 
            :class="['add-to-cart-btn', { 'in-cart': isInCart }]">
            {{ isInCart ? 'Добавлено в корзину' : 'Добавить в корзину' }}
          </button>
          <p class="delivery-info">Мгновенная доставка ключа на e-mail</p>
        </div>
      </header>

      <!-- ******** MAIN CONTENT GRID ******** -->
      <div class="content-grid">
        <!-- Left Column -->
        <div class="main-content-col">
          <section v-if="game.trailerUrl" class="content-section">
            <h2 class="section-title">Трейлер</h2>
            <div class="video-container">
              <iframe :src="game.trailerUrl" :title="`Официальный трейлер ${game.title}`" frameborder="0" allowfullscreen></iframe>
            </div>
          </section>

          <section v-if="game.screenshots && game.screenshots.length" class="content-section">
            <h2 class="section-title">Скриншоты</h2>
            <div class="screenshots-grid">
              <a v-for="(src, index) in game.screenshots" :key="index" :href="`/${src}`" target="_blank">
                <img :src="`/${src}`" :alt="`Скриншот ${game.title} ${index + 1}`" class="screenshot-img" />
              </a>
            </div>
          </section>

          <section class="content-section">
            <h2 class="section-title">Об игре {{ game.title }}</h2>
             <p>В онлайн-магазине <strong>GameStore</strong> вы можете <strong>купить ключ {{ game.title }}</strong> для платформы {{ game.platform }} по самой выгодной цене. Это знаменитая игра в жанре <em>{{ game.genre }}</em>, выпущенная в {{ game.release_year }} году, которая уже успела завоевать сердца тысяч геймеров.</p>
            <p>Мы гарантируем моментальную доставку лицензионного ключа активации на ваш e-mail сразу после оплаты. Начните свое приключение в мире {{ game.title }} уже сегодня!</p>
          </section>

          <section v-if="game.systemRequirements" class="content-section">
             <h2 class="section-title">Системные требования</h2>
             <div class="requirements-grid">
                <div class="req-block">
                  <h3>Минимальные</h3>
                  <ul>
                    <li><strong>ОС:</strong> {{ game.systemRequirements.min.os }}</li>
                    <li><strong>Процессор:</strong> {{ game.systemRequirements.min.processor }}</li>
                    <li><strong>Память:</strong> {{ game.systemRequirements.min.memory }}</li>
                    <li><strong>Видеокарта:</strong> {{ game.systemRequirements.min.graphics }}</li>
                    <li><strong>На диске:</strong> {{ game.systemRequirements.min.storage }}</li>
                  </ul>
                </div>
                <div class="req-block">
                  <h3>Рекомендуемые</h3>
                  <ul>
                    <li><strong>ОС:</strong> {{ game.systemRequirements.rec.os }}</li>
                    <li><strong>Процессор:</strong> {{ game.systemRequirements.rec.processor }}</li>
                    <li><strong>Память:</strong> {{ game.systemRequirements.rec.memory }}</li>
                    <li><strong>Видеокарта:</strong> {{ game.systemRequirements.rec.graphics }}</li>
                    <li><strong>На диске:</strong> {{ game.systemRequirements.rec.storage }}</li>
                  </ul>
                </div>
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
              <li><span>Разработчик:</span> <strong>{{ game.developer || 'Не указан' }}</strong></li>
              <li><span>Издатель:</span> <strong>{{ game.publisher || 'Не указан' }}</strong></li>
            </ul>
          </div>
        </aside>
      </div>

       <!-- ******** SIMILAR GAMES ******** -->
      <section v-if="similarGames.length" class="similar-games-section">
        <h2 class="section-title">Похожие игры</h2>
        <div class="similar-games-grid">
           <router-link v-for="simGame in similarGames" :key="simGame.id" :to="`/games/${simGame.id}`" class="similar-game-card">
                <img :src="simGame.image.startsWith('img/') ? `/${simGame.image}` : `/img/${simGame.image}`" :alt="simGame.title" class="similar-game-img" />
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
/* GENERAL */
.page-wrapper { max-width: 1200px; margin: 0 auto; padding: 24px; color: #e5e7eb; }
.status-message { text-align: center; padding: 60px; font-size: 1.2rem; }
.status-message.error { color: #fca5a5; }
.section-title { font-size: 1.8rem; font-weight: 700; color: #fff; margin-top: 0; margin-bottom: 20px; border-bottom: 2px solid #3b82f6; padding-bottom: 10px; }

/* HEADER (BUY BLOCK) */
.game-header { display: grid; grid-template-columns: 300px 1fr; gap: 32px; background: #111827; padding: 24px; border-radius: 12px; margin-bottom: 32px; align-items: center; }
.header-cover-image { width: 100%; border-radius: 8px; box-shadow: 0 10px 20px rgba(0,0,0,0.3); }
.header-info-container { display: flex; flex-direction: column; }
.game-title { font-size: 3rem; font-weight: 800; line-height: 1.1; margin: 0 0 16px; }
.price-block { display: flex; align-items: baseline; gap: 12px; margin-bottom: 20px; }
.current-price { font-size: 2.5rem; font-weight: 800; color: #4ade80; }
.old-price { font-size: 1.25rem; color: #6b7280; text-decoration: line-through; }
.discount-badge { background-color: #ef4444; color: white; padding: 5px 10px; border-radius: 6px; font-weight: 700; }
.add-to-cart-btn { background-color: #3b82f6; color: white; border: none; font-size: 1.2rem; font-weight: 700; padding: 16px; border-radius: 8px; cursor: pointer; transition: all 0.2s ease; text-align: center; }
.add-to-cart-btn:hover:not(:disabled) { background-color: #2563eb; transform: scale(1.03); }
.add-to-cart-btn:disabled,
.add-to-cart-btn.in-cart {
  background-color: #22c55e;
  cursor: not-allowed;
  transform: none;
}
.add-to-cart-btn:disabled:hover {
  filter: brightness(1.0);
}
.delivery-info { color: #9ca3af; margin-top: 12px; font-size: 0.9rem; text-align: center; }

/* LAYOUT */
.content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 32px; }
.main-content-col { display: flex; flex-direction: column; gap: 32px; min-width: 0; }
.content-section { background: #111827; border: 1px solid #1f2937; padding: 24px; border-radius: 12px; }

/* SIDEBAR */
.sidebar-col { position: sticky; top: 80px; align-self: start; }
.details-card { background: #111827; border: 1px solid #1f2937; padding: 24px; border-radius: 12px; }
.details-card-title { font-size: 1.3rem; margin: 0 0 16px; color: #fff; }
.details-list { list-style: none; padding: 0; margin: 0; }
.details-list li { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #1f2937; font-size: 0.95rem; }
.details-list li:last-child { border: none; }
.details-list li span { color: #9ca3af; }
.details-list li strong { color: #e5e7eb; }

/* SPECIFIC SECTIONS */
.video-container { position: relative; padding-bottom: 56.25%; height: 0; border-radius: 8px; overflow: hidden; }
.video-container iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
.screenshots-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
.screenshot-img { width: 100%; border-radius: 8px; transition: transform 0.2s ease; }
.screenshot-img:hover { transform: scale(1.05); }
.content-section p { font-size: 1rem; line-height: 1.8; color: #d1d5db; }
.requirements-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.req-block h3 { margin: 0 0 12px; color: #9ca3af; }
.req-block ul { list-style: none; padding: 0; margin: 0; line-height: 1.6; font-size: 0.9rem; }

/* SIMILAR GAMES */
.similar-games-section { margin-top: 32px; }
.similar-games-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
.similar-game-card { text-decoration: none; background: #1f2937; border-radius: 8px; overflow: hidden; transition: transform 0.2s ease, box-shadow 0.2s ease; }
.similar-game-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.25); }
.similar-game-img { width: 100%; aspect-ratio: 4/5; object-fit: cover; }
.similar-game-info { padding: 12px; }
.similar-game-title { font-weight: 600; color: #fff; margin-bottom: 4px; white-space: nowrap; text-overflow: ellipsis; overflow: hidden; }
.similar-game-price { color: #4ade80; font-weight: 700; }

/* RESPONSIVE */
@media (max-width: 992px) {
  .content-grid { grid-template-columns: 1fr; }
  .sidebar-col { position: static; top: auto; margin-top: 32px; }
}
@media (max-width: 768px) {
  .game-header { grid-template-columns: 1fr; text-align: center; }
  .header-cover-container { max-width: 300px; margin: 0 auto; }
  .header-info-container { align-items: center; }
  .price-block { justify-content: center; }
  .game-title { font-size: 2.5rem; }
  .requirements-grid { grid-template-columns: 1fr; }
}

</style>