<template>
  <main class="home-page">

    <!-- Секция с главным предложением -->
    <section class="hero-section">
      <div class="particles-container">
        <div class="particle-orbit orbit-1"></div>
        <div class="particle-orbit orbit-2"></div>
        <div class="particle-orbit orbit-3"></div>
      </div>
      <div class="hero-content">
        <h1 class="hero-title">Новые горизонты в мире игр</h1>
        <p class="hero-subtitle">Откройте для себя последние новинки, эксклюзивные предложения и станьте частью игрового сообщества.</p>
        <router-link to="/catalog" class="hero-button">Перейти в каталог</router-link>
      </div>
    </section>

    <!-- Секция Карусель Игр (во всю ширину) -->
    <section class="home-section game-carousel-section">
      <GameCarousel />
    </section>

    <!-- Обертка для остального контента -->
    <div class="page-content-wrapper">
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

      <!-- Секция Часто задаваемые вопросы (FAQ) -->
      <section class="home-section faq-section">
        <h2 class="section-title">Помощь и поддержка</h2>
        <div class="faq-container">
          <div v-for="item in faqItems" :key="item.id" class="faq-item">
            <div class="faq-question" @click="toggleFaq(item.id)" :aria-expanded="openFaqItem === item.id">
              <span>{{ item.question }}</span>
              <span class="faq-icon">{{ openFaqItem === item.id ? '−' : '+' }}</span>
            </div>
            <Transition name="faq-slide">
              <div v-if="openFaqItem === item.id" class="faq-answer">
                <p>{{ item.answer }}</p>
              </div>
            </Transition>
          </div>
        </div>
      </section>
    </div>

  </main>
</template>

<script setup>
import { ref } from 'vue';
import { useHead } from '@vueuse/head';
import GameCarousel from '../components/GameCarousel.vue';

useHead({
  title: 'GameStore - Купить ключи для игр',
  meta: [
    {
      name: 'description',
      content: 'Магазин лицензионных ключей для игр. Покупайте игры для Steam, Epic Games, GOG и других платформ по выгодным ценам.'
    }
  ]
});

const faqItems = ref([
  { id: 1, question: 'Как я получу купленную игру?', answer: 'Сразу после оплаты ключ от игры будет отправлен на вашу электронную почту. Также он будет доступен в вашем личном кабинете на сайте в разделе "Мои покупки".' },
  { id: 2, question: 'Принимаете ли вы карты всех банков?', answer: 'Мы принимаем к оплате карты Visa, MasterCard и МИР большинства банков. Если у вас возникли проблемы с оплатой, свяжитесь с нашей поддержкой.' },
  { id: 3, question: 'Что делать, если ключ не работает?', answer: 'В редких случаях могут возникать проблемы. Немедленно свяжитесь с нашей службой поддержки, предоставьте номер заказа и скриншот ошибки. Мы оперативно заменим ключ или вернем деньги.' },
  { id: 4, question: 'Могу ли я вернуть игру, если она мне не понравилась?', answer: 'Цифровые ключи, согласно законодательству, не подлежат возврату или обмену, если они являются рабочими. Пожалуйста, внимательно ознакомьтесь с системными требованиями и описанием игры до покупки.' },
  { id: 5, question: 'На какой платформе я смогу активировать ключ?', answer: 'Платформа для активации (например, Steam, Epic Games Store, GOG) всегда указана на странице товара. Пожалуйста, убедитесь, что у вас есть аккаунт в соответствующем сервисе.' }
]);
const openFaqItem = ref(null);
const toggleFaq = (id) => { openFaqItem.value = openFaqItem.value === id ? null : id; };
</script>

