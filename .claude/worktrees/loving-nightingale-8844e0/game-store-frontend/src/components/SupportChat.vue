<script setup>
import { ref, computed, nextTick, watch } from 'vue';
import { useAuthStore } from '../stores/auth';
import { storeToRefs } from 'pinia';
import api from '../api/axios';

const authStore = useAuthStore();
const { user, isLoggedIn } = storeToRefs(authStore);

// ── State ──────────────────────────────────────────────────────────────────
const isOpen = ref(false);
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

  // ── Orders ──
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

  // ── Games ──
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

  // ── Account ──
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

  // ── Tech ──
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

  // ── Other ──
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

  // Navigate deeper in the tree
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

// ── Last bot message options (only the last message shows clickable options) ──
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
</script>

<template>
  <!-- Floating trigger button -->
  <Teleport to="body">
    <div class="support-chat-root">

      <!-- Chat panel -->
      <Transition name="chat-panel">
        <div v-if="isOpen" class="chat-panel" role="dialog" aria-label="Чат поддержки">

          <!-- Header -->
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

          <!-- Breadcrumb path -->
          <div v-if="problemPath.length" class="chat-path">
            <span v-for="(p, i) in problemPath" :key="i" class="path-part">
              <span v-if="i > 0" class="path-sep">›</span>
              {{ p }}
            </span>
          </div>

          <!-- Messages body -->
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

                  <!-- Options — only on last bot message with options -->
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

            <!-- Contact form -->
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

            <!-- Done state extra actions -->
            <Transition name="form-slide">
              <div v-if="stage === 'done'" class="done-actions">
                <button class="cf-submit" @click="resetChat">Новое обращение</button>
              </div>
            </Transition>
          </div>
        </div>
      </Transition>

      <!-- Floating button -->
      <Transition name="fab">
        <button
          class="chat-fab"
          @click="isOpen ? closeChat() : openChat()"
          :class="{ open: isOpen }"
          :aria-label="isOpen ? 'Закрыть чат' : 'Открыть чат поддержки'"
          title="Поддержка"
        >
          <Transition name="fab-icon" mode="out-in">
            <span v-if="!isOpen" key="open" class="fab-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>
            </span>
            <span v-else key="close" class="fab-icon fab-close">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </span>
          </Transition>
          <span class="fab-label">Поддержка</span>
        </button>
      </Transition>

    </div>
  </Teleport>
</template>

<style scoped>
/* ── Root ────────────────────────────────────────────────────────────────── */
.support-chat-root {
  position: fixed;
  bottom: 28px;
  right: 28px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 12px;
  pointer-events: none;
}
.support-chat-root > * { pointer-events: all; }

/* ── FAB button ──────────────────────────────────────────────────────────── */
.chat-fab {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 20px 0 14px;
  height: 52px;
  border-radius: 999px;
  border: none;
  background: linear-gradient(135deg, #3b82f6 0%, #6366f1 50%, #8b5cf6 100%);
  color: #fff;
  font-size: 0.9rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow:
    0 8px 32px rgba(99,102,241,0.55),
    0 2px 8px rgba(0,0,0,0.3),
    inset 0 1px 0 rgba(255,255,255,0.2);
  transition: transform 0.25s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.25s, filter 0.2s;
  user-select: none;
}
.chat-fab:hover {
  transform: translateY(-3px) scale(1.04);
  box-shadow:
    0 12px 40px rgba(99,102,241,0.7),
    0 4px 12px rgba(0,0,0,0.35);
  filter: brightness(1.1);
}
.chat-fab.open {
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  box-shadow:
    0 6px 24px rgba(0,0,0,0.5),
    0 0 0 1px rgba(255,255,255,0.08);
}
.fab-icon {
  font-size: 1.25rem;
  line-height: 1;
  display: block;
  transition: transform 0.2s ease;
}
.fab-close { font-size: 1rem; }
.fab-label { letter-spacing: 0.3px; }

/* FAB transition */
.fab-icon-enter-active, .fab-icon-leave-active { transition: opacity 0.15s, transform 0.15s; }
.fab-icon-enter-from { opacity: 0; transform: rotate(-90deg) scale(0.7); }
.fab-icon-leave-to   { opacity: 0; transform: rotate(90deg) scale(0.7); }

/* ── Chat panel ──────────────────────────────────────────────────────────── */
.chat-panel {
  width: 370px;
  max-height: 580px;
  border-radius: 20px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  background: #0d1117;
  border: 1px solid rgba(99,102,241,0.3);
  box-shadow:
    0 24px 80px rgba(0,0,0,0.7),
    0 0 0 1px rgba(255,255,255,0.04),
    0 0 60px rgba(99,102,241,0.12);
}

/* Panel transition */
.chat-panel-enter-active {
  transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.34,1.56,0.64,1);
}
.chat-panel-leave-active {
  transition: opacity 0.22s ease, transform 0.22s ease;
}
.chat-panel-enter-from {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
  transform-origin: bottom right;
}
.chat-panel-leave-to {
  opacity: 0;
  transform: translateY(16px) scale(0.96);
  transform-origin: bottom right;
}

