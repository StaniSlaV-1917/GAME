<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useHead } from '@vueuse/head';
import { RouterLink } from 'vue-router';
import api from '../api/axios';

const employees = ref([]);

const roleInfo = {
  manager: {
    label: 'Посадник',
    tone: 'brass',
    help: 'Поможет с заказами, вопросами по активации ключей и возвратами.',
  },
  admin: {
    label: 'Старейшина',
    tone: 'ember',
    help: 'Управляет каталогом игр, хрониками и настройками оплота.',
  },
};
const getRoleInfo = (role) => roleInfo[role] || { label: role || 'Воин', tone: 'bronze', help: 'Член команды оплота.' };
const getInitials = (name) =>
  name ? name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase() : '?';

useHead({
  title: 'О клане — GameStore',
  meta: [
    { name: 'description', content: 'GameStore — оплот, где ковают цифровые ключи к игровым мирам. Наша миссия, уклад и гарантии.' },
    { property: 'og:type', content: 'website' },
    { property: 'og:title', content: 'О клане — GameStore' },
    { property: 'og:description', content: 'GameStore — оплот, где ковают цифровые ключи к игровым мирам. Наша миссия, уклад и гарантии.' },
    { name: 'robots', content: 'index, follow' },
  ],
});

const stats = [
  { num: '500+',    label: 'Клинков в оружейной' },
  { num: '12 000+', label: 'Воинов в рядах' },
  { num: '99.8%',  label: 'Ключей активировано' },
  { num: '24/7',   label: 'Горн не гаснет' },
];

// 6 ценностей — чередуем тон (ember / bronze / brass), чтобы разнообразить без пёстрого цвета
const values = [
  { title: 'Железная защита',    desc: 'Все транзакции защищены SSL-шифрованием. Ключи поставляются только от проверенных официальных дистрибьюторов.', tone: 'ember' },
  { title: 'Удар молнии',        desc: 'Ключ приходит на e-mail сразу после оплаты — без ожидания, без задержек, в любое время суток.',                   tone: 'brass' },
  { title: 'Стража на посту',    desc: 'Наша команда всегда на связи. Любой вопрос — от активации ключа до возврата — решается быстро.',                    tone: 'bronze' },
  { title: 'Честная ковка цены', desc: 'Мониторим рынок ежедневно и предлагаем лучшие цены без скрытых комиссий и наценок.',                                 tone: 'ember' },
  { title: 'Печать оригинала',   desc: 'Только официальные лицензии. Каждый ключ проверяется перед продажей и гарантированно активируется.',                 tone: 'brass' },
  { title: 'Все врата',          desc: 'Steam, Epic Games, GOG, Origin, Ubisoft Connect, Xbox, PlayStation — один оплот для всех платформ.',                 tone: 'bronze' },
];

const steps = [
  { num: 'I',   title: 'Выбор клинка',      desc: 'Воспользуйтесь поиском или фильтрами оружейной' },
  { num: 'II',  title: 'Сбор в заплечный',  desc: 'Оформите заказ за несколько ударов молота' },
  { num: 'III', title: 'Плата кованой',     desc: 'Visa, MasterCard, МИР — безопасная транзакция' },
  { num: 'IV',  title: 'Ключ в руке',       desc: 'Мгновенно на e-mail и в личном свитке' },
];

const setupReveal = () => {
  if (!revealObs) return;
  setTimeout(() => {
    document.querySelectorAll('.about-root .reveal').forEach(el => revealObs.observe(el));
  }, 100);
};

let revealObs = null;
onMounted(async () => {
  revealObs = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) { e.target.classList.add('is-visible'); revealObs.unobserve(e.target); }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
  setupReveal();
  try {
    const { data } = await api.get('/employees');
    employees.value = Array.isArray(data) ? data : (data.data || []);
    setupReveal();
  } catch (e) { console.error(e); }
});
onUnmounted(() => revealObs?.disconnect());
</script>

