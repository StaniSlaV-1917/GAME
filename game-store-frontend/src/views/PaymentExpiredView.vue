<script setup>
/**
 * Pay/A — экран «время оплаты истекло».
 * Показывается когда payment.status='expired' (15 мин таймер вышел).
 */
import { onMounted, ref, computed } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useHead } from '@vueuse/head';
import api from '../api/axios';

const route = useRoute();
const router = useRouter();

useHead({
  title: 'Время оплаты истекло — GameStore',
  meta: [{ name: 'robots', content: 'noindex, nofollow' }],
});

const payment = ref(null);
const loading = ref(true);
const retrying = ref(false);

const cartItems = computed(() => payment.value?.metadata?.cart || []);

onMounted(async () => {
  try {
    const { data } = await api.get(`/payments/${route.params.id}`);
    payment.value = data;
  } catch (e) {
    console.warn('[PaymentExpired] fetch failed', e);
  } finally {
    loading.value = false;
  }
});

/**
 * Создаём новый pending с теми же позициями. Юзер не теряет корзину.
 */
const retry = async () => {
  if (!cartItems.value.length) return;
  retrying.value = true;
  try {
    const { data } = await api.post('/payments', {
      items: cartItems.value.map((i) => ({ game_id: i.game_id, quantity: i.quantity })),
    });
    router.replace({ name: 'payment', params: { id: data.id } });
  } catch (e) {
    console.warn('[PaymentExpired] retry failed', e);
    retrying.value = false;
  }
};
</script>

<template>
  <div class="expired-view">
    <div class="expired-shell">
      <div v-if="loading" class="loading">Загрузка…</div>

      <template v-else>
        <div class="expired-sigil" aria-hidden="true">⏳</div>
        <h1 class="expired-title">Время оплаты истекло</h1>
        <p class="expired-sub">
          Окно в 15 минут закрылось. Если ты успел отправить транзакцию —
          она отразится в Tronscan, но автоматическая проверка уже не сработает.
          В этом случае напиши в поддержку — мы вручную проверим хэш.
        </p>

        <div class="actions">
          <button
            v-if="cartItems.length"
            class="action-btn primary"
            :disabled="retrying"
            @click="retry"
          >
            {{ retrying ? 'Создаём…' : 'Создать новый платёж' }}
          </button>
          <RouterLink to="/cart" class="action-btn ghost">Вернуться в корзину</RouterLink>
        </div>
      </template>
    </div>
  </div>
</template>

<style scoped>
.expired-view {
  min-height: calc(100vh - 73px);
  padding: 48px 16px 64px;
  display: flex;
  align-items: flex-start;
  justify-content: center;
}
.expired-shell {
  max-width: 520px;
  width: 100%;
  text-align: center;
}
.loading { padding: 64px; color: var(--text-muted); }

.expired-sigil {
  font-size: 56px;
  margin-bottom: 12px;
  opacity: 0.7;
}
.expired-title {
  font-size: 24px;
  margin: 0 0 12px;
  color: var(--text-bright);
}
.expired-sub {
  margin: 0 0 28px;
  color: var(--text-parchment);
  line-height: 1.5;
  font-size: 14px;
}

.actions {
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
  cursor: pointer;
  font-family: inherit;
}
.action-btn.primary {
  border-color: var(--iron-warm);
  background: linear-gradient(180deg, rgba(226, 67, 16, 0.18) 0%, rgba(0,0,0,0) 100%);
  color: var(--text-bright);
}
.action-btn:hover:not(:disabled) {
  border-color: var(--iron-warm);
  color: var(--text-bright);
  transform: translateY(-1px);
}
.action-btn:disabled { opacity: 0.5; cursor: not-allowed; }
</style>
