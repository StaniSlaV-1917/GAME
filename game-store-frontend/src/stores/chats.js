/**
 * Pinia-store для DM-чатов.
 * Phase 4 / Batch D.
 *
 * Архитектура:
 *   • items — список всех чатов юзера (sidebar)
 *   • activeRoomId — какой чат сейчас открыт справа
 *   • activeMessages — сообщения активного чата
 *   • activeChannel — Echo channel subscription для real-time push
 *   • totalUnread — счётчик для header bell
 *
 * При open(roomId):
 *   1. Загружаем chat detail + messages
 *   2. Подписываемся на private channel chat-room.{id}
 *   3. На .NewChatMessage event — пушим в activeMessages
 *   4. markRead до latest message
 *
 * При close() / открытие другого чата → unsubscribe от старого.
 */
import { defineStore } from 'pinia';
import api from '../api/axios';
import { useAuthStore } from './auth';
import { getEcho } from '../utils/echo';

export const useChatsStore = defineStore('chats', {
  state: () => ({
    items: [],              // список всех чатов
    loaded: false,
    loading: false,

    activeRoomId: null,     // какой чат сейчас открыт
    activeRoom: null,       // detail (counterpart, type, etc.)
    activeMessages: [],     // сообщения активного чата
    activeLoading: false,
    activeChannel: null,    // ссылка на Echo channel чтобы можно было unsub

    totalUnread: 0,         // для header badge
    sending: false,
    pollTimer: null,

    // Phase 4/D.1 — global subscription на User channel для header badge
    // обновлений когда юзер не в активном чате.
    userChannelSub: null,
  }),

  getters: {
    badge: (s) => {
      if (s.totalUnread <= 0) return '';
      return s.totalUnread >= 100 ? '99+' : String(s.totalUnread);
    },
  },

  actions: {
    /** Загрузить список чатов в sidebar. */
    async fetchAll() {
      const auth = useAuthStore();
      if (!auth.isLoggedIn) return;

      this.loading = true;
      try {
        const { data } = await api.get('/chats');
        this.items = data.data || [];
        this.loaded = true;
        // Сразу пересчитаем общий unread из суммы по чатам
        this.totalUnread = this.items.reduce((sum, c) => sum + (c.unread_count || 0), 0);
      } catch (e) {
        console.warn('[chats] fetchAll failed', e);
      } finally {
        this.loading = false;
      }
    },

    /** Лёгкий poll для header badge. */
    async fetchUnreadCount() {
      const auth = useAuthStore();
      if (!auth.isLoggedIn) {
        this.totalUnread = 0;
        return;
      }
      try {
        const { data } = await api.get('/chats/unread-count');
        this.totalUnread = data.unread_count || 0;
      } catch (e) {
        if (e?.response?.status !== 401) {
          console.warn('[chats] fetchUnreadCount failed', e);
        }
      }
    },

    /** Открыть чат: load detail + subscribe + markRead. */
    async open(roomId) {
      const id = Number(roomId);
      if (!id || id === this.activeRoomId) {
        // Тот же чат — просто перезагружаем сообщения
        if (id === this.activeRoomId) await this.refreshActive();
        return;
      }

      // Закрываем предыдущий
      this.close();

      this.activeRoomId = id;
      this.activeLoading = true;

      try {
        const { data } = await api.get(`/chats/${id}`);
        this.activeRoom = {
          id: data.id,
          type: data.type,
          name: data.name,
          avatar_url: data.avatar_url,
          counterpart: data.counterpart,
        };
        this.activeMessages = data.messages || [];

        // Real-time подписка
        this.subscribeToRoom(id);

        // Mark read до последнего сообщения
        await this.markRead(id);
      } catch (e) {
        console.warn('[chats] open failed', e);
        this.activeRoomId = null;
        this.activeRoom = null;
        this.activeMessages = [];
      } finally {
        this.activeLoading = false;
      }
    },

    /** Закрыть активный чат: unsubscribe + clear state. */
    close() {
      this.unsubscribeFromRoom();
      this.activeRoomId = null;
      this.activeRoom = null;
      this.activeMessages = [];
    },

    async refreshActive() {
      if (!this.activeRoomId) return;
      try {
        const { data } = await api.get(`/chats/${this.activeRoomId}`);
        this.activeMessages = data.messages || [];
      } catch (e) {
        console.warn('[chats] refresh failed', e);
      }
    },

    /** Phase 4/D — Echo subscribe на private chat-room channel. */
    subscribeToRoom(roomId) {
      const echo = getEcho();
      if (!echo) return;

      try {
        const channel = echo.private(`chat-room.${roomId}`);

        channel.listen('.NewChatMessage', (payload) => {
          // Если это другой активный чат — игнорируем (мог переключить пока ehco
          // ещё не отписался). Также увеличиваем общий unread.
          if (this.activeRoomId !== roomId) return;

          // Дедуп: если уже есть в массиве (мог прийти и через REST response) — пропускаем
          if (this.activeMessages.find((m) => m.id === payload.id)) return;

          this.activeMessages.push(payload);

          // Авто-mark-read если это активный чат и юзер на нём
          if (document.visibilityState === 'visible') {
            this.markRead(roomId, payload.id);
          } else {
            // Вкладка скрыта — не помечаем, увеличиваем bell badge
            this.totalUnread++;
            // И обновляем counter в sidebar
            const it = this.items.find((c) => c.id === roomId);
            if (it) it.unread_count = (it.unread_count || 0) + 1;
          }

          // Обновляем preview в sidebar (last_message)
          const it = this.items.find((c) => c.id === roomId);
          if (it) {
            it.last_message = {
              id: payload.id,
              sender_id: payload.sender_id,
              body: (payload.body || '').slice(0, 80),
              created_at: payload.created_at,
            };
            it.last_message_at = payload.created_at;
            // Перенос «свежий чат сверху»
            const idx = this.items.indexOf(it);
            if (idx > 0) {
              this.items.splice(idx, 1);
              this.items.unshift(it);
            }
          }
        });

        this.activeChannel = channel;
      } catch (e) {
        console.warn('[chats] subscribe failed', e);
      }
    },

    unsubscribeFromRoom() {
      if (this.activeChannel && this.activeRoomId) {
        try {
          const echo = getEcho();
          if (echo) echo.leave(`chat-room.${this.activeRoomId}`);
        } catch (e) {
          console.warn('[chats] unsubscribe failed', e);
        }
        this.activeChannel = null;
      }
    },

    /**
     * Отправить сообщение в активный чат.
     * Возвращает {ok:true, data} или {ok:false, error} — фронт показывает
     * toast и восстанавливает черновик при провале.
     */
    async send(body, replyToId = null) {
      if (!this.activeRoomId) {
        return { ok: false, error: 'Чат не выбран — открой переписку слева' };
      }
      if (!body?.trim()) {
        return { ok: false, error: 'Пустое сообщение' };
      }
      if (this.sending) {
        return { ok: false, error: 'Уже отправляется…' };
      }

      this.sending = true;
      // Фиксируем активный чат на момент отправки (защита от race с переключением)
      const targetRoomId = this.activeRoomId;

      try {
        const { data } = await api.post(`/chats/${targetRoomId}/messages`, {
          body,
          reply_to_message_id: replyToId,
        });

        // Если за время отправки юзер переключил чат — добавляем только в sidebar,
        // не в activeMessages (чтобы не показывать в чужом thread'е)
        if (this.activeRoomId === targetRoomId) {
          this.activeMessages.push(data);
        }

        // Обновляем sidebar preview
        const it = this.items.find((c) => c.id === targetRoomId);
        if (it) {
          it.last_message = {
            id: data.id,
            sender_id: data.sender_id,
            body: (data.body || '').slice(0, 80),
            created_at: data.created_at,
          };
          it.last_message_at = data.created_at;
          const idx = this.items.indexOf(it);
          if (idx > 0) {
            this.items.splice(idx, 1);
            this.items.unshift(it);
          }
        }

        return { ok: true, data };
      } catch (e) {
        const status = e?.response?.status;
        const msg = e?.response?.data?.message
          || `Не удалось отправить (HTTP ${status || '???'})`;
        console.warn('[chats] send failed', {
          status,
          message: msg,
          activeRoomId: this.activeRoomId,
          targetRoomId,
        });
        return { ok: false, error: msg, status };
      } finally {
        this.sending = false;
      }
    },

    /** Отметить прочитанным до messageId (или последнего). */
    async markRead(roomId, messageId = null) {
      try {
        const { data } = await api.post(`/chats/${roomId}/read`, { message_id: messageId });
        this.totalUnread = data.unread_count || 0;
        // Сбрасываем unread в sidebar для этого чата
        const it = this.items.find((c) => c.id === roomId);
        if (it) it.unread_count = 0;
      } catch (e) {
        console.warn('[chats] markRead failed', e);
      }
    },

    /** Создать или найти DM с юзером (по username). */
    async createDmByUsername(username) {
      try {
        const { data } = await api.post('/chats/dm', { username });
        // Перезагрузим список чатов чтобы новый появился в sidebar
        // (await важно: иначе MessagesView откроется с пустым sidebar)
        await this.fetchAll();
        return data; // { id, type }
      } catch (e) {
        console.warn('[chats] createDm failed', e);
        throw e;
      }
    },

    /**
     * Phase 4/D.1 — подписка на App.Models.User.{id} канал для chat-events
     * (отдельно от subscribe-to-room, который только для активного чата).
     *
     * Когда любое из чатов юзера получает новое сообщение, бэк broadcast'ит
     * на user channel тоже. Здесь мы ловим это и обновляем header badge +
     * sidebar last_message preview даже если юзер на другой странице сайта.
     */
    subscribeUserChannel() {
      const auth = useAuthStore();
      if (!auth.isLoggedIn || !auth.user?.id) return;

      const echo = getEcho();
      if (!echo) return;
      if (this.userChannelSub) return; // уже подписаны

      try {
        const channel = echo.private(`App.Models.User.${auth.user.id}`);
        channel.listen('.NewChatMessage', (payload) => {
          // Если этот message — для активного чата, его обработает
          // chat-room subscription. Здесь только обновляем глобальный badge.
          if (this.activeRoomId === payload.chat_room_id) return;

          // Инкремент общего badge
          this.totalUnread++;

          // Обновляем sidebar item: last_message + перенос вверх + ++unread
          const it = this.items.find((c) => c.id === payload.chat_room_id);
          if (it) {
            it.unread_count = (it.unread_count || 0) + 1;
            it.last_message = {
              id: payload.id,
              sender_id: payload.sender_id,
              body: (payload.body || '').slice(0, 80),
              created_at: payload.created_at,
            };
            it.last_message_at = payload.created_at;
            const idx = this.items.indexOf(it);
            if (idx > 0) {
              this.items.splice(idx, 1);
              this.items.unshift(it);
            }
          } else {
            // Чата нет в текущем списке (новый или ещё не загружен) —
            // тихо перезагружаем sidebar
            this.fetchAll();
          }
        });
        this.userChannelSub = channel;
      } catch (e) {
        console.warn('[chats] user channel subscribe failed', e);
      }
    },

    unsubscribeUserChannel() {
      if (this.userChannelSub) {
        try {
          const echo = getEcho();
          const auth = useAuthStore();
          if (echo && auth.user?.id) {
            // Не вызываем echo.leave() — этот канал также используется
            // notifications-store. Просто снимаем НАШ listener.
            this.userChannelSub.stopListening('.NewChatMessage');
          }
        } catch (e) {
          console.warn('[chats] user channel unsub failed', e);
        }
        this.userChannelSub = null;
      }
    },

    /** Запустить poll для общего unread count в header + Reverb push. */
    startPolling() {
      this.stopPolling();
      this.fetchUnreadCount();
      // Сразу подгружаем sidebar в фоне чтобы header-badge на /messages мог сразу что-то показать
      this.fetchAll();

      // Phase 4/D.1 — глобальная подписка на user channel для всех chat-events
      this.subscribeUserChannel();

      this.pollTimer = setInterval(() => {
        if (document.visibilityState !== 'visible') return;
        this.fetchUnreadCount();
      }, 5 * 60_000); // 5 мин — Reverb push даёт мгновенно, polling=fallback
    },

    stopPolling() {
      if (this.pollTimer) {
        clearInterval(this.pollTimer);
        this.pollTimer = null;
      }
      this.unsubscribeUserChannel();
    },

    /** Сброс при logout. */
    reset() {
      this.stopPolling();
      this.close();
      this.items = [];
      this.loaded = false;
      this.totalUnread = 0;
    },
  },
});
