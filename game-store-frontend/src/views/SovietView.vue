<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useHead } from '@vueuse/head';

useHead({
  title: 'СССР — Игры советской эпохи | GameStore',
  meta: [
    { name: 'description', content: 'Экспериментальная страница: лучшие игры про Советский Союз. Атмосфера, история, идеология. Серп и молот в мире геймдева.' },
    { property: 'og:type', content: 'website' },
    { property: 'og:title', content: 'СССР — Игры советской эпохи | GameStore' },
    { property: 'og:description', content: 'Лучшие игры про Советский Союз. Атмосфера, история, идеология.' },
    { property: 'og:image', content: '/images.png' },
    { name: 'robots', content: 'index, follow' },
  ],
});

const isAnthemPlaying = ref(false);
const anthemAudio = ref(null);
const currentTime = ref(0);
const duration = ref(0);
const progress = ref(0);
let animFrame = null;

const games = [
  {
    id: 1,
    title: 'Atomic Heart',
    year: 2023,
    genre: 'Шутер / RPG',
    studio: 'Mundfish',
    rating: 9.1,
    tag: 'ХИТ',
    tagColor: '#ff1a1a',
    description: 'Альтернативный СССР 1955 года. Советский солдат-испытатель противостоит восставшим роботам на секретном объекте 3826. Уникальная атмосфера советского retrofuturism.',
    emoji: '⚙️',
    gradient: 'linear-gradient(135deg, #1a0000 0%, #3d0000 50%, #1a0000 100%)',
    accent: '#ff4444',
  },
  {
    id: 2,
    title: 'Metro 2033',
    year: 2010,
    genre: 'Шутер / Хоррор',
    studio: '4A Games',
    rating: 8.8,
    tag: 'КЛАССИКА',
    tagColor: '#d4af37',
    description: 'Постапокалиптическая Москва. Выжившие прячутся в туннелях московского метро. Мрачная атмосфера, основанная на романе Дмитрия Глуховского.',
    emoji: '🚇',
    gradient: 'linear-gradient(135deg, #0a0a1a 0%, #1a1a3d 50%, #0a0a1a 100%)',
    accent: '#6688cc',
  },
  {
    id: 3,
    title: 'Metro: Last Light',
    year: 2013,
    genre: 'Шутер / Стелс',
    studio: '4A Games',
    rating: 9.0,
    tag: 'КУЛЬТ',
    tagColor: '#d4af37',
    description: 'Продолжение эпической саги о московском метро. Противостояние фракций, тёмные и светлые рейнджеры, судьба последних людей в руинах СССР.',
    emoji: '🔦',
    gradient: 'linear-gradient(135deg, #0f0f0f 0%, #1f1f1f 50%, #0f0f0f 100%)',
    accent: '#888888',
  },
  {
    id: 4,
    title: 'STALKER: Shadow of Chernobyl',
    year: 2007,
    genre: 'Шутер / RPG',
    studio: 'GSC Game World',
    rating: 9.3,
    tag: 'ЛЕГЕНДА',
    tagColor: '#ff6600',
    description: 'Зона отчуждения после взрыва на Чернобыльской АЭС. Аномалии, мутанты и артефакты в открытом мире. Один из величайших шутеров постсоветского пространства.',
    emoji: '☢️',
    gradient: 'linear-gradient(135deg, #0a0f00 0%, #1a2a00 50%, #0a0f00 100%)',
    accent: '#88cc00',
  },
  {
    id: 5,
    title: 'Workers & Resources: Soviet Republic',
    year: 2019,
    genre: 'Стратегия / Симулятор',
    studio: '3Division',
    rating: 8.6,
    tag: 'СТРОЙ!',
    tagColor: '#ff1a1a',
    description: 'Построй СССР с нуля! Управляй советской республикой: планируй экономику, стройте заводы, прокладывай железные дороги. Детальная советская градостроительная стратегия.',
    emoji: '🏭',
    gradient: 'linear-gradient(135deg, #1a0a00 0%, #3d1f00 50%, #1a0a00 100%)',
    accent: '#ff8800',
  },
  {
    id: 6,
    title: 'Red Alert 2',
    year: 2000,
    genre: 'Стратегия',
    studio: 'Westwood Studios',
    rating: 9.5,
    tag: 'ШЕДЕВР',
    tagColor: '#d4af37',
    description: 'Советский Союз вторгается в США! Командуй армией Советов: танки Апокалипсис, психо-ментальные отряды и Юрий. Культовая стратегия эпохи холодной войны.',
    emoji: '🚀',
    gradient: 'linear-gradient(135deg, #1a0000 0%, #330000 50%, #1a0000 100%)',
    accent: '#ff2222',
  },
];

const quotes = [
  { text: 'Пролетарии всех стран, соединяйтесь!', author: 'К. Маркс, Ф. Энгельс' },
  { text: 'Победа будет за нами.', author: 'И.В. Сталин, 1941' },
  { text: 'Поехали!', author: 'Ю.А. Гагарин, 12 апреля 1961' },
];

const currentQuote = ref(0);
let quoteTimer = null;

function nextQuote() {
  currentQuote.value = (currentQuote.value + 1) % quotes.length;
}