<template>
  <div class="about-root">

    <!-- ═══ HERO ═══ -->
    <section class="ab-hero">
      <div class="ab-hero-bg" aria-hidden="true">
        <div class="hero-ember hero-ember-1"></div>
        <div class="hero-ember hero-ember-2"></div>
        <div class="hero-grid"></div>
      </div>

      <div class="ab-hero-inner reveal">
        <span class="tribal-eyebrow">
          <span class="eb-spike"></span>
          Об оплоте
          <span class="eb-spike"></span>
        </span>
        <h1 class="ab-hero-title">
          Магазин игр,<br>
          <span class="ab-accent">которому доверяют</span>
        </h1>
        <p class="ab-hero-sub">
          GameStore — команда ковачей, собравшаяся, чтобы превратить покупку<br>
          лицензионных ключей в чистый, быстрый и честный удар молотом.
        </p>
      </div>
    </section>

    <!-- ═══ STATS ═══ -->
    <section class="ab-stats-wrap reveal">
      <div class="ab-inner">
        <div class="stats-grid">
          <div v-for="s in stats" :key="s.num" class="stat-item">
            <div class="stat-num">{{ s.num }}</div>
            <div class="stat-label">{{ s.label }}</div>
          </div>
        </div>
      </div>
    </section>

    <div class="ab-inner">

      <!-- ═══ MISSION ═══ -->
      <section class="mission-block reveal">
        <div class="mission-text">
          <span class="section-eyebrow">Наш уклад</span>
          <h2 class="section-title">Доступ к играм — простым и мгновенным</h2>
          <p class="section-body">
            Мы верим, что каждый воин заслуживает честного доступа к любимым играм.
            GameStore создан командой энтузиастов, которые сами прошли через
            неудобные платформы, завышенные цены и сомнительных продавцов —
            и решили сделать лучше.
          </p>
          <p class="section-body">
            Сегодня мы предлагаем сотни лицензионных ключей для всех популярных
            врат с гарантированной доставкой и стражей на каждом шаге.
          </p>
        </div>
        <div class="mission-visual">
          <div class="mv-card">
            <div class="mv-stat">500+</div>
            <div class="mv-label">клинков в оружейной</div>
            <span class="mv-rivet mv-rivet--tl"></span>
            <span class="mv-rivet mv-rivet--tr"></span>
            <span class="mv-rivet mv-rivet--bl"></span>
            <span class="mv-rivet mv-rivet--br"></span>
          </div>
          <div class="mv-card accent">
            <div class="mv-stat">~2 сек</div>
            <div class="mv-label">среднее время доставки</div>
            <span class="mv-rivet mv-rivet--tl"></span>
            <span class="mv-rivet mv-rivet--tr"></span>
            <span class="mv-rivet mv-rivet--bl"></span>
            <span class="mv-rivet mv-rivet--br"></span>
          </div>
          <div class="mv-card">
            <div class="mv-stat">4.9</div>
            <div class="mv-label">средний рейтинг</div>
            <span class="mv-rivet mv-rivet--tl"></span>
            <span class="mv-rivet mv-rivet--tr"></span>
            <span class="mv-rivet mv-rivet--bl"></span>
            <span class="mv-rivet mv-rivet--br"></span>
          </div>
          <div class="mv-card">
            <div class="mv-stat">100%</div>
            <div class="mv-label">безопасных сделок</div>
            <span class="mv-rivet mv-rivet--tl"></span>
            <span class="mv-rivet mv-rivet--tr"></span>
            <span class="mv-rivet mv-rivet--bl"></span>
            <span class="mv-rivet mv-rivet--br"></span>
          </div>
        </div>
      </section>

      <!-- ═══ VALUES ═══ -->
      <section class="values-section">
        <div class="section-head reveal">
          <span class="section-eyebrow">Наш кодекс</span>
          <h2 class="section-title">Почему к нам идут</h2>
        </div>
        <div class="values-grid">
          <div
            v-for="v in values" :key="v.title"
            class="value-card reveal"
            :class="`tone-${v.tone}`"
          >
            <span class="vc-rivet vc-rivet--tl" aria-hidden="true"></span>
            <span class="vc-rivet vc-rivet--tr" aria-hidden="true"></span>
            <div class="vc-spike" aria-hidden="true"></div>
            <h3 class="vc-title">{{ v.title }}</h3>
            <p class="vc-desc">{{ v.desc }}</p>
          </div>
        </div>
      </section>

      <!-- ═══ HOW IT WORKS ═══ -->
      <section class="how-section">
        <div class="section-head reveal">
          <span class="section-eyebrow">Ритуал</span>
          <h2 class="section-title">Четыре удара до игры</h2>
        </div>
        <div class="how-grid">
          <div
            v-for="(s, i) in steps" :key="s.num"
            class="how-card reveal"
            :style="{ '--delay': `${i * 0.1}s` }"
          >
            <div class="how-num">{{ s.num }}</div>
            <h3 class="how-title">{{ s.title }}</h3>
            <p class="how-desc">{{ s.desc }}</p>
            <div v-if="i < steps.length - 1" class="how-connector" aria-hidden="true"></div>
          </div>
        </div>
      </section>

      <!-- ═══ TEAM ═══ -->
      <section v-if="employees.length" class="team-section">
        <div class="section-head reveal">
          <span class="section-eyebrow">Совет старейшин</span>
          <h2 class="section-title">Те, кто держит горн</h2>
        </div>
        <div class="team-grid">
          <div
            v-for="emp in employees"
            :key="emp.id"
            class="team-card reveal"
            :class="`tone-${getRoleInfo(emp.role).tone}`"
          >
            <span class="tc-rivet tc-rivet--tl" aria-hidden="true"></span>
            <span class="tc-rivet tc-rivet--tr" aria-hidden="true"></span>
            <span class="tc-rivet tc-rivet--bl" aria-hidden="true"></span>
            <span class="tc-rivet tc-rivet--br" aria-hidden="true"></span>
            <div class="tc-avatar">
              <span class="tc-initials">{{ getInitials(emp.fullname) }}</span>
              <div class="tc-avatar-ring" aria-hidden="true"></div>
            </div>
            <div class="tc-body">
              <div class="tc-role-badge">{{ getRoleInfo(emp.role).label }}</div>
              <h3 class="tc-name">{{ emp.fullname }}</h3>
              <p class="tc-help">
                <span class="tc-help-label">Чем может помочь</span>
                {{ getRoleInfo(emp.role).help }}
              </p>
              <a v-if="emp.email" :href="`mailto:${emp.email}`" class="tc-contact">
                ✉ {{ emp.email }}
              </a>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══ CTA ═══ -->
      <section class="cta-block reveal">
        <div class="cta-glow" aria-hidden="true"></div>
        <span class="cta-rivet cta-rivet--tl" aria-hidden="true"></span>
        <span class="cta-rivet cta-rivet--tr" aria-hidden="true"></span>
        <span class="cta-rivet cta-rivet--bl" aria-hidden="true"></span>
        <span class="cta-rivet cta-rivet--br" aria-hidden="true"></span>
        <div class="cta-content">
          <span class="tribal-eyebrow">
            <span class="eb-spike"></span>
            Встань у горна
            <span class="eb-spike"></span>
          </span>
          <h2 class="cta-title">Готовы начать?</h2>
          <p class="cta-sub">Тысячи игр ждут вас — мгновенно, по честной цене.</p>
          <div class="cta-btns">
            <RouterLink to="/catalog" class="forge-btn primary">
              <span class="fb-label">Войти в оружейную →</span>
            </RouterLink>
            <RouterLink to="/register" class="forge-btn ghost">
              <span class="fb-label">Вписать имя</span>
            </RouterLink>
          </div>
        </div>
      </section>

    </div>
  </div>
