<script setup>
/**
 * Pay/A — заглушка успешной оплаты.
 *
 * Показывается после confirmed-перехода с PaymentView.
 * MVP: товара нет, юзер видит «спасибо за оплату, тут будет ключ когда
 * добавим товары». Хэш транзакции и сумма выводятся для референса.
 */
import { ref, computed, onMounted } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import { useHead } from '@vueuse/head';
import api from '../api/axios';

const route = useRoute();

useHead({
  title: 'Оплата успешна — GameStore',
  meta: [{ name: 'robots', content: 'noindex, nofollow' }],
});

const payment = ref(null);
const loading = ref(true);

// Key copy
const keyCopied = ref(false);
function copyKey() {
  navigator.clipboard.writeText('0QAB4-0I3CG-AFID2').then(() => {
    keyCopied.value = true;
    setTimeout(() => { keyCopied.value = false; }, 2200);
  });
}

// Activation instructions
const instrOpen = ref(false);
const activeTab = ref('pc');
const instrTabs = [
  { id: 'pc',      label: '🖥 Steam на ПК' },
  { id: 'browser', label: '🌐 Браузер' },
  { id: 'mobile',  label: '📱 Телефон' },
];

const tronscanUrl = computed(() => {
  if (!payment.value?.transaction_hash) return null;
  // BEP-20 → BscScan, иначе → TronScan
  if (payment.value.crypto_currency === 'USDT_BEP20') {
    return `https://bscscan.com/tx/${payment.value.transaction_hash}`;
  }
  return `https://tronscan.org/#/transaction/${payment.value.transaction_hash}`;
});

const currencyUnit = computed(() => {
  return payment.value?.crypto_currency === 'TRX' ? 'TRX' : 'USDT';
});

const currencyNetwork = computed(() => {
  switch (payment.value?.crypto_currency) {
    case 'USDT_TRC20': return 'TRC-20 (Tron)';
    case 'TRX':        return 'Tron';
    case 'USDT_BEP20': return 'BEP-20 (BNB Smart Chain)';
    default:           return payment.value?.crypto_currency || '';
  }
});

