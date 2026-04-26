<script setup>
import { ref, computed, nextTick, watch, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import { storeToRefs } from 'pinia';
import api from '../api/axios';

const authStore = useAuthStore();
const { user, isLoggedIn } = storeToRefs(authStore);

// ── State ──────────────────────────────────────────────────────────────────
const isOpen = ref(false);
const attractMode = ref(false);   // вкладка-стрелка "приглашает" — слегка выезжает
const tabHover = ref(false);      // при наведении вкладка приоткрывается
const chatBodyRef = ref(null);

// Conversation state
const messages = ref([]);          // { id, from: 'bot'|'user', text, options? }
const problemPath = ref([]);       // breadcrumb trail e.g. ['Заказы', 'Не пришёл ключ']
const stage = ref('tree');         // 'tree' | 'form' | 'done'

// Contact form
const formEmail = ref('');
const formMessage = ref('');
const formSending = ref(false);
const formError = ref('');

let msgId = 0;
const nextId = () => ++msgId;

// ── Decision tree ──────────────────────────────────────────────────────────
const TREE = {
  root: {
    text: 'Здравствуйте! Я бот поддержки GameStore. Выберите категорию вашего вопроса:',
    options: [
      { label: 'Заказы и оплата',       next: 'orders'  },
      { label: 'Игры и ключи',          next: 'games'   },
      { label: 'Аккаунт',              next: 'account' },
      { label: 'Технические проблемы', next: 'tech'    },
      { label: 'Другой вопрос',        next: 'other'   },
    ],
  },

  orders: {
    text: 'Что именно не так с заказом?',
    options: [
      { label: 'Ключ не пришёл на почту',  next: 'leaf', leaf: 'Заказы → Ключ не пришёл на почту' },
      { label: 'Ключ не работает',         next: 'leaf', leaf: 'Заказы → Ключ не работает' },
      { label: 'Хочу вернуть заказ',       next: 'leaf', leaf: 'Заказы → Возврат заказа' },
      { label: 'Двойное списание средств', next: 'leaf', leaf: 'Заказы → Двойное списание' },
      { label: 'Другой вопрос по заказу',  next: 'leaf', leaf: 'Заказы → Другое' },
      { label: '← Назад',                 next: 'root', back: true },
    ],
  },

  games: {
    text: 'Что именно произошло с игрой?',
    options: [
      { label: 'Ключ не активируется',     next: 'leaf', leaf: 'Игры → Ключ не активируется' },
      { label: 'Регион не подходит',       next: 'leaf', leaf: 'Игры → Регион ключа' },
      { label: 'Игра не запускается',      next: 'leaf', leaf: 'Игры → Игра не запускается' },
      { label: 'Неверное описание игры',   next: 'leaf', leaf: 'Игры → Неверное описание' },
      { label: 'Другой вопрос по игре',    next: 'leaf', leaf: 'Игры → Другое' },
      { label: '← Назад',                 next: 'root', back: true },
    ],
  },

  account: {
    text: 'С чем возникли трудности в аккаунте?',
    options: [
      { label: 'Не могу войти',            next: 'leaf', leaf: 'Аккаунт → Не могу войти' },
      { label: 'Забыл пароль',             next: 'leaf', leaf: 'Аккаунт → Забыт пароль' },
      { label: 'Сменить email',            next: 'leaf', leaf: 'Аккаунт → Смена email' },
      { label: 'Удалить аккаунт',          next: 'leaf', leaf: 'Аккаунт → Удаление аккаунта' },
      { label: 'Другой вопрос по аккаунту',next: 'leaf', leaf: 'Аккаунт → Другое' },
      { label: '← Назад',                 next: 'root', back: true },
    ],
  },

  tech: {
    text: 'Опиши техническую проблему:',
    options: [
      { label: 'Сайт не загружается',      next: 'leaf', leaf: 'Техника → Сайт не загружается' },
      { label: 'Ошибка при оплате',        next: 'leaf', leaf: 'Техника → Ошибка оплаты' },
      { label: 'Проблема с корзиной',      next: 'leaf', leaf: 'Техника → Проблема с корзиной' },
      { label: 'Другая техническая ошибка',next: 'leaf', leaf: 'Техника → Другое' },
      { label: '← Назад',                 next: 'root', back: true },
    ],
  },

  other: {
    text: 'Хорошо! Напиши своё обращение — мы передадим его команде поддержки.',
    options: [
      { label: '← Назад',                 next: 'root', back: true },
      { label: 'Написать в поддержку',    next: 'form', leaf: 'Другое' },
    ],
  },
};

// ── Helpers ────────────────────────────────────────────────────────────────
const addBotMessage = (text, options = null) => {
  messages.value.push({ id: nextId(), from: 'bot', text, options });
  scrollToBottom();
};

const addUserMessage = (text) => {
  messages.value.push({ id: nextId(), from: 'user', text });
  scrollToBottom();
};

const scrollToBottom = () => {
  nextTick(() => {
    if (chatBodyRef.value) {
      chatBodyRef.value.scrollTop = chatBodyRef.value.scrollHeight;
    }
  });
};

// ── Open / Close ───────────────────────────────────────────────────────────
const openChat = () => {
  if (messages.value.length === 0) {
    startConversation();
  }
  isOpen.value = true;
  attractMode.value = false;  // выкл. "пощёчину" — пользователь уже зашёл
};

const closeChat = () => {
  isOpen.value = false;
};

const startConversation = () => {
  messages.value = [];
  problemPath.value = [];
  stage.value = 'tree';
  formEmail.value = isLoggedIn.value && user.value?.email ? user.value.email : '';
  formMessage.value = '';
  formError.value = '';
  const node = TREE.root;
  addBotMessage(node.text, node.options);
};

const resetChat = () => {
  messages.value = [];
  problemPath.value = [];
  stage.value = 'tree';
  formEmail.value = isLoggedIn.value && user.value?.email ? user.value.email : '';
  formMessage.value = '';
  formError.value = '';
  const node = TREE.root;
  addBotMessage(node.text, node.options);
};

// ── Option click ───────────────────────────────────────────────────────────
const handleOption = (opt) => {
  if (opt.back) {
    addUserMessage(opt.label);
    setTimeout(() => {
      const node = TREE[opt.next];
      addBotMessage(node.text, node.options);
    }, 300);
    return;
  }

  if (opt.next === 'form' || opt.next === 'leaf') {
    addUserMessage(opt.label);
    if (opt.leaf) problemPath.value.push(opt.leaf);

    const pathStr = problemPath.value.join(' → ');
    setTimeout(() => {
      addBotMessage(
        `Понял! Тема: «${pathStr}».\n\nЗаполни форму ниже, и я отправлю твоё обращение нашей команде поддержки. Мы ответим тебе на указанный email.`,
        null
      );
      stage.value = 'form';
      scrollToBottom();
    }, 350);
    return;
  }

  addUserMessage(opt.label);
  const node = TREE[opt.next];
  if (node) {
    if (!opt.back && opt.label !== '← Назад') {
      problemPath.value.push(opt.label);
    }
    setTimeout(() => {
      addBotMessage(node.text, node.options);
    }, 300);
  }
};

const lastBotMessageWithOptions = computed(() => {
  for (let i = messages.value.length - 1; i >= 0; i--) {
    if (messages.value[i].from === 'bot' && messages.value[i].options) {
      return messages.value[i].id;
    }
  }
  return null;
});

// ── Submit form ────────────────────────────────────────────────────────────
const submitForm = async () => {
  formError.value = '';
  if (!formEmail.value.trim() || !formEmail.value.includes('@')) {
    formError.value = 'Введи корректный email для ответа.';
    return;
  }
  if (formMessage.value.trim().length < 10) {
    formError.value = 'Опиши проблему подробнее (минимум 10 символов).';
    return;
  }

  formSending.value = true;
  try {
    await api.post('/support/send', {
      user_email:   formEmail.value.trim(),
      problem_path: problemPath.value.join(' → ') || 'Другое',
      message:      formMessage.value.trim(),
      user_name:    isLoggedIn.value && user.value?.fullname ? user.value.fullname : null,
    });
    stage.value = 'done';
    addBotMessage('Обращение отправлено. Мы получили ваше письмо и ответим в ближайшее время.');
  } catch (e) {
    const msg = e?.response?.data?.message || 'Не удалось отправить. Попробуй ещё раз.';
    formError.value = msg;
  } finally {
    formSending.value = false;
  }
};

// Sync email if user logs in while chat is open
watch(
  () => user.value?.email,
  (email) => {
    if (email && !formEmail.value) formEmail.value = email;
  }
);

// Auto-scroll when new messages arrive
watch(() => messages.value.length, () => scrollToBottom());

// ── Auto-attract: вкладка иногда сама высовывается, чтобы напомнить о себе ──
// Логика: первое "приглашение" через 45 сек после загрузки, далее раз в ~120 сек.
// Если чат открыт — не дёргаемся.
let firstAttractTimer = null;
let attractInterval = null;
let attractEndTimer = null;

const triggerAttract = () => {
  if (isOpen.value) return;
  attractMode.value = true;
  clearTimeout(attractEndTimer);
  attractEndTimer = setTimeout(() => {
    attractMode.value = false;
  }, 2400); // выезжает на ~2.4 сек, потом возвращается
};

onMounted(() => {
  // Первый "поклон" вкладки через 45 секунд
  firstAttractTimer = setTimeout(() => {
    triggerAttract();
    // Далее изредка (раз в 120-180 сек, рандомизированно), но только если чат закрыт
    attractInterval = setInterval(() => {
      if (!isOpen.value) triggerAttract();
    }, 150000);
  }, 45000);
});

onUnmounted(() => {
  clearTimeout(firstAttractTimer);
  clearTimeout(attractEndTimer);
  clearInterval(attractInterval);
});
</script>

<template>
  <Teleport to="body">
    <div class="support-chat-root">

      <!-- ──────────────── Edge tab — стрелка-вкладка из правого края ──────────────── -->
      <button
        v-if="!isOpen"
        class="edge-tab"
        :class="{ 'is-attract': attractMode, 'is-hover': tabHover }"
        @click="openChat"
        @mouseenter="tabHover = true"
        @mouseleave="tabHover = false"
        title="Поддержка"
        aria-label="Открыть чат поддержки"
      >
        <span class="edge-tab-arrow" aria-hidden="true">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"/>
          </svg>
        </span>
        <span class="edge-tab-icon" aria-hidden="true">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>
        </span>
        <span class="edge-tab-label">ПОДДЕРЖКА</span>
        <span class="edge-tab-pulse" aria-hidden="true"></span>
      </button>

      <!-- ──────────────── Chat panel ──────────────── -->
      <Transition name="chat-panel">
        <div v-if="isOpen" class="chat-panel" role="dialog" aria-label="Чат поддержки">

          <div class="chat-header">
            <div class="chat-header-left">
              <div class="chat-avatar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>
              </div>
              <div>
                <p class="chat-title">Поддержка GameStore</p>
                <p class="chat-subtitle"><span class="online-dot"></span> Онлайн</p>
              </div>
            </div>
            <div class="chat-header-actions">
              <button class="chat-icon-btn" @click="resetChat" title="Начать заново">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.95"/></svg>
              </button>
              <button class="chat-icon-btn" @click="closeChat" title="Закрыть">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
          </div>

          <div v-if="problemPath.length" class="chat-path">
            <span v-for="(p, i) in problemPath" :key="i" class="path-part">
              <span v-if="i > 0" class="path-sep">›</span>
              {{ p }}
            </span>
          </div>

          <div class="chat-body" ref="chatBodyRef">
            <TransitionGroup name="msg">
              <div
                v-for="msg in messages"
                :key="msg.id"
                class="msg-row"
                :class="{ 'msg-bot': msg.from === 'bot', 'msg-user': msg.from === 'user' }"
              >
                <div v-if="msg.from === 'bot'" class="bot-icon-wrap">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>
                </div>
                <div class="msg-bubble" :class="{ 'bubble-bot': msg.from === 'bot', 'bubble-user': msg.from === 'user' }">
                  <span class="bubble-text">{{ msg.text }}</span>

                  <div
                    v-if="msg.options && msg.id === lastBotMessageWithOptions && stage === 'tree'"
                    class="msg-options"
                  >
                    <button
                      v-for="opt in msg.options"
                      :key="opt.label"
                      class="option-btn"
                      :class="{ 'option-back': opt.back }"
                      @click="handleOption(opt)"
                    >
                      {{ opt.label }}
                    </button>
                  </div>
                </div>
              </div>
            </TransitionGroup>

            <Transition name="form-slide">
              <div v-if="stage === 'form'" class="contact-form-wrap">
                <div class="contact-form">
                  <label class="cf-label">
                    <span>Ваш email для ответа</span>
                    <input
                      v-model="formEmail"
                      type="email"
                      class="cf-input"
                      placeholder="example@mail.ru"
                      autocomplete="email"
                    />
                  </label>
                  <label class="cf-label">
                    <span>Описание проблемы</span>
                    <textarea
                      v-model="formMessage"
                      class="cf-textarea"
                      rows="4"
                      placeholder="Опишите подробнее, что произошло..."
                      maxlength="3000"
                    ></textarea>
                    <span class="cf-counter">{{ formMessage.length }}/3000</span>
                  </label>
                  <p v-if="formError" class="cf-error">{{ formError }}</p>
                  <button class="cf-submit" :disabled="formSending" @click="submitForm">
                    <span v-if="!formSending">Отправить обращение</span>
                    <span v-else class="cf-spinner"></span>
                  </button>
                </div>
              </div>
            </Transition>

            <Transition name="form-slide">
              <div v-if="stage === 'done'" class="done-actions">
                <button class="cf-submit" @click="resetChat">Новое обращение</button>
              </div>
            </Transition>
          </div>
        </div>
      </Transition>

    </div>
  </Teleport>
</template>

<style scoped>
/* ==========================================================
   SUPPORT CHAT · Edge-tab + sliding panel
   ========================================================== */

.support-chat-root {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 9999;
}
.support-chat-root > * { pointer-events: all; }

/* ──────────────────────────────────────────────────────────
   EDGE TAB — кованая вкладка-стрелка из правой стенки
   ────────────────────────────────────────────────────────── */
.edge-tab {
  position: absolute;
  right: 0;
  top: 50%;
  /* По умолчанию большая часть скрыта за краем — торчит только полоска со стрелкой */
  transform: translate(calc(100% - 26px), -50%);
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 18px 12px 12px;
  height: 52px;
  border: 1px solid var(--ember-heart);
  border-right: none;
  background: var(--grad-ember);
  color: var(--text-bright);
  font-family: var(--font-display);
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 1.4px;
  text-transform: uppercase;
  cursor: pointer;
  border-radius: 8px 0 0 8px;
  box-shadow:
    var(--inset-iron-top),
    inset 0 -2px 3px rgba(0, 0, 0, 0.35),
    -6px 0 18px rgba(0, 0, 0, 0.45),
    var(--glow-ember);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
  transition: transform 0.45s var(--ease-forge), box-shadow 0.3s var(--ease-smoke);
  user-select: none;
}
/* Hover — выезжает целиком + иконка/лейбл видны */
.edge-tab:hover,
.edge-tab.is-hover {
  transform: translate(0, -50%);
  box-shadow:
    var(--inset-iron-top),
    inset 0 -2px 3px rgba(0, 0, 0, 0.35),
    -10px 0 28px rgba(226, 67, 16, 0.5),
    var(--glow-ember-strong);
}
/* Auto-attract — вкладка сама приоткрывается на ~2.4 сек, мягким двойным "вздохом" */
.edge-tab.is-attract {
  animation: edgeTabAttract 2.4s var(--ease-forge) both;
}
@keyframes edgeTabAttract {
  0%   { transform: translate(calc(100% - 26px), -50%); }
  18%  { transform: translate(0, -50%); }
  42%  { transform: translate(calc(100% - 60px), -50%); }
  62%  { transform: translate(0, -50%); }
  100% { transform: translate(calc(100% - 26px), -50%); }
}

.edge-tab-arrow {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 16px;
  color: var(--text-bright);
  flex-shrink: 0;
  filter: drop-shadow(0 1px 2px rgba(0,0,0,0.5));
  transition: transform 0.3s var(--ease-forge);
}
.edge-tab:hover .edge-tab-arrow,
.edge-tab.is-hover .edge-tab-arrow {
  transform: translateX(-2px);
}

.edge-tab-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  flex-shrink: 0;
  color: var(--text-bright);
  filter: drop-shadow(0 1px 2px rgba(0,0,0,0.5));
}