<style scoped>
/* Анимации орбит */
@keyframes rotate-cw {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes rotate-ccw {
  from { transform: rotate(0deg); }
  to { transform: rotate(-360deg); }
}

.home-page {
  color: #e5e7eb;
  width: 100%;
  background-color: #020617; /* Основной темный фон */
  position: relative;
  z-index: 1;
  overflow-x: hidden;
}

/* Псевдо-элемент для создания градиентного "разделителя" */
.game-carousel-section::after {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    bottom: -100px; /* Позиционируем ниже секции карусели */
    height: 300px; /* Высота градиентной области */
    background: radial-gradient(ellipse 100% 150px at 50% 50%, rgba(30, 41, 55, 0.8) 0%, rgba(2, 6, 23, 0) 100%);
    z-index: -1;
}

.page-content-wrapper {
  max-width: 1200px;
  margin: 30px auto 40px;
  padding: 0 18px;
}

/* Секция Hero */
.hero-section {
  position: relative;
  text-align: center;
  padding: 140px 20px 100px;
  background: transparent;
  overflow: hidden;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.hero-content { position: relative; z-index: 2; }
.hero-title { font-size: 3.2rem; font-weight: 800; color: #fff; margin-bottom: 1rem; }
.hero-subtitle { font-size: 1.2rem; color: #9ca3af; max-width: 650px; margin: 0 auto 2.5rem; }
.hero-button { display: inline-block; padding: 14px 35px; border-radius: 8px; background: linear-gradient(90deg, #3b82f6, #6366f1); color: #fff; font-weight: 600; text-decoration: none; transition: all 0.2s ease; z-index: 1; }
.hero-button:hover { transform: translateY(-3px); filter: brightness(1.15); box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4); }

/* Анимация частиц */
.particles-container {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1;
  pointer-events: none;
}

.particle-orbit {
  position: absolute;
  /* Изменено: центр смещен выше (40% от верха вместо 50%) */
  top: 0%;
  left: 25%;
  border-radius: 50%;
  border: 1px solid rgba(255, 255, 255, 0.07);
  transform-origin: center center;
}

.orbit-1 { width: 45vw; height: 45vw; min-width: 300px; max-width: 500px; animation: rotate-cw 25s linear infinite; }
.orbit-2 { width: 65vw; height: 65vw; min-width: 400px; max-width: 800px; animation: rotate-ccw 40s linear infinite; }
.orbit-3 { width: 90vw; height: 90vw; min-width: 500px; max-width: 1100px; animation: rotate-cw 65s linear infinite; border-color: rgba(255, 255, 255, 0.05); }

.game-carousel-section { 
  position: relative;
  padding-top: 100px; 
  padding-bottom: 50px;
}

/* Общие стили секций */
.home-section { margin-bottom: 50px; }
.section-title { font-size: 1.8rem; font-weight: 700; color: #f9fafb; margin-bottom: 1.5rem; border-left: 4px solid #3b82f6; padding-left: 1rem; }

/* Секция "Почему мы?" */
.features-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; }
.feature-item { text-align: center; padding: 25px; border-radius: 12px; background: rgba(31, 41, 55, 0.5); border: 1px solid rgba(255, 255, 255, 0.1); transition: all 0.2s ease; }
.feature-item:hover { transform: translateY(-5px); border-color: #3b82f6; }
.feature-icon { font-size: 2.5rem; margin-bottom: 10px; }
.feature-title { font-size: 1.3rem; font-weight: 600; color: #fff; margin-bottom: 8px; }
.feature-description { font-size: 0.95rem; color: #9ca3af; line-height: 1.6; }

/* Секция FAQ */
.faq-container { max-width: 900px; margin: 0 auto; border-radius: 12px; overflow: hidden; background: rgba(17, 24, 39, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5); }
.faq-item { border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
.faq-item:last-child { border-bottom: none; }
.faq-question { padding: 20px 25px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; font-weight: 500; font-size: 1.1rem; color: #f3f4f6; transition: background-color 0.2s; }
.faq-question:hover { background-color: rgba(255, 255, 255, 0.05); }
.faq-icon { font-size: 1.6rem; color: #60a5fa; transition: transform 0.3s ease-in-out; }
.faq-question[aria-expanded="true"] .faq-icon { transform: rotate(135deg); }
.faq-answer { background-color: rgba(3, 7, 18, 0.5); color: #bdc1c6; line-height: 1.7; overflow: hidden; }
.faq-answer p { margin: 0; padding: 0 25px 20px; }

/* --- Анимация для FAQ --- */
.faq-slide-enter-active, .faq-slide-leave-active { transition: max-height 0.4s ease-in-out, opacity 0.3s ease-in-out, padding 0.3s ease-in-out; max-height: 200px; }
.faq-slide-enter-from, .faq-slide-leave-to { max-height: 0; opacity: 0; padding-top: 0; padding-bottom: 0; }

.loading-placeholder, .error-message { text-align: center; color: #9ca3af; padding: 40px; }
</style>