</template>

<style scoped>
/* ══ Reveal ══ */
.reveal { opacity: 0; transform: translateY(28px); transition: opacity 0.65s var(--ease-smoke), transform 0.65s var(--ease-smoke); }
.reveal.is-visible { opacity: 1; transform: none; }

.about-root { color: var(--text-bone); min-height: 100vh; }
.ab-inner { max-width: 1140px; margin: 0 auto; padding: 0 24px; }

/* ══ HERO ══ */
.ab-hero {
  position: relative;
  overflow: hidden;
  padding: 96px 24px 84px;
  text-align: center;
}
.ab-hero-bg { position: absolute; inset: 0; pointer-events: none; z-index: 0; }
.hero-ember {
  position: absolute;
  border-radius: 50%;
  filter: blur(100px);
  opacity: 0.35;
}
.hero-ember-1 {
  width: 560px; height: 560px;
  background: radial-gradient(circle, var(--ember-glow) 0%, transparent 70%);
  top: -120px; left: -80px;
  animation: heroFloat 14s ease-in-out infinite;
}
.hero-ember-2 {
  width: 440px; height: 440px;
  background: radial-gradient(circle, var(--ember-heart) 0%, transparent 70%);
  bottom: -100px; right: -60px;
  animation: heroFloat 18s ease-in-out infinite reverse;
}
.hero-grid {
  position: absolute; inset: 0;
  background-image:
    linear-gradient(rgba(122, 93, 72, 0.07) 1px, transparent 1px),
    linear-gradient(90deg, rgba(122, 93, 72, 0.07) 1px, transparent 1px);
  background-size: 60px 60px;
  mask-image: radial-gradient(ellipse at center, black 30%, transparent 80%);
  -webkit-mask-image: radial-gradient(ellipse at center, black 30%, transparent 80%);
}
@keyframes heroFloat {
  0%, 100% { transform: translate(0, 0); }
  50%      { transform: translate(24px, -20px); }
}

