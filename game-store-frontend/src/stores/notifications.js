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
     * Вызывается на /notifications странице или при открытии dropdown.
     */
    async fetchAll() {
      const auth = useAuthStore();
      if (!auth.isLoggedIn) return;

      this.loading = true;
      try {
        const { data } = await api.get('/notifications');
        this.items = data.data || [];
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
     */
    async fetchUnreadCount() {
      const auth = useAuthStore();
      if (!auth.isLoggedIn) {
        this.unreadCount = 0;
        return;
      }
      try {
        const { data } = await api.get('/notifications/unread-count');
        this.unreadCount = data.unread_count || 0;
      } catch (e) {
        // 401 при разлогине — норма, остальные warn
        if (e?.response?.status !== 401) {
          console.warn('[notifications] fetchUnreadCount failed', e);
        }
      }
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
    },
  },
});
