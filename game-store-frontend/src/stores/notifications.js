/**
 * Pinia-store для in-app нотификаций.
 * Phase 4 / Batch A — database + polling
 * Phase 4 / Batch C — добавлен Reverb broadcast push (мгновенно)
 *
 * Двойной канал: Reverb WebSocket для мгновенной доставки + polling
 * как safety net (если WS оборвался). Polling после 4C — раз в 5 мин
 * вместо 60с (Reverb даёт push, polling только resync на случай разрыва).
 *
 * Bell-badge в шапке читает unreadCount; список вешается на NotificationsView.
 */
import { defineStore } from 'pinia';
import api from '../api/axios';
import { useAuthStore } from './auth';
import { getEcho, disconnectEcho } from '../utils/echo';

const POLL_INTERVAL_MS = 5 * 60_000; // 5 мин — Reverb primary, polling fallback

export const useNotificationsStore = defineStore('notifications', {
  state: () => ({
    items: [],            // последние 50 уведомлений
    unreadCount: 0,
    loaded: false,        // был ли хотя бы один успешный fetchAll
    loading: false,
    pollTimer: null,

    // ── Peek-очередь (Phase 4/A.1) ──
    // Когда poll детектит увеличение unreadCount, мы делаем fetchAll и
    // diff'аем по `lastSeenIds`. Новые ID попадают в peeks — App.vue
    // рендерит их как маленькие карточки под колокольчиком, которые
    // через ~5с уезжают обратно в bell. Намёк юзеру: «загляни сюда».
    lastSeenIds: new Set(),
    peeks: [],            // массив items, выкатываемых пользователю
    suppressPeeks: false, // когда dropdown открыт — peek не нужен

    // ── Reverb subscription (Phase 4/C) ──
    echoChannel: null,    // ссылка на channel чтобы можно было unsubscribe
    realtimeConnected: false,
  }),

  getters: {
    hasUnread: (s) => s.unreadCount > 0,
    /** Бэйдж: '99+' если ≥100, иначе число. */
    badge: (s) => {
      if (s.unreadCount <= 0) return '';
      return s.unreadCount >= 100 ? '99+' : String(s.unreadCount);
    },
  },

  actions: {
    /**
     * Загрузить все уведомления (50 последних).
     * Вызывается на /notifications странице, при открытии dropdown,
     * и при детектировании прироста unreadCount.
     *
     * @param {object} opts
     * @param {boolean} opts.queuePeeks  — диффать ли по lastSeenIds и
     *   подкидывать новые в peek-очередь. Включено при автоподгрузке
     *   из poll'а (когда unreadCount вырос). Выключено при manual
     *   fetchAll из dropdown/page (там peek не нужен, юзер УЖЕ смотрит).
     */
    async fetchAll(opts = {}) {
      const auth = useAuthStore();
      if (!auth.isLoggedIn) return;

      this.loading = true;
      try {
        const { data } = await api.get('/notifications');
        const incoming = data.data || [];

        // Diff против lastSeenIds — то что есть в incoming но не в Set'е,
        // считается «новым» и идёт в peek-очередь. Берём только непрочитанные
        // (read_at=null) — прочитанные показывать peek'ом нелогично.
        if (opts.queuePeeks && this.loaded && !this.suppressPeeks) {
          const fresh = incoming.filter(
            (n) => !n.read_at && !this.lastSeenIds.has(n.id)
          );
          // Лимит 3 одновременно — больше визуально шумно. Берём свежайшие.
          const toQueue = fresh.slice(0, 3);
          toQueue.forEach((n) => this.peeks.push(n));
        }

        // Обновляем lastSeenIds полным актуальным набором (для следующего diff)
        this.lastSeenIds = new Set(incoming.map((n) => n.id));
        this.items = incoming;
        this.unreadCount = data.unread_count || 0;
        this.loaded = true;
      } catch (e) {
        console.warn('[notifications] fetchAll failed', e);
      } finally {
        this.loading = false;
      }
    },

    /**
     * Лёгкий poll — только число непрочитанных.
     * Используется для bell-badge в header.
     *
     * Если число выросло (новое событие пришло) — автоматически дёргаем
     * fetchAll({queuePeeks:true}), чтобы загрузить полный список и
     * подкинуть свежие в peek-очередь. Это даёт пользователю мгновенную
     * визуальную обратную связь без необходимости открывать колокольчик.
     */
    async fetchUnreadCount() {
      const auth = useAuthStore();
      if (!auth.isLoggedIn) {
        this.unreadCount = 0;
        return;
      }
      try {
        const { data } = await api.get('/notifications/unread-count');
        const newCount = data.unread_count || 0;
        const grew = newCount > this.unreadCount;
        this.unreadCount = newCount;
        // Прирост → подгружаем свежие и кидаем в peek-очередь.
        // Не блокируем основной poll — fire-and-forget.
        if (grew) {
          this.fetchAll({ queuePeeks: true });
        }
      } catch (e) {
        // 401 при разлогине — норма, остальные warn
        if (e?.response?.status !== 401) {
          console.warn('[notifications] fetchUnreadCount failed', e);
        }
      }
    },

    /**
     * Убрать peek по id (когда юзер кликнул, или истёк таймер).
     */
    dismissPeek(id) {
      this.peeks = this.peeks.filter((n) => n.id !== id);
    },

    /**
     * Сброс всех peek'ов (когда юзер открыл dropdown — там уже всё видно).
     */
    clearPeeks() {
      this.peeks = [];
    },

    /**
     * Запретить/разрешить рендер peek'ов (когда dropdown открыт).
     */
    setPeekSuppression(value) {
      this.suppressPeeks = !!value;
      if (this.suppressPeeks) this.clearPeeks();
    },

    /** Пометить одну как прочитанную. */
    async markRead(id) {
      try {
        const { data } = await api.post(`/notifications/${id}/read`);
        this.unreadCount = data.unread_count || 0;
        const it = this.items.find((n) => n.id === id);
        if (it && !it.read_at) {
          it.read_at = new Date().toISOString();
        }
      } catch (e) {
        console.warn('[notifications] markRead failed', e);
      }
    },

    /** Пометить все как прочитанные. */
    async markAllRead() {
      try {
        await api.post('/notifications/read-all');
        this.unreadCount = 0;
        const now = new Date().toISOString();
        this.items.forEach((n) => { if (!n.read_at) n.read_at = now; });
      } catch (e) {
        console.warn('[notifications] markAllRead failed', e);
      }
    },

    /** Удалить уведомление. */
    async remove(id) {
      try {
        const { data } = await api.delete(`/notifications/${id}`);
        this.items = this.items.filter((n) => n.id !== id);
        this.unreadCount = data.unread_count || 0;
      } catch (e) {
        console.warn('[notifications] remove failed', e);
      }
    },

    /**
     * Phase 4/C — обработать notification, прилетевшую через Reverb push.
     * Payload идентичен toDatabase(): {event, post_id, actor, preview, ...}.
     * Проблема: при broadcast у нас НЕТ id из таблицы notifications
     * (broadcast не пишет в БД, это делает database channel параллельно).
     * Решение: дёргаем fetchAll() чтобы подтянуть только что записанную
     * запись с реальным id, и diff против lastSeenIds подкинет её в peek.
     */
    handleRealtimeNotification(payload) {
      const auth = useAuthStore();
      if (!auth.isLoggedIn) return;
      // Реакция мгновенная — fetchAll с queuePeeks тоже отработает diff
      // и подкинет новый item в peek-очередь (App.vue его покажет с
      // bell-homing анимацией).
      this.fetchAll({ queuePeeks: true });
    },

    /**
     * Подписаться на private channel `App.Models.User.{id}` через Reverb.
     * Echo доставляет broadcast notifications в `.notification` event.
     */
    subscribeRealtime() {
      const auth = useAuthStore();
      if (!auth.isLoggedIn || !auth.user?.id) return;

      const echo = getEcho();
      if (!echo) return; // REVERB envs не настроены — продолжаем на polling

      // Если уже подписаны — ничего не делаем
      if (this.echoChannel) return;

      try {
        const channelName = `App.Models.User.${auth.user.id}`;
        this.echoChannel = echo.private(channelName);

        this.echoChannel.notification((payload) => {
          this.handleRealtimeNotification(payload);
        });

        // Connection status — для отладки
        echo.connector.pusher.connection.bind('connected', () => {
          this.realtimeConnected = true;
        });
        echo.connector.pusher.connection.bind('disconnected', () => {
          this.realtimeConnected = false;
        });
      } catch (e) {
        console.warn('[notifications] subscribeRealtime failed', e);
      }
    },

    unsubscribeRealtime() {
      if (this.echoChannel) {
        try {
          const echo = getEcho();
          const auth = useAuthStore();
          if (echo && auth.user?.id) {
            echo.leave(`App.Models.User.${auth.user.id}`);
          }
        } catch (e) {
          console.warn('[notifications] unsubscribe failed', e);
        }
        this.echoChannel = null;
        this.realtimeConnected = false;
      }
    },

    /**
     * Запустить polling unread-count + подписаться на Reverb.
     * Останавливается на logout.
     */
    startPolling() {
      this.stopPolling();
      // Сразу один fetch для свежего состояния
      this.fetchUnreadCount();

      // Phase 4/C — подписываемся на real-time push
      this.subscribeRealtime();

      this.pollTimer = setInterval(() => {
        // Не дёргаем когда вкладка скрыта (экономия батареи + траффика)
        if (document.visibilityState !== 'visible') return;
        this.fetchUnreadCount();
      }, POLL_INTERVAL_MS);
    },

    stopPolling() {
      if (this.pollTimer) {
        clearInterval(this.pollTimer);
        this.pollTimer = null;
      }
      this.unsubscribeRealtime();
    },

    /** Сброс при logout — отключаем WS и чистим state. */
    reset() {
      this.stopPolling();
      disconnectEcho(); // полный teardown Echo singleton
      this.items = [];
      this.unreadCount = 0;
      this.loaded = false;
      this.lastSeenIds = new Set();
      this.peeks = [];
      this.suppressPeeks = false;
    },
  },
});