const formattedDate = computed(() => {
  if (!payment.value?.confirmed_at) return '';
  return new Date(payment.value.confirmed_at).toLocaleString('ru-RU', {
    day: 'numeric', month: 'long', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
});

const cartItems = computed(() => payment.value?.metadata?.cart || []);

onMounted(async () => {
  try {
    const { data } = await api.get(`/payments/${route.params.id}`);
    payment.value = data;
  } catch (e) {
    console.warn('[PaymentSuccess] fetch failed', e);
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="success-view">
    <div class="success-shell">

      <div v-if="loading" class="loading">Загрузка…</div>

      <template v-else-if="payment">
        <!-- Sigil + title -->
        <div class="success-sigil" aria-hidden="true">🜨</div>
        <h1 class="success-title">Оплата прошла успешно</h1>
        <p class="success-sub">Кузница приняла твоё подношение.</p>

        <!-- Receipt -->
        <div class="receipt">
          <div class="receipt-row">
            <span class="receipt-label">Сумма</span>
            <span class="receipt-value">
              {{ payment.amount_crypto.toFixed(6) }} {{ currencyUnit }}
              <span class="receipt-equiv">≈ {{ payment.amount_rub.toFixed(2) }} ₽</span>
            </span>
          </div>

          <div class="receipt-row">
            <span class="receipt-label">Сеть</span>
            <span class="receipt-value">{{ currencyNetwork }}</span>
          </div>

          <div v-if="payment.transaction_hash" class="receipt-row">
            <span class="receipt-label">Хэш транзакции</span>
            <span class="receipt-value mono">
              <a :href="tronscanUrl" target="_blank" rel="noopener" class="tx-link">
                {{ payment.transaction_hash.slice(0, 16) }}…{{ payment.transaction_hash.slice(-6) }}
              </a>
            </span>
          </div>

          <div class="receipt-row">
            <span class="receipt-label">Дата</span>
            <span class="receipt-value">{{ formattedDate }}</span>
          </div>

          <div v-if="cartItems.length" class="receipt-row items-row">
            <span class="receipt-label">Покупка</span>
            <ul class="receipt-items">
              <li v-for="(it, i) in cartItems" :key="i">
                {{ it.title }} × {{ it.quantity }}
              </li>
            </ul>
          </div>
        </div>

        <!-- Key delivery block -->
        <div class="key-block">
          <div class="key-eyebrow">🗝 Ваш ключ активации</div>
          <div class="key-row">
            <a
              href="https://key-steam.com/ggsel-keys/?uniquecode=385eae80-3720-497b-bb4a-709835e6ba4d#"
              target="_blank"
              rel="noopener"
              class="key-value"
            >0QAB4-0I3CG-AFID2</a>
            <button class="key-copy-btn" @click="copyKey" :class="{ copied: keyCopied }">
              <span v-if="keyCopied">✓ Скопировано</span>
              <span v-else>Копировать</span>
            </button>
          </div>
          <p class="key-hint">
            Нажмите на ключ, чтобы перейти на страницу активации, или скопируйте его вручную.
          </p>
        </div>

        <!-- Activation instructions (accordion) -->
        <div class="instr-block">
          <button class="instr-toggle" @click="instrOpen = !instrOpen">
            <span>Инструкция активации</span>
            <span class="instr-chevron" :class="{ open: instrOpen }">▾</span>
          </button>

          <div v-show="instrOpen" class="instr-body">
            <p class="instr-lead">
              Активируйте ключ только в том регионе, для которого предназначен товар.
              Если указан <strong>ROW</strong>, ключ подходит для большинства регионов,
              но итоговая активация зависит от ограничений Steam.
            </p>

            <div class="instr-tabs">
              <button
                v-for="tab in instrTabs"
                :key="tab.id"
                class="instr-tab"
                :class="{ active: activeTab === tab.id }"
                @click="activeTab = tab.id"
              >{{ tab.label }}</button>
            </div>

            <!-- PC Steam -->
            <ol v-if="activeTab === 'pc'" class="instr-steps">
              <li>Откройте клиент Steam и войдите в нужный аккаунт.</li>
              <li>В верхнем меню нажмите <strong>Игры → Активировать в Steam…</strong></li>
              <li>Нажмите <strong>Далее</strong>, затем <strong>Согласен</strong>.</li>
              <li>Вставьте ключ полностью — без лишних пробелов в начале и конце.</li>
              <li>Подтвердите активацию и дождитесь сообщения об успешном добавлении игры в библиотеку.</li>
              <li>Перейдите в <strong>Библиотеку</strong>, найдите игру и нажмите <strong>Установить</strong>.</li>
            </ol>

            <!-- Browser -->
            <ol v-else-if="activeTab === 'browser'" class="instr-steps">
              <li>
                Откройте прямую ссылку:
                <a href="https://store.steampowered.com/account/registerkey" target="_blank" rel="noopener" class="instr-link">
                  store.steampowered.com/account/registerkey
                </a>
              </li>
              <li>Авторизуйтесь на сайте Steam под нужным аккаунтом.</li>
              <li>Подтвердите лицензионное соглашение, если Steam его запросит.</li>
              <li>Вставьте ключ в поле и нажмите <strong>Продолжить</strong>.</li>
              <li>После успешной активации убедитесь, что игра появилась в библиотеке аккаунта.</li>
            </ol>

            <!-- Mobile -->
            <ol v-else-if="activeTab === 'mobile'" class="instr-steps">
              <li>На телефоне удобнее использовать браузерную ссылку — откройте <a href="https://store.steampowered.com/account/registerkey" target="_blank" rel="noopener" class="instr-link">registerkey</a> в браузере.</li>
              <li>Если мобильное приложение Steam не показывает нужную форму, используйте именно браузерную версию сайта.</li>
              <li>Вставьте ключ вручную или из буфера обмена и подтвердите активацию.</li>
              <li>В мобильном приложении можно открыть раздел <strong>Поддержка</strong> и найти активацию через поиск.</li>
            </ol>

            <div class="instr-warn">
              <span class="instr-warn-icon">⚠</span>
              Если Steam пишет, что ключ уже использован, не подходит по региону или введён с ошибкой —
              сначала перепроверьте символы. Чаще всего путают <strong>0&nbsp;и&nbsp;O</strong>, <strong>1&nbsp;и&nbsp;I</strong>.
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="success-actions">
          <RouterLink to="/catalog" class="action-btn primary">Вернуться в каталог</RouterLink>
          <RouterLink :to="{ name: 'my-payments' }" class="action-btn ghost">Мои платежи</RouterLink>
        </div>
      </template>

      <div v-else class="loading">Платёж не найден.</div>
    </div>
  </div>
</template>

<style scoped>
.success-view {
  min-height: calc(100vh - 73px);
  padding: 48px 16px 64px;
  display: flex;
  align-items: flex-start;
  justify-content: center;
}
.success-shell {
  max-width: 600px;
  width: 100%;
  text-align: center;
}
.loading {
  padding: 64px;
  color: var(--text-muted);
}

.success-sigil {
  font-size: 64px;
  color: var(--iron-warm);
  margin-bottom: 16px;
  filter: drop-shadow(0 0 20px rgba(226, 67, 16, 0.5));
  animation: sigil-glow 3s ease-in-out infinite;
}
@keyframes sigil-glow {
  0%, 100% { filter: drop-shadow(0 0 20px rgba(226, 67, 16, 0.5)); }
  50% { filter: drop-shadow(0 0 32px rgba(226, 67, 16, 0.85)); }
}

.success-title {
  font-size: 28px;
  margin: 0 0 8px;
  color: var(--text-bright);
  font-family: var(--font-display, inherit);
  letter-spacing: 0.02em;
}
.success-sub {
  margin: 0 0 32px;
  color: var(--text-parchment);
  font-style: italic;
}

.receipt {
  text-align: left;
  margin-bottom: 24px;
  padding: 22px 26px;
  border: 1px solid var(--iron-warm);
  border-radius: var(--r-md);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  box-shadow: var(--inset-iron-top), 0 8px 24px rgba(0,0,0,0.4);
}
.receipt-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 10px 0;
  border-bottom: 1px solid var(--iron-dark);
  gap: 16px;
}
.receipt-row:last-child { border-bottom: none; }
.receipt-row.items-row {
  flex-direction: column;
  align-items: stretch;
  gap: 6px;
}
.receipt-label {
  font-size: 11px;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: var(--iron-warm);
}
.receipt-value {
  font-size: 14px;
  color: var(--text-bright);
  text-align: right;
  word-break: break-all;
}
.receipt-equiv {
  display: block;
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 2px;
}
.mono { font-family: 'SF Mono', Monaco, Consolas, monospace; }
.tx-link {
  color: #ffba78;
  text-decoration: none;
  border-bottom: 1px dashed;
}
.tx-link:hover { color: var(--text-bright); }

.receipt-items {
  list-style: none;
  margin: 0;
  padding: 0;
  font-size: 13px;
  color: var(--text-parchment);
}
.receipt-items li {
  padding: 4px 0;
}
.receipt-items li::before {
  content: '⚒ ';
  color: var(--iron-warm);
  margin-right: 4px;
}

/* ── Key delivery block ───────────────────────── */
.key-block {
  margin-bottom: 16px;
  padding: 20px 24px;
  border: 1px solid var(--iron-warm);
  border-radius: var(--r-md);
  background: linear-gradient(160deg, rgba(226,67,16,0.10) 0%, rgba(0,0,0,0) 60%),
              linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  box-shadow: 0 0 24px rgba(226,67,16,0.18), var(--inset-iron-top);
  text-align: left;
}
.key-eyebrow {
  font-size: 10px;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  color: var(--iron-warm);
  margin-bottom: 10px;
}
.key-row {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 8px;
}
.key-value {
  font-family: 'SF Mono', Monaco, Consolas, monospace;
  font-size: 22px;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: var(--text-bright);
  background: rgba(0,0,0,0.35);
  border: 1px dashed rgba(226,67,16,0.5);
  border-radius: var(--r-sm);
  padding: 8px 16px;
  text-decoration: none;
  user-select: all;
  transition: border-color 0.2s, box-shadow 0.2s;
  flex: 1;
  text-align: center;
  word-break: break-all;
}
.key-value:hover {
  border-color: var(--iron-warm);
  box-shadow: 0 0 12px rgba(226,67,16,0.35);
  color: #ffba78;
}
.key-copy-btn {
  flex-shrink: 0;
  padding: 9px 18px;
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-sm);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  color: var(--text-parchment);
  font-size: 12px;
  letter-spacing: 0.06em;
  cursor: pointer;
  transition: all 0.18s;
  white-space: nowrap;
}
.key-copy-btn:hover,
.key-copy-btn.copied {
  border-color: var(--iron-warm);
  color: var(--text-bright);
  box-shadow: 0 0 8px rgba(226,67,16,0.35);
}
.key-copy-btn.copied {
  background: linear-gradient(180deg, rgba(226,67,16,0.20) 0%, rgba(0,0,0,0) 100%);
}
.key-hint {
  margin: 0;
  font-size: 12px;
  color: var(--text-muted);
  line-height: 1.4;
}

/* ── Activation instructions ──────────────────── */
.instr-block {
  margin-bottom: 24px;
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-md);
  background: rgba(0,0,0,0.2);
  overflow: hidden;
  text-align: left;
}
.instr-toggle {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 20px;
  background: transparent;
  border: none;
  cursor: pointer;
  color: var(--text-parchment);
  font-size: 13px;
  letter-spacing: 0.06em;
  transition: color 0.18s, background 0.18s;
}
.instr-toggle:hover {
  color: var(--text-bright);
  background: rgba(226,67,16,0.06);
}
.instr-chevron {
  font-size: 16px;
  color: var(--iron-warm);
  transition: transform 0.22s;
}
.instr-chevron.open { transform: rotate(180deg); }

.instr-body {
  padding: 4px 20px 20px;
  border-top: 1px solid var(--iron-dark);
}
.instr-lead {
  margin: 14px 0 16px;
  font-size: 13px;
  color: var(--text-parchment);
  line-height: 1.55;
}
.instr-lead strong { color: var(--text-bright); }

.instr-tabs {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  margin-bottom: 14px;
}
.instr-tab {
  padding: 7px 14px;
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-sm);
  background: transparent;
  color: var(--text-muted);
  font-size: 12px;
  cursor: pointer;
  transition: all 0.16s;
}
.instr-tab:hover {
  color: var(--text-parchment);
  border-color: rgba(226,67,16,0.4);
}
.instr-tab.active {
  border-color: var(--iron-warm);
  color: var(--text-bright);
  background: rgba(226,67,16,0.12);
}

.instr-steps {
  margin: 0 0 16px;
  padding-left: 0;
  counter-reset: step;
  list-style: none;
}
.instr-steps li {
  counter-increment: step;
  display: flex;
  gap: 12px;
  font-size: 13px;
  color: var(--text-parchment);
  line-height: 1.55;
  padding: 7px 0;
  border-bottom: 1px solid rgba(255,255,255,0.04);
}
.instr-steps li:last-child { border-bottom: none; }
.instr-steps li::before {
  content: counter(step);
  flex-shrink: 0;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 1px solid var(--iron-warm);
  color: var(--iron-warm);
  font-size: 11px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 1px;
}
.instr-steps li strong { color: var(--text-bright); }
.instr-link {
  color: #ffba78;
  text-decoration: none;
  border-bottom: 1px dashed;
  word-break: break-all;
}
.instr-link:hover { color: var(--text-bright); }

.instr-warn {
  display: flex;
  gap: 10px;
  padding: 12px 14px;
  border-radius: var(--r-sm);
  background: rgba(226,67,16,0.08);
  border: 1px solid rgba(226,67,16,0.25);
  font-size: 12px;
  color: var(--text-parchment);
  line-height: 1.5;
}
.instr-warn strong { color: var(--text-bright); }
.instr-warn-icon {
  flex-shrink: 0;
  color: var(--iron-warm);
  font-size: 14px;
}

.success-actions {
  display: flex;
  justify-content: center;
  gap: 12px;
  flex-wrap: wrap;
}
.action-btn {
  padding: 11px 22px;
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-sm);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  color: var(--text-parchment);
  text-decoration: none;
  font-size: 13px;
  letter-spacing: 0.05em;
  transition: all var(--dur-fast) var(--ease-smoke);
}
.action-btn.primary {
  border-color: var(--iron-warm);
  background: linear-gradient(180deg, rgba(226, 67, 16, 0.18) 0%, rgba(0,0,0,0) 100%);
  color: var(--text-bright);
}
.action-btn:hover {
  border-color: var(--iron-warm);
  color: var(--text-bright);
  transform: translateY(-1px);
  box-shadow: 0 0 10px rgba(226, 67, 16, 0.4);
}
</style>