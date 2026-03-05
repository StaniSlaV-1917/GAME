<template>
  <main class="home-page">

    <!-- Секция с главным предложением -->
    <section class="hero-section">
      <div class="hero-content">
        <h1 class="hero-title">Новые горизонты в мире игр</h1>
        <p class="hero-subtitle">Откройте для себя последние новинки, эксклюзивные предложения и станьте частью игрового сообщества.</p>
        <router-link to="/catalog" class="hero-button">Перейти в каталог</router-link>
      </div>
    </section>

    <!-- Секция Специальные предложения -->
    <section class="home-section">
      <h2 class="section-title">Специальные предложения</h2>
      <div v-if="loading" class="loading-placeholder">Загрузка игр...</div>
      <div v-if="error" class="error-message">{{ error }}</div>
      <div v-if="!loading && specialOffers.length" class="games-grid">
        <GameCard v-for="game in specialOffers" :key="game.id" :game="game" />
      </div>
    </section>

    <!-- Секция "Почему мы?" -->
    <section class="home-section features-section">
        <h2 class="section-title">Почему GameStore?</h2>
        <div class="features-grid">
            <div class="feature-item">
                <div class="feature-icon">⚡️</div>
                <h3 class="feature-title">Мгновенная доставка</h3>
                <p class="feature-description">Ключ от игры приходит на вашу почту сразу после оплаты — без ожидания и задержек.</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon">🛡️</div>
                <h3 class="feature-title">Гарантия качества</h3>
                <p class="feature-description">Мы работаем только с официальными издателями и гарантируем валидность каждого ключа.</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon">💬</div>
                <h3 class="feature-title">Поддержка 24/7</h3>
                <p class="feature-description">Наша служба поддержки всегда готова помочь вам с любыми вопросами в кратчайшие сроки.</p>
            </div>
        </div>
    </section>


    <!-- Секция Последние новости -->
    <section class="home-section">
      <h2 class="section-title">Последние новости</h2>
      <div v-if="newsLoading" class="loading-placeholder">Загрузка новостей...</div>
      <div v-if="newsError" class="error-message">{{ newsError }}</div>
      <div v-if="!newsLoading && latestNews.length" class="news-grid">
        <div v-for="item in latestNews" :key="item.id" class="news-card">
          <img :src="`http://localhost:8000${item.image_url}`" alt="" class="news-image"/>
          <div class="news-content">
            <h3 class="news-title">{{ item.title }}</h3>
            <p class="news-excerpt">{{ item.excerpt }}</p>
            <a :href="`/news/${item.id}`" class="news-link">Читать далее →</a>
          </div>
        </div>
      </div>
    </section>

  </main>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../api/axios';
import GameCard from '../components/GameCard.vue';

// --- Логика загрузки игр ---
const allGames = ref([]);
const loading = ref(true);
const error = ref('');

const specialOffers = computed(() => {
  if (!allGames.value || !Array.isArray(allGames.value)) return [];
  return allGames.value
    .filter(g => g.discount_percent > 0 || g.is_featured)
    .slice(0, 4);
});

// --- Логика загрузки новостей ---
const latestNews = ref([]);
const newsLoading = ref(true);
const newsError = ref('');

const fetchNews = async () => {
    try {
        const { data } = await api.get('/news');
        // Берем только 2 последние новости
        latestNews.value = (Array.isArray(data) ? data : data.data).slice(0, 2);
    } catch (e) {
        console.error(e);
        newsError.value = 'Не удалось загрузить новости.';
    } finally {
        newsLoading.value = false;
    }
};

// --- Загрузка всех данных при монтировании ---
onMounted(async () => {
  loading.value = true;
  error.value = '';
  
  // Запускаем обе загрузки параллельно
  await Promise.all([
    (async () => {
        try {
            const { data } = await api.get('/games');
            allGames.value = Array.isArray(data) ? data : data.data;
        } catch (e) {
            console.error(e);
            error.value = 'Не удалось загрузить спецпредложения.';
        }
    })(),
    fetchNews()
  ]);
  
  loading.value = false;
});
</script>

<style scoped>
.home-page { max-width: 1200px; margin: 30px auto 40px; padding: 0 18px; color: #e5e7eb; }

/* Hero Section */
.hero-section { text-align: center; padding: 60px 20px; margin-bottom: 40px; border-radius: 16px; background: rgba(17, 24, 39, 0.7); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5); }
.hero-title { font-size: 2.8rem; font-weight: 800; color: #fff; margin-bottom: 1rem; }
.hero-subtitle { font-size: 1.1rem; color: #9ca3af; max-width: 600px; margin: 0 auto 2rem; }
.hero-button { display: inline-block; padding: 12px 30px; border-radius: 8px; background: linear-gradient(90deg, #3b82f6, #6366f1); color: #fff; font-weight: 600; text-decoration: none; transition: all 0.2s ease; }
.hero-button:hover { transform: translateY(-2px); filter: brightness(1.1); }

/* General Section Styles */
.home-section { margin-bottom: 50px; }
.section-title { font-size: 1.8rem; font-weight: 700; color: #f9fafb; margin-bottom: 1.5rem; border-left: 4px solid #3b82f6; padding-left: 1rem; }

/* Games Grid */
.games-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px; }

/* Features Section */
.features-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; }
.feature-item { text-align: center; padding: 25px; border-radius: 12px; background: rgba(31, 41, 55, 0.5); border: 1px solid rgba(255, 255, 255, 0.1); transition: all 0.2s ease; }
.feature-item:hover { transform: translateY(-5px); border-color: #3b82f6; }
.feature-icon { font-size: 2.5rem; margin-bottom: 10px; }
.feature-title { font-size: 1.3rem; font-weight: 600; color: #fff; margin-bottom: 8px; }
.feature-description { font-size: 0.95rem; color: #9ca3af; line-height: 1.6; }

/* News Section */
.news-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; }
.news-card { background: rgba(31, 41, 55, 0.5); border-radius: 12px; overflow: hidden; border: 1px solid rgba(255, 255, 255, 0.1); transition: all 0.2s ease; }
.news-card:hover { transform: translateY(-5px); border-color: #3b82f6; }
.news-image { width: 100%; height: 200px; object-fit: cover; }
.news-content { padding: 20px; }
.news-title { font-size: 1.25rem; font-weight: 600; color: #fff; margin: 0 0 10px; }
.news-excerpt { font-size: 0.95rem; color: #9ca3af; line-height: 1.6; margin: 0 0 15px; }
.news-link { color: #3b82f6; text-decoration: none; font-weight: 500; transition: color 0.2s; }
.news-link:hover { color: #60a5fa; }

.loading-placeholder, .error-message { text-align: center; color: #9ca3af; padding: 40px; }
</style>