.edge-tab-label {
  white-space: nowrap;
  font-family: var(--font-display);
}

/* Пульсирующий ореол для привлечения внимания (постоянно, но мягко) */
.edge-tab-pulse {
  position: absolute;
  inset: -3px;
  border-radius: 10px 0 0 10px;
  border: 1px solid var(--ember-glow);
  opacity: 0;
  pointer-events: none;
  animation: edgePulse 3.2s var(--ease-smoke) infinite;
}
@keyframes edgePulse {
  0%   { opacity: 0;    transform: scale(0.96); }
  35%  { opacity: 0.55; transform: scale(1.04); }
  70%  { opacity: 0;    transform: scale(1.08); }
  100% { opacity: 0;    transform: scale(1.08); }
}

/* ──────────────────────────────────────────────────────────
   CHAT PANEL — выезжает справа, прижата к нижне-правому углу
   ────────────────────────────────────────────────────────── */
.chat-panel {
  position: absolute;
  bottom: 28px;
  right: 28px;
  width: 380px;
  max-height: 600px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  background: linear-gradient(180deg,
    var(--ash-ironrust) 0%,
    var(--ash-stone) 40%,
    var(--ash-coal) 100%);
  border: 1px solid var(--bronze-dark);
  clip-path: var(--clip-forged-md);
  box-shadow:
    inset 0 0 0 1px var(--iron-mid),
    var(--shadow-deep),
    0 0 60px rgba(226, 67, 16, 0.18);
}