function toggleAnthem() {
  if (!anthemAudio.value) return;
  if (isAnthemPlaying.value) {
    anthemAudio.value.pause();
    isAnthemPlaying.value = false;
    cancelAnimationFrame(animFrame);
  } else {
    anthemAudio.value.play().then(() => {
      isAnthemPlaying.value = true;
      updateProgress();
    }).catch(() => {});
  }
}

function updateProgress() {
  if (!anthemAudio.value) return;
  currentTime.value = anthemAudio.value.currentTime;
  duration.value = anthemAudio.value.duration || 0;
  progress.value = duration.value ? (currentTime.value / duration.value) * 100 : 0;
  animFrame = requestAnimationFrame(updateProgress);
}

function seekAnthem(e) {
  if (!anthemAudio.value || !duration.value) return;
  const bar = e.currentTarget;
  const rect = bar.getBoundingClientRect();
  const x = e.clientX - rect.left;
  const pct = x / rect.width;
  anthemAudio.value.currentTime = pct * duration.value;
}

function formatTime(s) {
  if (!s || isNaN(s)) return '0:00';
  const m = Math.floor(s / 60);
  const sec = Math.floor(s % 60);
  return `${m}:${sec.toString().padStart(2, '0')}`;
}

onMounted(() => {
  quoteTimer = setInterval(nextQuote, 4000);
  if (anthemAudio.value) {
    anthemAudio.value.addEventListener('ended', () => {
      isAnthemPlaying.value = false;
      cancelAnimationFrame(animFrame);
    });
  }
});

onUnmounted(() => {
  clearInterval(quoteTimer);
  cancelAnimationFrame(animFrame);
});
</script>

