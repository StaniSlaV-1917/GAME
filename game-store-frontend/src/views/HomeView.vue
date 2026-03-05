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
      <div v-if="!loading &amp;&amp; specialOffers.length" class="games-grid">
        <GameCard v-for="game in specialOffers" :key="game.id" :game="game" />
      </div>
    </section>

    <!-- Секция Лента сообщества -->
    <section class="home-section">
      <h2 class="section-title">Лента сообщества</h2>
      <div class="community-feed">
        <div class="feed-messages">
          <div v-if="feedLoading" class="loading-placeholder">Загрузка ленты...</div>
           <div v-if="!feedLoading &amp;&amp; communityMessages.length === 0" class="loading-placeholder">Сообщений пока нет.</div>
          <div v-for="message in communityMessages" :key="message.id" class="feed-message">
            <div class="message-header">
              <span class="message-author">{{ message.author_name || 'Аноним' }}</span>
              <span class="message-date">{{ formatMessageDate(message.created_at) }}</span>
            </div>
            <p class="message-text">{{ message.text }}</p>
          </div>
        </div>
        <div v-if="isLoggedIn" class="feed-input-area">
          <input 
            type="text" 
            v-model="newMessage" 
            @keyup.enter="postMessage"
            placeholder="Присоединяйтесь к обсуждению..."
            :disabled="postingMessage"
          />
          <button @click="postMessage" :disabled="postingMessage || !newMessage.trim()">
            {{ postingMessage ? 'Отправка...' : 'Отправить' }}
          </button>
        </div>
         <div v-else class="login-prompt">
            <router-link to="/login">Войдите</router-link>, чтобы оставить сообщение.
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

  </main>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../api/axios';
import GameCard from '../components/GameCard.vue';
import { useAuthStore } from '../stores/auth';
import { storeToRefs } from 'pinia';

// --- Основная логика страницы ---
const allGames = ref([]);
const loading = ref(true);
const error = ref('');

const specialOffers = computed(() => {
  if (!allGames.value || !Array.isArray(allGames.value)) return [];
  return allGames.value
    .filter(g => g.discount_percent > 0 || g.is_featured)
    .slice(0, 4);
});

// --- Логика ленты сообщества ---
const authStore = useAuthStore();
const { isLoggedIn } = storeToRefs(authStore);
const communityMessages = ref([]);
const newMessage = ref('');
const feedLoading = ref(true);
const postingMessage = ref(false);

const fetchMessages = async () => {
  try {
    feedLoading.value = true;
    // ПРЕДУПРЕЖДЕНИЕ: Этого эндпоинта еще не существует!
    const { data } = await api.get('/community-messages');
    // ИСПРАВЛЕНИЕ: Обработка ответа от Laravel
    const messages = Array.isArray(data) ? data : data.data;
    communityMessages.value = messages.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
  } catch (err) {
    console.error("Ошибка загрузки ленты: ", err);
    communityMessages.value = [];
  } finally {
    feedLoading.value = false;
  }
};

const postMessage = async () => {
  if (!newMessage.value.trim()) return;

  try {
    postingMessage.value = true;
    // ПРЕДУПРЕЖДЕНИЕ: Этого эндпоинта еще не существует!
    const { data: postedMessage } = await api.post('/community-messages', { text: newMessage.value });
    communityMessages.value.unshift(postedMessage);
    newMessage.value = '';
  } catch (err) {
    console.error("Ошибка отправки сообщения: ", err);
  } finally {
    postingMessage.value = false;
  }
};

const formatMessageDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};


// --- Логика FAQ ---
const faqItems = ref([
  { id: 1, question: 'Как я получу купленную игру?', answer: 'Сразу после оплаты ключ от игры будет отправлен на вашу электронную почту. Также он будет доступен в вашем личном кабинете на сайте в разделе "Мои покупки".' },
  { id: 2, question: 'Принимаете ли вы карты всех банков?', answer: 'Мы принимаем к оплате карты Visa, MasterCard и МИР большинства банков. Если у вас возникли проблемы с оплатой, свяжитесь с нашей поддержкой.' },
  { id: 3, question: 'Что делать, если ключ не работает?', answer: 'В редких случаях могут возникать проблемы. Немедленно свяжитесь с нашей службой поддержки, предоставьте номер заказа и скриншот ошибки. Мы оперативно заменим ключ или вернем деньги.' },
  { id: 4, question: 'Могу ли я вернуть игру, если она мне не понравилась?', answer: 'Цифровые ключи, согласно законодательству, не подлежат возврату или обмену, если они являются рабочими. Пожалуйста, внимательно ознакомьтесь с системными требованиями и описанием игры до покупки.' },
  { id: 5, question: 'На какой платформе я смогу активировать ключ?', answer: 'Платформа для активации (например, Steam, Epic Games Store, GOG) всегда указана на странице товара. Пожалуйста, убедитесь, что у вас есть аккаунт в соответствующем сервисе.' }
]);
const openFaqItem = ref(null);
const toggleFaq = (id) => { openFaqItem.value = openFaqItem.value === id ? null : id; };