.chat-panel-enter-active {
  transition: opacity 0.32s var(--ease-smoke), transform 0.32s var(--ease-forge);
}
.chat-panel-leave-active {
  transition: opacity 0.22s var(--ease-smoke), transform 0.22s var(--ease-smoke);
}
.chat-panel-enter-from {
  opacity: 0;
  transform: translateX(40px) scale(0.96);
  transform-origin: bottom right;
}
.chat-panel-leave-to {
  opacity: 0;
  transform: translateX(40px) scale(0.97);
  transform-origin: bottom right;
}

/* ── Header ── */
.chat-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 18px;
  background: linear-gradient(180deg,
    var(--ash-bloodrock) 0%,
    var(--ash-ironrust) 100%);
  border-bottom: 1px solid var(--iron-dark);
  flex-shrink: 0;
  position: relative;
}
.chat-header::after {
  content: '';
  position: absolute;
  left: 14px; right: 14px;
  bottom: -1px;
  height: 1px;
  background: linear-gradient(90deg,
    transparent 0%,
    var(--bronze) 50%,
    transparent 100%);
  opacity: 0.4;
}
.chat-header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}
.chat-avatar {
  width: 42px;
  height: 42px;
  clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
  background: var(--grad-ember);
  color: var(--text-bright);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: var(--inset-iron-top), 0 0 12px rgba(226, 67, 16, 0.45);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
}
.chat-title {
  margin: 0;
  font-family: var(--font-display);
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--text-bright);
  letter-spacing: 0.3px;
}
.chat-subtitle {
  margin: 3px 0 0;
  font-family: var(--font-ui);
  font-size: 0.7rem;
  color: var(--text-parchment);
  display: flex;
  align-items: center;
  gap: 6px;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}
