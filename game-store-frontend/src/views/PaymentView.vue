<script setup>
/**
 * Pay/A — окно оплаты крипто-платежа.
 *
 * Поток:
 *   1. На /payment/:id mounted'е → загружаем детали платежа
 *   2. Рендерим QR + адрес + точную сумму + countdown 15:00
 *   3. Каждые 3 сек polling статуса:
 *      • status='confirmed' → редирект на /payment/:id/success
 *      • status='expired'   → редирект на /payment/:id/expired
 *      • status='pending'   → продолжаем ждать
 *   4. На unmount чистим все таймеры/poll'ы
 *
 * Юзер может перевести USDT TRC-20 любым кошельком (TronLink, Trust
 * Wallet, OKX, биржи). QR содержит plain TRON-адрес — большинство
 * кошельков понимают и автоматически подставляют адрес. Сумму
 * пользователь вводит вручную (0.000001 точность критична — иначе
 * платёж не матчнется).
 */
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useHead } from '@vueuse/head';
import QRCode from 'qrcode';
import api from '../api/axios';
import { useToast } from '../composables/useToast';

const route = useRoute();
const router = useRouter();
const toast = useToast();

useHead({
  title: 'Оплата — GameStore',
  meta: [{ name: 'robots', content: 'noindex, nofollow' }],
});

// ── State ──────────────────────────────────────────────
const payment = ref(null);
const loading = ref(true);
const error = ref('');
const qrDataUrl = ref('');
const secondsLeft = ref(0);

let pollTimer = null;
let countdownTimer = null;

// ── Computed ───────────────────────────────────────────
const paymentId = computed(() => Number(route.params.id));

/**
 * Метаданные валюты — название/сеть/единица. Используем для UI.
 */
const currencyMeta = computed(() => {
  const c = payment.value?.crypto_currency;
  switch (c) {
    case 'USDT_TRC20':
      return { title: 'USDT (TRC-20)', unit: 'USDT', network: 'TRC-20 (Tron)',
               wrongNetworks: 'Не отправляй USDT в сетях ERC-20 / BEP-20 — они не подтвердятся.' };
    case 'TRX':
      return { title: 'TRX', unit: 'TRX', network: 'Tron',
               wrongNetworks: 'Это native TRX (не USDT). Отправляй именно TRX в сети Tron.' };
    case 'USDT_BEP20':
      return { title: 'USDT (BEP-20)', unit: 'USDT', network: 'BEP-20 (BNB Smart Chain)',
               wrongNetworks: 'Не отправляй USDT в сетях TRC-20 / ERC-20 — они не подтвердятся.' };
    default:
      return { title: 'Крипто-платёж', unit: '', network: c || '', wrongNetworks: '' };
  }
});