// --- Загрузка данных при монтировании компонента ---
onMounted(async () => {
  loading.value = true;
  await Promise.all([
    (async () => {
        try {
            const { data } = await api.get('/games');
            // ИСПРАВЛЕНИЕ: Обработка ответа от Laravel
            allGames.value = Array.isArray(data) ? data : data.data;
        } catch (e) {
            console.error(e);
            error.value = 'Не удалось загрузить спецпредложения.';
        }
    })(),
    // fetchMessages() // Временно отключаем загрузку ленты, т.к. эндпоинт не готов
  ]);
  loading.value = false;
  feedLoading.value = false; 
});
</script>

<style scoped>
.home-page { max-width: 1200px; margin: 30px auto 40px; padding: 0 18px; color: #e5e7eb; }

/* Hero Section */
.hero-section {
  text-align: center;
  padding: 60px 20px;
  margin-bottom: 40px;
  border-radius: 16px;
  /* Эффект стекла */
  background: rgba(17, 24, 39, 0.7);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
}
.hero-title { font-size: 2.8rem; font-weight: 800; color: #fff; margin-bottom: 1rem; }
.hero-subtitle { font-size: 1.1rem; color: #9ca3af; max-width: 600px; margin: 0 auto 2rem; }
.hero-button {
  display: inline-block;
  padding: 12px 30px;
  border-radius: 8px;
  background: linear-gradient(90deg, #3b82f6, #6366f1);
  color: #fff;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.2s ease;
}
.hero-button:hover { transform: translateY(-2px); filter: brightness(1.1); }

/* General Section Styles */
.home-section { margin-bottom: 50px; }
.section-title {
  font-size: 1.8rem;
  font-weight: 700;
  color: #f9fafb;
  margin-bottom: 1.5rem;
  border-left: 4px solid #3b82f6; /* Акцентный цвет */
  padding-left: 1rem;
}

/* Games Grid */
.games-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px; }

/* Community Feed */
.community-feed {
  background: rgba(17, 24, 39, 0.7);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 20px 25px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
}
.feed-messages { max-height: 300px; overflow-y: auto; margin-bottom: 1.5rem; display: flex; flex-direction: column; gap: 1rem; padding-right: 15px; }
.feed-message { border-bottom: 1px solid rgba(255, 255, 255, 0.07); padding-bottom: 1rem; }
.feed-message:last-child { border-bottom: none; }
.message-header { display: flex; align-items: baseline; gap: 10px; margin-bottom: 5px; }
.message-author { font-weight: 600; color: #60a5fa; }
.message-date { font-size: 0.8rem; color: #6b7280; }
.message-text { color: #d1d5db; line-height: 1.6; white-space: pre-wrap; }
.feed-input-area { display: flex; gap: 0.5rem; border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 1.5rem; }
.feed-input-area input {
  flex-grow: 1; padding: 12px 15px; border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  background: rgba(31, 41, 55, 0.5);
  color: #e5e7eb; font-size: 1rem; outline: none; transition: border-color 0.2s, box-shadow 0.2s;
}
.feed-input-area input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
.feed-input-area button {
  padding: 10px 20px; border-radius: 8px; border: none;
  background: linear-gradient(90deg, #3b82f6, #6366f1);
  color: #fff; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.feed-input-area button:hover:not(:disabled) { filter: brightness(1.1); }
.feed-input-area button:disabled { background: #4b5563; cursor: not-allowed; }
.login-prompt { text-align: center; padding-top: 1.5rem; border-top: 1px solid rgba(255, 255, 255, 0.1); color: #9ca3af; }
.login-prompt a { color: #3b82f6; text-decoration: none; font-weight: 500; }

/* FAQ Section */
.faq-container {
  max-width: 900px;
  margin: 0 auto;
  border-radius: 12px;
  overflow: hidden;
  background: rgba(17, 24, 39, 0.7);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
}
.faq-item { border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
.faq-item:last-child { border-bottom: none; }
.faq-question {
  padding: 20px 25px; display: flex; justify-content: space-between; align-items: center;
  cursor: pointer; font-weight: 500; font-size: 1.1rem; color: #f3f4f6;
  transition: background-color 0.2s;
}
.faq-question:hover { background-color: rgba(255, 255, 255, 0.05); }
.faq-icon {
    font-size: 1.6rem;
    color: #60a5fa;
    transition: transform 0.3s ease-in-out;
}
.faq-question[aria-expanded="true"] .faq-icon {
    transform: rotate(135deg);
}

.faq-answer {
  background-color: rgba(3, 7, 18, 0.5);
  color: #bdc1c6;
  line-height: 1.7;
  overflow: hidden;
}
.faq-answer p { margin: 0; padding: 0 25px 20px; }

/* --- Анимация для FAQ --- */
.faq-slide-enter-active, .faq-slide-leave-active {
  transition: max-height 0.4s ease-in-out, opacity 0.3s ease-in-out, padding 0.3s ease-in-out;
  max-height: 200px; /* Больше максимальной высоты контента */
}

.faq-slide-enter-from, .faq-slide-leave-to {
  max-height: 0;
  opacity: 0;
  padding-top: 0;
  padding-bottom: 0;
}

.loading-placeholder, .error-message { text-align: center; color: #9ca3af; padding: 40px; }

</style>