.online-dot {
  display: inline-block;
  width: 7px; height: 7px;
  border-radius: 50%;
  background: var(--ember-gold);
  box-shadow: 0 0 8px var(--ember-gold);
  animation: pulse-dot 2s var(--ease-smoke) infinite;
}
@keyframes pulse-dot {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.55; }
}

.chat-header-actions {
  display: flex;
  align-items: center;
  gap: 6px;
}
.chat-icon-btn {
  width: 32px; height: 32px;
  border: 1px solid var(--iron-dark);
  background: rgba(8, 6, 10, 0.5);
  color: var(--text-parchment);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: var(--inset-iron-top);
  transition: all 0.2s var(--ease-smoke);
}
.chat-icon-btn:hover {
  background: rgba(138, 31, 24, 0.35);
  border-color: var(--ember-heart);
  color: var(--ember-gold);
}

/* ── Breadcrumb path ── */
.chat-path {
  padding: 8px 16px;
  background: rgba(226, 67, 16, 0.08);
  border-bottom: 1px dashed var(--iron-dark);
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 4px;
  flex-shrink: 0;
}
.path-part {
  font-family: var(--font-ui);
  font-size: 0.72rem;
  color: var(--ember-spark);
  font-weight: 700;
  letter-spacing: 0.5px;
  display: flex;
  align-items: center;
  gap: 4px;
}
.path-sep {
  color: var(--bronze-dark);
  font-size: 0.85rem;
}