.ab-hero-inner { position: relative; z-index: 2; max-width: 820px; margin: 0 auto; }
.ab-hero-title {
  font-family: var(--font-display);
  font-weight: var(--fw-black, 900);
  font-size: clamp(2.4rem, 5.5vw, 4.2rem);
  line-height: 1.1;
  color: var(--text-bright);
  margin: 0 0 22px;
  text-shadow: 0 4px 16px rgba(0, 0, 0, 0.55);
}
.ab-accent {
  background: linear-gradient(135deg,
    var(--ember-spark) 0%,
    var(--ember-glow) 35%,
    var(--ember-flame) 70%,
    var(--ember-heart) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.ab-hero-sub {
  font-family: var(--font-body);
  font-size: clamp(1rem, 1.4vw, 1.15rem);
  color: var(--text-parchment);
  line-height: 1.75;
  margin: 0;
}

/* ══ Tribal eyebrow (общая) ══ */
.tribal-eyebrow {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  font-family: var(--font-ui);
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: var(--bronze);
  margin-bottom: 22px;
}
.eb-spike {
  width: 0; height: 0;
  border-left: 5px solid transparent;
  border-right: 5px solid transparent;
  border-top: 7px solid var(--bronze);
  filter: drop-shadow(0 0 4px rgba(199, 154, 94, 0.55));
}

/* ══ STATS ══ */
.ab-stats-wrap {
  background: linear-gradient(180deg,
    var(--ash-coal) 0%,
    var(--ash-obsidian) 100%);
  border-top: 1px solid var(--iron-dark);
  border-bottom: 1px solid var(--iron-dark);
  padding: 46px 24px;
  box-shadow: var(--inset-forge);
}
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0;
}
.stat-item {
  text-align: center;
  padding: 12px 24px;
  border-right: 1px solid var(--iron-dark);
  position: relative;
}
.stat-item:last-child { border-right: none; }
.stat-item::before {
  content: '';
  position: absolute;
  top: 10%; bottom: 10%;
  right: -1px;
  width: 1px;
  background: linear-gradient(180deg,
    transparent 0%,
    var(--bronze) 50%,
    transparent 100%);
  opacity: 0.35;
}
.stat-item:last-child::before { display: none; }
.stat-num {
  font-family: var(--font-display);
  font-weight: var(--fw-black, 900);
  font-size: clamp(2rem, 3.5vw, 2.8rem);
  line-height: 1;
  margin-bottom: 8px;
  background: linear-gradient(180deg,
    var(--text-bright) 0%,
    var(--bronze) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.stat-label {
  font-family: var(--font-ui);
  font-size: 0.78rem;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  color: var(--text-ash);
}

/* ══ Общая секция ══ */
.ab-inner > section { padding: 80px 0; }
.section-head { margin-bottom: 44px; text-align: center; }
.section-eyebrow {
  display: inline-block;
  font-family: var(--font-ui);
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: var(--bronze);
  margin-bottom: 12px;
}
.section-title {
  font-family: var(--font-display);
  font-weight: var(--fw-black, 900);
  font-size: clamp(1.8rem, 3.5vw, 2.6rem);
  color: var(--text-bright);
  margin: 0;
  line-height: 1.15;
  text-shadow: 0 2px 12px rgba(0, 0, 0, 0.5);
}

/* ══ MISSION ══ */
.mission-block {
  display: grid;
  grid-template-columns: 1.1fr 1fr;
  gap: 56px;
  align-items: center;
}
.section-body {
  font-family: var(--font-body);
  font-size: 1rem;
  line-height: 1.85;
  color: var(--text-parchment);
  margin: 0 0 18px;
}
.section-body:last-child { margin-bottom: 0; }

/* Mission visual — 4 каменных тайла с заклёпками */
.mission-visual {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}
.mv-card {
  position: relative;
  padding: 26px 22px;
  text-align: center;
  background: linear-gradient(180deg,
    var(--ash-ironrust) 0%,
    var(--ash-stone) 100%);
  border: 1px solid var(--iron-mid);
  clip-path: var(--clip-forged-sm);
  box-shadow: var(--inset-iron-top), var(--shadow-cast);
  transition: transform 0.3s var(--ease-forge), box-shadow 0.3s var(--ease-smoke);
}
.mv-card:hover {
  transform: translateY(-4px);
  box-shadow:
    var(--inset-iron-top),
    var(--shadow-cast),
    var(--glow-ember-soft, 0 0 18px rgba(226, 67, 16, 0.25));
}
.mv-card.accent {
  grid-column: span 2;
  background: linear-gradient(135deg,
    var(--ash-bloodrock) 0%,
    var(--ash-forge) 100%);
  border-color: var(--bronze-dark);
}
.mv-card.accent .mv-stat {
  color: var(--ember-gold);
}
.mv-stat {
  font-family: var(--font-display);
  font-weight: var(--fw-black, 900);
  font-size: 1.7rem;
  color: var(--text-bright);
  line-height: 1.1;
  margin-bottom: 4px;
}
.mv-label {
  font-family: var(--font-ui);
  font-size: 0.78rem;
  letter-spacing: 1px;
  text-transform: uppercase;
  color: var(--text-ash);
}

.mv-rivet {
  position: absolute;
  width: 7px; height: 7px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%,
    var(--brass) 0%,
    var(--bronze) 50%,
    var(--iron-void) 100%);
  box-shadow:
    inset -1px -1px 1px rgba(0, 0, 0, 0.7),
    0 0 3px rgba(199, 154, 94, 0.35);
}
.mv-rivet--tl { top: 10px; left: 10px; }
.mv-rivet--tr { top: 10px; right: 10px; }
.mv-rivet--bl { bottom: 10px; left: 10px; }
.mv-rivet--br { bottom: 10px; right: 10px; }

/* ══ VALUES ══ */
.values-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 18px;
}
.value-card {
  position: relative;
  padding: 32px 26px 28px;
  background: linear-gradient(180deg,
    var(--ash-stone) 0%,
    var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  clip-path: var(--clip-forged-sm);
  box-shadow: var(--inset-iron-top), var(--shadow-cast);
  overflow: hidden;
  transition: transform 0.3s var(--ease-forge), box-shadow 0.3s var(--ease-smoke);
}
.value-card:hover {
  transform: translateY(-6px);
  box-shadow:
    var(--inset-iron-top),
    var(--shadow-cast),
    0 0 24px var(--tone-glow, rgba(226, 67, 16, 0.25));
}
.value-card.tone-ember  { --tone-color: var(--ember-flame); --tone-glow: rgba(226, 67, 16, 0.3); }
.value-card.tone-bronze { --tone-color: var(--bronze);      --tone-glow: rgba(199, 154, 94, 0.3); }
.value-card.tone-brass  { --tone-color: var(--brass);       --tone-glow: rgba(255, 201, 121, 0.3); }

/* Шип вверху вместо круглой иконки */
.vc-spike {
  width: 0; height: 0;
  border-left: 10px solid transparent;
  border-right: 10px solid transparent;
  border-top: 14px solid var(--tone-color);
  filter: drop-shadow(0 0 6px var(--tone-glow));
  margin-bottom: 18px;
}
.vc-rivet {
  position: absolute;
  width: 7px; height: 7px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%,
    var(--brass) 0%,
    var(--bronze) 50%,
    var(--iron-void) 100%);
  box-shadow: inset -1px -1px 1px rgba(0, 0, 0, 0.7);
}
.vc-rivet--tl { top: 12px; left: 12px; }
.vc-rivet--tr { top: 12px; right: 12px; }

.vc-title {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.15rem;
  color: var(--text-bright);
  margin: 0 0 10px;
  letter-spacing: 0.3px;
}
.vc-desc {
  font-family: var(--font-body);
  font-size: 0.92rem;
  color: var(--text-parchment);
  line-height: 1.7;
  margin: 0;
}

/* ══ HOW IT WORKS ══ */
.how-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0;
  position: relative;
}
.how-card {
  position: relative;
  padding: 30px 22px;
  background: linear-gradient(180deg,
    var(--ash-stone) 0%,
    var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  text-align: center;
  margin: 0 7px;
  box-shadow: var(--inset-iron-top), var(--shadow-cast);
  transition: transform 0.3s var(--ease-forge), box-shadow 0.3s var(--ease-smoke);
  transition-delay: var(--delay, 0s);
  clip-path: var(--clip-forged-sm);
}
.how-card:hover {
  transform: translateY(-6px);
  box-shadow: var(--inset-iron-top), var(--shadow-cast), var(--glow-ember);
}
.how-connector {
  position: absolute;
  top: 44px;
  right: -18px;
  width: 30px;
  height: 2px;
  background: linear-gradient(90deg,
    var(--bronze) 0%,
    var(--brass) 50%,
    var(--bronze) 100%);
  opacity: 0.55;
  z-index: 1;
}
.how-num {
  font-family: var(--font-display);
  font-weight: var(--fw-black, 900);
  font-size: 2rem;
  line-height: 1;
  color: var(--ember-gold);
  margin-bottom: 14px;
  text-shadow: 0 0 12px rgba(255, 201, 121, 0.4);
  letter-spacing: 1px;
}
.how-title {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.05rem;
  color: var(--text-bright);
  margin: 0 0 10px;
  letter-spacing: 0.3px;
}
.how-desc {
  font-family: var(--font-body);
  font-size: 0.88rem;
  color: var(--text-parchment);
  line-height: 1.65;
  margin: 0;
}

/* ══ TEAM ══ */
.team-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 22px;
}
.team-card {
  position: relative;
  display: flex;
  gap: 22px;
  align-items: flex-start;
  padding: 28px;
  background: linear-gradient(180deg,
    var(--ash-ironrust) 0%,
    var(--ash-stone) 45%,
    var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  clip-path: var(--clip-forged-sm);
  box-shadow: var(--inset-iron-top), var(--shadow-cast);
  overflow: hidden;
  transition: transform 0.3s var(--ease-forge), box-shadow 0.3s var(--ease-smoke);
}
.team-card:hover {
  transform: translateY(-4px);
  box-shadow:
    var(--inset-iron-top),
    var(--shadow-cast),
    0 0 30px var(--tone-glow, rgba(226, 67, 16, 0.25));
}
.team-card.tone-ember  { --tone-color: var(--ember-flame); --tone-glow: rgba(226, 67, 16, 0.3); }
.team-card.tone-bronze { --tone-color: var(--bronze);      --tone-glow: rgba(199, 154, 94, 0.3); }
.team-card.tone-brass  { --tone-color: var(--brass);       --tone-glow: rgba(255, 201, 121, 0.3); }

.tc-rivet {
  position: absolute;
  width: 7px; height: 7px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%,
    var(--brass) 0%,
    var(--bronze) 50%,
    var(--iron-void) 100%);
}
.tc-rivet--tl { top: 12px; left: 12px; }
.tc-rivet--tr { top: 12px; right: 12px; }
.tc-rivet--bl { bottom: 12px; left: 12px; }
.tc-rivet--br { bottom: 12px; right: 12px; }