<template>
  <div class="soviet-page">

    <!-- Кованая ссылка возврата — единственный Ashenforge-элемент,
         чтобы страница была встроена в навигацию сайта.
         Контент-часть остаётся в самобытной советской эстетике. -->
    <router-link to="/" class="soviet-back-link">
      <span class="sbl-arrow" aria-hidden="true">←</span>
      <span class="sbl-text">Назад в Оплот</span>
    </router-link>

    <!-- HERO SECTION -->
    <section class="hero">
      <div class="hero-scanlines"></div>
      <div class="hero-noise"></div>

      <!-- Full-screen waving flag background -->
      <div class="hero-flag-bg" aria-hidden="true">
        <svg width="0" height="0" style="position:absolute">
          <defs>
            <filter id="flag-wave-filter" x="-5%" y="-5%" width="110%" height="110%">
              <feTurbulence type="fractalNoise" baseFrequency="0.012 0.04" numOctaves="3" seed="5" result="turbulence">
                <animate attributeName="baseFrequency"
                  dur="7s"
                  values="0.012 0.04; 0.018 0.055; 0.012 0.04"
                  repeatCount="indefinite"/>
              </feTurbulence>
              <feDisplacementMap in="SourceGraphic" in2="turbulence"
                scale="55" xChannelSelector="R" yChannelSelector="G"/>
            </filter>
          </defs>
        </svg>
        <div class="flag-cloth">
          <div class="flag-cloth-shading"></div>
          <span class="flag-cloth-emblem">☭</span>
        </div>
      </div>

      <!-- Animated flag stripes -->
      <div class="flag-stripe red"></div>

      <div class="hero-content">
        <!-- Emblem -->
        <div class="emblem-ring">
          <div class="star-container">
            <span class="red-star">★</span>
          </div>
          <div class="hammer-sickle">☭</div>
        </div>

        <div class="hero-text">
          <div class="hero-label">ЭКСПЕРИМЕНТАЛЬНАЯ СТРАНИЦА</div>
          <h1 class="hero-title">
            <span class="title-ussr">СССР</span>
            <span class="title-sub">В МИРЕ ВИДЕОИГР</span>
          </h1>
          <p class="hero-desc">
            Серп и молот. Красная звезда. Атмосфера великой эпохи в лучших играх
            современности. Откройте для себя советский геймдев.
          </p>
        </div>

        <!-- Stats bar -->
        <div class="hero-stats">
          <div class="stat-item">
            <span class="stat-num">6</span>
            <span class="stat-label">Игр в подборке</span>
          </div>
          <div class="stat-divider"></div>
          <div class="stat-item">
            <span class="stat-num">1917</span>
            <span class="stat-label">Год основания СССР</span>
          </div>
          <div class="stat-divider"></div>
          <div class="stat-item">
            <span class="stat-num">74</span>
            <span class="stat-label">Года истории</span>
          </div>
        </div>
      </div>

      <!-- Decorative side flags -->
      <div class="side-flag left">
        <div class="flag-body">
          <div class="flag-emblem">☭</div>
        </div>
        <div class="flag-pole"></div>
      </div>
      <div class="side-flag right">
        <div class="flag-body">
          <div class="flag-emblem">☭</div>
        </div>
        <div class="flag-pole"></div>
      </div>
    </section>

    <!-- ANTHEM PLAYER -->
    <section class="anthem-section">
      <div class="anthem-container">
        <div class="anthem-left">
          <div class="anthem-icon">🎵</div>
          <div class="anthem-info">
            <div class="anthem-title">Государственный гимн СССР</div>
            <div class="anthem-composer">Музыка: А. Александров · Слова: С. Михалков</div>
          </div>
        </div>

        <div class="anthem-player">
          <audio ref="anthemAudio" preload="auto">
            <source src="/anthem.mp3" type="audio/mpeg">
          </audio>

          <button class="anthem-btn" @click="toggleAnthem" :class="{ playing: isAnthemPlaying }">
            <span v-if="!isAnthemPlaying">▶</span>
            <span v-else>⏸</span>
          </button>

          <div class="anthem-progress-wrap">
            <div class="anthem-progress-bar" @click="seekAnthem">
              <div class="anthem-progress-fill" :style="{ width: progress + '%' }">
                <div class="progress-thumb"></div>
              </div>
            </div>
            <div class="anthem-time">
              <span>{{ formatTime(currentTime) }}</span>
              <span>{{ formatTime(duration) }}</span>
            </div>
          </div>
        </div>

        <div class="anthem-waves" :class="{ active: isAnthemPlaying }">
          <span v-for="i in 7" :key="i" :style="{ animationDelay: (i * 0.12) + 's' }"></span>
        </div>
      </div>
    </section>

    <!-- QUOTE TICKER -->
    <section class="quotes-section">
      <div class="quote-ticker">
        <div class="quote-star">★</div>
        <transition name="quote-fade" mode="out-in">
          <div class="quote-text" :key="currentQuote">
            <span class="quote-body">"{{ quotes[currentQuote].text }}"</span>
            <span class="quote-author">— {{ quotes[currentQuote].author }}</span>
          </div>
        </transition>
        <div class="quote-star">★</div>
      </div>
      <div class="quote-dots">
        <button
          v-for="(q, i) in quotes"
          :key="i"
          class="quote-dot"
          :class="{ active: i === currentQuote }"
          @click="currentQuote = i"
        ></button>
      </div>
    </section>

    <!-- GAMES GRID -->
    <section class="games-section">
      <div class="section-header">
        <div class="section-badge">☭ ПОДБОРКА</div>
        <h2 class="section-title">Игры Советской Эпохи</h2>
        <p class="section-desc">Лучшие игры, погружающие в атмосферу СССР — от киберпанка до постапокалипсиса</p>
        <div class="section-divider">
          <span>★</span>
        </div>
      </div>

      <div class="games-grid">
        <div
          v-for="(game, idx) in games"
          :key="game.id"
          class="game-card"
          :style="{ animationDelay: idx * 0.08 + 's', '--accent': game.accent, background: game.gradient }"
        >
          <div class="card-top">
            <div class="card-emoji">{{ game.emoji }}</div>
            <div class="card-tag" :style="{ background: game.tagColor }">{{ game.tag }}</div>
          </div>

          <div class="card-body">
            <div class="card-meta">
              <span class="card-year">{{ game.year }}</span>
              <span class="card-genre">{{ game.genre }}</span>
            </div>
            <h3 class="card-title">{{ game.title }}</h3>
            <p class="card-studio">{{ game.studio }}</p>
            <p class="card-desc">{{ game.description }}</p>
          </div>

          <div class="card-footer">
            <div class="card-rating">
              <span class="rating-star">★</span>
              <span class="rating-num">{{ game.rating }}</span>
            </div>
            <button class="card-btn">Подробнее →</button>
          </div>

          <div class="card-glow"></div>
        </div>
      </div>
    </section>

    <!-- MANIFESTO SECTION -->
    <section class="manifesto-section">
      <div class="manifesto-bg">
        <div class="manifesto-star-left">★</div>
        <div class="manifesto-star-right">★</div>
      </div>
      <div class="manifesto-content">
        <div class="manifesto-flag">
          <div class="mflag-red">
            <span class="mflag-emblem">☭</span>
          </div>
        </div>
        <div class="manifesto-text">
          <h2>Почему СССР в играх?</h2>
          <p>
            Советский Союз — это не просто историческая эпоха. Это уникальная эстетика,
            которая продолжает вдохновлять разработчиков по всему миру. Конструктивизм,
            пропаганда, космос, холодная война — всё это даёт богатейший контекст
            для создания неповторимых игровых миров.
          </p>
          <p>
            От мрачных туннелей московского метро до ярких советских утопий будущего —
            игры про СССР дарят атмосферу, которую невозможно найти нигде больше.
          </p>
          <div class="manifesto-badges">
            <span class="mbadge">🚀 Космическая гонка</span>
            <span class="mbadge">🏭 Индустриализация</span>
            <span class="mbadge">🔴 Идеология</span>
            <span class="mbadge">☢️ Чернобыль</span>
            <span class="mbadge">⚔️ Великая война</span>
          </div>
        </div>
      </div>
    </section>

    <!-- TIMELINE -->
    <section class="timeline-section">
      <div class="section-header">
        <div class="section-badge">★ ИСТОРИЯ</div>
        <h2 class="section-title">Хронология СССР</h2>
        <div class="section-divider"><span>★</span></div>
      </div>
      <div class="timeline">
        <div class="tl-item" v-for="(ev, i) in [
          { year: '1917', text: 'Великая Октябрьская революция. Рождение Советского государства.', icon: '🔴' },
          { year: '1941', text: 'Начало Великой Отечественной войны. СССР встаёт на защиту Родины.', icon: '⚔️' },
          { year: '1945', text: 'Победа над фашизмом. 9 мая — день, изменивший историю.', icon: '🏆' },
          { year: '1961', text: 'Юрий Гагарин — первый человек в космосе. «Поехали!»', icon: '🚀' },
          { year: '1986', text: 'Авария на Чернобыльской АЭС. Событие, изменившее мир.', icon: '☢️' },
          { year: '1991', text: 'Распад СССР. Конец эпохи. Начало новой истории.', icon: '📜' },
        ]" :key="i" :class="{ right: i % 2 === 1 }">
          <div class="tl-connector">
            <div class="tl-dot">{{ ev.icon }}</div>
          </div>
          <div class="tl-card">
            <div class="tl-year">{{ ev.year }}</div>
            <p class="tl-text">{{ ev.text }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- BOTTOM BANNER -->
    <section class="bottom-banner">
      <div class="bb-content">
        <div class="bb-emblem">☭</div>
        <div class="bb-text">
          <h3>СССР живёт в культуре</h3>
          <p>Изучайте историю через игры. Помните прошлое.</p>
        </div>
        <div class="bb-stars">
          <span v-for="i in 5" :key="i">★</span>
        </div>
      </div>
    </section>

  </div>
</template>

<style scoped>
/* ===== BASE ===== */
.soviet-page {
  position: relative;
  background: #080808;
  color: #e8e0d0;
  font-family: 'Georgia', serif;
  overflow-x: hidden;
}

/* ===== Кованая ссылка возврата — мост между Ashenforge-сайтом и
        самобытной советской страницей ===== */
.soviet-back-link {
  position: absolute;
  top: 18px;
  left: 22px;
  z-index: 10;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 9px 16px;
  font-family: var(--font-display, Cinzel, Georgia), serif;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 1.6px;
  text-transform: uppercase;
  text-decoration: none;
  color: var(--text-parchment, #d8c49a);
  background: linear-gradient(180deg,
    rgba(36, 29, 23, 0.92) 0%,
    rgba(27, 22, 17, 0.92) 100%);
  border: 1px solid var(--iron-mid, #5a463a);
  border-radius: 4px;
  box-shadow:
    inset 0 1px 0 rgba(199, 154, 94, 0.18),
    inset 0 -1px 0 rgba(0, 0, 0, 0.45),
    0 4px 14px rgba(0, 0, 0, 0.55);
  transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
}
.soviet-back-link:hover {
  color: var(--ember-gold, #ffc979);
  border-color: var(--bronze, #a07348);
  background: linear-gradient(180deg,
    rgba(58, 42, 34, 0.95) 0%,
    rgba(36, 29, 23, 0.95) 100%);
  transform: translateX(-2px);
  box-shadow:
    inset 0 1px 0 rgba(199, 154, 94, 0.25),
    inset 0 -1px 0 rgba(0, 0, 0, 0.45),
    0 6px 18px rgba(226, 67, 16, 0.35);
}
.sbl-arrow {
  font-size: 1rem;
  color: var(--bronze, #a07348);
  transition: transform 0.22s cubic-bezier(0.4, 0, 0.2, 1);
}
.soviet-back-link:hover .sbl-arrow {
  transform: translateX(-3px);
  color: var(--ember-spark, #ffa758);
}

@media (max-width: 480px) {
  .soviet-back-link { top: 12px; left: 12px; padding: 7px 12px; font-size: 0.7rem; }
  .sbl-text { display: none; }
  .sbl-arrow { font-size: 0.95rem; }
}

/* ===== HERO ===== */
.hero {
  position: relative;
  min-height: 92vh;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  background: radial-gradient(ellipse 120% 80% at 50% 40%, #1a0000 0%, #0d0000 40%, #050000 70%, #000 100%);
}

.hero-scanlines {
  position: absolute;
  inset: 0;
  background: repeating-linear-gradient(
    0deg,
    transparent,
    transparent 3px,
    rgba(0, 0, 0, 0.15) 3px,
    rgba(0, 0, 0, 0.15) 4px
  );
  pointer-events: none;
  z-index: 1;
}

.hero-noise {
  position: absolute;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
  opacity: 0.35;
  pointer-events: none;
  z-index: 1;
}

.flag-stripe {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 6px;
  z-index: 2;
}
.flag-stripe.red { background: linear-gradient(90deg, #8b0000, #cc0000, #ff1a1a, #cc0000, #8b0000); }

/* ===== FULLSCREEN WAVING FLAG BACKGROUND ===== */
.hero-flag-bg {
  position: absolute;
  inset: -8%;          /* чуть больше чтобы края волны не обрезались */
  z-index: 0;
  pointer-events: none;
  overflow: hidden;
}

.flag-cloth {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    160deg,
    #9b0000 0%,
    #cc0000 25%,
    #aa0000 45%,
    #bb0000 60%,
    #880000 80%,
    #cc0000 100%
  );
  display: flex;
  align-items: center;
  justify-content: center;
  filter: url(#flag-wave-filter);
  opacity: 0.22;
}

/* Движущееся освещение поверх ткани — имитация складок */
.flag-cloth-shading {
  position: absolute;
  inset: 0;
  background: repeating-linear-gradient(
    100deg,
    rgba(255,255,255,0.07) 0px,
    rgba(0,0,0,0.18) 80px,
    rgba(255,255,255,0.06) 160px,
    rgba(0,0,0,0.22) 240px,
    rgba(255,255,255,0.05) 320px
  );
  animation: shadingMove 7s ease-in-out infinite;
}

@keyframes shadingMove {
  0%   { background-position: 0px 0px; }
  50%  { background-position: 120px 0px; }
  100% { background-position: 0px 0px; }
}

.flag-cloth-emblem {
  position: relative;
  z-index: 1;
  font-size: clamp(14rem, 28vw, 26rem);
  color: rgba(212, 175, 55, 0.28);
  line-height: 1;
  user-select: none;
  animation: emblemDrift 7s ease-in-out infinite;
  filter: blur(0.5px);
}

@keyframes emblemDrift {
  0%   { transform: scale(1) translateY(0px); }
  35%  { transform: scale(1.015) translateY(-8px); }
  70%  { transform: scale(0.99) translateY(4px); }
  100% { transform: scale(1) translateY(0px); }
}

.hero-content {
  position: relative;
  z-index: 10;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 40px;
  text-align: center;
  padding: 60px 24px;
  max-width: 900px;
}

/* Emblem */
.emblem-ring {
  position: relative;
  width: 160px;
  height: 160px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: radial-gradient(circle, #1a0000 0%, #0d0000 60%, transparent 100%);
  border: 2px solid rgba(204, 0, 0, 0.5);
  box-shadow:
    0 0 40px rgba(204, 0, 0, 0.3),
    0 0 80px rgba(204, 0, 0, 0.15),
    inset 0 0 30px rgba(204, 0, 0, 0.1);
  animation: emblemPulse 3s ease-in-out infinite;
}

@keyframes emblemPulse {
  0%, 100% { box-shadow: 0 0 40px rgba(204,0,0,0.3), 0 0 80px rgba(204,0,0,0.15), inset 0 0 30px rgba(204,0,0,0.1); }
  50% { box-shadow: 0 0 60px rgba(204,0,0,0.5), 0 0 120px rgba(204,0,0,0.25), inset 0 0 40px rgba(204,0,0,0.2); }
}

.star-container {
  position: absolute;
  top: -18px;
  left: 50%;
  transform: translateX(-50%);
}

.red-star {
  font-size: 2.2rem;
  color: #ff1a1a;
  filter: drop-shadow(0 0 12px #ff1a1a);
  animation: starSpin 20s linear infinite;
  display: block;
}

@keyframes starSpin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.hammer-sickle {
  font-size: 5rem;
  color: #d4af37;
  filter: drop-shadow(0 0 20px rgba(212, 175, 55, 0.6));
  animation: goldGlow 2s ease-in-out infinite alternate;
  line-height: 1;
}

@keyframes goldGlow {
  from { filter: drop-shadow(0 0 15px rgba(212,175,55,0.4)); }
  to { filter: drop-shadow(0 0 30px rgba(212,175,55,0.8)) drop-shadow(0 0 60px rgba(212,175,55,0.3)); }
}

/* Hero text */
.hero-label {
  font-family: 'Courier New', monospace;
  font-size: 0.75rem;
  letter-spacing: 6px;
  color: #cc0000;
  text-transform: uppercase;
  border: 1px solid rgba(204, 0, 0, 0.4);
  padding: 6px 20px;
  border-radius: 2px;
  background: rgba(204, 0, 0, 0.05);
}

.hero-title {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin: 0;
  line-height: 1;
}

.title-ussr {
  font-size: clamp(5rem, 12vw, 9rem);
  font-weight: 900;
  color: #cc0000;
  text-shadow:
    0 0 40px rgba(204, 0, 0, 0.6),
    0 0 80px rgba(204, 0, 0, 0.3),
    4px 4px 0 #5a0000;
  letter-spacing: 12px;
  font-style: italic;
  animation: titleFlicker 8s ease-in-out infinite;
}

@keyframes titleFlicker {
  0%, 95%, 100% { opacity: 1; }
  96% { opacity: 0.85; }
  97% { opacity: 1; }
  98% { opacity: 0.9; }
}

.title-sub {
  font-size: clamp(0.9rem, 2.5vw, 1.4rem);
  color: #d4af37;
  letter-spacing: 8px;
  font-weight: 400;
  text-shadow: 0 0 20px rgba(212, 175, 55, 0.5);
}

.hero-desc {
  font-size: 1.1rem;
  color: #c0b090;
  line-height: 1.8;
  max-width: 600px;
  margin: 0;
}

/* Stats */
.hero-stats {
  display: flex;
  align-items: center;
  gap: 32px;
  background: rgba(204, 0, 0, 0.05);
  border: 1px solid rgba(204, 0, 0, 0.2);
  border-radius: 4px;
  padding: 20px 40px;
  backdrop-filter: blur(10px);
}

.stat-item { display: flex; flex-direction: column; align-items: center; gap: 4px; }
.stat-num { font-size: 2rem; font-weight: 900; color: #ff1a1a; font-family: 'Courier New', monospace; line-height: 1; }
.stat-label { font-size: 0.7rem; color: #888; letter-spacing: 2px; text-transform: uppercase; }
.stat-divider { width: 1px; height: 40px; background: rgba(204, 0, 0, 0.3); }

/* Side Flags */
.side-flag {
  position: absolute;
  bottom: 0;
  top: 0;
  display: flex;
  align-items: flex-start;
  padding-top: 80px;
  z-index: 3;
  opacity: 0.4;
}
.side-flag.left { left: 40px; }
.side-flag.right { right: 40px; flex-direction: row-reverse; }

.flag-body {
  width: 70px;
  height: 50px;
  background: linear-gradient(135deg, #cc0000, #8b0000);
  display: flex;
  align-items: center;
  justify-content: center;
  clip-path: polygon(0 0, 100% 0, 100% 80%, 50% 100%, 0 80%);
  box-shadow: 0 4px 20px rgba(204, 0, 0, 0.4);
  animation: flagWave 3s ease-in-out infinite;
}

@keyframes flagWave {
  0%, 100% { transform: rotate(-2deg); }
  50% { transform: rotate(2deg); }
}

.flag-emblem { color: #d4af37; font-size: 1.4rem; }
.flag-pole { width: 3px; height: 80%; background: linear-gradient(180deg, #d4af37, #8b6914); }

/* ===== ANTHEM ===== */
.anthem-section {
  background: #0f0000;
  border-top: 1px solid rgba(204, 0, 0, 0.3);
  border-bottom: 1px solid rgba(204, 0, 0, 0.3);
  padding: 0;
}

.anthem-container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 28px 32px;
  display: flex;
  align-items: center;
  gap: 32px;
}

.anthem-left { display: flex; align-items: center; gap: 16px; flex-shrink: 0; }
.anthem-icon { font-size: 2rem; filter: drop-shadow(0 0 10px rgba(212,175,55,0.6)); }
.anthem-title { font-size: 1rem; font-weight: 700; color: #e8e0d0; }
.anthem-composer { font-size: 0.75rem; color: #888; margin-top: 2px; }

.anthem-player { display: flex; align-items: center; gap: 16px; flex: 1; }

.anthem-btn {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  border: 2px solid #cc0000;
  background: rgba(204, 0, 0, 0.1);
  color: #cc0000;
  font-size: 1.2rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.25s;
  flex-shrink: 0;
}
.anthem-btn:hover, .anthem-btn.playing {
  background: #cc0000;
  color: #fff;
  box-shadow: 0 0 20px rgba(204, 0, 0, 0.5);
}

.anthem-progress-wrap { flex: 1; display: flex; flex-direction: column; gap: 6px; }

.anthem-progress-bar {
  height: 6px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 3px;
  cursor: pointer;
  position: relative;
  overflow: visible;
}

.anthem-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #8b0000, #cc0000, #ff4444);
  border-radius: 3px;
  position: relative;
  transition: width 0.1s linear;
}

.progress-thumb {
  position: absolute;
  right: -5px;
  top: 50%;
  transform: translateY(-50%);
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: #ff1a1a;
  box-shadow: 0 0 8px rgba(255, 26, 26, 0.8);
}

.anthem-time { display: flex; justify-content: space-between; font-size: 0.72rem; color: #666; font-family: 'Courier New', monospace; }

/* Waves */
.anthem-waves {
  display: flex;
  align-items: center;
  gap: 3px;
  flex-shrink: 0;
}
.anthem-waves span {
  display: block;
  width: 3px;
  height: 16px;
  background: #333;
  border-radius: 2px;
  transition: all 0.3s;
}
.anthem-waves.active span {
  background: #cc0000;
  animation: waveBar 0.8s ease-in-out infinite alternate;
  box-shadow: 0 0 6px rgba(204, 0, 0, 0.6);
}
.anthem-waves span:nth-child(1) { animation-duration: 0.6s; }
.anthem-waves span:nth-child(2) { animation-duration: 0.8s; }
.anthem-waves span:nth-child(3) { animation-duration: 0.5s; }
.anthem-waves span:nth-child(4) { animation-duration: 0.9s; }
.anthem-waves span:nth-child(5) { animation-duration: 0.7s; }
.anthem-waves span:nth-child(6) { animation-duration: 1.0s; }
.anthem-waves span:nth-child(7) { animation-duration: 0.65s; }

@keyframes waveBar {
  from { transform: scaleY(0.3); }
  to { transform: scaleY(1.8); }
}

/* ===== QUOTES ===== */
.quotes-section {
  background: #000;
  padding: 48px 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}

.quote-ticker {
  display: flex;
  align-items: center;
  gap: 24px;
  max-width: 800px;
  width: 100%;
}

.quote-star { color: #cc0000; font-size: 1.4rem; flex-shrink: 0; }
.quote-text { display: flex; flex-direction: column; align-items: center; gap: 8px; flex: 1; }
.quote-body { font-size: 1.3rem; color: #e8e0d0; text-align: center; font-style: italic; line-height: 1.6; }
.quote-author { font-size: 0.85rem; color: #888; letter-spacing: 2px; text-transform: uppercase; }

.quote-fade-enter-active, .quote-fade-leave-active { transition: all 0.4s ease; }
.quote-fade-enter-from { opacity: 0; transform: translateY(10px); }
.quote-fade-leave-to { opacity: 0; transform: translateY(-10px); }

.quote-dots { display: flex; gap: 8px; }
.quote-dot { width: 8px; height: 8px; border-radius: 50%; border: none; background: #333; cursor: pointer; transition: all 0.2s; }
.quote-dot.active { background: #cc0000; box-shadow: 0 0 8px rgba(204,0,0,0.6); }

/* ===== GAMES SECTION ===== */
.games-section {
  padding: 80px 24px;
  max-width: 1300px;
  margin: 0 auto;
}

.section-header {
  text-align: center;
  margin-bottom: 60px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.section-badge {
  font-family: 'Courier New', monospace;
  font-size: 0.72rem;
  letter-spacing: 5px;
  color: #cc0000;
  text-transform: uppercase;
  border: 1px solid rgba(204, 0, 0, 0.4);
  padding: 6px 18px;
  border-radius: 2px;
}

.section-title {
  font-size: clamp(1.8rem, 4vw, 2.8rem);
  color: #e8e0d0;
  margin: 0;
  font-weight: 900;
  letter-spacing: 2px;
}

.section-desc {
  font-size: 1rem;
  color: #888;
  margin: 0;
  max-width: 500px;
  line-height: 1.6;
}

.section-divider {
  display: flex;
  align-items: center;
  gap: 16px;
  width: 100%;
  justify-content: center;
  color: #cc0000;
  font-size: 1.2rem;
}
.section-divider::before,
.section-divider::after {
  content: '';
  flex: 1;
  max-width: 200px;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(204,0,0,0.4));
}
.section-divider::after { background: linear-gradient(90deg, rgba(204,0,0,0.4), transparent); }

/* Game Cards */
.games-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
  gap: 24px;
}

.game-card {
  position: relative;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.05);
  display: flex;
  flex-direction: column;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  animation: cardAppear 0.5s ease both;
  cursor: default;
}

@keyframes cardAppear {
  from { opacity: 0; transform: translateY(24px); }
  to { opacity: 1; transform: translateY(0); }
}

.game-card:hover {
  transform: translateY(-6px) scale(1.01);
  border-color: var(--accent);
  box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 30px color-mix(in srgb, var(--accent) 20%, transparent);
}

.game-card:hover .card-glow { opacity: 1; }

.card-glow {
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse 60% 40% at 50% 0%, color-mix(in srgb, var(--accent) 12%, transparent), transparent);
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.3s;
}

.card-top {
  padding: 20px 20px 0;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.card-emoji {
  font-size: 2.4rem;
  filter: drop-shadow(0 0 10px rgba(255,255,255,0.2));
}

.card-tag {
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 2px;
  padding: 4px 12px;
  border-radius: 2px;
  color: #fff;
  text-shadow: 0 1px 2px rgba(0,0,0,0.5);
}

.card-body {
  padding: 16px 20px;
  flex: 1;
}

.card-meta { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
.card-year { font-family: 'Courier New', monospace; font-size: 0.8rem; color: var(--accent); font-weight: 700; }
.card-genre {
  font-size: 0.72rem;
  color: #666;
  background: rgba(255,255,255,0.04);
  padding: 2px 8px;
  border-radius: 2px;
  border: 1px solid rgba(255,255,255,0.06);
}

.card-title {
  font-size: 1.4rem;
  font-weight: 900;
  color: #fff;
  margin: 0 0 4px;
  letter-spacing: 0.5px;
}

.card-studio { font-size: 0.8rem; color: var(--accent); margin: 0 0 12px; opacity: 0.8; }
.card-desc { font-size: 0.88rem; color: #aaa; line-height: 1.65; margin: 0; }

.card-footer {
  padding: 16px 20px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px solid rgba(255,255,255,0.05);
}

.card-rating { display: flex; align-items: center; gap: 6px; }
.rating-star { color: #d4af37; font-size: 1rem; }
.rating-num { font-size: 1.1rem; font-weight: 700; color: #d4af37; font-family: 'Courier New', monospace; }

.card-btn {
  font-size: 0.82rem;
  padding: 8px 18px;
  border-radius: 3px;
  border: 1px solid var(--accent);
  background: transparent;
  color: var(--accent);
  cursor: pointer;
  transition: all 0.2s;
  letter-spacing: 1px;
}
.card-btn:hover { background: var(--accent); color: #000; }

/* ===== MANIFESTO ===== */
.manifesto-section {
  position: relative;
  background: linear-gradient(135deg, #0f0000 0%, #1a0000 50%, #0f0000 100%);
  padding: 80px 24px;
  overflow: hidden;
  border-top: 1px solid rgba(204, 0, 0, 0.2);
  border-bottom: 1px solid rgba(204, 0, 0, 0.2);
}

.manifesto-bg {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.manifesto-star-left,
.manifesto-star-right {
  position: absolute;
  font-size: 20rem;
  color: rgba(204, 0, 0, 0.04);
  font-weight: 900;
  line-height: 1;
  top: 50%;
  transform: translateY(-50%);
}

.manifesto-star-left { left: -80px; }
.manifesto-star-right { right: -80px; }

.manifesto-content {
  max-width: 900px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  gap: 60px;
}

.manifesto-flag { flex-shrink: 0; }

.mflag-red {
  width: 120px;
  height: 80px;
  background: linear-gradient(135deg, #cc0000, #8b0000);
  border-radius: 4px 0 0 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 32px rgba(204, 0, 0, 0.4);
  border: 1px solid rgba(212, 175, 55, 0.3);
}

.mflag-emblem { font-size: 3.5rem; color: #d4af37; filter: drop-shadow(0 0 10px rgba(212,175,55,0.5)); }

.manifesto-text h2 {
  font-size: 1.8rem;
  color: #e8e0d0;
  margin: 0 0 16px;
  font-weight: 900;
}

.manifesto-text p { color: #aaa; line-height: 1.8; margin: 0 0 16px; font-size: 0.95rem; }

.manifesto-badges { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 24px; }
.mbadge {
  font-size: 0.8rem;
  padding: 6px 14px;
  border-radius: 3px;
  background: rgba(204, 0, 0, 0.1);
  border: 1px solid rgba(204, 0, 0, 0.3);
  color: #cc6666;
}

/* ===== TIMELINE ===== */
.timeline-section {
  padding: 80px 24px;
  max-width: 900px;
  margin: 0 auto;
}

.timeline {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 0;
}

.timeline::before {
  content: '';
  position: absolute;
  left: 50%;
  top: 0;
  bottom: 0;
  width: 2px;
  background: linear-gradient(180deg, transparent, #cc0000 10%, #cc0000 90%, transparent);
  transform: translateX(-50%);
}

.tl-item {
  display: flex;
  align-items: center;
  gap: 0;
  margin-bottom: 32px;
}

.tl-item.right { flex-direction: row-reverse; }

.tl-connector {
  flex-shrink: 0;
  width: 50%;
  display: flex;
  justify-content: center;
  position: relative;
}

.tl-item.right .tl-connector { justify-content: center; }

.tl-dot {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: #1a0000;
  border: 2px solid #cc0000;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  box-shadow: 0 0 20px rgba(204, 0, 0, 0.3);
  z-index: 2;
  position: relative;
}

.tl-card {
  width: calc(50% - 40px);
  background: linear-gradient(135deg, #0f0000, #1a0000);
  border: 1px solid rgba(204, 0, 0, 0.2);
  border-radius: 6px;
  padding: 20px;
  transition: border-color 0.3s;
}

.tl-card:hover { border-color: rgba(204, 0, 0, 0.5); }

.tl-year {
  font-size: 1.6rem;
  font-weight: 900;
  color: #cc0000;
  font-family: 'Courier New', monospace;
  margin-bottom: 6px;
}

.tl-text { font-size: 0.9rem; color: #aaa; line-height: 1.6; margin: 0; }

/* ===== BOTTOM BANNER ===== */
.bottom-banner {
  background: linear-gradient(90deg, #0f0000, #1a0000 30%, #200000 50%, #1a0000 70%, #0f0000);
  border-top: 2px solid #cc0000;
  padding: 48px 24px;
}

.bb-content {
  max-width: 700px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 32px;
  flex-wrap: wrap;
  text-align: center;
}

.bb-emblem {
  font-size: 4rem;
  color: #d4af37;
  filter: drop-shadow(0 0 20px rgba(212,175,55,0.5));
  animation: goldGlow 2s ease-in-out infinite alternate;
}

.bb-text h3 { font-size: 1.4rem; color: #e8e0d0; margin: 0 0 6px; font-weight: 900; }
.bb-text p { color: #888; margin: 0; font-size: 0.9rem; }

.bb-stars { display: flex; gap: 8px; color: #cc0000; font-size: 1.3rem; }
.bb-stars span { animation: starPulse 2s ease-in-out infinite; }
.bb-stars span:nth-child(1) { animation-delay: 0s; }
.bb-stars span:nth-child(2) { animation-delay: 0.2s; }
.bb-stars span:nth-child(3) { animation-delay: 0.4s; }
.bb-stars span:nth-child(4) { animation-delay: 0.6s; }
.bb-stars span:nth-child(5) { animation-delay: 0.8s; }

@keyframes starPulse {
  0%, 100% { opacity: 0.4; transform: scale(0.9); }
  50% { opacity: 1; transform: scale(1.1); }
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
  .hero-stats { flex-direction: column; gap: 16px; padding: 20px; }
  .stat-divider { width: 60px; height: 1px; }
  .anthem-container { flex-direction: column; gap: 20px; }
  .anthem-left { justify-content: center; }
  .games-grid { grid-template-columns: 1fr; }
  .manifesto-content { flex-direction: column; gap: 32px; text-align: center; }
  .manifesto-badges { justify-content: center; }
  .timeline::before { left: 24px; }
  .tl-item, .tl-item.right { flex-direction: column; align-items: flex-start; padding-left: 60px; }
  .tl-connector { width: auto; position: absolute; left: 0; }
  .tl-card { width: 100%; }
  .side-flag { display: none; }
}

@media (max-width: 480px) {
  .title-ussr { letter-spacing: 6px; }
  .hero-stats { width: 100%; }
  .anthem-waves { display: none; }
}
</style>