/* ── Messages body ── */
.chat-body {
  flex: 1;
  overflow-y: auto;
  padding: 18px 16px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  scrollbar-width: thin;
  scrollbar-color: var(--bronze-dark) transparent;
}
.chat-body::-webkit-scrollbar { width: 5px; }
.chat-body::-webkit-scrollbar-track { background: transparent; }
.chat-body::-webkit-scrollbar-thumb { background: var(--bronze-dark); }

/* ── Message rows ── */
.msg-row {
  display: flex;
  align-items: flex-end;
  gap: 8px;
}
.msg-bot  { flex-direction: row; }
.msg-user { flex-direction: row-reverse; }

.bot-icon-wrap {
  width: 26px;
  height: 26px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--bronze);
  border: 1px solid var(--bronze-dark);
  background: rgba(8, 6, 10, 0.55);
  clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
  flex-shrink: 0;
  margin-bottom: 2px;
  box-shadow: var(--inset-iron-top);
}

.msg-bubble {
  max-width: 82%;
  padding: 11px 15px;
  font-family: var(--font-body);
  font-size: 0.9rem;
  line-height: 1.6;
  word-break: break-word;
  border-radius: 3px;
}
.bubble-bot {
  background: linear-gradient(180deg,
    var(--ash-stone) 0%,
    var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  color: var(--text-bone);
  box-shadow: var(--inset-iron-top);
  clip-path: polygon(0 0, 100% 0, 100% 100%, 8px 100%, 0 calc(100% - 8px));
}
.bubble-user {
  background: var(--grad-ember);
  border: 1px solid var(--ember-heart);
  color: var(--text-bright);
  box-shadow: var(--inset-iron-top), 0 4px 12px rgba(226, 67, 16, 0.35);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
  clip-path: polygon(0 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%);
}

.bubble-text { white-space: pre-wrap; }

/* ── Option buttons ── */
.msg-options {
  margin-top: 12px;
  display: flex;
  flex-direction: column;
  gap: 7px;
}
.option-btn {
  padding: 9px 14px;
  border: 1px solid var(--bronze-dark);
  background: rgba(8, 6, 10, 0.45);
  color: var(--ember-spark);
  font-family: var(--font-ui);
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.5px;
  text-align: left;
  cursor: pointer;
  line-height: 1.4;
  box-shadow: var(--inset-iron-top);
  transition: all 0.2s var(--ease-smoke);
}
.option-btn:hover {
  background: rgba(226, 67, 16, 0.18);
  border-color: var(--ember-flame);
  color: var(--ember-gold);
  transform: translateX(3px);
}
.option-btn.option-back {
  border-color: var(--iron-dark);
  background: transparent;
  color: var(--text-ash);
  font-size: 0.76rem;
  text-transform: uppercase;
  letter-spacing: 1px;
}
.option-btn.option-back:hover {
  background: rgba(122, 93, 72, 0.1);
  border-color: var(--bronze-dark);
  color: var(--text-parchment);
  transform: translateX(0);
}

.msg-enter-active { transition: opacity 0.25s var(--ease-smoke), transform 0.25s var(--ease-forge); }
.msg-enter-from   { opacity: 0; transform: translateY(8px); }

/* ── Contact form ── */
.contact-form-wrap { margin-top: 6px; }
.contact-form {
  background: linear-gradient(180deg,
    rgba(8, 6, 10, 0.75) 0%,
    rgba(18, 16, 13, 0.85) 100%);
  border: 1px solid var(--iron-mid);
  padding: 18px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  box-shadow: var(--inset-iron-top);
}
.cf-label {
  display: flex;
  flex-direction: column;
  gap: 7px;
  font-family: var(--font-ui);
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--bronze);
  letter-spacing: 1.4px;
  text-transform: uppercase;
  position: relative;
}
.cf-input,
.cf-textarea {
  background: rgba(8, 6, 10, 0.65);
  border: 1px solid var(--iron-dark);
  padding: 10px 13px;
  color: var(--text-bone);
  font-family: var(--font-body);
  font-size: 0.88rem;
  outline: none;
  resize: vertical;
  box-shadow: var(--inset-iron-top);
  transition: border-color 0.2s var(--ease-smoke), box-shadow 0.2s var(--ease-smoke);
}
.cf-input:focus,
.cf-textarea:focus {
  border-color: var(--ember-flame);
  box-shadow: var(--inset-iron-top), 0 0 0 3px rgba(226, 67, 16, 0.14);
}
.cf-input::placeholder,
.cf-textarea::placeholder { color: var(--text-void); }
.cf-textarea { min-height: 86px; }
.cf-counter {
  position: absolute;
  bottom: 8px;
  right: 10px;
  font-family: var(--font-ui);
  font-size: 0.68rem;
  color: var(--text-ash);
  letter-spacing: 0;
  text-transform: none;
  font-weight: 400;
}
.cf-error {
  margin: 0;
  font-family: var(--font-body);
  font-size: 0.82rem;
  color: #ffb4a8;
  background: linear-gradient(180deg, rgba(138, 31, 24, 0.25), rgba(90, 20, 18, 0.35));
  border: 1px solid rgba(194, 40, 26, 0.45);
  padding: 9px 12px;
  box-shadow: var(--inset-iron-top);
}
.cf-submit {
  position: relative;
  padding: 12px 0;
  border: 1px solid var(--ember-heart);
  background: var(--grad-ember);
  color: var(--text-bright);
  font-family: var(--font-display);
  font-size: 0.88rem;
  font-weight: 700;
  letter-spacing: 1.3px;
  text-transform: uppercase;
  cursor: pointer;
  box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.3), var(--glow-ember);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
  clip-path: var(--clip-forged-sm);
  transition: transform 0.2s var(--ease-forge), box-shadow 0.2s var(--ease-smoke);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  overflow: hidden;
}
.cf-submit::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg,
    transparent 0%, rgba(255, 201, 121, 0.4) 50%, transparent 100%);
  transform: translateX(-120%);
  transition: transform 0.6s var(--ease-smoke);
  pointer-events: none;
}
.cf-submit:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.3), var(--glow-ember-strong);
}
.cf-submit:hover:not(:disabled)::after { transform: translateX(120%); }
.cf-submit:disabled {
  background: var(--ash-stone);
  border-color: var(--iron-mid);
  color: var(--text-smoke);
  box-shadow: var(--inset-iron-top);
  cursor: not-allowed;
  text-shadow: none;
}
.cf-spinner {
  display: inline-block;
  width: 16px; height: 16px;
  border: 2.5px solid rgba(255, 248, 234, 0.3);
  border-top-color: var(--text-bright);
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.form-slide-enter-active { transition: opacity 0.3s var(--ease-smoke), transform 0.3s var(--ease-forge); }
.form-slide-enter-from   { opacity: 0; transform: translateY(10px); }

/* ── Done state ── */
.done-actions {
  display: flex;
  padding: 4px 0 0;
}
.done-actions .cf-submit { flex: 1; }

/* ── Responsive ── */
@media (max-width: 480px) {
  .edge-tab {
    padding: 10px 14px 10px 10px;
    height: 46px;
    font-size: 0.7rem;
    gap: 7px;
  }
  .edge-tab-label { display: none; }
  .chat-panel {
    bottom: 16px;
    right: 16px;
    width: calc(100vw - 32px);
    max-height: 70vh;
  }
}
</style>