.tc-avatar {
  position: relative;
  width: 68px; height: 68px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: var(--grad-iron);
  border: 1px solid var(--bronze-dark);
  clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
  box-shadow: var(--inset-iron-top), 0 0 12px var(--tone-glow);
}
.tc-avatar-ring {
  position: absolute;
  inset: 4px;
  clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
  border: 1px solid var(--tone-color);
  opacity: 0.65;
}
.tc-initials {
  font-family: var(--font-display);
  font-size: 1.3rem;
  font-weight: var(--fw-black, 900);
  color: var(--text-bright);
  letter-spacing: 1px;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.6);
  z-index: 1;
}

.tc-body { flex: 1; min-width: 0; }
.tc-role-badge {
  display: inline-block;
  font-family: var(--font-ui);
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 2px;
  text-transform: uppercase;
  padding: 4px 11px;
  color: var(--tone-color);
  background: rgba(8, 6, 10, 0.6);
  border: 1px solid currentColor;
  margin-bottom: 10px;
}
.tc-name {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.12rem;
  color: var(--text-bright);
  margin: 0 0 12px;
  letter-spacing: 0.3px;
}
.tc-help {
  font-family: var(--font-body);
  font-size: 0.88rem;
  color: var(--text-parchment);
  line-height: 1.65;
  margin: 0 0 14px;
}
.tc-help-label {
  display: block;
  font-family: var(--font-ui);
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  color: var(--text-ash);
  margin-bottom: 4px;
}
.tc-contact {
  font-family: var(--font-ui);
  font-size: 0.85rem;
  color: var(--ember-spark);
  text-decoration: none;
  border-bottom: 1px solid rgba(255, 167, 88, 0.35);
  transition: color 0.2s var(--ease-smoke), border-color 0.2s var(--ease-smoke);
  word-break: break-all;
}
.tc-contact:hover {
  color: var(--ember-gold);
  border-bottom-color: var(--ember-gold);
}