/* ── Header ──────────────────────────────────────────────────────────────── */
.chat-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  background: linear-gradient(135deg, #0f0c29 0%, #1a0e3d 40%, #0f2460 100%);
  border-bottom: 1px solid rgba(255,255,255,0.06);
  flex-shrink: 0;
}
.chat-header-left {
  display: flex;
  align-items: center;
  gap: 10px;
}
.chat-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  box-shadow: 0 0 16px rgba(99,102,241,0.4);
  flex-shrink: 0;
}
.chat-title  { margin: 0; font-size: 0.92rem; font-weight: 700; color: #e2e8f0; }
.chat-subtitle {
  margin: 2px 0 0;
  font-size: 0.72rem;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 5px;
}
.online-dot {
  display: inline-block;
  width: 6px; height: 6px;
  border-radius: 50%;
  background: #4ade80;
  box-shadow: 0 0 6px #4ade80;
  animation: pulse-dot 2s ease infinite;
}
@keyframes pulse-dot {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.5; }
}

.chat-header-actions {
  display: flex;
  align-items: center;
  gap: 6px;
}
.chat-icon-btn {
  width: 30px; height: 30px;
  border-radius: 8px;
  border: 1px solid rgba(255,255,255,0.08);
  background: rgba(255,255,255,0.05);
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}
.chat-icon-btn:hover {
  background: rgba(255,255,255,0.12);
  color: #e2e8f0;
}

/* ── Breadcrumb path ─────────────────────────────────────────────────────── */
.chat-path {
  padding: 7px 14px;
  background: rgba(59,130,246,0.08);
  border-bottom: 1px solid rgba(59,130,246,0.15);
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 3px;
  flex-shrink: 0;
}
.path-part {
  font-size: 0.72rem;
  color: #60a5fa;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 3px;
}
.path-sep {
  color: #334155;
  font-size: 0.8rem;
}

/* ── Messages body ───────────────────────────────────────────────────────── */
.chat-body {
  flex: 1;
  overflow-y: auto;
  padding: 16px 14px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  scrollbar-width: thin;
  scrollbar-color: rgba(99,102,241,0.3) transparent;
}
.chat-body::-webkit-scrollbar { width: 4px; }
.chat-body::-webkit-scrollbar-track { background: transparent; }
.chat-body::-webkit-scrollbar-thumb { background: rgba(99,102,241,0.35); border-radius: 2px; }

/* ── Message rows ────────────────────────────────────────────────────────── */
.msg-row {
  display: flex;
  align-items: flex-end;
  gap: 8px;
}
.msg-bot  { flex-direction: row; }
.msg-user { flex-direction: row-reverse; }

.bot-icon-wrap {
  font-size: 1.1rem;
  flex-shrink: 0;
  margin-bottom: 2px;
}

.msg-bubble {
  max-width: 82%;
  padding: 10px 14px;
  border-radius: 16px;
  font-size: 0.875rem;
  line-height: 1.55;
  word-break: break-word;
}
.bubble-bot {
  background: #131b2e;
  border: 1px solid rgba(59,130,246,0.2);
  color: #cbd5e1;
  border-bottom-left-radius: 4px;
}
.bubble-user {
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: #fff;
  border-bottom-right-radius: 4px;
  box-shadow: 0 4px 16px rgba(99,102,241,0.35);
}

