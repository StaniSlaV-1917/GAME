/**
 * Phase 4 / Batch C — Reverb (Pusher-protocol) Echo singleton.
 *
 * Реальное-время доставка нотификаций через WebSocket. Echo инстанс
 * создаётся при первой подписке (lazy), переиспользуется до logout'а.
 *
 * Auth для приватных каналов: POST /api/broadcasting/auth с Sanctum
 * Bearer-токеном (кросс-домен Firebase ↔ Fly).
 *
 * VITE_REVERB_* env vars поставляются GitHub Actions в .env при сборке.
 */
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import api from '../api/axios';

window.Pusher = Pusher;

let echoInstance = null;

/**
 * Получить (или создать) singleton Echo instance.
 * Вернёт null если REVERB_APP_KEY не настроен (development mode).
 */
export function getEcho() {
  if (echoInstance) return echoInstance;

  const key   = import.meta.env.VITE_REVERB_APP_KEY;
  const host  = import.meta.env.VITE_REVERB_HOST;
  const port  = Number(import.meta.env.VITE_REVERB_PORT || 443);
  const scheme = import.meta.env.VITE_REVERB_SCHEME || 'https';

  if (!key || !host) {
    console.warn('[echo] REVERB envs не настроены — real-time выключен');
    return null;
  }

  const isHttps = scheme === 'https';

  echoInstance = new Echo({
    broadcaster: 'reverb',
    key,
    wsHost: host,
    wsPort: port,
    wssPort: port,
    forceTLS: isHttps,
    enabledTransports: isHttps ? ['ws', 'wss'] : ['ws'],
    // Custom authorizer — шлём auth-запрос через axios (Bearer token)
    // на /api/broadcasting/auth. Default Echo использует fetch без headers.
    authorizer: (channel) => ({
      authorize: (socketId, callback) => {
        api
          .post('/broadcasting/auth', {
            socket_id: socketId,
            channel_name: channel.name,
          })
          .then((response) => callback(false, response.data))
          .catch((error) => {
            console.warn('[echo] auth failed for', channel.name, error?.response?.status);
            callback(true, error);
          });
      },
    }),
  });

  return echoInstance;
}

/**
 * Полный teardown — отключить WebSocket, забыть instance.
 * Вызывать на logout.
 */
export function disconnectEcho() {
  if (echoInstance) {
    try {
      echoInstance.disconnect();
    } catch (e) {
      console.warn('[echo] disconnect failed', e);
    }
    echoInstance = null;
  }
}
