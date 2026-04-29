/**
 * Pinia-store для in-app нотификаций.
 * Phase 4 / Batch A.
 *
 * Поллим unread-count раз в 60 секунд (только когда юзер залогинен и
 * вкладка активна). Полный список грузим только когда юзер открывает
 * dropdown / страницу /notifications.
 *
 * Bell-badge в шапке читает unreadCount; список вешается на NotificationsView.
 */
import { defineStore } from 'pinia';
import api from '../api/axios';
import { useAuthStore } from './auth';

const POLL_INTERVAL_MS = 60_000;

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
     * Запустить polling unread-count.
     * Останавливается автоматически на logout (через stopPolling в auth-сторе).
     */
    startPolling() {
      this.stopPolling();
      // Сразу один fetch для свежего состояния
      this.fetchUnreadCount();

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
    },

    /** Сброс при logout. */
    reset() {
      this.stopPolling();
      this.items = [];
      this.unreadCount = 0;
      this.loaded = false;
      this.lastSeenIds = new Set();
      this.peeks = [];
      this.suppressPeeks = false;
    },
  },
});