.bubble-text { white-space: pre-wrap; }

/* ── Option buttons ──────────────────────────────────────────────────────── */
.msg-options {
  margin-top: 10px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.option-btn {
  padding: 8px 12px;
  border-radius: 10px;
  border: 1px solid rgba(59,130,246,0.35);
  background: rgba(59,130,246,0.08);
  color: #93c5fd;
  font-size: 0.82rem;
  font-weight: 600;
  text-align: left;
  cursor: pointer;
  transition: all 0.2s;
  line-height: 1.35;
}
.option-btn:hover {
  background: rgba(59,130,246,0.2);
  border-color: rgba(59,130,246,0.6);
  color: #bfdbfe;
  transform: translateX(3px);
}
.option-btn.option-back {
  border-color: rgba(255,255,255,0.08);
  background: transparent;
  color: #475569;
  font-size: 0.78rem;
}
.option-btn.option-back:hover {
  background: rgba(255,255,255,0.04);
  color: #94a3b8;
  transform: translateX(0);
}

/* ── Message transition ──────────────────────────────────────────────────── */
.msg-enter-active { transition: opacity 0.25s ease, transform 0.25s ease; }
.msg-enter-from   { opacity: 0; transform: translateY(8px); }

/* ── Contact form ────────────────────────────────────────────────────────── */
.contact-form-wrap {
  margin-top: 4px;
}
.contact-form {
  background: #111827;
  border: 1px solid rgba(99,102,241,0.25);
  border-radius: 14px;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.cf-label {
  display: flex;
  flex-direction: column;
  gap: 6px;
  font-size: 0.78rem;
  font-weight: 700;
  color: #64748b;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  position: relative;
}
.cf-input,
.cf-textarea {
  background: #0d1117;
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 9px;
  padding: 9px 12px;
  color: #e2e8f0;
  font-size: 0.875rem;
  font-family: inherit;
  outline: none;
  resize: vertical;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.cf-input:focus,
.cf-textarea:focus {
  border-color: rgba(99,102,241,0.6);
  box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
}
.cf-input::placeholder,
.cf-textarea::placeholder { color: #334155; }
.cf-textarea { min-height: 80px; }
.cf-counter {
  position: absolute;
  bottom: 8px;
  right: 10px;
  font-size: 0.7rem;
  color: #334155;
  letter-spacing: 0;
  text-transform: none;
  font-weight: 400;
}
.cf-error {
  margin: 0;
  font-size: 0.8rem;
  color: #f87171;
  background: rgba(239,68,68,0.08);
  border: 1px solid rgba(239,68,68,0.2);
  border-radius: 8px;
  padding: 8px 10px;
}
.cf-submit {
  padding: 11px 0;
  border-radius: 10px;
  border: none;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: #fff;
  font-size: 0.88rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 16px rgba(99,102,241,0.4);
  transition: filter 0.2s, transform 0.2s, box-shadow 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}
.cf-submit:hover:not(:disabled) {
  filter: brightness(1.1);
  transform: translateY(-1px);
  box-shadow: 0 6px 24px rgba(99,102,241,0.55);
}
.cf-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.cf-spinner {
  display: inline-block;
  width: 16px; height: 16px;
  border: 2.5px solid rgba(255,255,255,0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Form slide transition */
.form-slide-enter-active { transition: opacity 0.3s ease, transform 0.3s ease; }
.form-slide-enter-from   { opacity: 0; transform: translateY(10px); }

/* ── Done state ──────────────────────────────────────────────────────────── */
.done-actions {
  display: flex;
  padding: 4px 0 0;
}
.done-actions .cf-submit {
  flex: 1;
  background: linear-gradient(135deg, #059669, #10b981);
  box-shadow: 0 4px 16px rgba(16,185,129,0.35);
}
.done-actions .cf-submit:hover {
  box-shadow: 0 6px 24px rgba(16,185,129,0.5);
}

/* ── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 480px) {
  .support-chat-root {
    bottom: 16px;
    right: 16px;
  }
  .chat-panel {
    width: calc(100vw - 32px);
    max-height: 70vh;
    border-radius: 16px;
  }
  .fab-label { display: none; }
  .chat-fab  { padding: 0 14px; }
}
</style>