/* ══ CTA ══ */
.cta-block {
  position: relative;
  overflow: hidden;
  background: linear-gradient(180deg,
    var(--ash-ironrust) 0%,
    var(--ash-stone) 45%,
    var(--ash-coal) 100%);
  border: 1px solid var(--bronze-dark);
  clip-path: var(--clip-forged-md);
  padding: 68px 56px;
  text-align: center;
  box-shadow: var(--inset-iron-top), var(--shadow-deep);
}
.cta-glow {
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at center bottom,
    rgba(226, 67, 16, 0.22) 0%,
    transparent 70%);
  pointer-events: none;
}
.cta-rivet {
  position: absolute;
  width: 11px; height: 11px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%,
    var(--brass) 0%,
    var(--bronze) 50%,
    var(--iron-void) 100%);
  box-shadow: inset -1px -1px 2px rgba(0, 0, 0, 0.7), 0 0 5px rgba(199, 154, 94, 0.45);
}
.cta-rivet--tl { top: 18px; left: 18px; }
.cta-rivet--tr { top: 18px; right: 18px; }
.cta-rivet--bl { bottom: 18px; left: 18px; }
.cta-rivet--br { bottom: 18px; right: 18px; }

.cta-content { position: relative; z-index: 1; }
.cta-title {
  font-family: var(--font-display);
  font-weight: var(--fw-black, 900);
  font-size: clamp(2rem, 3.5vw, 2.8rem);
  color: var(--text-bright);
  margin: 0 0 14px;
  text-shadow: 0 3px 16px rgba(0, 0, 0, 0.6);
}
.cta-sub {
  font-family: var(--font-body);
  font-size: 1.1rem;
  color: var(--text-parchment);
  margin: 0 0 34px;
}
.cta-btns {
  display: flex;
  gap: 16px;
  justify-content: center;
  flex-wrap: wrap;
}