const minutesLabel = computed(() => {
  const s = Math.max(0, secondsLeft.value);
  const m = Math.floor(s / 60);
  const sec = s % 60;
  return `${String(m).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
});

const progressPercent = computed(() => {
  // Прогресс-бар оставшегося времени. Используем metadata.ttl или
  // 15 мин по дефолту. seconds_remaining приходит с бэка при первом
  // load — оттуда же берём «исходный» TTL.
  const ttl = (payment.value?.seconds_remaining ?? 900);
  if (ttl <= 0) return 100;
  return Math.max(0, Math.min(100, (1 - secondsLeft.value / ttl) * 100));
});

// ── Helpers ────────────────────────────────────────────
const generateQr = async (address) => {
  try {
    qrDataUrl.value = await QRCode.toDataURL(address, {
      errorCorrectionLevel: 'M',
      margin: 2,
      width: 280,
      color: { dark: '#1b1611', light: '#f2e5c9' },
    });
  } catch (e) {
    console.error('[Payment] QR generation failed', e);
  }
};

const copyToClipboard = async (text, label = 'Скопировано') => {
  try {
    await navigator.clipboard.writeText(text);
    toast.success(label);
  } catch {
    toast.error('Не удалось скопировать');
  }
};

const fetchPayment = async () => {
  try {
    const { data } = await api.get(`/payments/${paymentId.value}`);
    payment.value = data;

    // Если уже confirmed — сразу на success
    if (data.status === 'confirmed') {
      stopAllTimers();
      router.replace({ name: 'payment-success', params: { id: paymentId.value } });
      return;
    }
    // Если expired — на экран expired
    if (data.status === 'expired' || data.status === 'failed') {
      stopAllTimers();
      router.replace({ name: 'payment-expired', params: { id: paymentId.value } });
      return;
    }

    // Pending — обновляем секунды
    secondsLeft.value = Math.max(0, data.seconds_remaining || 0);
  } catch (e) {
    if (e?.response?.status === 404) {
      error.value = 'Платёж не найден.';
    } else {
      console.warn('[Payment] fetch failed', e);
    }
  }
};

const stopAllTimers = () => {
  if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
  if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null; }
};

// ── Lifecycle ──────────────────────────────────────────
onMounted(async () => {
  loading.value = true;
  await fetchPayment();
  loading.value = false;

  if (payment.value?.recipient_address) {
    await generateQr(payment.value.recipient_address);
  }

  // Countdown — раз в секунду уменьшаем; если 0 → ещё один fetch
  // для перевода в expired (бэк сам пометит когда worker пробежит,
  // но мы ускоряем UX через эффективный статус в present()).
  countdownTimer = setInterval(() => {
    if (secondsLeft.value > 0) {
      secondsLeft.value--;
    } else {
      // время вышло — спросим backend о финальном статусе
      fetchPayment();
    }
  }, 1000);

  // Status polling — каждые 3 секунды
  pollTimer = setInterval(() => {
    if (payment.value?.status === 'pending') fetchPayment();
  }, 3000);
});

onUnmounted(() => {
  stopAllTimers();
});

// Smooth navigation: если route.params.id поменялся (редко но возможно)
watch(paymentId, async (id) => {
  if (id) {
    stopAllTimers();
    await fetchPayment();
  }
});
</script>

<template>
  <div class="payment-view">
    <div class="payment-shell">

      <!-- Loading -->
      <div v-if="loading" class="payment-loading">
        Готовим окно оплаты…
      </div>

      <!-- Error -->
      <div v-else-if="error" class="payment-error">
        <h2>⚠ Ошибка</h2>
        <p>{{ error }}</p>
        <RouterLink to="/cart" class="back-link">← Вернуться в корзину</RouterLink>
      </div>

      <!-- Main payment UI -->
      <template v-else-if="payment">
        <header class="payment-hero">
          <div class="payment-eyebrow">⚒ Оплата криптой</div>
          <h1 class="payment-title">{{ currencyMeta.title }}</h1>
          <p class="payment-sub">
            Сумма к оплате: <strong>{{ payment.amount_rub.toFixed(2) }} ₽</strong>
            (по курсу 1 {{ currencyMeta.unit }} = {{ payment.exchange_rate.toFixed(2) }} ₽)
          </p>
        </header>

        <!-- Countdown -->
        <div class="countdown-block" :class="{ critical: secondsLeft < 60 }">
          <div class="countdown-label">До истечения окна оплаты:</div>
          <div class="countdown-time">{{ minutesLabel }}</div>
          <div class="countdown-bar">
            <div class="countdown-bar-fill" :style="{ width: progressPercent + '%' }"></div>
          </div>
        </div>

        <!-- QR + address -->
        <div class="payment-grid">
          <div class="qr-block">
            <div class="qr-frame">
              <img v-if="qrDataUrl" :src="qrDataUrl" alt="QR-код адреса" class="qr-img" />
              <div v-else class="qr-loading">QR…</div>
            </div>
            <p class="qr-hint">Сканируй кошельком (TronLink / Trust&nbsp;Wallet / OKX) или скопируй адрес ниже.</p>
          </div>

          <div class="details-block">
            <!-- Address -->
            <div class="detail-field">
              <label class="detail-label">Адрес получателя</label>
              <div class="detail-row">
                <code class="detail-value mono">{{ payment.recipient_address }}</code>
                <button
                  class="copy-btn"
                  @click="copyToClipboard(payment.recipient_address, 'Адрес скопирован')"
                  title="Скопировать"
                  type="button"
                >📋</button>
              </div>
            </div>

            <!-- Amount EXACT -->
            <div class="detail-field highlight">
              <label class="detail-label">Точная сумма ({{ currencyMeta.unit }})</label>
              <div class="detail-row">
                <code class="detail-value mono amount">{{ payment.amount_crypto.toFixed(6) }}</code>
                <button
                  class="copy-btn"
                  @click="copyToClipboard(payment.amount_crypto.toFixed(6), 'Сумма скопирована')"
                  title="Скопировать"
                  type="button"
                >📋</button>
              </div>
              <p class="detail-warning">
                ⚠ Сумма должна совпадать <strong>до 6 знака после запятой</strong> — иначе платёж
                не подтвердится автоматически.
              </p>
            </div>

            <!-- Network -->
            <div class="detail-field">
              <label class="detail-label">Сеть</label>
              <div class="detail-value">{{ currencyMeta.network }}</div>
              <p class="detail-warning subtle">{{ currencyMeta.wrongNetworks }}</p>
            </div>
          </div>
        </div>

        <!-- Status -->
        <div class="status-block">
          <span class="status-pulse" aria-hidden="true"></span>
          <span class="status-text">Ожидаем перевод… проверяем blockchain каждые 30 секунд</span>
        </div>

        <!-- Footer actions -->
        <div class="payment-foot">
          <RouterLink to="/cart" class="back-link">← Отменить и вернуться</RouterLink>
          <RouterLink
            :to="{ name: 'my-payments' }"
            class="back-link"
          >Все мои платежи →</RouterLink>
        </div>
      </template>

    </div>
  </div>
</template>

<style scoped>
.payment-view {
  min-height: calc(100vh - 73px);
  padding: 32px 16px 64px;
}
.payment-shell {
  max-width: 880px;
  margin: 0 auto;
}

/* States */
.payment-loading,
.payment-error {
  text-align: center;
  padding: 64px 16px;
  color: var(--text-muted);
}
.payment-error h2 { color: var(--text-bright); margin-bottom: 8px; }
.payment-error p { margin-bottom: 16px; }

/* Hero */
.payment-hero {
  text-align: center;
  margin-bottom: 28px;
  padding: 28px 24px;
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-md);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  box-shadow: var(--inset-iron-top), 0 4px 14px rgba(0,0,0,0.4);
  position: relative;
  overflow: hidden;
}
.payment-hero::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at top, rgba(226, 67, 16, 0.18) 0%, transparent 65%);
  pointer-events: none;
}
.payment-eyebrow {
  font-size: 11px;
  letter-spacing: 0.25em;
  text-transform: uppercase;
  color: var(--iron-warm);
  position: relative;
}
.payment-title {
  font-size: 30px;
  margin: 6px 0 8px;
  color: var(--text-bright);
  font-family: var(--font-display, inherit);
  letter-spacing: 0.02em;
  position: relative;
}
.payment-sub {
  margin: 0;
  color: var(--text-parchment);
  font-size: 14px;
  position: relative;
}
.payment-sub strong {
  color: var(--text-bright);
  text-shadow: 0 0 8px rgba(226, 67, 16, 0.4);
}

/* Countdown */
.countdown-block {
  text-align: center;
  margin-bottom: 24px;
  padding: 18px 20px;
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-sm);
  background: rgba(0,0,0,0.25);
}
.countdown-label {
  font-size: 11px;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: var(--text-muted);
  margin-bottom: 4px;
}
.countdown-time {
  font-size: 36px;
  font-family: var(--font-display, monospace);
  color: var(--text-bright);
  letter-spacing: 0.1em;
  text-shadow: 0 0 12px rgba(226, 67, 16, 0.4);
  margin-bottom: 10px;
}
.countdown-block.critical .countdown-time {
  color: #ff8a4c;
  text-shadow: 0 0 14px rgba(255, 122, 76, 0.6);
  animation: pulse-critical 1s ease-in-out infinite;
}
@keyframes pulse-critical {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.65; }
}
.countdown-bar {
  height: 4px;
  background: rgba(0,0,0,0.3);
  border-radius: 2px;
  overflow: hidden;
}
.countdown-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #e24310, #b8341a);
  transition: width 0.5s linear;
}

/* Grid */
.payment-grid {
  display: grid;
  grid-template-columns: 320px 1fr;
  gap: 24px;
  margin-bottom: 20px;
}
@media (max-width: 700px) {
  .payment-grid { grid-template-columns: 1fr; }
}

/* QR block */
.qr-block {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}
.qr-frame {
  padding: 16px;
  background: linear-gradient(180deg, #f2e5c9 0%, #d8c49a 100%);
  border: 2px solid var(--iron-warm);
  border-radius: var(--r-md);
  box-shadow:
    0 8px 24px rgba(0,0,0,0.45),
    inset 0 0 0 1px rgba(0,0,0,0.15);
  width: 280px;
  height: 280px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.qr-img {
  display: block;
  width: 100%;
  height: 100%;
  border-radius: 4px;
}
.qr-loading { color: #5a3d22; font-size: 14px; }
.qr-hint {
  margin: 14px 0 0;
  font-size: 12px;
  color: var(--text-muted);
  max-width: 280px;
  line-height: 1.4;
}

/* Details */
.details-block {
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.detail-field {
  padding: 14px 16px;
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-sm);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
}
.detail-field.highlight {
  border-color: var(--iron-warm);
  box-shadow: 0 0 0 1px rgba(226, 67, 16, 0.15) inset, 0 0 12px rgba(226, 67, 16, 0.18);
}
.detail-label {
  display: block;
  font-size: 11px;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: var(--iron-warm);
  margin-bottom: 6px;
}
.detail-row {
  display: flex;
  align-items: center;
  gap: 8px;
}
.detail-value {
  flex: 1;
  font-size: 14px;
  color: var(--text-bright);
  word-break: break-all;
  background: rgba(0,0,0,0.3);
  padding: 8px 10px;
  border-radius: 4px;
  border: 1px solid var(--iron-dark);
}
.detail-value.amount {
  font-size: 18px;
  font-family: var(--font-display, monospace);
  color: #ffba78;
  text-shadow: 0 0 8px rgba(226, 67, 16, 0.4);
  letter-spacing: 0.02em;
}
.mono { font-family: 'SF Mono', Monaco, Consolas, monospace; }
.copy-btn {
  flex-shrink: 0;
  width: 36px;
  height: 36px;
  padding: 0;
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-sm);
  background: rgba(0,0,0,0.3);
  color: var(--text-bright);
  font-size: 16px;
  cursor: pointer;
  transition: all var(--dur-fast) var(--ease-smoke);
  font-family: inherit;
}
.copy-btn:hover {
  border-color: var(--iron-warm);
  background: rgba(226, 67, 16, 0.15);
  transform: translateY(-1px);
}
.detail-warning {
  margin: 8px 0 0;
  font-size: 11px;
  color: #ffba78;
  line-height: 1.4;
}
.detail-warning.subtle {
  color: var(--text-muted);
}

/* Status */
.status-block {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 14px 18px;
  margin-bottom: 18px;
  border: 1px dashed var(--iron-warm);
  border-radius: var(--r-sm);
  background: rgba(226, 67, 16, 0.06);
}
.status-pulse {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #e24310;
  box-shadow: 0 0 8px rgba(226, 67, 16, 0.6);
  animation: pulse-dot 1.6s ease-in-out infinite;
}
@keyframes pulse-dot {
  0%, 100% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.4); opacity: 0.6; }
}
.status-text {
  font-size: 13px;
  color: var(--text-parchment);
}

/* Footer */
.payment-foot {
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
}
.back-link {
  font-size: 12px;
  letter-spacing: 0.05em;
  color: var(--text-muted);
  text-decoration: none;
  padding: 8px 12px;
  border-radius: var(--r-sm);
  transition: color var(--dur-fast);
}
.back-link:hover { color: var(--iron-warm); }
</style>