/* ── общая кованая кнопка (также используется в hero-CTA) ── */
.forge-btn {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 15px 34px;
  border: 1px solid var(--ember-heart);
  background: var(--grad-ember);
  color: var(--text-bright);
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1rem;
  letter-spacing: 1.3px;
  text-transform: uppercase;
  text-decoration: none;
  cursor: pointer;
  overflow: hidden;
  box-shadow:
    var(--inset-iron-top),
    inset 0 -2px 3px rgba(0, 0, 0, 0.35),
    var(--glow-ember);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.65);
  transition: transform 0.18s var(--ease-forge), box-shadow 0.2s var(--ease-smoke);
  clip-path: var(--clip-forged-sm);
}
.forge-btn::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg,
    transparent 0%,
    rgba(255, 201, 121, 0.4) 50%,
    transparent 100%);
  transform: translateX(-120%);
  transition: transform 0.6s var(--ease-smoke);
  pointer-events: none;
}
.forge-btn:hover {
  transform: translateY(-2px);
  box-shadow:
    var(--inset-iron-top),
    inset 0 -2px 3px rgba(0, 0, 0, 0.35),
    var(--glow-ember-strong);
}
.forge-btn:hover::after { transform: translateX(120%); }

.forge-btn.ghost {
  background: transparent;
  border-color: var(--bronze-dark);
  color: var(--text-parchment);
  box-shadow: var(--inset-iron-top);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
}
.forge-btn.ghost:hover {
  background: rgba(122, 93, 72, 0.15);
  border-color: var(--bronze);
  color: var(--text-bright);
  box-shadow: var(--inset-iron-top), 0 0 14px rgba(199, 154, 94, 0.3);
}

.fb-label { position: relative; z-index: 1; }

/* ── responsive ── */
@media (max-width: 1024px) {
  .mission-block { grid-template-columns: 1fr; gap: 40px; }
  .values-grid { grid-template-columns: repeat(2, 1fr); }
  .how-grid { grid-template-columns: repeat(2, 1fr); gap: 14px; }
  .how-connector { display: none; }
  .how-card { margin: 0; }
}
@media (max-width: 640px) {
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
  .stat-item { border-right: none; border-bottom: 1px solid var(--iron-dark); padding: 16px; }
  .stat-item:nth-child(odd) { border-right: 1px solid var(--iron-dark); }
  .stat-item:nth-last-child(-n+2) { border-bottom: none; }
  .values-grid { grid-template-columns: 1fr; }
  .how-grid { grid-template-columns: 1fr; }
  .cta-block { padding: 46px 24px; }
  .ab-hero { padding: 72px 24px 60px; }
  .mv-card.accent { grid-column: auto; }
}
</style>
